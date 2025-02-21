<?php
if ( ! function_exists('array_filter_not') ) {
	function array_filter_not( $array, $callback ) {
		if ( ! function_exists($callback) ) {
			return array_filter($array);
		}
		$array = array_filter($array,
			function ( $v ) use ( $callback ) {
				return ! $callback($v);
			}
		);
		return $array;
	}
}

if ( ! function_exists('array_to_js_object') ) {
	function array_to_js_object( $array, $output = ARRAY_A ) {
		$result = array();
		foreach ( make_array($array) as $key => $value ) {
			if ( is_null($value) || is_string($value) || is_float($value) || is_int($value) ) {
				$value = "'" . $value . "'";
			} elseif ( is_bool($value) ) {
				$value = $value === true ? 'true' : 'false';
			} else {
				continue;
			}
			$result[ $key ] = $key . ': ' . $value;
		}
		if ( $output === ARRAY_A ) {
			return $result;
		} elseif ( $output === ARRAY_N ) {
			return array_values($result);
		}
		return empty($result) ? '{}' : '{ ' . implode(', ', $result) . ' }';
	}
}

if ( ! function_exists('array_value_unset') ) {
	function array_value_unset( $array, $value, $removals = -1 ) {
		if ( empty($array) ) {
			return $array;
		}
		if ( $removals >= 1 ) {
			// Remove some.
			$i = 0;
			while ( $i < $removals && in_array($value, $array, true) ) {
				$key = array_search($value, $array, true);
				if ( $key === false ) {
					break;
				}
				unset($array[ $key ]);
				$i++;
			}
		} else {
			// Remove all.
			$array = array_diff($array, array( $value ));
		}
		return $array;
	}
}

if ( ! function_exists('in_array_int') ) {
	function in_array_int( $needle, $haystack, $strict = true ) {
		$haystack = make_array($haystack);
		$haystack = array_filter($haystack, 'is_numeric');
		$haystack = array_map('intval', $haystack);
		return in_array( (int) $needle, $haystack, $strict);
	}
}

if ( ! function_exists('make_array') ) {
	function make_array( $value, $sep = ',' ) {
		if ( is_array($value) ) {
			return $value;
		}
		$array = array();
		if ( empty_zero_ok($value) ) {
			return $array;
		}
		if ( str_contains($value, '=') && str_contains($value, '&') ) {
			parse_str($value, $array);
		} else {
			$array = explode($sep, trim($value, " \n\r\t\v\0" . $sep));
		}
		$array = array_map('trim', $array);
		$array = array_filter_not($array, 'empty_zero_ok');
		return $array;
	}
}

if ( ! function_exists('sort_longest_first') ) {
	function sort_longest_first( $array ) {
		$array = make_array($array);
		$callback = function ( $a, $b ) {
			return strlen($b) - strlen($a);
		};
		uasort($array, $callback);
		return $array;
	}
}
