<?php
/**
 * Created by AnQin on 2017-04-04.
 * Email:an-qin@qq.com
 */

namespace an;


use an\httpCurl\CaseInsensitiveArray;
use ErrorException;

class HttpCurl {
    /* 程序固定变量 */
    private $version = '1.0.0';

    private $curl;
    private $id;
    private $timeOut = 10;

    /* 错误信息 */
    private $error = false;
    private $errorCode = 0;
    private $errorMessage = null;

    /* 设置信息 */
    private $url = null;
    private $method = 'GET';
    private $data = [];
    private $beforeSendFunction = null;
    private $successFunction = null;
    private $errorFunction = null;
    private $completeFunction = null;

    private $headers = [];
    private $options = [];

    /* 返回信息 */
    private $httpStatusCode = null;
    private $response = null;
    private $rawResponseHeaders = '';
    private $responseCookies = [];

    /**
     * HttpCurl constructor.
     * @throws ErrorException
     */
    public function __construct() {
        if (!extension_loaded('curl')) throw new ErrorException('cURL library is not loaded');

        $this->setDefault();
    }

    /* 函数主体 */

    private function setDefault(): void {
        $this->error = false;
        $this->errorCode = 0;
        $this->errorMessage = null;
        $this->url = null;
        $this->method = 'GET';
        $this->data = [];
        $this->beforeSendFunction = null;
        $this->successFunction = null;
        $this->errorFunction = null;
        $this->completeFunction = null;

        $this->curl = curl_init();
        $this->id = uniqid(microtime(true), true);
        $this->headers = new CaseInsensitiveArray();
        $this->options = [];
        $user_agent = 'PHP-Http-Request/' . $this->version;
        $user_agent .= ' PHP/' . PHP_VERSION;
        $curl_version = curl_version();
        $user_agent .= ' curl/' . $curl_version['version'];
        $this->setUserAgent($user_agent);
        $this->setDefaultTimeout();
        $this->setOpt(CURLOPT_FAILONERROR, false);
        $this->setOpt(CURLINFO_HEADER_OUT, true);
        $this->setOpt(CURLOPT_ENCODING, true);
        $this->setOpt(CURLOPT_HEADERFUNCTION, [$this, 'headerCallback']);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->httpStatusCode = null;
        $this->response = null;
        $this->rawResponseHeaders = '';
        $this->responseCookies = [];
    }

    public function setUserAgent(string $user_agent): HttpCurl {
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);

