<?php

namespace CarstenWalther\DynDNS\Controller;

use CarstenWalther\DynDNS\Domain\Model\Dns;
use CarstenWalther\DynDNS\Domain\Repository\DnsRepository;
use CarstenWalther\DynDNS\Utility\DebugUtility;
use Exception;

use Proxy\Config;
use Proxy\Http\Request;
use Proxy\Proxy;

/**
 * DynDnsController
 */
class DynDnsController
{
    /**
     * @var string
     */
    protected string $basePath;

    /**
     * @var DnsRepository
     */
    protected DnsRepository $dnsRepository;

    /**
     * @var Dns
     */
    protected Dns $dns;

    /**
     * @param string $basePath
     * @param array $repositoryConfiguration
     * @throws Exception
     */
    public function __construct(string $basePath, array $repositoryConfiguration)
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
    private function dispatch(): void
    {
        $mode = $_GET['mode'] ?? null;

        $user = $_GET['username'] ?? null;
        $pass = $_GET['pass'] ?? null;

        $ip = array_key_exists('ipaddr', $_GET) ? $_GET['ipaddr'] : '';
        $ip6 = array_key_exists('ip6addr', $_GET) ? $_GET['ip6addr'] : '';
        $domain = array_key_exists('domain', $_GET) ? $_GET['domain'] : '';

        if ($mode === 'dyndns' && $user === USERNAME && $pass === PASSWORD) {

            $this->dns = new Dns();
            $this->dns->setIp($ip)->setIp6($ip6)->setDomain($domain);
            $this->dnsRepository->add($this->dns);

        } elseif ($this->dns = $this->dnsRepository->findLatest()) {

            if (USE_PROXY) {

                $url = PROTOCOL . '://' . (USE_IP6 ? '[' . $this->dns->getIp6() . ']' : $this->dns->getIp()) . (PORT ? ':' . PORT : '') . PATH . $_SERVER['REQUEST_URI'];

                Config::set('curl', CURL);
                Config::set('app_key', API_KEY);
                Config::set('url_mode', URL_MODE);

                if (Config::get('url_mode') === 2) {
                    Config::set('encryption_key', md5(Config::get('app_key') . $_SERVER['REMOTE_ADDR']));
                } elseif (Config::get('url_mode') === 3) {
                    Config::set('encryption_key', md5(Config::get('app_key') . session_id()));
                }

                $proxy = new Proxy();

                foreach (['HeaderRewrite', /*'Cors',*/ 'Stream', 'Cookie', 'Proxify'] as $plugin) {
                    $plugin_class = $plugin . 'Plugin';
                    if (class_exists('\\Proxy\\Plugin\\' . $plugin_class)) {
                        $plugin_class = '\\Proxy\\Plugin\\' . $plugin_class;
                        $proxy->addSubscriber(new $plugin_class());
                    }
                }

                // request sent to index.php
                $request = Request::createFromGlobals();
                // remove all GET parameters such as ?q=
                $request->get->clear();

                // forward it to some other URL
                $response = $proxy->forward($request, $url);
                // if that was a streaming response, then everything was already sent and script will be killed before it even reaches this line
                $response->send();

            } else {
                header("Location: " . PROTOCOL . '://' . (USE_IP6 ? '[' . $this->dns->getIp6() . ']' : $this->dns->getIp()) . ':' . PORT . PATH . $_SERVER['REQUEST_URI'], true, 302);
            }

        } else {
            http_response_code(404);
        }
    }
}
