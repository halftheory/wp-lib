<?php
if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'functions-wp.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'functions-wp.php';
}

if ( ! function_exists('get_schema_meta') ) {
	function get_schema_meta( $itemtype = 'article', $post = null ) {
		$post = get_post($post);
		if ( ! $post ) {
			return false;
		}
		$result = '';
		switch ( strtolower($itemtype) ) {
			case 'article':
				// Only if there is content.
				if ( empty_zero_ok(trim(ht_strip_shortcodes($post->post_content))) ) {
					break;
				}
				$result .= '<meta itemprop="headline" content="' . esc_attr(substr(get_the_title($post), 0, 110)) . '" />' . "\n";
				$author = null;
				if ( $tmp = get_userdata($post->user_id) ) {
					$author = apply_filters('the_author', is_object($tmp) ? $tmp->display_name : '');
				}
				if ( $author ) {
					$result .= '<meta itemprop="author" content="' . esc_attr($author) . '" />' . "\n";
				}
				$args = array(
					'search' => array(
						'attached_media' => true,
						'gallery' => true,
						'content' => true,
						'parent' => true,
						'logo' => false,
					),
					'min_width' => get_option('thumbnail_size_w', 0),
					'min_height' => get_option('thumbnail_size_h', 0),
				);
				if ( $image_url = get_post_thumbnail_context('url', $post, 'medium', array(), $args) ) {
					$result .= '<meta itemprop="image" content="' . esc_url($image_url) . '" />' . "\n";
				}
				$result .= '<span itemprop="publisher" itemscope itemtype="' . set_url_scheme('http://schema.org/Organization') . '" class="none">' . "\n";
				$result .= '<meta itemprop="name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
				$args = array(
					'search' => array(
						'logo' => true,
						'attached_media' => true,
						'gallery' => true,
						'content' => false,
						'parent' => false,
					),
					'min_width' => get_option('thumbnail_size_w', 0),
					'min_height' => get_option('thumbnail_size_h', 0),
				);
				if ( $logo_url = get_post_thumbnail_context('url', $post, 'medium', array(), $args) ) {
					$result .= '<meta itemprop="logo" content="' . esc_url($logo_url) . '" />' . "\n";
				}
				$result .= '</span>' . "\n";
				$result .= get_schema_meta('dates', $post);
				break;

			case 'dates':
				$time = current_time('U');
				if ( strtotime($post->post_date) > $time ) {
					break;
				}
				$result .= '<meta itemprop="datePublished" content="' . wp_date('c', strtotime($post->post_date)) . '" />' . "\n";
				if ( strtotime($post->post_modified) < $time && strtotime($post->post_modified) > strtotime($post->post_date) ) {
					$result .= '<meta itemprop="dateModified" content="' . wp_date('c', strtotime($post->post_modified)) . '" />' . "\n";
				}
				break;

			default:
				break;
		}
		return empty($result) ? false : $result;
	}
}

if ( ! function_exists('get_schema_props') ) {
	function get_schema_props( $tag = 'article', $post = null ) {
		$result = '';
		switch ( strtolower($tag) ) {
			case 'article':
				$result = 'itemscope itemtype="' . esc_url(set_url_scheme('https://schema.org/Article')) . '"';
				break;

			case 'body':
			case 'head':
				$result = 'itemscope itemtype="' . esc_url(set_url_scheme('https://schema.org/WebPage')) . '"';
				break;

			default:
				break;
		}
		return empty($result) ? false : $result;
	}
}

if ( ! function_exists('the_schema_meta') ) {
	function the_schema_meta( $itemtype = 'article', $post = null ) {
		if ( $tmp = get_schema_meta($itemtype, $post) ) {
			echo wp_kses($tmp, get_allowed_html_tags(array( 'meta' => true, 'span' => true )));
		}
	}
}

if ( ! function_exists('the_schema_props') ) {
	function the_schema_props( $tag = 'article', $post = null ) {
		if ( $tmp = get_schema_props($tag, $post) ) {
			echo wp_sprintf(' %s', trim($tmp));
		}
	}
}
