<?php
if ( ! function_exists('content_is_ready_to_display') ) {
	function content_is_ready_to_display( $content = '', $filter = 'the_content' ) {
		if ( empty($content) ) {
			return false;
		}
		if ( is_feed() ) {
			return false;
		}
		if ( wp_doing_ajax() ) {
			return in_the_loop();
		}
		if ( did_action('get_header') === 0 || did_filter('body_class') === 0 || did_action('get_footer') > 0 || is_404() || is_signup_page() || is_login_page() ) {
			return false;
		}
		switch ( $filter ) {
			case 'get_the_excerpt':
			case 'the_excerpt':
				break;
			case 'the_content':
				if ( ! is_main_query() && ! in_the_loop() && ! is_singular() && ! is_tax() && ! is_tag() && ! is_category() && ! is_posts_page() && ! is_search() ) {
					return false;
				}
				break;
			default:
				break;
		}
		return true;
	}
}

if ( ! function_exists('current_page') ) {
	function current_page() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = 1;
			if ( is_paged() ) {
				global $paged, $page;
				$_result = $paged ? absint($paged) : absint($page);
			}
		}
		return $_result;
	}
}

if ( ! function_exists('has_search_results') ) {
	function has_search_results() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( is_search() ) {
				global $wp_query;
				if ( $wp_query->posts ) {
					$_result = true;
				}
			}
		}
		return $_result;
	}
}

if ( ! function_exists('ht_is_archive') ) {
	function ht_is_archive() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( is_archive() || is_posts_page() ) {
				$_result = true;
			} elseif ( is_singular() && get_taxonomy_from_page_path() ) {
				$_result = true;
			}
		}
		return $_result;
	}
}

if ( ! function_exists('ht_is_front_page') ) {
	function ht_is_front_page( $post_id = null ) {
		if ( is_numeric($post_id) ) {
			if ( $tmp = get_post_front_page() ) {
				return (int) $post_id === (int) $tmp->ID;
			}
			return false;
		}
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = is_front_page() && ! is_login_page() && ! is_signup_page();
		}
		return $_result;
	}
}

if ( ! function_exists('is_posts_page') ) {
	function is_posts_page( $post_id = null ) {
		if ( is_numeric($post_id) ) {
			if ( $tmp = get_post_posts_page() ) {
				return (int) $post_id === (int) $tmp->ID;
			}
			return false;
		}
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( ! is_archive() && ! is_author() && ! is_category() && ! is_date() && ! is_post_type_archive() && ! is_tag() && ! is_tax() ) {
				global $wp_query;
				if ( isset($wp_query->is_posts_page) && $wp_query->is_posts_page ) {
					$_result = true;
				} elseif ( isset($wp_query->is_home) && $wp_query->is_home && get_option('show_on_front') === 'posts' ) {
					$_result = true;
				}
			}
		}
		return $_result;
	}
}
