<?php
if ( ! function_exists('get_visitor_ip') ) {
	function get_visitor_ip() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( ! is_localhost() ) {
				if ( getenv('HTTP_CLIENT_IP') && stripos(getenv('HTTP_CLIENT_IP'), 'unknown') === false ) {
					$_result = getenv('HTTP_CLIENT_IP');
				} elseif ( getenv('HTTP_X_FORWARDED_FOR') && stripos(getenv('HTTP_X_FORWARDED_FOR'), 'unknown') === false ) {
					$_result = getenv('HTTP_X_FORWARDED_FOR');
				} elseif ( getenv('REMOTE_ADDR') && stripos(getenv('REMOTE_ADDR'), 'unknown') === false ) {
					$_result = getenv('REMOTE_ADDR');
				} elseif ( isset($_SERVER['REMOTE_ADDR']) ) {
					if ( stripos(stripslashes($_SERVER['REMOTE_ADDR'], 'unknown')) === false ) {
						$_result = stripslashes($_SERVER['REMOTE_ADDR']);
					}
				}
				$_result = filter_var($_result, FILTER_VALIDATE_IP) ? $_result : false;
			}
		}
		return $_result;
	}
}
