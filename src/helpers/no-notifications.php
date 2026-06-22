<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class No_Notifications extends Filters {

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
		} else {
			// Admin.
			add_action('admin_init', array( $this, 'admin_init' ), 900);
		}
		parent::autoload();
	}

	 // Admin.

	public function admin_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Remove plugin notices, but leave defaults.
		global $wp_filter;
		$defaults = array(
			'network_admin_notices' => array(
				'new_user_email_admin_notice',
				'site_admin_notice',
				'update_nag',
				'maintenance_nag',
				array( 'Halftheory\Lib\helpers\Admin_Common', 'admin_notices' ),
			),
			'user_admin_notices' => array(
				'new_user_email_admin_notice',
			),
			'admin_notices' => array(
				'default_password_nag',
				'new_user_email_admin_notice',
				'update_nag',
				'deactivated_plugins_notice',
				'paused_plugins_notice',
				'paused_themes_notice',
				'maintenance_nag',
				'wp_recovery_mode_nag',
				array( 'WP_Privacy_Policy_Content', 'notice' ),
				array( 'WP_Privacy_Policy_Content', 'policy_text_changed_notice' ),
				'site_admin_notice',
				array( 'Halftheory\Lib\helpers\Admin_Common', 'admin_notices' ),
			),
			'all_admin_notices' => array(),
			'wp_network_dashboard_setup' => array(),
			'wp_user_dashboard_setup' => array(),
			'wp_dashboard_setup' => array(
				array( 'Halftheory\Lib\helpers\Admin_Common', 'admin_wp_dashboard_setup' ),
			),
		);

		$callback = function ( $input ) use ( &$callback ) {
			if ( is_string($input) ) {
				return $input;
			} elseif ( is_object($input) ) {
				return get_class($input);
			} elseif ( is_array($input) ) {
				return array_map($callback, $input);
			}
			return false;
		};

		// Collect non-default filters.
		$array = array();
		foreach ( $defaults as $key => $value ) {
			if ( empty($value) ) {
				remove_all_actions($key);
				continue;
			}
			if ( ! isset($wp_filter[ $key ], $wp_filter[ $key ]->callbacks) ) {
				continue;
			}
			foreach ( $wp_filter[ $key ]->callbacks as $priority => $v ) {
				$functions = wp_list_pluck($v, 'function');
				if ( empty($functions) ) {
					continue;
				}
				foreach ( $functions as $function ) {
					$tmp = $callback($function);
					if ( ! in_array($tmp, $value, true) ) {
						if ( ! isset($array[ $key ]) ) {
							$array[ $key ] = array();
						}
						if ( ! isset($array[ $key ][ $priority ]) ) {
							$array[ $key ][ $priority ] = array();
						}
						$array[ $key ][ $priority ][] = $tmp;
					}
				}
			}
		}
		if ( empty($array) ) {
			return;
		}

		// Remove them.
		$this->load_functions('wp-admin');
		foreach ( $array as $key => $value ) {
			foreach ( $value as $priority => $v ) {
				foreach ( $v as $function ) {
					ht_remove_filter($key, $function, $priority);
				}
			}
		}
	}
}
