<?php

namespace CarstenWalther\DynDNS\Service;

use CarstenWalther\DynDNS\Utility\DebugUtility;
use CURLFile;
use RuntimeException;

/**
 * ProxyService
 *
 * Usage:
 *    To call this script two headers must be sent
 *        HTTP_PROXY_AUTH           Access key for the proxy (should be changed)
 *        HTTP_PROXY_TARGET_URL     URL to be called by this script
 *
 * Debug:
 *    To debug, send HTTP_PROXY_DEBUG header with any non-zero value
 *
 * Compatibility:
 *    PHP >=5.6
 *    libcurl
 *    gzip
 *    PHP safe_mode disabled
 */
class ProxyService
{
    /**
     * Your private auth key. It is recommended to change it.
     * If you installed the package via composer, call `Proxy::$AUTH_KEY = '<your-new-key>';` before running the proxy.
     * If you copied this file, change the value here in place.
     * @var string
     */
    public static $AUTH_KEY = 'QX#YK@7X4jdQiK6ZakCG%CsZ&@c7PiEw';

    /**
     * Set this to false to disable authorization. Useful for debugging, not recommended in production.
     * @var bool
     */
    public static $ENABLE_AUTH = true;

    /**
     * Enable debug mode (you can do it by sending Proxy-Debug header as well).
     * This value overrides any value specified in Proxy-Debug header.
     * @var bool
     */
    public static $DEBUG = false;

    /**
     * When set to false the fetched header is not included in the result
     * @var bool
     */
    public static $CURLOPT_HEADER = true;

    /**
     * When set to false the fetched result is echoed immediately instead of waiting for the fetch to complete first
     * @var bool
     */
    public static $CURLOPT_RETURNTRANSFER = true;

    /**
     * Target URL is set via Proxy-Target-URL header. For debugging purposes you might set it directly here.
     * This value overrides any value specified in Proxy-Target-URL header.
     * @var string
     */
    public static $TARGET_URL = '';

    /**
     * Name of remote debug header
     * @var string
     */
    public static $HEADER_HTTP_PROXY_DEBUG = 'HTTP_PROXY_DEBUG';

    /**
     * Name of the target url header
     * @var string
     */
    public static $HEADER_HTTP_PROXY_TARGET_URL = 'HTTP_PROXY_TARGET_URL';

    /**
     * Line break for debug purposes
     * @var string
     */
    private static $HR = PHP_EOL . PHP_EOL . '----------------------------------------------' . PHP_EOL . PHP_EOL;

