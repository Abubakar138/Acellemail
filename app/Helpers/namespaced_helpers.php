<?php

namespace Acelle\Helpers;

use Acelle\Library\StringHelper;
use Exception;
use Closure;
use Carbon\Carbon;
use SimpleXMLElement;
use Mika56\SPFCheck\SPFCheck;
use Mika56\SPFCheck\DNSRecordGetterDirect;
use Mika56\SPFCheck\DNSRecordGetter;

function generatePublicPath($absPath, $withHost = false)
{
    // Notice: $relativePath must be relative to storage/ folder
    // For example, with a real path of /home/deploy/acellemail/storage/app/sub/example.png
    // then $relativePath should be "app/sub/example.png"

    if (empty(trim($absPath))) {
        throw new Exception('Empty path');
    }

    $excludeBase = storage_path();
    $pos = strpos($absPath, $excludeBase); // Expect pos to be exactly 0

    if ($pos === false) {
        throw new Exception(sprintf("File '%s' cannot be made public, only files under storage/ folder can", $absPath));
    }

    if ($pos != 0) {
        throw new Exception(sprintf("Invalid path '%s', cannot make it public", $absPath));
    }

    // Do not use string replace, as path parts may occur more than once
    // For example: abc/xyz/abc/xyz...
    $relativePath = substr($absPath, strlen($excludeBase) + 1);

    if ($relativePath === false) {
        throw new Exception("Invalid path {$absPath}");
    }

    $dirname = dirname($relativePath);
    $basename = basename($relativePath);
    $encodedDirname = StringHelper::base64UrlEncode($dirname);

    // If Laravel is under a subdirectory
    $subdirectory = getAppSubdirectory();

    if (empty($subdirectory) || $withHost) {
        // Return something like
        //     "http://localhost/{subdirectory if any}/p/assets/ef99238abc92f43e038efb"   # withHost = true, OR
        //     "/p/assets/ef99238abc92f43e038efb"                   # withHost = false
        $url = route('public_assets', [ 'dirname' => $encodedDirname, 'basename' => rawurlencode($basename) ], $withHost);
    } else {
        // Make sure the $subdirectory has a leading slash ('/')
        $subdirectory = join_paths('/', $subdirectory);
        $url = join_paths($subdirectory, route('public_assets', [ 'dirname' => $encodedDirname, 'basename' => $basename ], $withHost));
    }

    return $url;
}

function getAppSubdirectory()
{
    // IMPORTANT: do not use url('/') as it will not work correctly
    // when calling from another file (like filemanager/config/config.php for example)
    // Otherwise, it will always return 'http://localhost' --> without subdirectory
    $path = parse_url(config('app.url'), PHP_URL_PATH);

    if (is_null($path)) {
        return null;
    }

    $path = trim($path, '/');
    return empty($path) ? null : $path;
}

// Get application host with {scheme}://{host}:{port} (without subdirectory)
function getAppHost()
{
    $fullUrl = config('app.url');
    $meta = parse_url($fullUrl);

    if (!array_key_exists('scheme', $meta) || !array_key_exists('host', $meta)) {
        throw new Exception('Invalid app.url setting');
    }

    $appHost = "{$meta['scheme']}://{$meta['host']}";

    if (array_key_exists('port', $meta)) {
        $appHost = "{$appHost}:{$meta['port']}";
    }

    return $appHost;
}

function updateTranslationFile($targetFile, $sourceFile, $overwriteTargetPhrases = false, $deleteTargetKeys = true, $sort = false)
{
    $source = include $sourceFile;
    $target = include $targetFile;

    if ($overwriteTargetPhrases) {
        // Overwrite $target
        $merged = $source + $target;
    } else {
        // Respect $target
        $merged = $target + $source;
    }

    if ($deleteTargetKeys) {
        // Find keys in $target that are that not available in $source
        $diff = array_diff_key($target, $source);

        // Delete those keys in the final result
        $merged = array_diff_key($merged, $diff);
    }

    if ($sort) {
        ksort($merged);
    }

    $out = '<?php return '.var_export(\Yaml::parse(\Yaml::dump($merged)), true).' ?>';
    \Illuminate\Support\Facades\File::put($targetFile, $out);
}

