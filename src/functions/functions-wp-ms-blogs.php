<?php
if ( ! function_exists('switch_from_native_blog') ) {
	function switch_from_native_blog( $switched = false ) {
		if ( ! $switched ) {
			return;
		}
		if ( ! is_multisite() ) {
			return;
		}
		if ( is_numeric($switched) ) {
			switch_to_blog($switched);
		}
	}
}

if ( ! function_exists('switch_to_native_blog') ) {
	function switch_to_native_blog() {
		if ( ! is_multisite() ) {
			return false;
		}
		if ( ! ms_is_switched() ) {
			return false;
		}
		$switched = get_current_blog_id();
		restore_current_blog();
		return $switched;
	}
}
