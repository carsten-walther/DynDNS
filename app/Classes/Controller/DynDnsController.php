<?php

namespace CarstenWalther\DynDNS\Controller;

use CarstenWalther\DynDNS\Domain\Model\Dns;
use CarstenWalther\DynDNS\Domain\Repository\DnsRepository;
use Exception;

/**
 * DynDnsController
 */
class DynDnsController
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var DnsRepository
     */
    protected $dnsRepository;

    /**
     * @param string $basePath
     * @param array $repositoryConfiguration
     * @throws Exception
     */
    function __construct(string $basePath, array $repositoryConfiguration)
    {
        $this->basePath = $basePath;
        $this->initRepository($repositoryConfiguration);
        $this->dispatch();
    }

    /**
     * @param array $repositoryConfiguration
     * @return void
     */
    private function initRepository(array $repositoryConfiguration): void
    {
        $this->dnsRepository = new DnsRepository($repositoryConfiguration['data'], 'ip.txt');
    }

    /**
     * @throws Exception
     */
    private function dispatch()
    {
        $mode = key_exists('mode', $_GET) ? $_GET['mode'] : null;

        $user = key_exists('username', $_GET) ? $_GET['username'] : null;
        $pass = key_exists('pass', $_GET) ? $_GET['pass'] : null;

        $ip = key_exists('ipaddr', $_GET) ? $_GET['ipaddr'] : '';
        $ip6 = key_exists('ip6addr', $_GET) ? $_GET['ip6addr'] : '';
        $domain = key_exists('domain', $_GET) ? $_GET['domain'] : '';

        if ($mode === 'dyndns' && $user === USERNAME && $pass === PASSWORD) {
            $dns = new Dns();
            $dns->setIp($ip)->setIp6($ip6)->setDomain($domain);
            $this->dnsRepository->add($dns);
        } else {
            $dns = $this->dnsRepository->get();
            if ($dns) {
                $requestUri = $_SERVER['REQUEST_URI'];
                $url = PROTOCOL . '://' . $dns->getIp() . ':' . PORT . PATH . $requestUri;
                header("Location: " . $url, true, 302);
            } else {
                http_response_code(404);
            }
        }
    }
}
