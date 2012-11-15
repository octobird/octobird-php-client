<?php

/**
 * @author Vitaly Gridasov <vitaly@octobird.com>
 */
class OctobirdClient
{
    const VERSION        = '3.0.0';
    const VERSION_API    = 300;
    const SERVER_URL     = 'http://show.octobird.com/';
    const HTML_SIGNATURE = '<!-- fe34er3 -->';
    const BANNER_TYPE_ALL   = 'all';
    const BANNER_TYPE_IMAGE = 'img';
    const BANNER_TYPE_TEXT  = 'txt';
    const BANNER_MAX_NUMBER = 5;
    const BLOCK_MAX_NUMBER  = 3;
    const COOKIE_LABEL_NAME = '_obid';
    const VIEWER_GENDER_FEMALE = 'f';
    const VIEWER_GENDER_MALE   = 'm';
    const RESPONSE_FORMAT_HTML      = 'html';
    const RESPONSE_FORMAT_JSON      = 'json';
    const RESPONSE_FORMAT_JSON_HTML = 'json-html';
    const RESPONSE_FORMAT_XML       = 'xml';
    const DEFAULT_BANNER_TYPE     = self::BANNER_TYPE_ALL;
    const DEFAULT_BANNER_NUMBER   = 3;
    const DEFAULT_RESPONSE_FORMAT = self::RESPONSE_FORMAT_HTML;
    const DEFAULT_CONNECT_TIMEOUT = 1000;
    const DEFAULT_TIMEOUT         = 1000;

    private static $BANNER_TYPES = array(
        self::BANNER_TYPE_ALL,
        self::BANNER_TYPE_IMAGE,
        self::BANNER_TYPE_TEXT,
    );
    private static $instance;

