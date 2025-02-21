<?php
if ( ! function_exists('get_video_context') ) {
	function get_video_context( $context, $attachment_id, $attr = array() ) {
		if ( ht_get_post_type($attachment_id) !== 'attachment' ) {
			return false;
		}
		if ( ! wp_attachment_is('video', $attachment_id) ) {
			return false;
		}
		$result = null;
		switch ( $context ) {
			case 'id':
				$result = (int) $attachment_id;
				break;
			case 'path':
			case 'file':
				if ( $tmp = get_attachment_path($attachment_id) ) {
					$result = $tmp;
				}
				break;
			case 'url':
				if ( $tmp = wp_get_attachment_url($attachment_id) ) {
					$result = $tmp;
				}
				break;
			case 'metadata':
				// Returns array - width, height, file.
				if ( $tmp = wp_get_attachment_metadata($attachment_id) ) {
					$result = $tmp;
				}
				break;
			case 'link':
				// <a href="file.mp4"><video
				if ( $tmp = get_video_context('video', $attachment_id, $attr) ) {
					$result = '<a href="' . esc_url(wp_get_attachment_url($attachment_id)) . '">' . $tmp . '</a>';
				}
				break;
			case 'video':
				// <video
				// See https://developer.wordpress.org/reference/functions/wp_video_shortcode/
				if ( $tmp = get_video_context('url', $attachment_id, $attr) ) {
					$defaults = array(
						'autoplay' => true,
						'controls' => false,
						'controlslist' => 'nodownload',
						'disablepictureinpicture' => true,
						'loop' => true,
						'muted' => true,
						'playsinline' => true,
						'preload' => 'auto',
						'width' => '100%',
					);
					$attr = wp_parge_args($attr, $defaults);
					$attr_strings = array();
					foreach ( $attr as $key => $value ) {
						if ( is_bool($value) ) {
							$attr_strings[ $key ] = $value ? $key : $key . '="false"';
						} elseif ( is_string($value) && str_starts_with($value, 'http') ) {
							// Escape any URL attributes. e.g. 'poster'.
							$attr_strings[ $key ] = $key . '="' . esc_url($value) . '"';
						} else {
							$attr_strings[ $key ] = $key . '="' . esc_attr($value) . '"';
						}
					}
					$result = wp_sprintf('<video %s>', implode(' ', $attr_strings));
					$result .= wp_sprintf('<source type="%s" src="%s" />', esc_attr(get_post_mime_type($attachment_id)), esc_url($tmp));
					$result .= '</video>';
				}
				break;
			default:
				break;
		}
		return $result;
	}
}
