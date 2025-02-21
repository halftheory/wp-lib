<?php
if ( ! function_exists('delete_post_video') ) {
	function delete_post_video( $post = null ) {
		$post = get_post($post);
		if ( $post ) {
			return delete_post_meta($post->ID, '_video_id');
		}
		return false;
	}
}

if ( ! function_exists('get_post_video_id') ) {
	function get_post_video_id( $post = null ) {
		$post = get_post($post);
		if ( ! $post ) {
			return false;
		}
		$attachment_id = get_post_meta($post->ID, '_video_id', true);
		return empty($attachment_id) ? false : (int) $attachment_id;
	}
}

if ( ! function_exists('get_the_post_video') ) {
	function get_the_post_video( $post = null, $attr = array() ) {
		$post = get_post($post);
		if ( ! $post ) {
			return '';
		}
		$video = '';
		if ( $attachment_id = get_post_video_id($post) ) {
			$video = get_video_context('video', $attachment_id, $attr);
		}
		return $video;
	}
}

if ( ! function_exists('has_post_video') ) {
	function has_post_video( $post = null ) {
		$attachment_id  = get_post_video_id($post);
		return (bool) $attachment_id;
	}
}

if ( ! function_exists('set_post_video') ) {
	function set_post_video( $post = null, $attachment_id = null ) {
		$post = get_post($post);
		$attachment_id = absint($attachment_id);
		if ( $post && $attachment_id && get_post($attachment_id) ) {
			if ( wp_attachment_is('video', $attachment_id) ) {
				return update_post_meta($post->ID, '_video_id', $attachment_id);
			} else {
				return delete_post_meta($post->ID, '_video_id');
			}
		}
		return false;
	}
}

if ( ! function_exists('the_post_video') ) {
	function the_post_video( $attr = array() ) {
		$attachment_id = get_post_video_id();
		if ( ! $attachment_id ) {
			return;
		}
		// Add more attributes.
		$defaults = array(
			'data-title' => the_title_attribute('echo=0'),
		);
		$div_class = array(
			'post-video',
		);
		if ( $tmp = get_video_context('metadata', $attachment_id, $attr) ) {
			if ( $tmp['width'] && $tmp['height'] ) {
				if ( $orientation = get_image_orientation($tmp['width'], $tmp['height']) ) {
					$defaults['class'] = $orientation;
				}
			}
		}
		$attr = wp_parse_args($attr, $defaults);
		if ( isset($attr['class']) ) {
			$div_class[] = trim($attr['class']);
		}
		$video = get_the_post_video(null, $attr);
		if ( ! $video ) {
			return;
		}
		if ( is_singular() ) {
			// Singular.
			$div_class[] = 'singular';
			?>
			<div class="<?php echo esc_attr(implode(' ', $div_class)); ?>">
				<?php echo wp_kses_post($video); ?>
			</div>
			<?php
		} else {
			// Archives.
			?>
			<div class="<?php echo esc_attr(implode(' ', $div_class)); ?>">
				<?php echo wp_kses_post($video); ?>
			</div>
			<?php
		}
	}
}
