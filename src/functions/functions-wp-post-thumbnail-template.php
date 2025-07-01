<?php
if ( ! function_exists('get_post_thumbnail_context') ) {
	function get_post_thumbnail_context( $context, $post = null, $size = 'medium', $attr = array(), $fallback_args = array() ) {
		$post = get_post($post);
		if ( ! $post ) {
			return false;
		}
		$thumbnail_id = post_thumbnail_id_fallback(get_post_thumbnail_id($post), $post, $fallback_args);
		return $thumbnail_id ? get_image_context($context, $thumbnail_id, $size, $attr) : false;
	}
}

if ( ! function_exists('ht_the_post_thumbnail') ) {
	function ht_the_post_thumbnail( $size = null, $attr = array(), $fallback_args = array() ) {
		if ( empty($size) ) {
			$size = is_singular() ? 'large' : 'medium';
		}
		$thumbnail_id = get_post_thumbnail_context('id', null, $size, $attr, $fallback_args);
		if ( ! $thumbnail_id ) {
			return;
		}
		// Add more attributes.
		$defaults = array(
			'alt' => the_title_attribute('echo=0'),
		);
		$div_class = array(
			'post-thumbnail',
		);
		if ( $tmp = get_image_info($thumbnail_id, $size) ) {
			$defaults['width'] = $tmp['width'];
			$defaults['height'] = $tmp['height'];
			$defaults['class'] = $tmp['orientation'];
		}
		$attr = wp_parse_args($attr, $defaults);
		if ( isset($attr['class']) ) {
			$div_class[] = trim($attr['class']);
		}
		$label = $attr['alt'] ? wp_sprintf('%s: "%s"', __('Image'), $attr['alt']) : __('Image');
		if ( is_singular() ) {
			// Singular.
			$div_class[] = 'singular';
			?>
			<div class="<?php echo esc_attr(implode(' ', $div_class)); ?>" role="img" aria-label="<?php echo esc_attr($label); ?>">
				<a href="<?php echo esc_url(get_image_context('url', $thumbnail_id, 'large')); ?>" rel="lightbox"><?php echo wp_kses_post(get_image_context('img', $thumbnail_id, $size, $attr)); ?></a>
				<?php
				// Caption.
				if ( $tmp = wp_get_attachment_caption($thumbnail_id) ) {
					?>
					<p class="caption"><?php echo wp_kses_post($tmp); ?></p>
					<?php
				}
				?>
			</div>
			<?php
		} else {
			// Archives.
			?>
			<div class="<?php echo esc_attr(implode(' ', $div_class)); ?>" role="img" aria-label="<?php echo esc_attr($label); ?>">
				<a href="<?php the_permalink(); ?>"><?php echo wp_kses_post(get_image_context('img', $thumbnail_id, $size, $attr)); ?></a>
			</div>
			<?php
		}
	}
}

