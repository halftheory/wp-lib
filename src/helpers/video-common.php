<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Video_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		$this->load_functions('video-common');
		if ( is_public() ) {
			// Public.
			add_filter('embed_oembed_html', array( $this, 'public_embed_oembed_html' ), 20, 4);
			$filters = array( 'link_description', 'link_notes', 'term_description', 'the_content', 'get_the_excerpt', 'comment_text', 'widget_text' );
			foreach ( $filters as $filter ) {
				add_filter($filter, array( $this, 'public_youtube_nocookie_filter' ), 90);
			}
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
		}
		parent::autoload();
	}

	// Public.

	public function public_embed_oembed_html( $cache, $url, $attr, $post_id ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $cache;
		}
		if ( str_contains($cache, 'youtube.com') ) {
			$cache = str_replace('youtube.com', 'youtube-nocookie.com', $cache);
		}
		return $cache;
	}

	public function public_youtube_nocookie_filter( $text ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $text;
		}
		if ( str_contains($text, 'youtube.com/embed') ) {
			$text = str_replace('youtube.com/embed', 'youtube-nocookie.com/embed', $text);
		}
		return $text;
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( did_filter('wp_video_shortcode_override') === 0 && did_filter('wp_video_shortcode_library') === 0 && did_filter('wp_video_shortcode_class') === 0 && did_filter('wp_video_shortcode') === 0 ) {
			return;
		}
		// CSS.
		if ( ! wp_style_is(static::$handle) ) {
			$this->load_functions('wp-theme');
			$file = __DIR__ . '/assets/css/video-common-public.css';
			if ( $url = get_stylesheet_uri_from_file($file) ) {
				wp_enqueue_style(static::$handle, $url, array(), get_file_version($file), 'screen');
			}
		}
	}
}
