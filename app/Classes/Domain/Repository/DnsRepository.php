<?php

namespace CarstenWalther\DynDNS\Domain\Repository;

use CarstenWalther\DynDNS\Domain\Model\Dns;
use DateTime;

/**
 * DnsRepository
 */
class DnsRepository extends AbstractRepository
{
    /**
     * @param Dns $dns
     * @return void
     */
    public function add(Dns $dns): void
    {
        $content = (new DateTime('now'))->getTimestamp() . $this->delimiter . $dns->getDomain() . $this->delimiter . $dns->getIp() . $this->delimiter . $dns->getIp6() . PHP_EOL;

        $resource = fopen($this->file, "w");
        fwrite($resource, $content);
        fclose($resource);
    }

    /**
     * @return Dns|null
     */
    public function findLatest(): ?Dns
    {
        $resource = fopen($this->file, "r+");
        $content = fread($resource, filesize($this->file) > 0 ? filesize($this->file) : 1);
        fclose($resource);

        $content = explode($this->delimiter, trim($content));

        if (sizeof($content) < 4) {
            return null;
        }

        $dns = new Dns();
        $dns->setDomain($content[1] ?: '');
        $dns->setIp($content[2] ?: '');
        $dns->setIp6($content[3] ?: '');

        return $dns;
    }
}
