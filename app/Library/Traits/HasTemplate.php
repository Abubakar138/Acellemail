<?php

namespace Acelle\Library\Traits;

use Acelle\Model\Template;
use Exception;
use Acelle\Library\ExtendedSwiftMessage;
use Acelle\Model\Setting;
use Acelle\Library\StringHelper;
use League\Pipeline\PipelineBuilder;
use Acelle\Library\HtmlHandler\ParseRss;
use Acelle\Library\HtmlHandler\ReplaceBareLineFeed;
use Acelle\Library\HtmlHandler\AppendHtml;
use Acelle\Library\HtmlHandler\TransformTag;
use Acelle\Library\HtmlHandler\InjectTrackingPixel;
use Acelle\Library\HtmlHandler\MakeInlineCss;
use Acelle\Library\HtmlHandler\TransformUrl;
use Acelle\Library\HtmlHandler\TransformWidgets;
use Acelle\Library\HtmlHandler\AddDoctype;
use Acelle\Library\HtmlHandler\RemoveTitleTag;
use Acelle\Library\HtmlHandler\AddPreheader;
use Acelle\Library\HtmlHandler\GenerateSpintax;
use Acelle\Library\HtmlHandler\GenerateSpintaxForPlainText;
use Acelle\Library\HtmlHandler\DecodeHtmlSpecialChars;
use Acelle\Library\HtmlHandler\InjectMessageIdToBody;
use Acelle\Library\Lockable;
use Cache;
use Soundasleep\Html2Text;
use Acelle\Library\Domain;

use function Acelle\Helpers\dkim_sign;
use function Acelle\Helpers\dkim_sign_with_default_domain;

trait HasTemplate
{
    /**
     * Campaign has one template.
     */
    public function template()
    {
        return $this->belongsTo('Acelle\Model\Template');
    }

    /**
     * Get template.
     */
    public function setTemplate($template, $name = null)
    {
        $campaignTemplate = $template->copyAsPrivate([
            'name' => $name ? $name : trans('messages.campaign.template_name', ['name' => $this->name]),
            'customer_id' => $this->customer_id,
        ]);

        // remove exist template
        if ($this->template) {
            $this->template->deleteAndCleanup();
        }

        $this->template_id = $campaignTemplate->id;
        $this->save();
        $this->refresh();
        if (\Schema::hasColumn($this->getTable(), 'plain')) {
            $this->updatePlainFromHtml();
        }
        if (method_exists($this, 'updateLinks')) {
            $this->updateLinks();
        }
    }

    /**
     * Upload a template.
     */
    public function uploadTemplate($request)
    {
        $template = Template::uploadTemplate($request);
        $this->setTemplate($template);
    }

    /**
     * Check if email has template.
     */
    public function hasTemplate()
    {
        return $this->template()->exists();
    }

    /**
     * Get thumb.
     */
    public function getThumbUrl()
    {
        if ($this->template) {
            return $this->template->getThumbUrl();
        } else {
            return url('images/placeholder.jpg');
        }
    }

    /**
     * Remove email template.
     */
    public function removeTemplate()
    {
        $this->template->deleteAndCleanup();
    }

    /**
     * Update campaign plain text.
     */
    public function updatePlainFromHtml()
    {
        if (!$this->plain) {
            $this->plain = preg_replace('/\s+/', ' ', preg_replace('/\r\n/', ' ', strip_tags($this->getTemplateContent())));
            $this->save();
        }
    }