// Copy and:
// + Remove the destination first
// + Create parent folders if not exist
function pcopy($src, $dst)
{
    if (!\Illuminate\Support\Facades\File::exists($src)) {
        throw new Exception("File `{$src}` does not exist");
    }

    if (\Illuminate\Support\Facades\File::exists($dst)) {
        // Delete the file or link or directory
        if (is_link($dst) || is_file($dst)) {
            \Illuminate\Support\Facades\File::delete($dst);
        } else {
            \Illuminate\Support\Facades\File::deleteDirectory($dst);
        }
    } else {
        // Make sure the PARENT directory exists
        $dirname = pathinfo($dst)['dirname'];
        if (!\Illuminate\Support\Facades\File::exists($dirname)) {
            \Illuminate\Support\Facades\File::makeDirectory($dirname, 0777, true, true);
        }
    }

    // if source is a file, just copy it
    if (\Illuminate\Support\Facades\File::isFile($src)) {
        \Illuminate\Support\Facades\File::copy($src, $dst);
    } else {
        \Illuminate\Support\Facades\File::copyDirectory($src, $dst);
    }
}

function ptouch($filepath)
{
    $dirname = dirname($filepath);
    if (!\Illuminate\Support\Facades\File::exists($dirname)) {
        \Illuminate\Support\Facades\File::makeDirectory($dirname, 0777, true, true);
    }

    touch($filepath);
}

function xml_to_array(SimpleXMLElement $xml)
{
    $parser = function (SimpleXMLElement $xml, array $collection = []) use (&$parser) {
        $nodes = $xml->children();
        $attributes = $xml->attributes();

        if (0 !== count($attributes)) {
            foreach ($attributes as $attrName => $attrValue) {
                $collection['attributes'][$attrName] = html_entity_decode(strval($attrValue));
            }
        }

        if (0 === $nodes->count()) {
            // $collection['value'] = strval($xml);
            // return $collection;
            return html_entity_decode(strval($xml));
        }

        foreach ($nodes as $nodeName => $nodeValue) {
            if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
                $collection[$nodeName] = $parser($nodeValue);
                continue;
            }

            $collection[$nodeName][] = $parser($nodeValue);
        }

        return $collection;
    };

    return [
        $xml->getName() => $parser($xml)
    ];
}

function spfcheck($ipOrHostname, $domain)
{
    $checker = new SPFCheck(new DNSRecordGetterDirect('8.8.8.8'));

    // $checker = new SPFCheck(new DNSRecordGetter());
    $result = $checker->isIPAllowed($ipOrHostname, $domain);

    if (SPFCheck::RESULT_PASS != $result) {
        // try again with another method
        $checker = new SPFCheck(new DNSRecordGetter());
        $result = $checker->isIPAllowed($ipOrHostname, $domain);
    }

    return $result;
}

function forceAddCustomerToUnlimitedPlan($customer)
{
    // Default subscription
    $subscription = new \Acelle\Model\Subscription();
    $subscription->status = \Acelle\Model\Subscription::STATUS_ACTIVE;
    $subscription->current_period_ends_at = \Carbon\Carbon::now()->addYears(1000);
    $subscription->plan_id = \Acelle\Model\PlanGeneral::UNLIMITED_PLAN_ID;
    $subscription->customer_id = $customer->id;
    $subscription->save();
}

function isValidPublicHostnameOrIpAddress($host)
{
    if ($host == '127.0.0.1' || $host == 'localhost') {
        return false;
    }

    $isValidIpAddress = filter_var($host, FILTER_VALIDATE_IP);
    $getHostByName = gethostbyname($host);

    if ($isValidIpAddress) {
        return true;
    } elseif (filter_var($getHostByName, FILTER_VALIDATE_IP)) {
        return true;
    } else {
        return false;
    }
}

function write_env($key, $value, $overwrite = true)
{
    // Important, make the new environment var available
    // Otherwise, this method may failed if called twice (in a loop for example) in the same process
    \Artisan::call('config:clear');

    // In case config:clear does not work
    if (file_exists(base_path('bootstrap/cache/config.php'))) {
        unlink(base_path('bootstrap/cache/config.php'));
    }

    $envs = load_env_from_file(app()->environmentFilePath());

    // Set the value if overwrite is set to true or the key value is empty
    if ($overwrite || !array_key_exists($key, $envs) || empty($envs[$key])) {
        // Quote if there is at least one space or # or any suspected char!
        if (preg_match('/[\s\#!\$]/', $value)) {
            // Escape single quote
            $value = addcslashes($value, '"');
            $value = "\"$value\"";
        }

        $envs[$key] = $value;
    } else {
        return;
    }

    $out = [];
    foreach ($envs as $k => $v) {
        $out[] = "$k=$v";
    }

    $out = implode("\n", $out);

    // Actually write to file .env
    file_put_contents(app()->environmentFilePath(), $out);
}

function write_envs($params)
{
    foreach ($params as $key => $value) {
        write_env($key, $value);
    }
}

