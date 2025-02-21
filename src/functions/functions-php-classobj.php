<?php
if ( ! function_exists('load_class_from_file') ) {
	function load_class_from_file( $file, $args = null ) {
		if ( ! is_file($file) ) {
			return false;
		}
		if ( in_array($file, get_included_files()) ) {
			return false;
		}
		$old = get_declared_classes();
		if ( is_readable($file) ) {
			include_once $file;
		}
		$new = array_diff(get_declared_classes(), $old);
		if ( count($new) === 0 ) {
			return false;
		}
		$class = reset($new);
		return is_null($args) ? new $class() : new $class($args);
	}
}
