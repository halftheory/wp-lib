<?php
if ( ! function_exists('get_current_url') ) {
	function get_current_url( $strip_query = true ) {
		static $_url = null;
		if ( is_null($_url) ) {
			if ( function_exists('wp_doing_ajax') ) {
				if ( wp_doing_ajax() && isset($_SERVER['HTTP_REFERER']) && ! empty($_SERVER['HTTP_REFERER']) ) {
					$_url = stripslashes($_SERVER['HTTP_REFERER']);
				}
			}
			if ( empty($_url) ) {
				$_url = is_ssl() ? 'https://' : 'http://';
				$_url .= isset($_SERVER['HTTP_HOST']) ? stripslashes($_SERVER['HTTP_HOST']) : 'localhost';
				if ( isset($_SERVER['REQUEST_URI']) ) {
					$_url .= stripslashes($_SERVER['REQUEST_URI']);
				} elseif ( isset($_SERVER['PHP_SELF']) ) {
					$_url .= stripslashes($_SERVER['PHP_SELF']);
				}
			}
		}
		return $strip_query ? url_strip_query($_url) : $_url;
	}
}

if ( ! function_exists('get_url_path') ) {
	function get_url_path( $url = null, $trim = true ) {
		$url = empty($url) ? get_current_url() : url_strip_query($url);
		$result = parse_url($url, PHP_URL_PATH);
		return $trim ? trim($result, ' /') : $result;
	}
}

if ( ! function_exists('get_urls') ) {
	function get_urls( $string ) {
		if ( empty(trim($string)) ) {
			return false;
		}
		if ( ! preg_match('#(^|\s|>)https?://#i', $string) ) {
			return false;
		}
		$results = array();
		// Find URLs on their own line.
		if ( preg_match_all('|^\s*(https?://[^\s<>"\']+)\s*$|im', $string, $matches) ) {
			$results = array_merge($results, $matches[1]);
		}
		// Find URLs in their own paragraph.
		if ( preg_match_all('|(<p(?: [^>]*)?>\s*)(https?://[^\s<>"\']+)(\s*<\/p>)|i', $string, $matches) ) {
			$results = array_merge($results, $matches[2]);
		}
		return empty($results) ? false : $results;
	}
}

if ( ! function_exists('is_localhost') ) {
	function is_localhost() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( isset($_SERVER['HTTP_HOST']) ) {
				$host = stripslashes($_SERVER['HTTP_HOST']);
				if ( str_starts_with($host, 'localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.test') ) {
					$_result = true;
				}
			}
		}
		return $_result;
	}
}

if ( ! function_exists('is_ssl') ) {
	function is_ssl() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( isset($_SERVER['HTTPS']) ) {
				if ( 'on' === strtolower($_SERVER['HTTPS']) ) {
					$_result = true;
				} elseif ( '1' === (string) $_SERVER['HTTPS'] ) {
					$_result = true;
				}
			} elseif ( isset($_SERVER['SERVER_PORT']) && '443' === (string) $_SERVER['SERVER_PORT'] ) {
				$_result = true;
			}
		}
		return $_result;
	}
}

if ( ! function_exists('path_in_url') ) {
	function path_in_url( $path, $url = null ) {
		$path = trim($path, ' /');
		if ( empty($path) ) {
			return false;
		}
		$url_path = get_url_path($url);
		if ( empty($url_path) ) {
			return false;
		}
		// Try the whole path.
		if ( $url_path === $path ) {
			return true;
		}
		// Try the end.
		if ( str_ends_with($url_path, '/' . $path) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists('url_exists') ) {
	function url_exists( $url ) {
		if ( empty($url) ) {
			return false;
		}
		$headers = @get_headers($url);
		if ( $headers === false ) {
			return false;
		}
		if ( is_array($headers) ) {
			// array could be indexed or associative.
			reset($headers);
			if ( str_contains(current($headers), '404 Not Found') ) {
				return false;
			} elseif ( str_contains(current($headers), '301 Moved Permanently') ) {
				// maybe 404 is hiding in next header.
				foreach ( array_slice($headers, 0, 2) as $value ) {
					if ( str_starts_with($value, 'HTTP/') && str_contains($value, '404 Not Found') ) {
						return false;
					}
				}
			}
		}
		return true;
	}
}

if ( ! function_exists('url_strip_query') ) {
	function url_strip_query( $url ) {
		if ( empty($url) ) {
			return $url;
		}
		$search = array();
		if ( $tmp = parse_url($url, PHP_URL_QUERY) ) {
			$search[] = '?' . trim($tmp, ' ?');
		}
		if ( $tmp = parse_url($url, PHP_URL_FRAGMENT) ) {
			$search[] = '#' . trim($tmp, ' #');
		}
		return str_replace($search, '', $url);
	}
}
