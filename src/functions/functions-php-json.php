<?php
if ( ! function_exists('json_to_array') ) {
	function json_to_array( $json ) {
		$result = array();
		$json = trim($json);
		if ( empty_zero_ok($json) ) {
			return $result;
		}
		$tmp = json_decode($json, true);
		if ( is_array($tmp) ) {
			return $tmp;
		}
		$sep_start = '{"';
		$sep_end = '}';
		if ( str_contains($json, $sep_end . $sep_start) ) {
			$strings = preg_split('/(' . preg_quote($sep_end, '/') . ')(' . preg_quote($sep_start, '/') . ')/s', $json, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			foreach ( $strings as $value ) {
				if ( $value === $sep_start || $value === $sep_end ) {
					continue;
				}
				$tmp = str_starts_with($value, $sep_start) ? json_decode($value . $sep_end, true) : json_decode($sep_start . $value, true);
				if ( is_array($tmp) ) {
					$result[] = $tmp;
				}
			}
		}
		return $result;
	}
}
