<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Editors_Menus extends Filters {

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
			add_action('admin_init', array( $this, 'admin_init' ), 20);
		}
		parent::autoload();
	}

	// Global.

	public function global_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Editors can edit menus.
		if ( is_user_logged_in() && ! is_public() ) {
			global $current_user;
			if ( is_object($current_user) ) {
				if ( in_array('editor', $current_user->roles) ) {
					if ( ! $current_user->has_cap('edit_theme_options') ) {
						$current_user->add_cap('edit_theme_options');
					}
				}
			}
		}
	}

	// Admin.

	public function admin_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Editors can edit menus.
		global $current_user;
		if ( is_object($current_user) ) {
			if ( in_array('editor', $current_user->roles) ) {
				if ( $current_user->has_cap('edit_theme_options') ) {
					global $submenu;
					if ( is_array($submenu) && isset($submenu['themes.php']) ) {
						foreach ( $submenu['themes.php'] as $value ) {
							if ( ! isset($value[1], $value[2]) ) {
								continue;
							}
							if ( str_starts_with($value[2], 'nav-menus.php') ) {
								continue;
							} elseif ( $value[1] === 'edit_theme_options' ) {
								remove_submenu_page('themes.php', $value[2]);
							}
						}
					}
				}
			}
		}
	}
}
