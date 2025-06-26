<?php
if ( is_readable(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions-wp.php') ) {
	include_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions-wp.php';
}

if ( ! function_exists('get_microdata_meta') ) {
	function get_microdata_meta( $itemtype = 'article' ) {
		$result = '';
		$post = in_the_loop() || is_singular() ? get_post(get_the_ID()) : null;
		if ( ! $post ) {
			return $result;
		}
		switch ( strtolower($itemtype) ) {
			case 'article':
				// Only if there is content.
				if ( empty_zero_ok(trim_content(ht_strip_shortcodes($post->post_content))) ) {
					break;
				}
				$result .= '<meta itemprop="headline" content="' . esc_attr(substr(get_the_title($post), 0, 110)) . '">' . "\n";
				$author = null;
				if ( $tmp = get_userdata($post->user_id) ) {
					$author = apply_filters('the_author', is_object($tmp) ? $tmp->display_name : '');
				}
				if ( $author ) {
					$result .= '<meta itemprop="author" content="' . esc_attr($author) . '">' . "\n";
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
					$result .= '<meta itemprop="image" content="' . esc_url($image_url) . '">' . "\n";
				}
				$result .= '<span itemprop="publisher" itemscope itemtype="' . esc_url(set_url_scheme('https://schema.org/Organization')) . '" class="none">' . "\n";
				$result .= '<meta itemprop="name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
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
					$result .= '<meta itemprop="logo" content="' . esc_url($logo_url) . '">' . "\n";
				}
				$result .= '</span>' . "\n";
				$result .= get_microdata_meta('dates');
				break;

			case 'dates':
				$time = current_time('U');
				if ( strtotime($post->post_date) > $time ) {
					break;
				}
				$result .= '<meta itemprop="datePublished" content="' . wp_date('c', strtotime($post->post_date)) . '">' . "\n";
				if ( strtotime($post->post_modified) < $time && strtotime($post->post_modified) > strtotime($post->post_date) ) {
					$result .= '<meta itemprop="dateModified" content="' . wp_date('c', strtotime($post->post_modified)) . '">' . "\n";
				}
				break;

			default:
				break;
		}
		return $result;
	}
}

if ( ! function_exists('get_microdata_props') ) {
	function get_microdata_props( $tag = 'article' ) {
		$result = '';
		switch ( strtolower($tag) ) {
			case 'article':
				$result = 'role="article" itemscope itemtype="' . esc_url(set_url_scheme('https://schema.org/Article')) . '"';
				break;

			case 'html':
			case 'head':
			case 'body':
				$result = 'itemscope itemtype="' . esc_url(set_url_scheme('https://schema.org/WebPage')) . '"';
				break;

			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$result = 'role="heading" itemprop="name"';
				break;

			case 'ol':
			case 'ul':
				$result = 'itemscope itemtype="' . esc_url(set_url_scheme('https://schema.org/ItemList')) . '"';
				break;

			case 'li':
				$result = 'itemprop="itemListElement" itemscope itemtype="' . esc_url(set_url_scheme('https://schema.org/ListItem')) . '"';
				break;

			default:
				break;
		}
		return $result;
	}
}

if ( ! function_exists('the_microdata_meta') ) {
	function the_microdata_meta( $itemtype = 'article' ) {
		if ( $tmp = get_microdata_meta($itemtype) ) {
			echo wp_kses($tmp, get_allowed_html_tags(array( 'meta' => true, 'span' => true )));
		}
	}
}

if ( ! function_exists('the_microdata_props') ) {
	function the_microdata_props( $tag = 'article' ) {
		if ( $tmp = get_microdata_props($tag) ) {
			echo $tmp;
		}
	}
}
