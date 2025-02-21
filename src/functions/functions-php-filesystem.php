<?php
if ( ! function_exists('filesystem_is_case_insensitive') ) {
	function filesystem_is_case_insensitive() {
		// Hack for case-insensitive file systems. e.g. Functions like 'basename' and 'dirname' will break on the first capitalized letter.
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = file_exists(strtolower(__DIR__)) && is_dir(strtolower(__DIR__)); // __DIR__ is ok.
		}
		return $_result;
	}
}

if ( ! function_exists('ht_basename') ) {
	function ht_basename( $path, $suffix = '' ) {
		if ( is_array($path) ) {
			return array_map(__FUNCTION__, $path, array_fill(0, count($path), $suffix));
		}
		if ( ! filesystem_is_case_insensitive() ) {
			return basename($path, $suffix);
		}
		$path = safe_path($path);
		$array = explode(DIRECTORY_SEPARATOR, $path);
		$result = end($array);
		if ( $suffix && str_ends_with($result, $suffix) ) {
			$result = preg_replace('/' . preg_quote($suffix, '/') . '$/s', '', $result, 1);
		}
	    return $result;
	}
}

if ( ! function_exists('ht_dirname') ) {
	function ht_dirname( $path, $levels = 1 ) {
		if ( is_array($path) ) {
			return array_map(__FUNCTION__, $path, array_fill(0, count($path), $levels));
		}
		if ( ! filesystem_is_case_insensitive() ) {
			return dirname($path, $levels);
		}
		if ( ! str_contains($path, DIRECTORY_SEPARATOR) ) {
			return '.';
		}
		$array = explode(DIRECTORY_SEPARATOR, $path);
		if ( str_contains(end($array), '.') ) {
			array_pop($array);
		}
		return implode(DIRECTORY_SEPARATOR, $array);
	}
}

if ( ! function_exists('ht_file_get_contents') ) {
	function ht_file_get_contents( $filename ) {
		if ( empty($filename) ) {
			return false;
		}
		$func = function ( $filename ) {
			$is_url = str_starts_with($filename, 'http');
			if ( $is_url ) {
				if ( ! url_exists($filename) ) {
					return false;
				}
			}
			// Use user_agent when available.
			$user_agent = ( isset($_SERVER['HTTP_USER_AGENT']) && ! empty($_SERVER['HTTP_USER_AGENT']) ) ? stripslashes($_SERVER['HTTP_USER_AGENT']) : 'PHP' . phpversion() . '/' . __FUNCTION__;
			// 1. Try php.
			$options = array(
				'http' => array(
					'user_agent' => $user_agent,
				),
			);
			// Try the 'correct' way.
			if ( $result = @file_get_contents($filename, false, stream_context_create($options)) ) {
				return $result;
			}
			// Try the 'insecure' way.
			$options['ssl'] = array(
				'verify_peer' => false,
				'verify_peer_name' => false,
			);
			if ( $result = @file_get_contents($filename, false, stream_context_create($options)) ) {
				return $result;
			}
			// 2. Try curl.
			if ( $is_url && function_exists('curl_init') ) {
				$c = @curl_init();
				// Try the 'correct' way.
				curl_setopt($c, CURLOPT_URL, $filename);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($c, CURLOPT_MAXREDIRS, 10);
				$result = curl_exec($c);
				if ( ! empty($result) ) {
					curl_close($c);
					return $result;
				}
				// Try the 'insecure' way.
				curl_setopt($c, CURLOPT_URL, $filename);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($c, CURLOPT_USERAGENT, $user_agent);
				$result = curl_exec($c);
				if ( ! empty($result) ) {
					curl_close($c);
					return $result;
				}
				curl_close($c);
			}
			return false;
		};
		$result = $func($filename);
		$result = maybe_specialchars_decode($result);
		$result = remove_excess_space($result);
		return empty($result) ? false : $result;
	}
}

if ( ! function_exists('safe_path') ) {
	function safe_path( $path ) {
		if ( is_array($path) ) {
			return array_map(__FUNCTION__, $path);
		}
		// Make i18n friendly.
		$path = urldecode(str_replace(array( '%2F', '%5C' ), DIRECTORY_SEPARATOR, urlencode($path)));
		if ( ! filesystem_is_case_insensitive() ) {
			return $path;
		}
		if ( ! str_contains($path, DIRECTORY_SEPARATOR) ) {
			return $path;
		}
		// Maybe convert directory to lowercase.
		$dir = ht_dirname($path);
		$dir_new = strtolower($dir);
		if ( $dir !== $dir_new ) {
			if ( file_exists($dir_new) ) {
				$path = preg_replace('/^' . preg_quote($dir, '/') . '/s', $dir_new, $path, 1);
			}
		}
		return $path;
	}
}
