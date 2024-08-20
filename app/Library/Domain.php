<?php

namespace Acelle\Library;

class Domain
{
    public const DOMAINKEY = '._domainkey';

    protected $name;
    protected $selctor;
    protected $publicKey;
    protected $privateKey;

    public function __construct($name, $selector, $publicKey, $privateKey)
    {
        $this->name = $name;
        $this->selector = $selector;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    public function getDkimDnsRecord()
    {
        return sprintf('v=DKIM1; k=rsa; p=%s;', $this->getCleanPublicKey());
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDkimSelector()
    {
        return $this->selector;
    }

    public function getFullDkimSelector()
    {
        return $this->getDkimSelector().self::DOMAINKEY; // For example "{selector}._domainkey"
    }

    public function getFullDkimHostName()
    {
        return "{$this->getFullDkimSelector()}.{$this->name}."; // For example "{selector}._domainkey.example.com."
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    private function getCleanPublicKey()
    {
        $cleanPublicKey = str_replace(array('-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'), '', $this->publicKey);
        $cleanPublicKey = trim(preg_replace('/\s+/', '', $cleanPublicKey));

        return $cleanPublicKey;
    }

    /**
     * Verify DKIM record, update domain status accordingly.
     *
     * @return mixed
     */
    public function verifyDkim()
    {
        $raw = $this->getDkimDnsRecord();
        $quoted = doublequote($raw);
        $escaped = str_replace(';', '\;', $quoted);

        $possibles = collect([$raw, $quoted, $quoted]);
        $possibles = $possibles->map(function ($item, $key) {
            return preg_replace('/\s+/', '', $item);
        });

        $fqdn = sprintf('%s.%s', $this->getFullDkimSelector(), $this->name);
        $results = collect(dns_get_record($fqdn, DNS_TXT))->where('type', 'TXT')->map(function ($item, $key) {
            return preg_replace('/\s+/', '', $item['txt']);
        });
        $results = $results->intersect($possibles);

        return $results->isEmpty() ? false : true;
    }

    public function generateDnsRecords()
    {
        return [
            'type' => 'TXT',
            'name' => $this->getFullDkimHostName(),
            'value' => doublequote($this->getDkimDnsRecord()),
        ];
    }
}