        return $this;
    }

    public function setOpt($option, $value): HttpCurl {
        $this->options[$option] = $value;

        return $this;
    }

    private function setDefaultTimeout(): void {
        $this->setTimeout($this->timeOut);
    }

    public function setTimeout(int $seconds): HttpCurl {
        $this->setOpt(CURLOPT_TIMEOUT, $seconds);
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, $seconds);

        return $this;
    }

    public function post(string $url = null, array $data = []): HttpCurl {
        if (empty($data) && !empty($this->data)) $data = $this->data; else $this->data = $data;
        if (!empty($data)) {

            if (isset($data['custom']) && true === $data['custom']) $this->setOpt(CURLOPT_POSTFIELDS, $data['body']); else {
                if (!isset($this->headers['Content-Type'])) $this->setHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                $this->setOpt(CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } else return $this->get($url, $data);
        if (empty($url)) {
            if (empty($this->url)) {
                $this->setError(-1, 'empty url');

                return $this;
            }
        } else $this->setUrl($url);
        $this->method = 'POST';
        $this->setOpt(CURLOPT_POST, true);

        return $this;
    }

    public function setHeader(string $key, string $value): HttpCurl {
        $this->headers[$key] = $value;

        return $this;
    }

    public function get(string $url = null, array $data = []): HttpCurl {
        if (empty($url)) {
            if (empty($this->url)) {
                $this->setError(-1, 'empty url');

                return $this;
            }
        } else $this->setUrl($url, $data);
        //		$this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->setOpt(CURLOPT_HTTPGET, true);

        return $this;
    }

    /* 获取配置信息 */

    private function setError($code, string $msg): void {
        $this->error = true;
        $this->errorCode = $code;
        $this->errorMessage = $msg;
    }

    public function clear(): HttpCurl {
        $this->close();
        $this->setDefault();
        return $this;
    }

    /* 获取资源信息 */

    public function close(): void {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
            $this->options = null;
            $this->error = null;
            $this->errorCode = null;
            $this->errorMessage = null;

            $this->url = null;
            $this->method = null;
            $this->data = null;

            $this->beforeSendFunction = null;
            $this->successFunction = null;
            $this->errorFunction = null;
            $this->completeFunction = null;

            $this->headers = null;
            $this->options = null;

            $this->httpStatusCode = null;
            $this->response = null;
            $this->rawResponseHeaders = null;
            $this->responseCookies = null;
        }
    }

    public function AssembleRemote(string $url = null, string $method = null, array $data = [], bool $debug = false, bool $showHeader = false): ?string {
        if (is_null($url)) {
            if (is_null($this->url)) {
                $this->setError(-1, 'empty url');

                return null;
            }
            $url = $this->url;
        } else $this->url = $url;
        if (empty($method)) $method = $this->method;
        $post = [
            'url'        => $url,
            'method'     => strtolower(trim($method)),
            'showHeader' => $showHeader,
            'header'     => $this->getHeaders(),
            'data'       => empty($data) ? (empty($this->data) ? [] : $this->data) : $data,
            'debug'      => $debug,
        ];

        return json_encode($post);
    }

    private function getHeaders(): array {
        $headers = [];
        foreach ($this->headers as $key => $value) $headers[] = $key . ': ' . $value;

        return $headers;
    }

    public function exec(): HttpCurl {
        if ($this->url) {
            $headers = $this->getHeaders();
            $this->setOpt(CURLOPT_HTTPHEADER, $headers);
            unset($headers);
            curl_setopt_array($this->curl, $this->options);
            $this->responseCookies = [];
            $this->rawResponseHeaders = '';
            $this->call($this->beforeSendFunction);
            $response = curl_exec($this->curl);
            $error = curl_errno($this->curl);
            if ($error) {
                $errorMsg = curl_error($this->curl);
                $this->setError($error, $errorMsg);
            } else {
                $this->httpStatusCode = $this->getInfo(CURLINFO_HTTP_CODE);
                if ($this->httpStatusCode >= 400) {
                    $this->setError($this->httpStatusCode, 'http error');
                }
            }

            $this->response = $response;
            unset($response);
        }
        if ($this->error) $this->call($this->errorFunction); else $this->call($this->successFunction);
        $this->call($this->completeFunction);

        return $this;
    }

    /* 设置信息 */

    private function call(): void {
        $args = func_get_args();
        $function = array_shift($args);
        if (!is_null($function) && is_callable($function)) {
            array_unshift($args, $this);
            call_user_func_array($function, $args);
        }
    }

    public function getInfo($opt = null) {
        return null === $opt ? curl_getinfo($this->curl) : curl_getinfo($this->curl, $opt);
    }

    public function writerFile(String $path): void {
        file_put_contents($path, $this->response);
    }

    public function toArray(int $options = 0): array {
        $response = $this->toString();
        if (empty($response)) {
            $this->setError(-1, 'empty response');

            return [];
        };
        $array = json_decode($response, true, 512, $options);
        if (!is_array($array)) {
            $this->error = json_last_error_msg();

            $this->setError(json_last_error(), json_last_error_msg());

            return [];
        }

        return $array;
    }

    public function toString(string $charset = null): ?string {
        $response = trim($this->response);
        if (!is_null($charset)) return mb_convert_encoding($response, $charset, ['gbk', 'utf-8']);

        return $response;
    }

    public function getUrl(): ?string {
        return $this->url;
    }

    public function setUrl(string $url, array $data = []): HttpCurl {
        $this->url = $this->buildURL($url, $data);
        $this->setOpt(CURLOPT_URL, $this->url);
        if (strpos($url, 'https://') === 0) {
            $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);
            $this->setOpt(CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT | CURL_SSLVERSION_TLSv1);
        }

        return $this;
    }

    public function getData(): array {
        return $this->data;
    }

    public function getError(): ?array {
        if ($this->error) return ['code' => $this->errorCode, 'msg' => $this->errorMessage];

        return null;
    }

    public function getResponseCookie(string $key = null) {
        if (empty($key)) return $this->responseCookies;

        return isset($this->responseCookies[$key]) ? $this->responseCookies[$key] : null;
    }

    public function getResponseHeaders(): string {
        return $this->rawResponseHeaders;
    }

    /* 回调函数 */

    public function setCookieString(string $string): HttpCurl {
        $this->setOpt(CURLOPT_COOKIE, $string);

        return $this;
    }

    public function setCookieFile(string $cookie_file): HttpCurl {
        $this->setOpt(CURLOPT_COOKIEFILE, $cookie_file);

        return $this;
    }

    public function setCookieJar(string $cookie_jar): HttpCurl {
        $this->setOpt(CURLOPT_COOKIEJAR, $cookie_jar);

        return $this;
    }

    public function setHeaders(array $headers = []): HttpCurl {
        foreach ($headers as $key => $value) $this->headers[$key] = $value;

        return $this;
    }

    /* 内部函数 */

    public function setReferrer(string $referrer): HttpCurl {
        $this->setOpt(CURLOPT_REFERER, $referrer);

        return $this;
    }

    public function setProxy(array $proxy): HttpCurl {
        if (!isset($proxy['type'])) $proxy['type'] = CURLPROXY_SOCKS5; else {
            if (is_string($proxy['type'])) {
                switch (strtolower($proxy['type'])) {
                    case 'http':
                        $proxy['type'] = CURLPROXY_HTTP;
                        break;

                    default:
                        $proxy['type'] = CURLPROXY_SOCKS5;
                }
            } else
                switch ($proxy['type']) {
                    case CURLPROXY_SOCKS5:
                    case CURLPROXY_SOCKS5_HOSTNAME:
                    case CURLPROXY_HTTP:
                    case CURLPROXY_HTTP_1_0:
                    case CURLPROXY_SOCKS4:
                        break;

                    default:
                        $proxy['type'] = CURLPROXY_SOCKS5;
                        break;
                }
        }

        if (isset($proxy['host']) && isset($proxy['port'])) {
            $this->setOpt(CURLOPT_HTTPPROXYTUNNEL, true);
            $this->setOpt(CURLOPT_PROXYTYPE, $proxy['type']);
            $this->setOpt(CURLOPT_PROXY, $proxy['host']); //代理服务器地址
            $this->setOpt(CURLOPT_PROXYPORT, $proxy['port']); //代理服务器端口
            $this->setOpt(CURLOPT_PROXYUSERPWD, isset($proxy['account']) ? $proxy['account'] : ''); //代理认证帐号，username:password的格式
        }

        return $this;
    }

    public function beforeSend($callback): HttpCurl {
        $this->beforeSendFunction = $callback;

        return $this;
    }

    public function successFunc($callback): HttpCurl {
        $this->successFunction = $callback;

        return $this;
    }

    public function errorFunc($callback): HttpCurl {
        $this->errorFunction = $callback;

        return $this;
    }

    public function completeFunc($callback): HttpCurl {
        $this->completeFunction = $callback;

        return $this;
    }

    public function headerCallback($ch, $header): int {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $header, $cookie) === 1) $this->responseCookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
        $this->rawResponseHeaders .= $header;

        return strlen($header);
    }

    /* 绑定函数 */

    public function __destruct() {
        $this->close();
    }

    private function buildURL(string $url, array $data = []): string {
        return trim($url) . (empty($data) ? '' : '?' . http_build_query($data));
    }

}