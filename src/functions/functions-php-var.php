<?php
if ( ! function_exists('empty_zero_ok') ) {
	function empty_zero_ok( $value ) {
		if ( is_numeric($value) && (int) $value === 0 ) {
			return false;
		}
		return empty($value);
	}
}

if ( ! function_exists('is_true') ) {
	function is_true( $value ) {
		if ( is_bool($value) ) {
			return $value;
		} elseif ( is_numeric($value) ) {
			return (int) $value === 1;
		} elseif ( is_string($value) ) {
			if ( in_array($value, array( '1', 'true', 'TRUE', 'True' ), true) ) {
				return true;
			} elseif ( in_array($value, array( '0', 'false', 'FALSE', 'False' ), true) ) {
				return false;
			}
			return filter_var($value, FILTER_VALIDATE_BOOL);
		} elseif ( empty($value) ) {
			return false;
		}
		return false;
	}
}

if ( ! function_exists('ht_var_dump') ) {
	function ht_var_dump( $value, $exit = true ) {
		echo '<pre>';
		if ( is_array($value) || is_object($value) || is_resource($value) ) {
			print_r($value);
		} elseif ( is_string($value) && empty($value) ) {
			echo 'empty string';
		} elseif ( is_string($value) || is_numeric($value) ) {
			echo $value;
		} elseif ( is_bool($value) ) {
			echo $value ? 'true' : 'false';
		} elseif ( is_null($value) ) {
			echo 'null';
		} else {
			var_export($value);
		}
		echo "\n</pre>";
		if ( is_true($exit) ) {
			exit;
		}
	}
}