    /**
     * Set template content.
     */
    public function setTemplateContent($content, $callback = null)
    {
        if (!$this->template) {
            throw new Exception('Cannot set content: campaign/email does not have template!');
        }

        $template = $this->template;
        $template->content = $content;
        $template->save();
        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Get template content.
     */
    public function getTemplateContent()
    {
        if (!$this->template) {
            throw new Exception('Cannot get content: campaign/email does not have template!');
        }

        return $this->template->content;
    }

    /**
     * Build Email Custom Headers.
     *
     * @return Hash list of custom headers
     */
    public function getCustomHeaders($subscriber, $server)
    {
        $msgId = StringHelper::generateMessageId(StringHelper::getDomainFromEmail($this->from_email));

        if ($this->isStdClassSubscriber($subscriber)) {
            $unsubscribeUrl = null;
        } else {
            $unsubscribeUrl = $subscriber->generateUnsubscribeUrl($msgId);
            if ($this->trackingDomain) {
                $unsubscribeUrl = $this->trackingDomain->buildTrackingUrl($unsubscribeUrl);
            }
        }

        $headers = array(
            'X-Acelle-Campaign-Id' => $this->uid,
            'X-Acelle-Subscriber-Id' => $subscriber->uid,
            'X-Acelle-Customer-Id' => $this->customer->uid,
            'X-Acelle-Message-Id' => $msgId,
            'X-Acelle-Sending-Server-Id' => $server->uid,
            'Precedence' => 'bulk',
        );

        // One-click unsubscribe header
        // Reference: https://aws.amazon.com/fr/blogs/messaging-and-targeting/using-one-click-unsubscribe-with-amazon-ses
        if ($unsubscribeUrl) {
            $headers['List-Unsubscribe'] = "<{$unsubscribeUrl}>";
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        } else {
            $sampleUnsubscribeUrl = route('campaign_message', ['message' => StringHelper::base64UrlEncode(trans('messages.email.test_link_note')) ]);
            $headers['List-Unsubscribe'] = "<{$sampleUnsubscribeUrl}>";
        }

        return $headers;
    }

    /**
     * Check if the given variable is a subscriber object (for actually sending a email)
     * Or a stdClass subscriber (for sending test email).
     *
     * @param object $object
     */
    public function isStdClassSubscriber($object)
    {
        return get_class($object) == 'stdClass';
    }

    /**
     * Prepare the email content using Swift Mailer.
     *
     * @input object subscriber
     * @input object sending server
     *
     * @return MIME text message
     */
    public function prepareEmail($subscriber, $server = null, $fromCache = false, $expiresInSeconds = 600)
    {
        // build the message
        $customHeaders = $this->getCustomHeaders($subscriber, $this);
        $msgId = $customHeaders['X-Acelle-Message-Id'];

        $message = new ExtendedSwiftMessage();
        $message->setId($msgId);

        if (is_null($this->type) || $this->type == self::TYPE_REGULAR) {
            $message->setContentType('text/html; charset=utf-8');
        } else {
            $message->setContentType('text/plain; charset=utf-8');
        }

        foreach ($customHeaders as $key => $value) {
            $message->getHeaders()->addTextHeader($key, $value);
        }

        // @TODO for AWS, setting returnPath requires verified domain or email address
        if (!is_null($server) && $server->allowCustomReturnPath()) {
            $returnPath = $server->getVerp($subscriber->email);
            if ($returnPath) {
                $message->setReturnPath($returnPath);
            }
        }
        $message->setSubject($this->getSubject($subscriber, $msgId));
        $message->setFrom(array($this->from_email => $this->from_name));
        $message->setTo($subscriber->email);

        if (!empty(Setting::get('campaign.bcc'))) {
            $addresses = array_filter(preg_split('/\s*,\s*/', Setting::get('campaign.bcc')));
            $message->setBcc($addresses);
        }

        if (!empty(Setting::get('campaign.cc'))) {
            $addresses = array_filter(preg_split('/\s*,\s*/', Setting::get('campaign.cc')));
            $message->setCc($addresses);
        }

        $message->setReplyTo($this->reply_to);
        $message->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('8bit'));
        if (is_null($this->type) || $this->type == self::TYPE_REGULAR) {
            $html = $this->getHtmlContent($subscriber, $msgId, $server, $fromCache, $expiresInSeconds);

            $plain = $this->getPlainContentFromHtml($html);

            // IMPORTANT: add plain part first, then html part
            $message->addPart($plain, 'text/plain');
            $message->addPart($html, 'text/html');
        } else {
            // Get plain content is for PLAIN campaign only
            $plain = $this->getPlainContent($subscriber, $msgId, $server);
            $message->addPart($plain, 'text/plain');
        }

        if ($this->sign_dkim) {
            $sendingDomain = $this->findSendingDomain($this->from_email);
            if (!is_null($sendingDomain)) {
                $message = dkim_sign($message, $sendingDomain->getDomain());
            }
        }

        if (config('custom.sign_with_default_domain')) {
            $message = dkim_sign_with_default_domain($message);
        }

        if ($this->attachments) {
            // Email model
            foreach ($this->attachments as $file) {
                $attachment = \Swift_Attachment::fromPath($file->file);
                $message->attach($attachment);
                // This is used by certain delivery services like ElasticEmail
                $message->extAttachments[] = [ 'path' => $file->file, 'type' => $attachment->getContentType()];
            }
        } else {
            // Campaign model
            //@todo attach function used for any attachment of Campaign
            $path_campaign = $this->getAttachmentPath();
            if (is_dir($path_campaign)) {
                $files = \Illuminate\Support\Facades\File::allFiles($path_campaign);
                foreach ($files as $file) {
                    $attachment = \Swift_Attachment::fromPath((string) $file);
                    $message->attach($attachment);
                    // This is used by certain delivery services like ElasticEmail
                    $message->extAttachments[] = [ 'path' => (string) $file, 'type' => $attachment->getContentType()];
                }
            }
        }

        return array($message, $msgId);
    }