    private $params = array();
    private $blocks = array();
    private $defaultResponse;
    private $serverUrl;
    private $connectTimeout;
    private $timeout;
    private $isSent;
    private $responseFormat;
    private $responseRaw;
    private $response;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this
            ->setParam('version', self::VERSION_API)
            ->setBannerType(self::DEFAULT_BANNER_TYPE)
            ->setBannerNumber(self::DEFAULT_BANNER_NUMBER)
            ->setResponseFormat(self::DEFAULT_RESPONSE_FORMAT)
            ->setServerUrl(self::SERVER_URL)
            ->setConnectTimeout(self::DEFAULT_CONNECT_TIMEOUT)
            ->setTimeout(self::DEFAULT_TIMEOUT)
            ->setupCookieLabel()
        ;
    }

    /**
     * @return OctobirdClient
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param integer $siteId
     *
     * @return OctobirdClient
     */
    public function setSiteId($siteId)
    {
        return $this->setParam('sid', $siteId);
    }

    /**
     * @param string $bannerType all|img|txt
     *
     * @return OctobirdClient
     */
    public function setBannerType($bannerType)
    {
        return $this->setParam('tp', $bannerType);
    }

    /**
     * @param integer $bannerNumber 1-5
     *
     * @return OctobirdClient
     */
    public function setBannerNumber($bannerNumber)
    {
        return $this->setParam('nb', $bannerNumber);
    }

    /**
     * @param integer $bannerSeverallyHtml 0|1, default 0
     *
     * @return OctobirdClient
     */
    public function setBannerSeverallyHtml($bannerSeverallyHtml)
    {
        return $this->setParam('adhtml', $bannerSeverallyHtml);
    }

    /**
     * @param string $viewerGender f|m
     *
     * @return OctobirdClient
     */
    public function setViewerGender($viewerGender)
    {
        return $this->setParam('gender', $viewerGender);
    }

    /**
     * @param ineteger $viewerAge 0-100
     *
     * @return OctobirdClient
     */
    public function setViewerAge($viewerAge)
    {
        return $this->setParam('age', $viewerAge);
    }

    /**
     * @param integer $viewerDateOfBirth YYYYMMDD, ex. 19900623
     *
     * @return OctobirdClient
     */
    public function setViewerDateOfBirth($viewerDateOfBirth)
    {
        return $this->setParam('dob', $viewerDateOfBirth);
    }

    /**
     * @param string $viewerCity
     *
     * @return OctobirdClient
     */
    public function setViewerCity($viewerCity)
    {
        return $this->setParam('city', $viewerCity);
    }

    /**
     * @param float $viewerLatitude
     * @param float $viewerLongitude
     *
     * @return OctobirdClient
     */
    public function setViewerGps($viewerLatitude, $viewerLongitude)
    {
        return $this->setParam('gps', $viewerLatitude . ',' . $viewerLongitude);
    }

    /**
     * @param string $viewerKeyWords ex. "sun,sea,girls"
     *
     * @return OctobirdClient
     */
    public function setViewerKeyWords($viewerKeyWords)
    {
        return $this->setParam('kws', $viewerKeyWords);
    }

    /**
     * @param integer $test 0|1
     *
     * @return OctobirdClient
     */
    public function setTest($test)
    {
        return $this->setParam('test', $test);
    }

    /**
     * @param string $responseFormat html|json|json-html|xml
     *
     * @return OctobirdClient
     */
    public function setResponseFormat($responseFormat)
    {
        return $this->setParam('format', $responseFormat);
    }

    /**
     * @param string  $name
     * @param integer $bannerNumber 1-5
     * @param string  $bannerType all|img|txt
     *
     * @return OctobirdClient
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function addBlock($name, $bannerNumber = null, $bannerType = null)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('The name of the block can not be empty');
        }

        if (count($this->blocks) > self::BLOCK_MAX_NUMBER) {
            throw new LogicException(sprintf('The maximum number of blocks is %d', self::BLOCK_MAX_NUMBER));
        }

        $block = array('n' => $name);

        if (!empty($bannerNumber)) {
            $this->checkBannerNumber($bannerNumber);
            $block['nb'] = $bannerNumber;
        }

        if (!empty($bannerType)) {
            $this->checkBannerType($bannerType);
            $block['tp'] = $bannerType;
        }

        $this->blocks[$name] = $block;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return OctobirdClient
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function getParam($name)
    {
        if (!isset($this->params[$name])) {
            throw new InvalidArgumentException(sprintf('%s not found', $name));
        }

        return $this->params[$name];
    }

    /**
     * @param string $defaultResponse
     *
     * @return OctobirdClient
     */
    public function setDefaultResponse($defaultResponse)
    {
        $this->defaultResponse = $defaultResponse;

        return $this;
    }

    /**
     * @param string $serverUrl
     *
     * @return OctobirdClient
     */
    public function setServerUrl($serverUrl)
    {
        $this->serverUrl = $serverUrl;

        return $this;
    }

    /**
     * @param integer $connectTimeout microseconds
     *
     * @return OctobirdClient
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;

        return $this;
    }

    /**
     * @param integer $timeout microseconds
     *
     * @return OctobirdClient
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Send request to OctobirdRotator
     *
     * @return OctobirdClient
     */
    public function send()
    {
        $url     = $this->createUrl();
        $headers = $this->createHeaders();

        if (function_exists('curl_init')) {
            $response = $this->curlRequest($url, $headers);
        } else {
            $response = $this->fsockopenRequest($url, $headers);
        }

        $this->isSent = true;
        $this->responseFormat = $this->getParam('format');
        $this->responseRaw = $response;
        $this->response = $this->parseResponse($response, $this->responseFormat);

        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        $this->checkSent();

        return $this->response ? $this->response : $this->defaultResponse;
    }

    /**
     * @param string $name
     * @param string $defaultBlock
     *
     * @return string|null
     *
     * @throws RuntimeException
     */
    public function getBlock($name = 'main', $defaultBlock = null)
    {
        $this->checkSent();

        if ($this->responseFormat !== self::RESPONSE_FORMAT_JSON_HTML) {
            throw new RuntimeException('This method implemented for "json-html" format');
        }

        if (empty($name)) {
            throw new RuntimeException('blockName should be defined');
        }

        return $this->getBlockFromResponse(
            $name,
            $this->response,
            $defaultBlock ? $defaultBlock : $this->getBlockFromResponse($name, $this->defaultResponse)
        );
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasBlock($name = 'main')
    {
        return $this->hasBlockFromResponse($name, $this->response);
    }

    /**
     * @return string
     */
    public function getResponseRaw()
    {
        $this->checkSent();

        return $this->responseRaw;
    }

    /**
     * @param string      $name
     * @param array       $response
     * @param string|null $defaultBlock
     *
     * @return string|null
     */
    private function getBlockFromResponse($name, $response, $defaultBlock = '')
    {
        if ($this->hasBlockFromResponse($response)) {
            return $response['blocks'][$name]['html'];
        } else {
            return $defaultBlock;
        }
    }

    /**
     * @param string $name
     * @param array  $response
     *
     * @return boolean
     */
    private function hasBlockFromResponse($name, $response)
    {
        return is_array($response)
            && !empty($response['blocks'][$name]['html'])
        ;
    }

    /**
     * @return string
     */
    private function createUrl()
    {
        $query = http_build_query($this->createQueryData(), null, '&');
        $url = $this->serverUrl . '?' . $query;

        return $url;
    }

    /**
     * @return array
     *
     * @throws InvalidArgumentException
     */
    private function createQueryData()
    {
        $params = $this->params;

        if (empty($params['sid'])) {
            throw new InvalidArgumentException('Site Id invalid');
        }

        if (!empty($params['tp'])) {
            $this->checkBannerType($params['tp']);
        }

        if (!empty($params['nb'])) {
            $this->checkBannerNumber($params['nb']);
        }

        if (!empty($this->blocks)) {
            $blocks = array();
            foreach ($this->blocks as $block) {
                $keyvalues = array();
                foreach ($block as $key => $value) {
                    $keyvalues[] = $key . ':' . $value;
                }
                $blocks[] = join(',', $keyvalues);
            }
            $params['blocks'] = join(';', $blocks);
        }

        $params = array_merge($params, $this->getSystemParams());

        return $params;
    }

    /**
     * @return array
     */
    private function createHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if ($this->isAvailableHeader($name)) {
                if (0 === strpos($name, 'HTTP_')) {
                    $name = substr($name, 5);
                }
                $headers[] = 'x-ob-' . strtolower(str_replace('_', '-', $name)) . ': ' . $value;
            }
        }

        return $headers;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isAvailableHeader($name)
    {
        return
            strpos($name, 'HTTP_X_') !== false
            || strpos($name, 'HTTP_ACCEPT_') !== false
            || in_array($name, array(
                'HTTP_USER_AGENT',
                'HTTP_HOST',
                'HTTP_REFERER',
            ))
        ;
    }

    private function getSystemParams()
    {
        $protocol = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol .= 's';
        }

        return array(
            'ip'   => $_SERVER['REMOTE_ADDR'],
            'curl' => $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        );
    }

    /**
     * @param string $bannerType
     *
     * @throws InvalidArgumentException
     */
    private function checkBannerType($bannerType)
    {
        if (!in_array($bannerType, self::$BANNER_TYPES)) {
            throw new InvalidArgumentException(sprintf('Banner type "%s" is not available', $bannerType));
        }
    }

    /**
     * @param integer $bannerNumber
     *
     * @throws InvalidArgumentException
     */
    private function checkBannerNumber($bannerNumber)
    {
        if (!is_numeric($bannerNumber)) {
            throw new InvalidArgumentException('Number of banners should be is numeric');
        }

        if ($bannerNumber < 1 || $bannerNumber > self::BANNER_MAX_NUMBER) {
            throw new InvalidArgumentException(sprintf('Number of banners should be in the range from 1 to %d', self::BANNER_MAX_NUMBER));
        }
    }

    /**
     * @throws RuntimeException
     */
    private function checkSent()
    {
        if (!$this->isSent) {
            throw new RuntimeException('Request not sent');
        }
    }

    /**
     * @return OctobirdClient
     *
     * @throws RuntimeException
     */
    private function setupCookieLabel()
    {
        if (empty($_COOKIE[self::COOKIE_LABEL_NAME])) {
            if (headers_sent()) {
                throw new RuntimeException('Initilize OctobirdClient before headers sent');
            }
            $did = sha1(uniqid() . microtime() . $_SERVER['REMOTE_ADDR']);
            setcookie(self::COOKIE_LABEL_NAME, $did, 0x7FFFFFFF);
        } else {
            $did = $_COOKIE[self::COOKIE_LABEL_NAME];
        }

        $this->setParam('did', $did);

        return $this;
    }

    /**
     * @param string $response
     * @param string $responseFormat
     *
     * @return mixed
     */
    private function parseResponse($response, $responseFormat)
    {
        if (empty($response)) {
            return null;
        }

        list($header, $content) = explode("\r\n\r\n", $response, 2);
        $content = trim($content);
        if (empty($content)) {
            return null;
        }

        $parsed = null;
        switch ($responseFormat) {
            case self::RESPONSE_FORMAT_HTML:
                if (strpos($content, self::HTML_SIGNATURE) !== false && $content !== self::HTML_SIGNATURE) {
                    $parsed = $content;
                }
                break;

            case self::RESPONSE_FORMAT_JSON:
            case self::RESPONSE_FORMAT_JSON_HTML:
                $parsed = @json_decode($content, true);
                break;

            case self::RESPONSE_FORMAT_XML:
                $parsed = @simplexml_load_string($content);
                break;
        }

        return $parsed;
    }

    /**
     * @param string $url
     * @param array  $headers
     *
     * @return string
     */
    private function curlRequest($url, $headers)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getClientUserAgent());

        $response = curl_exec($ch);

        return $response;
    }

    /**
     * @param string $url
     * @param array  $headers
     *
     * @return string
     */
    private function fsockopenRequest($url, $headers)
    {
        $headers = array_merge(
            array(
                sprintf('GET %s HTTP/1.0', parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY)),
                sprintf('Host: %s', parse_url($url, PHP_URL_HOST)),
                sprintf('User-Agent: %s', $this->getClientUserAgent()),
                'Connection: close',
            ),
            $headers
        );
        $request = join("\r\n", $headers) . "\r\n\r\n";

        $socket = @fsockopen(parse_url($url, PHP_URL_HOST), 80, $errno, $errstr, ceil($this->connectTimeout / 1000));
        if ($socket) {
            fwrite($socket, $request);

            stream_set_timeout($socket, ceil($this->timeout / 1000));
            $meta = stream_get_meta_data($socket);

            $response = '';
            while (!feof($socket) && !$meta['timed_out']) {
                $response .= fread($socket, 256);
                $meta = stream_get_meta_data($socket);
            }
            fclose($socket);

            if (!$meta['timed_out']) {
                return $response;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    private function getClientUserAgent()
    {
        return 'OctobirdClient/' . self::VERSION_API . ' (php; ' . self::VERSION . ')';
    }
}
