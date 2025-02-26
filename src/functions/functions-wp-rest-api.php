<?php
if ( ! function_exists('has_rest_namespace') ) {
	function has_rest_namespace( $namespace ) {
		if ( ! function_exists('rest_get_server') ) {
			return false;
		}
		// Wordpress bug.
		if ( ! class_exists('WP_Site_Health') ) {
			require_once path_join(ABSPATH, 'wp-admin/includes/class-wp-site-health.php');
		}
		$array = rest_get_server()->get_namespaces();
		if ( is_array($array) ) {
			if ( in_array($namespace, $array, true) ) {
				return $namespace;
			} elseif ( ! str_contains($namespace, '/') ) {
				// Try to find only first part of the name.
				foreach ( $array as $value ) {
					if ( str_starts_with($value, $namespace . '/') ) {
						return $value;
					}
				}
			}
		}
		return false;
	}
}

if ( ! function_exists('is_rest') ) {
	function is_rest() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( defined('REST_REQUEST') && REST_REQUEST ) {
				$_result = true;
			} elseif ( function_exists('wp_is_json_request') && wp_is_json_request() ) {
				$_result = true;
			} else {
				$path_current = untrailingslashit(add_query_arg(array()));
				if ( function_exists('rest_url') ) {
					$path_rest = untrailingslashit(wp_parse_url(rest_url(), PHP_URL_PATH));
					if ( str_starts_with($path_current, $path_rest) ) {
						$_result = true;
					}
				}
				if ( function_exists('get_graphql_setting') ) {
					if ( str_ends_with($path_current, get_graphql_setting('graphql_endpoint', 'graphql')) ) {
						$_result = true;
					}
				}
			}
		}
		return $_result;
	}
}