    /**
     * Get tagged Subject.
     *
     * @return string
     */
    public function getSubject($subscriber = null, $msgId = null)
    {
        $pipeline = new PipelineBuilder();

        if (!is_null($subscriber)) {
            if (is_null($msgId)) {
                throw new Exception('MessageID must not be null');
            }
            $pipeline->add(new TransformTag($this, $subscriber, $msgId));
        }

        $pipeline->add(new DecodeHtmlSpecialChars());
        $pipeline->add(new GenerateSpintaxForPlainText());
        $pipeline->add(new ReplaceBareLineFeed());
        return $pipeline->build()->process($this->subject);
    }

    /**
     * Check if email footer enabled.
     *
     * @return string
     * @deprecated this is a very poorly designed function with dependencies session!
     * @todo so, we are adding if/else to facilitate testing only
     */
    public function footerEnabled()
    {
        if (is_null($this->customer)) {
            return;
        }

        return (get_tmp_quota($this->customer, 'email_footer_enabled') == 'yes') ? true : false;
    }

    /**
     * Get HTML footer.
     *
     * @return string
     * @deprecated this is a very poorly designed function with dependencies session!
     * @todo so, we are adding if/else to facilitate testing only
     */
    public function getHtmlFooter()
    {
        if (is_null($this->customer)) {
            return;
        }

        return get_tmp_quota($this->customer, 'html_footer');
    }

    /**
     * Find sending domain from email.
     *
     * @return mixed
     */
    public function findSendingDomain($email)
    {
        $domainName = substr(strrchr($email, '@'), 1);

        if ($domainName == false) {
            return;
        }

        $domain = $this->customer->sendingDomains()->where('name', $domainName)->first();

        return $domain;
    }

    public function getCachedHtmlId()
    {
        return "{$this->uid}-html";
    }

    public function clearTemplateCache()
    {
        Cache::forget($this->getCachedHtmlId());
    }

    /**
     * Build Email HTML content.
     *
     * @return string
     */
    public function getHtmlContent($subscriber = null, $msgId = null, $server = null, $fromCache = false, $expiresInSeconds = 600)
    {
        $baseHtml = $this->getBaseHtmlContent($fromCache, $expiresInSeconds);

        // Bind subscriber/message/server information to email content
        $pipeline = new PipelineBuilder();
        $pipeline->add(new TransformTag($this, $subscriber, $msgId, $server));

        if (!$this->isStageExcluded(InjectTrackingPixel::class)) {
            $pipeline->add(new InjectTrackingPixel($this, $msgId));
        }

        $pipeline->add(new InjectMessageIdToBody($msgId));

        if (!$this->isStageExcluded(TransformUrl::class)) {
            $pipeline->add(new TransformUrl($this->template, $msgId, $this->trackingDomain));
        }

        $pipeline->add(new DecodeHtmlSpecialChars());
        $pipeline->add(new GenerateSpintax());
        $pipeline->add(new ReplaceBareLineFeed());

        // ReplaceBareLineFeed should go into the base HTML content instead
        // However, we need it here, as the last step, to make sure there is no bare LF produced

        // Actually push HTML to pipeline for processing
        $html = $pipeline->build()->process($baseHtml);

        // Return subscriber's bound html
        return $html;
    }