    /**
     * @return int HTTP response code (200, 404, 500, etc.)
     */
    public function run(): int
    {
        $debug = $this->isDebug();
        $targetURL = $this->getTargetUrl();

        $request = $this->createRequest($targetURL);

        // Get response
        $response = curl_exec($request);

        $headerSize = curl_getinfo($request, CURLINFO_HEADER_SIZE);
        $responseHeader = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        $responseInfo = curl_getinfo($request);
        $responseCode = $this->ri($responseInfo['http_code'], 500);
        $redirectCount = $this->ri($responseInfo['redirect_count'], 0);
        $requestHeaders = preg_split('/[\r\n]+/', $this->ri($responseInfo['request_header'], ''));

        if ($responseCode === 0) {
            $responseCode = 404;
        }

        $finalRequestURL = curl_getinfo($request, CURLINFO_EFFECTIVE_URL);

        if ($redirectCount > 0 && !empty($finalRequestURL)) {
            $finalRequestURLParts = parse_url($finalRequestURL);
            $effectiveURL = $this->ri($finalRequestURLParts['scheme'], 'http') . '://' . $this->ri($finalRequestURLParts['host']) . $this->ri($finalRequestURLParts['path'], '');
        }

        curl_close($request);

        //----------------------------------

        // Split header text into an array.
        $responseHeaders = $this->splitResponseHeaders($responseHeader);
        // Pass headers to output
        foreach ($responseHeaders as $header) {
            $headerParts = preg_split('/:\s+/', $header, 2);
            if (count($headerParts) !== 2) {
                throw new RuntimeException("Can not parse header \"$header\"");
            }

            $headerName = $headerParts[0];
            $loweredHeaderName = strtolower($headerName);

            $headerValue = $headerParts[1];
            $loweredHeaderValue = strtolower($headerValue);

            // Pass following headers to response
            if (in_array($loweredHeaderName, ['content-type', 'content-language', 'content-security', 'server'])) {
                header("$headerName: $headerValue");
            } elseif (strpos($loweredHeaderName, 'x-') === 0) {
                header("$headerName: $headerValue");
            } // Replace cookie domain and path
            elseif ($loweredHeaderName === 'set-cookie') {
                $newValue = preg_replace('/((?>domain)\s*=\s*)[^;\s]+/', '\1.' . $_SERVER['HTTP_HOST'], $headerValue);
                $newValue = preg_replace('/\s*;?\s*path\s*=\s*[^;\s]+/', '', $newValue);
                header("$headerName: $newValue", false);
            } // Decode response body if gzip encoding is used
            elseif ($loweredHeaderName === 'content-encoding' && $loweredHeaderValue === 'gzip') {
                $responseBody = gzdecode($responseBody);
            }
        }

        http_response_code($responseCode);

        //----------------------------------

        if ($debug) {
            echo 'Headers sent to proxy' . PHP_EOL . PHP_EOL;
            echo implode(PHP_EOL, $this->getIncomingRequestHeaders());
            echo static::$HR;

            if (!empty($_GET)) {
                echo '$_GET sent to proxy' . PHP_EOL . PHP_EOL;
                print_r($_GET);
                echo static::$HR;
            }

            if (!empty($_POST)) {
                echo '$_POST sent to proxy' . PHP_EOL . PHP_EOL;
                print_r($_POST);
                echo static::$HR;
            }

            echo 'Headers sent to target' . PHP_EOL . PHP_EOL;
            echo implode(PHP_EOL, $requestHeaders);
            echo static::$HR;

            if (isset($effectiveURL) && $effectiveURL !== $targetURL) {
                echo "Request was redirected from \"$targetURL\" to \"$effectiveURL\"";
                echo static::$HR;
            }

            echo 'Headers received from target' . PHP_EOL . PHP_EOL;
            echo $responseHeader;
            echo static::$HR;

            echo 'Headers sent from proxy to client' . PHP_EOL . PHP_EOL;
            echo implode(PHP_EOL, headers_list());
            echo static::$HR;

            echo 'Body sent from proxy to client' . PHP_EOL . PHP_EOL;
        }

        echo $responseBody;
        return $responseCode;
    }

    /**
     * Return variable or default value if not set
     *
     * @param mixed $variable
     * @param mixed|null $default
     * @return mixed
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    private function ri(&$variable, $default = null)
    {
        return $variable ?? $default;
    }

    /**
     * @return bool
     */
    private function isDebug(): bool
    {
        return static::$DEBUG || !empty($_SERVER[static::$HEADER_HTTP_PROXY_DEBUG]);
    }

