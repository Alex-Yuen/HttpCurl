<?php
/**
 * Created by AnQin on 2016-11-05.
 * Email:an-qin@qq.com
 */

namespace an\helper;


class Cookie
{
	private $cookie;
	private $type = 1;
	public $HTTP_STYLE = 1;
	public $NETSCAPE = 2;
	public $ARRAY = 3;

	public function SetCookie($cookie, int $type = 1) {
		if (is_array($cookie)) {
			$type = 3;
		}
		if (!in_array($type, [1, 2, 3])) {
			$type = 1;
		}
		$this->type   = $type;
		$this->cookie = $cookie;

		return $this;
	}

	public function ToHttpStyle(string $domain = ''): string {
		if (empty($this->cookie)) {
			return '';
		}

		switch ($this->type) {
			case 2:
				$result = self::ArrayToHttpStyle(self::NetscapeToArray((string)$this->cookie, $domain));
				break;

			case 3:
				$result = self::ArrayToHttpStyle((array)$this->cookie);
				break;

			default:
				$result = (string)$this->cookie;
				break;
		}

		return $result;
	}

	/**
	 * @param string $domain 如果源数据没有domain属性 请设置
	 * @return string
	 */
	public function ToNetscape(string $domain = ''): string {
		if (empty($this->cookie)) {
			return '';
		}

		switch ($this->type) {
			case 1:
				if (!$domain) return '';
				$result = self::ArrayToNetscape(self::HttpStyleToArray((string)$this->cookie, $domain));
				break;

			case 3:
				$result = self::ArrayToNetscape((array)$this->cookie, $domain);
				break;

			default:
				$result = (string)$this->cookie;
				break;
		}

		return $result;
	}

	public function ToArray(string $domain = ''): array {
		if (empty($this->cookie))
			return [];

		switch ($this->type) {
			case 1:
				$result = self::HttpStyleToArray((string)$this->cookie, $domain);
				break;

			case 2:
				$result = self::NetscapeToArray((string)$this->cookie, $domain);
				break;

			default:
				$result = (array)$this->cookie;
				break;
		}

		return $result;
	}

	/**
	 * 获取指定cookie数据
	 * @param string $name
	 * @param string $domain
	 * @return string
	 */
	public function get(string $name, string $domain = ''): string {
		if (empty($this->cookie)) {
			return '';
		}

		switch ($this->type) {
			case 2:
				$_cookie = self::ArrayToHttpStyle(self::NetscapeToArray((string)$this->cookie, $domain));
				break;

			case 3:
				$_cookie = self::ArrayToHttpStyle((array)$this->cookie, $domain);
				break;

			default:
				$_cookie = (string)$this->cookie;
				break;
		}

		return $this->FindCookieForName($_cookie, $name);
	}

	/**
	 * Netscape转Array
	 * @param string $cookie
	 * @param string $domain 取指定域名
	 * @return array
	 */
	public static function NetscapeToArray(string $cookie, string $domain = ''): array {
		$cookies = [];

		$lines = explode("\n", $cookie);

		foreach ($lines as $line) {
			// we only care for valid cookie def lines
			if (isset($line[0]) && substr_count($line, "\t") == 6) {
				if ($domain && strpos($line, $domain) === false) {
					continue;
				}
				// get tokens in an array
				$tokens = explode("\t", $line);
				// trim the tokens
				$tokens = array_map('trim', $tokens);
				$cookie = [];
				// Extract the data
				$cookie['domain'] = $tokens[0];
				$cookie['flag']   = $tokens[1] == 'TRUE' ? true : false;
				$cookie['path']   = $tokens[2];
				$cookie['secure'] = $tokens[3] == 'TRUE' ? true : false;
				$cookie['expiry'] = (int)$tokens[4];
				$cookie['name']   = $tokens[5];
				$cookie['value']  = $tokens[6];
				// Record the cookie.
				$cookies[] = $cookie;
			}
		}

		return $cookies;
	}

	/**
	 * HttpStyleToArray
	 * @param string $cookies
	 * @param string $domain 设置指定域名
	 * @return array
	 */
	public static function HttpStyleToArray(string $cookies, string $domain): array {
		$lines   = explode(';', $cookies);
		$cookies = [];

		foreach ($lines as $line) {
			// we only care for valid cookie def lines
			if (isset($line[0]) && !empty($line[0])) {
				// get tokens in an array
				$tokens = explode('=', $line);
				// trim the tokens
				$tokens = array_map('trim', $tokens);
				$cookie = [];
				// Extract the data
				$cookie['domain'] = $domain;
				$cookie['flag']   = true;
				$cookie['path']   = '/';
				$cookie['secure'] = false;
				$cookie['expiry'] = strtotime('+1 year');
				$cookie['name']   = $tokens[0];
				$cookie['value']  = $tokens[1];
				// Record the cookie.
				$cookies[] = $cookie;
			}
		}

		return $cookies;
	}

	/**
	 * ArrayToHttpStyle
	 * @param array $cookies
	 * @param string $domain 取指定域名
	 * @return string
	 */
	public static function ArrayToHttpStyle(array $cookies, string $domain = ''): string {
		$cookie = '';
		foreach (( array )$cookies as $value) {
			if (!empty($domain) && strpos($value['domain'], $domain) === false) {
				continue;
			}
			$cookie .= $value ['name'] . '=' . $value ['value'] . ';';
		}

		return $cookie;
	}

	/**
	 * ArrayToNetscape
	 * @param array $cookies
	 * @param string $domain 取指定域名
	 * @return string
	 */
	public static function ArrayToNetscape(array $cookies, string $domain = ''): string {
		$cookie = [];
		$EOL    = '	';
		$time   = strtotime('+1 year');

		foreach (( array )$cookies as $value) {
			if ($domain && strpos($value['domain'], $domain) === false) {
				continue;
			}
			$_tmp     = [
				$value['domain'],
				'TRUE',
				$value['path'],
				'FALSE',
				$time,
				$value['name'],
				$value['value'],
			];
			$cookie[] = implode($EOL, $_tmp);
		}

		return implode(PHP_EOL, $cookie);
	}

	/**
	 * FindCookieForName 取指定cookie
	 * @param string $cookie
	 * @param string $name
	 * @return string
	 */
	private function FindCookieForName(string $cookie, string $name): string {
		$preg = '/(?:^|;)\\s*' . $name . '=([^;]+)/';
//		$preg = '/\s*' . $name . '\s([^\n]+)/';
		preg_match($preg, $cookie, $match);

		return $match ? trim($match[1]) : '';
	}
}