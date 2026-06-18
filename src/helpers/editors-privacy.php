<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Editors_Privacy extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('init', array( $this, 'global_init' ), 20);
		if ( ! is_public() ) {
			// Admin.
			add_filter('map_meta_cap', array( $this, 'admin_map_meta_cap' ), 20, 4);
		}
		parent::autoload();
	}

	// Global.

	public function global_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Editors can edit privacy page.
		if ( is_user_logged_in() && ! is_public() ) {
			global $current_user;
			if ( is_object($current_user) ) {
				if ( in_array('editor', $current_user->roles) ) {
					if ( ! $current_user->has_cap('manage_privacy_options') ) {
						$current_user->add_cap('manage_privacy_options');
					}
				}
			}
		}
	}

	// Admin.

	public function admin_map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $caps;
		}
		if ( $cap !== 'manage_privacy_options' ) {
			return $caps;
		}
		$admin_cap = is_multisite() ? 'manage_network' : 'manage_options';
		$caps = array_values(array_diff($caps, array( $admin_cap )));
		return $caps;
	}
}
