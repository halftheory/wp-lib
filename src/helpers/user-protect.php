<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class User_Protect extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $users = array( 1 ) ) {
		$this->data['users'] = make_array($users);

		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_filter('map_meta_cap', array( $this, 'global_map_meta_cap' ), 20, 4);
		add_action('delete_user', array( $this, 'global_delete_user' ), 20, 3);
		parent::autoload();
	}

	// Global.

	public function global_map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $caps;
		}
		$array = array(
			'remove_user',
			'promote_user',
			'edit_user',
			'edit_users',
			'edit_user_meta',
			'delete_user_meta',
			'delete_user',
			'delete_users',
		);
		if ( ! in_array($cap, $array) ) {
			return $caps;
		}
		$edited_user_id = is_array($args) ? (int) current($args) : intval($args);
		if ( $user_id === $edited_user_id ) {
			return $caps;
		} elseif ( in_array_int($edited_user_id, $this->data['users']) ) {
			$caps[] = 'do_not_allow';
		}
		return $caps;
	}

	public function global_delete_user( $id, $reassign, $user ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( in_array_int($id, $this->data['users']) ) {
			$this->load_functions('wp-pluggable');
			$location = is_public() ? home_url('/') : get_admin_url(null, 'users.php');
			if ( ht_wp_redirect($location) ) {
                exit;
            }
		}
	}
}
