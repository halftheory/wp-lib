<?php
if ( ! function_exists('ht_get_search_link') ) {
	function ht_get_search_link( $query = '', $strip_query = true ) {
		$link = get_search_link($query);
		if ( empty($query) && $strip_query ) {
			$search = urlencode(get_search_query(false));
			$link = preg_replace('/(\/|\?s=)' . preg_quote($search, '/') . '\/?$/s', '$1', $link, 1);
		}
		return $link;
	}
}

if ( ! function_exists('ht_set_url_scheme') ) {
	function ht_set_url_scheme( $url, $scheme = null ) {
		// Find scheme.
		$orig_scheme = $scheme;
		if ( ! $scheme ) {
			$scheme = is_ssl() ? 'https' : 'http';
		} elseif ( $scheme === 'admin' || $scheme === 'login' || $scheme === 'login_post' || $scheme === 'rpc' ) {
			$scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
		} elseif ( $scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative' ) {
			$scheme = is_ssl() ? 'https' : 'http';
		}
		if ( is_array($url) ) {
			return array_map(__FUNCTION__, $url, array_fill(0, count($url), $scheme));
		}
		if ( ! str_contains($url, 'http') ) {
			return $url;
		}
		if ( $scheme === 'relative' ) {
			$url = preg_replace('#\w+://[^/]*#', '', $url);
		} else {
			$url = preg_replace('#\w+://(\w+)#', $scheme . '://$1', $url);
		}
		return apply_filters('set_url_scheme', $url, $scheme, $orig_scheme);
	}
}

if ( ! function_exists('pagination_args') ) {
	function pagination_args( $args = array() ) {
		$defaults = array(
			'prev_text' => __('Previous'),
			'next_text' => __('Next'),
			'before_page_number' => '<span class="screen-reader-text">' . __('Page') . '</span>',
			'mid_size' => 2,
		);
		return wp_parse_args($args, $defaults);
	}
}

if ( ! function_exists('wp_get_url_path') ) {
	function wp_get_url_path( $url = null, $trim = true ) {
		$url = empty($url) ? get_current_url() : url_strip_query($url);
		$search = array(
			untrailingslashit(set_url_scheme(home_url(), 'https')),
			untrailingslashit(set_url_scheme(home_url(), 'http')),
		);
		$result = str_replace($search, '', $url);
		if ( $url === $result ) {
			return false;
		}
		return $trim ? trim($result, ' /') : $result;
	}
}
