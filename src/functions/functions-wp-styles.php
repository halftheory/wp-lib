<?php
if ( ! function_exists('filter_style_deps') ) {
	function filter_style_deps( $deps = array() ) {
		foreach ( (array) $deps as $key => $handle ) {
			if ( ! wp_style_is($handle, 'registered') ) {
				unset($deps[ $key ]);
			}
		}
		return array_values($deps);
	}
}
