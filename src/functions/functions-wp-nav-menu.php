<?php
if ( ! function_exists('get_nav_menu_description') ) {
	function get_nav_menu_description( $slug ) {
		$nav_menus = get_registered_nav_menus();
		if ( empty($nav_menus) ) {
			return false;
		}
		return isset($nav_menus[ $slug ]) ? trim($nav_menus[ $slug ]) : false;
	}
}

if ( ! function_exists('get_nav_menu_items_by_location') ) {
	function get_nav_menu_items_by_location( $location = '', $args = array() ) {
		$locations = get_nav_menu_locations();
		if ( empty($locations) ) {
			return false;
		}
		$location = empty($location) ? key($locations) : $location;
		if ( $obj = wp_get_nav_menu_object($locations[ $location ]) ) {
			return wp_get_nav_menu_items($obj->name, $args);
		}
		return false;
	}
}
