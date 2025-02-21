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
		$this->load_functions('video-common');
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( is_public() ) {
			// Public.
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
		} else {
			// Admin.
		}
		parent::autoload();
	}

	// Public.

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( did_filter('wp_video_shortcode_override') === 0 && did_filter('wp_video_shortcode_library') === 0 && did_filter('wp_video_shortcode_class') === 0 && did_filter('wp_video_shortcode') === 0 ) {
			return;
		}
		// Load CSS.
		if ( ! wp_style_is(static::$handle) ) {
			$file = __DIR__ . '/assets/css/video-public.css';
			if ( $url = get_stylesheet_uri_from_file($file) ) {
				wp_enqueue_style(static::$handle, $url, array(), get_file_version($file), 'screen');
			}
		}
	}
}
