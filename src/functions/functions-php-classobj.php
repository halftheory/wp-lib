<?php
if ( ! function_exists('get_class_from_file') ) {
	function get_class_from_file( $file ) {
		// Store results in a static var. key = file, value = class.
		static $_results = array();
		if ( array_key_exists( $file, $_results) ) {
			return $_results[ $file ];
		}
		$_results[ $file ] = false;
		if ( is_file($file) ) {
			if ( ! in_array($file, get_included_files()) ) {
				$old = get_declared_classes();
				if ( is_readable($file) ) {
					include_once $file;
				}
				$new = array_diff(get_declared_classes(), $old);
				if ( count($new) > 0 ) {
					$_results[ $file ] = reset($new);
				}
			}
		}
		return $_results[ $file ];
	}
}

if ( ! function_exists('load_class_from_file') ) {
	function load_class_from_file( $file, $args = null ) {
		$class = get_class_from_file($file);
		if ( ! $class ) {
			return false;
		}
		return is_null($args) ? new $class() : new $class($args);
	}
}
