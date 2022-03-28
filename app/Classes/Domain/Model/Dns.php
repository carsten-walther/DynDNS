<?php

namespace CarstenWalther\DynDNS\Domain\Model;

/**
 * Dns
 */
class Dns extends AbstractModel
{
    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $ip6;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return Dns
     */
    public function setIp(string $ip): Dns
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp6(): string
    {
        return $this->ip6;
    }

    /**
     * @param string $ip6
     * @return Dns
     */
    public function setIp6(string $ip6): Dns
    {
        $this->ip6 = $ip6;
        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @return Dns
     */
    public function setDomain(string $domain): Dns
    {
        $this->domain = $domain;
        return $this;
    }
}
