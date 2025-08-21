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
				// File needs to be included.
				$old = get_declared_classes();
				if ( is_readable($file) ) {
					include_once $file;
				}
				$new = array_diff(get_declared_classes(), $old);
				if ( count($new) > 0 ) {
					$_results[ $file ] = reset($new);
				}
			} elseif ( $contents = file_get_contents($file, false, null, 0, 1024) ) {
				// Maybe a child class already included it.
				$namespace = null;
				$string = 'namespace ';
				if ( $array = preg_split('/(' . $string . '[\w\\\]+)/is', $contents, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) ) {
					$callback_filter = function ( $v ) use ( $string ) {
						return str_starts_with($v, $string);
					};
					$callback_map = function ( $v ) use ( $string ) {
						return preg_replace('/^' . $string . '/s', '', $v, 1);
					};
					$array = array_filter($array, $callback_filter);
					$array = array_map($callback_map, $array);
					$namespace = trim(current($array), '\\');
				}
				$class = null;
				$string = 'class ';
				if ( $array = preg_split('/(' . $string . '[\w]+)/is', $contents, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) ) {
					$callback_filter = function ( $v ) use ( $string ) {
						return str_starts_with($v, $string);
					};
					$callback_map = function ( $v ) use ( $string ) {
						return preg_replace('/^' . $string . '/s', '', $v, 1);
					};
					$array = array_filter($array, $callback_filter);
					$array = array_map($callback_map, $array);
					$class = current($array);
				}
				if ( $namespace || $class ) {
					$tmp = implode('\\', array_filter(array( $namespace, $class )));
					if ( in_array($tmp, get_declared_classes()) ) {
						$_results[ $file ] = $tmp;
					}
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