    // Return the HTML content which has been processed through base handlers (pipeline)
    // Which is not associated with any subscriber/message/server
    public function getBaseHtmlContent($fromCache = false, $expiresInSeconds = 600)
    {
        if (!$this->template) {
            throw new Exception('No template available');
        }

        $cacheId = $this->getCachedHtmlId();
        $updateCacheFlag = $fromCache && !Cache::has($cacheId);
        $html = null;

        if (!$fromCache || $updateCacheFlag) {
            $pipeline = new PipelineBuilder();
            $pipeline->add(new AddDoctype());
            $pipeline->add(new AddPreheader($this->preheader));
            $pipeline->add(new RemoveTitleTag());
            $pipeline->add(new AppendHtml($this->getHtmlFooter()));
            $pipeline->add(new ParseRss());
            $pipeline->add(new MakeInlineCss($this->template->findCssFiles()));
            $pipeline->add(new TransformWidgets());
            // $pipeline->add(new TransformTag($this, $subscriber, $msgId, $server));
            // $pipeline->add(new InjectTrackingPixel($this, $msgId));
            // $pipeline->add(new TransformUrl($this->template, $msgId, $this->trackingDomain));
            // $html = $this->template->wooTransform($html);
            $html = $pipeline->build()->process($this->getTemplateContent());
        }

        if ($updateCacheFlag) {
            $lockfile = storage_path('locks/campaign-cache-'.$this->uid);
            $lock = new Lockable($lockfile);

            $lock->getExclusiveLock(function ($f) use ($cacheId, $html, $expiresInSeconds) {
                Cache::put($cacheId, $html, $expiresInSeconds);
            }, $timeoutSeconds = 3, $timeoutCallback = function () {
                // echo "Quit me mememem";
                // just quit, do not throw exception
            });
        }

        // It is important to return $html in priority here, as cache update may not work!
        return $html ?: Cache::get($cacheId);
    }

    /**
     * Build Email HTML content.
     * Notice: this method is used for PLAIN CAMPAIGN only. To extract plain content from HTML, use Html2Text instead
     *
     * @return string
     */
    public function getPlainContent($subscriber = null, $msgId = null, $server = null)
    {
        $plain = $this->plain.$this->getPlainTextFooter();
        $pipeline = new PipelineBuilder();

        if (!is_null($subscriber)) {
            $pipeline->add(new TransformTag($this, $subscriber, $msgId, $server));
        }

        $pipeline->add(new DecodeHtmlSpecialChars());
        $pipeline->add(new GenerateSpintaxForPlainText());
        $pipeline->add(new ReplaceBareLineFeed());
        $plain = $pipeline->build()->process($plain);

        return $plain;
    }

    public function getPlainContentFromHtml($html)
    {
        $plain = Html2Text::convert($html, [ 'ignore_errors' => true ]);

        $pipeline = new PipelineBuilder();
        $pipeline->add(new ReplaceBareLineFeed());
        $plain = $pipeline->build()->process($plain);

        return $plain;
    }

    /**
     * Get PLAIN TEXT footer.
     *
     * @return string
     * @deprecated this is a very poorly designed function with dependencies session!
     * @todo so, we are adding if/else to facilitate testing only
     */
    public function getPlainTextFooter()
    {
        if (is_null($this->customer)) {
            return;
        }

        return get_tmp_quota($this->customer, 'plain_text_footer');
    }

    /**
     * Create a stdClass subscriber (for sending a campaign test email)
     * The campaign sending functions take a subscriber object as input
     * However, a test email address is not yet a subscriber object, so we have to build a fake stdClass object
     * which can be used as a real subscriber.
     *
     * @param array $subscriber
     */
    public function createStdClassSubscriber($subscriber)
    {
        // default attributes that are required
        $jsonObj = [
            'uid' => uniqid(),
        ];

        // append the customer specified attributes and build a stdClass object
        $stdObj = json_decode(json_encode(array_merge($jsonObj, $subscriber)));

        return $stdObj;
    }

    public function makeTrackingPixel($msgId)
    {
        if (!is_null($msgId)) {
            $url = route('openTrackingUrl', ['message_id' => StringHelper::base64UrlEncode($msgId)], true);
            if ($this->trackingDomain) {
                $url = $this->trackingDomain->buildTrackingUrl($url);
            }
        } else {
            $url = $this->makeSampleLink();
        }

        return '<img alt="This is a tracking pixel" src="'.$url.'" width="0" height="0" alt="" style="visibility:hidden" />';
    }

    public function makeSampleLink()
    {
        $sampleLink = route('campaign_message', [ 'message' => StringHelper::base64UrlEncode(trans('messages.email.test_link_note')) ]);
        if ($this->trackingDomain) {
            $sampleLink = $this->trackingDomain->buildTrackingUrl($sampleLink);
        }

        return $sampleLink;
    }
}
