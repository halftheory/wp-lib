<?php
if ( ! function_exists('get_active_plugins') ) {
	function get_active_plugins( $blog_ids = null ) {
		// array of keys (file path) => value (plugin path).
		$results = array();
		$callback = function ( $v ) {
			return str_replace_start(trailingslashit(WP_PLUGIN_DIR), '', $v);
		};
		if ( is_multisite() && ! empty($blog_ids) ) {
			// multisite.
			$blog_ids = array_filter(array_map('absint', make_array($blog_ids)));
			foreach ( $blog_ids as $value ) {
				$results = array_merge($results, get_blog_option($value, 'active_plugins', array()));
			}
			$sitewide = get_site_option('active_sitewide_plugins', array());
			$results = array_merge($results, array_keys($sitewide));
			$results = array_unique($results);
			$results = array_combine($results, array_map($callback, $results));
			asort($results);
		} else {
			// Single.
			static $_results = null;
			if ( is_null($_results) ) {
				$_results = wp_get_active_and_valid_plugins();
				if ( is_multisite() ) {
					$_results = array_merge($_results, wp_get_active_network_plugins());
				}
				$_results = array_combine($_results, array_map($callback, $_results));
				asort($_results);
			}
			$results = $_results;
		}
		return $results;
	}
}

if ( ! function_exists('is_development') ) {
	function is_development() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( getenv('WP_ENV') && getenv('WP_ENV') === 'development' ) {
				$_result = true;
			} elseif ( in_array(wp_get_environment_type(), array( 'local', 'development' )) ) {
				$_result = true;
			} elseif ( is_localhost() ) {
				$_result = true;
			}
		}
		return $_result;
	}
}

if ( ! function_exists('is_public') ) {
	function is_public() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = true;
			if ( is_admin() && ! wp_doing_ajax() ) {
				$_result = false;
			}
			if ( wp_doing_ajax() ) {
				if ( str_contains(get_current_url(), admin_url()) ) {
					$_result = false;
				}
			}
		}
		return $_result;
	}
}
