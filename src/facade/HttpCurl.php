<?php
/**
 * Created by AnQin on 2017-04-04.
 * Email:an-qin@qq.com
 */

namespace an\facade;


use think\Facade;

/**
 * @see \an\HttpCurl
 * @method \an\HttpCurl setUserAgent(string $user_agent) static
 * @see \an\HttpCurl::setUserAgent()
 * @method \an\HttpCurl setTimeout(int $seconds) static
 * @see \an\HttpCurl::setTimeout()
 * @method \an\HttpCurl post(?string $url=null,array $data=null) static
 * @see \an\HttpCurl::post()
 * @method \an\HttpCurl setHeader(string $key,string $value) static
 * @see \an\HttpCurl::setHeader()
 * @method \an\HttpCurl get(?string $url=null,array $data=null) static
 * @see \an\HttpCurl::get()
 * @method \an\HttpCurl clear() static
 * @see \an\HttpCurl::clear()
 * @method void close() static
 * @see \an\HttpCurl::close()
 * @method ?string AssembleRemote(?string $url=null,?string $method=null,array $data=null,bool $debug=null,bool $showHeader=null) static
 * @see \an\HttpCurl::AssembleRemote()
 * @method \an\HttpCurl exec() static
 * @see \an\HttpCurl::exec()
 * @method void writerFile(string $path) static
 * @see \an\HttpCurl::writerFile()
 * @method array toArray(int $options=null) static
 * @see \an\HttpCurl::toArray()
 * @method ?string toString(?string $charset=null) static
 * @see \an\HttpCurl::toString()
 * @method ?string getUrl() static
 * @see \an\HttpCurl::getUrl()
 * @method \an\HttpCurl setUrl(string $url,array $data=null) static
 * @see \an\HttpCurl::setUrl()
 * @method array getData() static
 * @see \an\HttpCurl::getData()
 * @method ?array getError() static
 * @see \an\HttpCurl::getError()
 * @method string getResponseHeaders() static
 * @see \an\HttpCurl::getResponseHeaders()
 * @method \an\HttpCurl setCookieString(string $string) static
 * @see \an\HttpCurl::setCookieString()
 * @method \an\HttpCurl setCookieFile(string $cookie_file) static
 * @see \an\HttpCurl::setCookieFile()
 * @method \an\HttpCurl setCookieJar(string $cookie_jar) static
 * @see \an\HttpCurl::setCookieJar()
 * @method \an\HttpCurl setHeaders(array $headers=null) static
 * @see \an\HttpCurl::setHeaders()
 * @method \an\HttpCurl setReferrer(string $referrer) static
 * @see \an\HttpCurl::setReferrer()
 * @method \an\HttpCurl setProxy(array $proxy) static
 * @see \an\HttpCurl::setProxy()
**/
class HttpCurl extends Facade {
    protected static function getFacadeClass() {
        return \an\HttpCurl::class;
    }
}