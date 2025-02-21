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
			if ( (int) $value === 1 ) {
				return true;
			} elseif ( (int) $value === 0 ) {
				return false;
			}
		} elseif ( is_string($value) ) {
			if ( in_array($value, array( '1', 'true', 'TRUE', 'True' ), true) ) {
				return true;
			} elseif ( in_array($value, array( '0', 'false', 'FALSE', 'False' ), true) ) {
				return false;
			}
		} elseif ( empty($value) ) {
			return false;
		}
		return false;
	}
}
