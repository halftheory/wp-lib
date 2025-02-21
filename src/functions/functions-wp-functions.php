<?php
if ( ! function_exists( 'wp_parse_args_recursive' ) ) {
	function wp_parse_args_recursive( $args, $defaults, $overwrite = array(), $depth = 0 ) {
		$args = is_array($args) ? $args : wp_parse_str($args, $args);
		$new_args = make_array($defaults);
		if ( empty($args) ) {
			return $new_args;
		}
		$overwrite = make_array($overwrite);
		$i = $depth + 1;
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
				if ( $depth === 0 && in_array($key, $overwrite) ) {
					$new_args[ $key ] = $value;
				} else {
					$new_args[ $key ] = wp_parse_args_recursive($value, $new_args[ $key ], $overwrite, $i);
				}
			} else {
				$new_args[ $key ] = $value;
			}
		}
		return $new_args;
	}
}
