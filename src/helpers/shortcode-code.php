<?php
namespace Halftheory\Lib\helpers;

if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Shortcode_Control.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Shortcode_Control.php';
}
use Halftheory\Lib\helpers\Shortcode_Control;

#[AllowDynamicProperties]
class Shortcode_Code extends Shortcode_Control {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		$this->data['shortcode'] = 'code';
		parent::__construct($autoload);
	}

	protected function autoload() {
		if ( is_public() ) {
			// Public.
			add_action('wp', array( $this, 'public_wp' ), 20);
		}
		parent::autoload();
	}

	// Public.

	public function public_wp( $wp ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Shortcode - [code].
		if ( ! shortcode_exists($this->data['shortcode']) ) {
			// Light work.
			$callback = function ( $atts = array(), $content = '', $shortcode_tag = '' ) {
				return remove_excess_space($content);
			};
			add_shortcode($this->data['shortcode'], $callback);
			// Hard work.
			$hooks = array(
				'the_content',
				'the_excerpt',
			);
			$this->shortcode_control($this->data['shortcode'], $hooks);
		}
	}
}
