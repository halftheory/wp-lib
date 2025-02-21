<?php
if ( ! function_exists('get_image_info') ) {
	function get_image_info( $attachment_id, $size = null ) {
		if ( ht_get_post_type($attachment_id) !== 'attachment' ) {
			return false;
		}
		if ( ! wp_attachment_is_image($attachment_id) ) {
			return false;
		}
		$func = function ( $width, $height ) {
			$orientation = get_image_orientation($width, $height);
			if ( ! $orientation ) {
				return false;
			}
			$result = array(
				'width' => (int) $width,
				'height' => (int) $height,
				'ratio' => (float) $width / (float) $height,
				'orientation' => $orientation,
			);
			return $result;
		};
		if ( $array = wp_get_attachment_metadata($attachment_id) ) {
			// Intermediate image sizes.
			if ( ! empty($size) && $size !== 'full' && isset($array['sizes'], $array['sizes'][ $size ], $array['sizes'][ $size ]['width'], $array['sizes'][ $size ]['height']) ) {
				if ( $tmp = $func($array['sizes'][ $size ]['width'], $array['sizes'][ $size ]['height']) ) {
					return $tmp;
				}
			}
			// Original.
			if ( isset($array['width'], $array['height']) ) {
				if ( $tmp = $func($array['width'], $array['height']) ) {
					return $tmp;
				}
			}
		}
		if ( $array = wp_getimagesize(get_attachment_path($attachment_id, $size)) ) {
			if ( isset($array[0], $array[1]) ) {
				if ( $tmp = $func($array[0], $array[1]) ) {
					return $tmp;
				}
			}
		}
		return false;
	}
}

if ( ! function_exists('get_image_context') ) {
	function get_image_context( $context, $attachment_id, $size = 'medium', $attr = array() ) {
		if ( ht_get_post_type($attachment_id) !== 'attachment' ) {
			return false;
		}
		if ( ! wp_attachment_is_image($attachment_id) ) {
			return false;
		}
		$result = null;
		switch ( $context ) {
			case 'id':
				$result = (int) $attachment_id;
				break;
			case 'path':
			case 'file':
				if ( $tmp = get_attachment_path($attachment_id, $size) ) {
					$result = $tmp;
				}
				break;
			case 'url':
				if ( $tmp = wp_get_attachment_image_url($attachment_id, $size, false) ) {
					$result = $tmp;
				}
				break;
			case 'metadata':
				// Returns array - width, height, ratio, orientation.
				if ( $tmp = get_image_info($attachment_id, $size, false) ) {
					$result = $tmp;
				}
				break;
			case 'src':
				// Returns array - url, width, height. Prefer 'get_image_info'.
				if ( $tmp = wp_get_attachment_image_src($attachment_id, $size, false) ) {
					$result = $tmp;
				}
				break;
			case 'img':
				// <img
				if ( $tmp = wp_get_attachment_image($attachment_id, $size, false, $attr) ) {
					$result = $tmp;
				}
				break;
			case 'link':
				// <a href="large.jpg"><img
				if ( $tmp = wp_get_attachment_link($attachment_id, $size, false, false, false, $attr) ) {
					$result = $tmp;
				}
				break;
			default:
				break;
		}
		return $result;
	}
}

if ( ! function_exists('get_image_orientation') ) {
	function get_image_orientation( $width, $height ) {
		if ( empty($width) || empty($height) ) {
			return false;
		}
		$ratio = (float) $width / (float) $height;
		if ( $ratio === 1.0 ) {
			$result = 'square';
		} elseif ( $ratio < 1 ) {
			$result = 'portrait';
		} else {
			$result = 'landscape';
		}
		return $result;
	}
}