    /**
     * @return string
     */
    private function getTargetUrl(): string
    {
        if (!empty(static::$TARGET_URL)) {
            $targetURL = static::$TARGET_URL;
        } else {
            $targetURL = $this->ri($_SERVER[static::$HEADER_HTTP_PROXY_TARGET_URL]);
        }

        if (empty($targetURL)) {
            throw new RuntimeException(static::$HEADER_HTTP_PROXY_TARGET_URL . ' header is empty');
        }

        if (filter_var($targetURL, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException(static::$HEADER_HTTP_PROXY_TARGET_URL . ' "' . $targetURL . '" is invalid');
        }

        return $targetURL;
    }

    /**
     * @param string $targetURL
     * @return resource|false|\CurlHandle
     */
    private function createRequest(string $targetURL)
    {
        $request = curl_init($targetURL);

        // Set input data
        $requestMethod = strtoupper($this->ri($_SERVER['REQUEST_METHOD']));
        if ($requestMethod === "PUT" || $requestMethod === "PATCH") {
            curl_setopt($request, CURLOPT_POST, true);
            curl_setopt($request, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
        } elseif ($requestMethod === "POST") {
            $data = [];

            if (!empty($_FILES)) {

                if (!$this->hasCURLFileSupport()) {
                    curl_setopt($request, CURLOPT_SAFE_UPLOAD, false);
                }

                foreach ($_FILES as $fileName => $file) {
                    $filePath = realpath($file['tmp_name']);

                    if ($this->hasCURLFileSupport()) {
                        $data[$fileName] = new CURLFile($filePath, $file['type'], $file['name']);
                    } else {
                        $data[$fileName] = '@' . $filePath;
                    }
                }
            }

            curl_setopt($request, CURLOPT_POST, true);
            curl_setopt($request, CURLOPT_POSTFIELDS, $data + $_POST);
        }

        $headers = $this->getIncomingRequestHeaders($this->getSkippedHeaders());

        if (isset($headers['Cookie'])) {
            curl_setopt($request, CURLOPT_COOKIE, $headers['Cookie']);
        }

        curl_setopt_array($request, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => static::$CURLOPT_HEADER,
            CURLOPT_RETURNTRANSFER => static::$CURLOPT_RETURNTRANSFER,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);

        return $request;
    }

    /**
     * @return bool
     */
    private function hasCURLFileSupport(): bool
    {
        return class_exists(\CURLFile::class);
    }

    /**
     * @param string[] $skippedHeaders
     * @return string[]
     */
    private function getIncomingRequestHeaders(array $skippedHeaders = []): array
    {
        $results = [];
        foreach ($_SERVER as $key => $value) {

            if (in_array($key, $skippedHeaders)) {
                continue;
            }

            $loweredKey = strtolower($key);

            if (strpos($loweredKey, 'http_') === 0) {
                // Remove prefix
                $key = substr($loweredKey, strlen('http_'));
                // Replace underscores with dashes
                $key = str_replace('_', '-', $key);
                // Capital each word
                $key = ucwords($key, '-');

                $results[$key] = "$key: $value";
            }
        }

        $replaceHost = parse_url($this->getTargetUrl(), PHP_URL_HOST);
        $replace = $replaceHost;

        $replacePort = parse_url($this->getTargetUrl(), PHP_URL_PORT);
        if ($replacePort != '80') {
            $replace = $replaceHost . ':' . $replacePort;
        }

        if (isset($results['Referer'])) {
            $results['Referer'] = 'Referer: ' . str_replace($_SERVER['HTTP_HOST'], $replace, $results['Referer']);
        }

        if (isset($results['Origin'])) {
            $results['Origin'] = 'Origin: ' . str_replace($_SERVER['HTTP_HOST'], $replace, $results['Origin']);
        }

        return $results;
    }

    /**
     * @return string[]
     */
    private function getSkippedHeaders(): array
    {
        return [
            static::$HEADER_HTTP_PROXY_TARGET_URL,
            static::$HEADER_HTTP_PROXY_DEBUG,
            'HTTP_HOST',
            'HTTP_ACCEPT_ENCODING'
        ];
    }

    /**
     * @param string $headerString
     * @return string[]
     */
    private function splitResponseHeaders(string $headerString): array
    {
        $results = [];
        $headerLines = preg_split('/[\r\n]+/', $headerString);

        foreach ($headerLines as $headerLine) {

            if (empty($headerLine)) {
                continue;
            }

            // Header contains HTTP version specification and path
            if (strpos($headerLine, 'HTTP/') === 0) {
                // Reset the output array as there may by multiple response headers
                $results = [];
                continue;
            }

            $results[] = "$headerLine";
        }

        return $results;
    }
}
