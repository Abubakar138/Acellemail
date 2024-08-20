<?php

namespace Acelle\Library\Everification;

use Exception;
use ZeroBounce\SDK\ZeroBounce as ZeroBounceApi;

class ZeroBounce
{
    protected $options;
    protected $logger;

    public function __construct($options, $logger = null)
    {
        $this->options = $options;
        $this->logger = $logger;
    }

    public function verify($email)
    {
        // See list of test email addresses at:
        // https://www.zerobounce.net/docs/email-validation-api-quickstart/#sandbox_mode__v2__
        ZeroBounceApi::Instance()->initialize($this->options['api_key']);
        $response = ZeroBounceApi::Instance()->validate($email, $ip = null);

        // It is stupid that ZB returns null if API is invalid
        // It should throw an exception instead
        if (empty($response->status)) {
            throw new Exception('Invalid ZeroBounce API KEY');
        }

        // See list of 7 status codes here: https://www.zerobounce.net/docs/email-validation-api-quickstart/#status_codes__v2__
        $statusMap = [
            'valid' => 'deliverable',
            'invalid' => 'undeliverable',
            'catch-all' => 'deliverable',
            'unknown'    => 'unknown',
            'spamtrap' => 'risky',
            'abuse' => 'risky', // really?
            'do_not_mail' => 'risky', // really?
        ];

        if (!array_key_exists(strtolower($response->status), $statusMap)) {
            throw new Exception('Unknown status code returned from ZeroBounce: '.$response->status);
        }

        $verificationStatus = $statusMap[strtolower($response->status)];
        $rawResponse = $response->__toString();

        return [$verificationStatus, $rawResponse];
    }
}