if ( ! function_exists('post_thumbnail_id_fallback') ) {
	function post_thumbnail_id_fallback( $thumbnail_id = 0, $post = null, $args = array() ) {
		if ( ! empty($thumbnail_id) ) {
			return $thumbnail_id;
		}
		$post = get_post($post);
		if ( ! $post ) {
			return $thumbnail_id;
		}
		$defaults = array(
			'search' => array(
				'attached_media' => true,
				'gallery' => true,
				'content' => true,
				'is_image' => true,
				'parent' => false,
				'logo' => false,
			),
			'min_width' => get_option('medium_size_w', 0),
			'min_height' => get_option('medium_size_h', 0),
		);
		$args = wp_parse_args_recursive($args, $defaults);
		if ( ! is_array($args['search']) ) {
			return $thumbnail_id;
		}

		// Tests - minimum width/height.
		$func_test = function ( $attachment_id ) use ( $args ) {
			$tmp = get_image_info($attachment_id);
			if ( ! $tmp ) {
				return false;
			}
			static $_w = null;
			if ( is_null($_w) ) {
				$_w = isset($args['min_width']) ? (int) $args['min_width'] : 0;
			}
			static $_h = null;
			if ( is_null($_h) ) {
				$_h = isset($args['min_height']) ? (int) $args['min_height'] : 0;
			}
			if ( $_w && $tmp['width'] < $_w ) {
				return false;
			}
			if ( $_h && $tmp['height'] < $_h ) {
				return false;
			}
			return true;
		};

		// Get the media.
		$media = array();
		$tmp = get_attached_media('image', $post);
		if ( ! empty($tmp) ) {
			$media = array_combine(array_map('absint', array_keys($tmp)), array_map('wp_get_attachment_metadata', array_keys($tmp)));
		}

		foreach ( $args['search'] as $key => $value ) {
			if ( ! $value ) {
				continue;
			}
			switch ( $key ) {
				case 'attached_media':
					if ( empty($media) ) {
						break;
					}
					reset($media);
					foreach ( $media as $attachment_id => $value ) {
						if ( $func_test($attachment_id) ) {
							$thumbnail_id = $attachment_id;
							break;
						}
					}
					break;

				case 'gallery':
					$tmp = get_post_gallery($post, false);
					if ( isset($tmp['ids']) && ! empty($tmp['ids']) ) {
						foreach ( make_array($tmp['ids']) as $attachment_id ) {
							$attachment_id = (int) $attachment_id;
							if ( $func_test($attachment_id) ) {
								$thumbnail_id = $attachment_id;
								break;
							}
						}
					}
					break;

				case 'content':
					$content = trim(get_post_field('post_content', $post, 'raw'));
					if ( empty($content) ) {
						break;
					}
					$array = array();
					if ( preg_match_all('/<img .*?src="([^"]+)"[^>]*>/is', $content, $matches) ) {
						if ( ! empty($matches[1]) ) {
							$array = ht_basename($matches[1]);
						}
					}
					if ( empty($array) ) {
						break;
					}
					// Prefer attached media.
					if ( ! empty($media) ) {
						$media_files = array();
						foreach ( $media as $attachment_id => $value ) {
							$media_files[ $attachment_id ] = array();
							if ( isset($value['file']) ) {
								$media_files[ $attachment_id ]['full'] = ht_basename($value['file']);
							}
							if ( isset($value['sizes']) ) {
								$media_files[ $attachment_id ] += wp_list_pluck($value['sizes'], 'file');
							}
						}
						$media_files_string = maybe_serialize($media_files);
						foreach ( $array as $file ) {
							if ( ! str_contains($media_files_string, $file) ) {
								continue;
							}
							foreach ( $media_files as $attachment_id => $files ) {
								if ( in_array($file, $files) ) {
									if ( $func_test($attachment_id) ) {
										$thumbnail_id = $attachment_id;
										break;
									}
								}
							}
							if ( $thumbnail_id ) {
								break;
							}
						}
						unset($media_files, $media_files_string);
						if ( $thumbnail_id ) {
							break;
						}
					}
					// Search for first unattached image in db.
					global $wpdb;
					foreach ( $array as $file ) {
						// Remove size suffix.
						$guid = preg_replace('/\-[0-9]+x[0-9]+(\.[\w]+)$/s', '$1', $file, 1);
						if ( $attachment_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND guid LIKE %s ORDER BY ID ASC", 'attachment', 0, '%' . $wpdb->esc_like($guid))) ) {
							$attachment_id = (int) $attachment_id;
							if ( $func_test($attachment_id) ) {
								$thumbnail_id = $attachment_id;
								break;
							}
						}
					}
					break;

				case 'is_image':
					if ( wp_attachment_is_image($post) ) {
						if ( $func_test($post->ID) ) {
							$thumbnail_id = $post->ID;
						}
					}
					break;

				case 'parent':
					if ( empty($post->post_parent) ) {
						break;
					}
					if ( $attachment_id = get_post_thumbnail_id($post->post_parent) ) {
						if ( $func_test($attachment_id) ) {
							$thumbnail_id = $attachment_id;
						}
					}
					break;

				case 'logo':
					if ( $attachment_id = get_custom_logo_id() ) {
						if ( $func_test($attachment_id) ) {
							$thumbnail_id = $attachment_id;
						}
					}
					break;

				default:
					break;
			}
			if ( $thumbnail_id ) {
				break;
			}
		}
		return $thumbnail_id;
	}
}
