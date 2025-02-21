<?php
if ( ! function_exists('is_administrator') ) {
	function is_administrator() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( is_user_logged_in() ) {
				if ( is_super_admin() ) {
					$_result = true;
				} elseif ( current_user_can('manage_options') ) {
					$_result = true;
				} else {
					global $current_user;
					if ( is_object($current_user) && isset($current_user->roles) && is_array($current_user->roles) ) {
						if ( in_array('administrator', $current_user->roles, true) ) {
							$_result = true;
						}
					}
				}
			}
		}
		return $_result;
	}
}