function reset_app_url($force = false)
{
    $envs = load_env_from_file(app()->environmentFilePath());
    if (!array_key_exists('APP_URL', $envs) || $force) {
        $url = url('/');
        write_env('APP_URL', $url);
    }
}

function url_get_contents_ssl_safe($url)
{
    // Check if $url is a URL
    if (!preg_match('/^https{0,1}:\/\//', $url)) {
        throw new \Exception('url_get_contents_ssl_safe() requires a URL as input. Received: '.$url);
    }

    $client = curl_init();
    curl_setopt_array($client, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $result = curl_exec($client);
    curl_close($client);

    return $result;
}

function is_non_web_link($url)
{
    $preserved = [ '#', 'mailto:', 'tel:', 'file:', 'ftp:', 'rss:', 'feed:', ':telnet', 'gopher:', 'ssh:', 'nntp:'];

    // Important: do not use filter_var($url, FILTER_VALIDATE_URL);
    $matched = false;
    foreach ($preserved as $prefix) {
        if (strpos($url, $prefix) === 0) {
            $matched = true;
            break;
        }
    }

    return $matched;
}

// IMPORTANT
// + This function does not purify values, it will load raw content like: [ DB => "'mydb'", OTHER => '""']
// + Allow only a-zA-Z_ in key name
function load_env_from_file($path)
{
    $content = file_get_contents($path);
    $lines = preg_split("/(\r\n|\n|\r)/", $content);
    $lines = array_where($lines, function ($value, $key) {
        if (is_null($value)) {
            return false;
        }

        if (preg_match('/^[a-zA-Z0-9_]+=/', $value)) {
            return true;
        } else {
            return false;
        }
    });

    $output = [];
    foreach ($lines as $line) {
        list($key, $value) = explode('=', $line, 2);

        if (is_null($value)) {
            $value = '';
        } else {
            $value = trim($value);
        }

        $output[ $key ] = $value;
    }

    return $output;
}

/*
 * Execute a task and count credits
 * Roll back credits if failure
 * @important: only rollback credits count. Do not rollback rate count because
 *             even a failed operation attempt is counted
 * Parameter 1: \Acelle\Library\RateTracker[]
 * Parameter 2: \Acelle\Library\CreditTracker[]
 */
function execute_with_limits(array $rateTrackers, array $creditTrackers, Closure $task = null)
{
    // Remove null tracker from array
    $rateTrackers = array_values(array_filter($rateTrackers));
    $creditTrackers = array_values(array_filter($creditTrackers));

    // Check credits first, because credits can be rolled back
    // Check rate after that
    $creditCounted = [];
    try {
        foreach ($creditTrackers as $creditTracker) {
            // Might throw \Acelle\Library\Exception\OutOfCredits
            $creditTracker->count();
            $creditCounted[] = $creditTracker;
        }
    } catch (\Acelle\Library\Exception\OutOfCredits $exception) {
        // Rollback 1: when OutOfCredits
        // @important: rate is counted even for a failed operation attempt
        // So there is no need to roll it back (that's why the rollback() method is @deprecated)
        // However, credits must be rolled back before exit
        foreach ($creditCounted as $creditTracker) {
            $creditTracker->rollback();
        }

        throw $exception;
    }

    $rateCounted = [];
    try {
        // Might throw \Acelle\Library\Exception\RateLimitExceeded
        foreach ($rateTrackers as $rateTracker) {
            $rateTracker->count();
            $rateCounted[] = $rateTracker;
        }
    } catch (\Acelle\Library\Exception\RateLimitExceeded $exception) {
        // Credits must not be counted
        foreach ($creditCounted as $creditTracker) {
            $creditTracker->rollback();
        }

        // In case of more than one rate trackers
        // + Tracker A works just fine
        // + Tracker B works just fine
        // + Tracker C fails
        // Rollback tracker A and B
        foreach ($rateCounted as $rateTracker) {
            $rateTracker->rollback();
        }

        // This exception is safely handled in SendMessage ( i.e. catch (RateLimitExceeded $ex) )
        throw $exception;
    }

    try {
        // Return null if task is null, i.e. count credits but do not actually do anything
        if (is_null($task)) {
            return;
        }

        // Execute task
        $task();
    } catch (\Throwable $exception) {
        // Rollback 2: when task error
        // @important: rate is counted even for a failed operation attempt
        // So there is no need to roll it back (that's why the rollback() method is @deprecated)
        // However, credits must be rolled back before exit
        foreach ($creditCounted as $creditTracker) {
            $creditTracker->rollback();
        }

        throw $exception;
    }
}

function maskEmail($email)
{
    // Split the email address into parts
    list($username, $domain) = explode('@', $email);

    // Get the length of the username
    $usernameLength = strlen($username);

    // Keep the first and last character of the username
    $maskedUsername = substr($username, 0, 1) . str_repeat('*', $usernameLength - 2) . substr($username, -1);

    // Mask the domain
    $maskedDomain = substr($domain, 0, 1) . str_repeat('*', strlen($domain) - 2) . substr($domain, -1);

    // Concatenate the masked username and domain
    $maskedEmail = $maskedUsername . '@' . $maskedDomain;

    return $maskedEmail;
}

function read_csv($file, $headerLine = false)
{
    if (!file_exists($file)) {
        throw new \Exception("File {$file} not found");
    }

    try {
        // Fix the problem with MAC OS's line endings
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        // return false or an encoding name
        $encoding = StringHelper::detectEncoding($file);

        if ($encoding == false) {
            // Cannot detect file's encoding
        } elseif ($encoding != 'UTF-8') {
            // Convert from {$encoding} to UTF-8";
            StringHelper::toUTF8($file, $encoding);
        } else {
            // File encoding is UTF-8
            StringHelper::checkAndRemoveUTF8BOM($file);
        }

        // Run this method anyway
        // to make sure mb_convert_encoding($content, 'UTF-8', 'UTF-8') is always called
        // which helps resolve the issue of
        //     "Error executing job. SQLSTATE[HY000]: General error: 1366 Incorrect string value: '\x83??s k...' for column 'company' at row 2562 (SQL: insert into `dlk__tmp_subscribers..."
        StringHelper::toUTF8($file, 'UTF-8');

        // Read CSV files
        $reader = \League\Csv\Reader::createFromPath($file);
        if ($headerLine) {
            $reader->setHeaderOffset(0);
            $headers = $reader->getHeader();

            // Make sure the headers are present
            // In case of duplicate column headers, an exception shall be thrown by League
            foreach ($headers as $index => $header) {
                if (is_null($header) || empty(trim($header))) {
                    throw new \Exception(trans('messages.list.import.error.header_empty', ['index' => $index]));
                }
            }

            // Remove leading/trailing spaces in headers, keep letter case
            // get the headers, using array_filter to strip empty/null header
            // to avoid the error of "InvalidArgumentException: Use a flat array with unique string values in /home/nghi/mailixa/vendor/league/csv/src/Reader.php:305"
            $headers = array_map(function ($r) {
                return trim($r);
            }, $headers);

            $results = $reader->getRecords($headers);
        } else {
            $headers = null;
            $results = $reader->getRecords();
        }

        return [$headers, iterator_count($results), $results];
    } catch (\Exception $ex) {
        // @todo: translation here
        // Common errors that will be catched: duplicate column, empty column
        throw new \Exception('Invalid headers. Original error message is: '.$ex->getMessage());
    }
}

function isSendingCreditPluginActive()
{
    return \Acelle\Model\Plugin::isInstalled('acelle/sending-credit') &&
        \Acelle\Model\Plugin::getByName('acelle/sending-credit')->isActive();
}

function plogger($name = null)
{
    $formatter = new \Monolog\Formatter\LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");
    $pid = getmypid();
    $logfile = storage_path(join_paths('logs', php_sapi_name(), '/process-'.$pid.'.log'));
    $stream = new \Monolog\Handler\RotatingFileHandler($logfile, 0, config('custom.log_level'));
    $stream->setFormatter($formatter);

    $logger = new \Monolog\Logger($name ?: 'process');
    $logger->pushHandler($stream);
    return $logger;
}

function dkim_sign(\Swift_Message $message, \Acelle\Library\Domain $domain)
{
    $privateKey = $domain->getPrivateKey();
    $domainName = $domain->getName();
    $selector = $domain->getDkimSelector();

    $signer = new \Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);
    $signer->ignoreHeader('Return-Path');

    $message->attachSigner($signer);

    return $message;
}

function dkim_sign_with_default_domain(\Swift_Message $message)
{
    if (empty(config('default_auth_domain'))) {
        throw new \Exception('"sign_with_default_domain" is set to TRUE but no default domain found');
    }

    $name = config('default_auth_domain.domain');
    $selector = config('default_auth_domain.selector');
    $publicKey = config('default_auth_domain.public_key');
    $privateKey = config('default_auth_domain.private_key');

    $domain = new \Acelle\Library\Domain($name, $selector, $publicKey, $privateKey);
    return \Acelle\Helpers\dkim_sign($message, $domain);
}
