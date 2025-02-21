<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Authors_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( is_public() ) {
			// Public.
			add_filter('the_author', array( $this, 'public_the_author' ), 90);
		} else {
			// Admin.
		}
		parent::autoload();
	}

	// Global.

	// Public.

	public function public_the_author( $display_name ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $display_name;
		}
		// change to blogname in some cases.
		$change = false;
		global $authordata;
		if ( empty($display_name) ) {
			$change = true;
		} elseif ( strtolower($display_name) === $display_name ) {
			$change = true;
		} elseif ( is_object($authordata) && is_super_admin($authordata->ID) ) {
			$change = true;
		}
		return $change ? get_bloginfo('name') : $display_name;
	}

	// Admin.
}
