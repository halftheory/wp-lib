<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Feed_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('after_setup_theme', array( $this, 'global_after_setup_theme' ), 20);
		if ( is_public() ) {
			// Public.
			add_filter('request', array( $this, 'public_request' ), 20);
			add_filter('get_wp_title_rss', array( $this, 'public_get_wp_title_rss' ));
			add_filter('get_the_guid', array( $this, 'public_get_the_guid' ), 10, 2);
			add_filter('the_content', array( $this, 'public_the_content' ), 13);
		}
		parent::autoload();
	}

	// Global.

	public function global_after_setup_theme() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		add_theme_support('automatic-feed-links');
	}

	// Public.

	public function public_request( $query_vars = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $query_vars;
		}
		if ( isset($query_vars['feed']) ) {
			// remove some post_types from feed.
			$query_vars['post_type'] = isset($query_vars['post_type']) ? make_array($query_vars['post_type']) : array_values(get_post_types(array( 'public' => true ), 'names'));
			$query_vars['post_type'] = array_values(array_diff($query_vars['post_type'], array( 'page', 'attachment', 'revision' )));
		}
		return $query_vars;
	}

	public function public_get_wp_title_rss( $title ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $title;
		}
		$description = get_bloginfo('description');
		if ( ! empty($description) ) {
			$title .= __(' - ') . $description;
		}
		return $title;
	}

	public function public_get_the_guid( $post_guid = '', $post_id = 0 ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $post_guid;
		}
		if ( is_feed() && ! empty($post_id) ) {
			if ( $tmp = get_permalink($post_id) ) {
				$post_guid = $tmp;
			}
		}
		return $post_guid;
	}

	public function public_the_content( $content = '' ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $content;
		}
		if ( is_feed() && headers_sent() ) {
			$tags = get_allowed_html_tags(array( 'data', 'br' => array(), 'img' => true ));
			$content = wp_kses($content, $tags);
			$content = ht_set_url_scheme($content);
			$content = make_clickable($content);
		}
		return $content;
	}
}
