<?php
if ( ! function_exists('get_attachment_path') ) {
	function get_attachment_path( $attachment_id, $size = null ) {
		if ( ht_get_post_type($attachment_id) !== 'attachment' ) {
			return false;
		}
		$result = null;
		// Images.
		if ( wp_attachment_is_image($attachment_id) ) {
			// Intermediate image sizes.
			if ( ! empty($size) && $size !== 'full' ) {
				if ( $tmp = wp_get_attachment_image_url($attachment_id, $size) ) {
					$upload_dir = wp_get_upload_dir();
					$result = str_replace_start(trailingslashit($upload_dir['baseurl']), trailingslashit($upload_dir['basedir']), $tmp);
				}
			}
			// Original.
			if ( empty($result) && function_exists('wp_get_original_image_path') ) {
				// Avoids 'scaled' or edited images.
				$result = wp_get_original_image_path($attachment_id);
			}
		}
		if ( empty($result) ) {
			$result = get_attached_file($attachment_id);
		}
		if ( $result ) {
			$result = preg_replace('/[\/\\\]{2,}/s', DIRECTORY_SEPARATOR, $result);
		}
		return file_exists($result) ? $result : false;
	}
}

if ( ! function_exists('get_post_archive') ) {
	function get_post_archive() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( ht_is_archive() ) {
				if ( is_post_type_archive() || is_posts_page() || is_date() ) {
					if ( $tmp = get_post_post_type_archive(get_post_type()) ) {
						$_result = $tmp;
					}
				} elseif ( is_tax() || is_category() || is_tag() ) {
					$queried_object = get_queried_object();
					if ( is_object($queried_object) && is_a($queried_object, 'WP_Term') && isset($queried_object->taxonomy) ) {
						if ( $tmp = get_post_taxonomy_archive($queried_object->taxonomy) ) {
							$_result = $tmp;
						}
					}
				} elseif ( is_singular() && get_taxonomy_from_page_path() ) {
					$_result = get_post(ht_get_the_ID());
				}
			}
		}
		return $_result;
	}
}

if ( ! function_exists('get_post_front_page') ) {
	function get_post_front_page() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			$id = null;
			switch ( get_option('show_on_front') ) {
				case 'page':
					$id = (int) get_option('page_on_front');
					break;
				case 'posts':
					$id = (int) get_option('page_for_posts');
					break;
				default:
					break;
			}
			if ( ! empty($id) ) {
				if ( $tmp = get_post($id) ) {
					$_result = $tmp;
				}
			}
		}
		return $_result;
	}
}

if ( ! function_exists('get_post_post_type_archive') ) {
	function get_post_post_type_archive( $post_type ) {
		// Store results in a static var. key = taxonomy, value = object.
		static $_results = array();
		if ( array_key_exists( $post_type, $_results) ) {
			return $_results[ $post_type ];
		}
		$_results[ $post_type ] = false;
		if ( is_post_type_viewable($post_type) ) {
			switch ( $post_type ) {
				case 'post':
					$_results[ $post_type ] = get_post_posts_page();
					break;
				default:
					if ( $post_type_obj = get_post_type_object($post_type) ) {
						if ( $post_type_obj->has_archive && is_array($post_type_obj->rewrite) && isset($post_type_obj->rewrite['slug']) ) {
							$_results[ $post_type ] = get_page_by_path($post_type_obj->rewrite['slug']);
						}
					}
					break;
			}
		}
		return $_results[ $post_type ];
	}
}

if ( ! function_exists('get_post_posts_page') ) {
	function get_post_posts_page() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			$id = (int) get_option('page_for_posts');
			if ( ! empty($id) ) {
				if ( $tmp = get_post($id) ) {
					$_result = $tmp;
				}
			}
		}
		return $_result;
	}
}

if ( ! function_exists('get_post_taxonomy_archive') ) {
	function get_post_taxonomy_archive( $taxonomy ) {
		// Store results in a static var. key = taxonomy, value = object.
		static $_results = array();
		if ( array_key_exists( $taxonomy, $_results) ) {
			return $_results[ $taxonomy ];
		}
		$_results[ $taxonomy ] = false;
		if ( $tmp = get_taxonomy_objects($taxonomy) ) {
			if ( count($tmp) === 1 ) {
				$post_type = current($tmp);
				if ( $post_type_archive = get_post_post_type_archive($post_type) ) {
					$_results[ $taxonomy ] = $post_type_archive;
				} elseif ( $taxonomy_object = get_taxonomy($taxonomy) ) {
					if ( $taxonomy_object->public && is_array($taxonomy_object->rewrite) && isset($taxonomy_object->rewrite['slug']) ) {
						$_results[ $taxonomy ] = get_page_by_path($taxonomy_object->rewrite['slug']);
					}
				}
			}
		}
		return $_results[ $taxonomy ];
	}
}

if ( ! function_exists('ht_get_post_type') ) {
	function ht_get_post_type( $post = null ) {
		// store results in a static var. key = id, value = post_type.
		static $_results = array();
		if ( is_numeric($post) && array_key_exists( (int) $post, $_results) ) {
			return $_results[ $post ];
		}
		$post = get_post($post);
		$result = $post ? $post->post_type : false;
		if ( $post ) {
			$_results[ (int) $post->ID ] = $result;
		}
		return $result;
	}
}

if ( ! function_exists('ht_get_posts') ) {
	function ht_get_posts( $args = null ) {
		$posts = get_posts($args);
		return is_wp_error($posts) || empty($posts) ? false : $posts;
	}
}

if ( ! function_exists('ht_register_post_type') ) {
	function ht_register_post_type( $post_types, $args = array() ) {
		$result = array();
		foreach ( make_array($post_types) as $post_type ) {
			if ( $post_type === 'any' ) {
				$result[ $post_type ] = false;
				continue;
			}
			$result[ $post_type ] = post_type_exists($post_type);
			if ( $result[ $post_type ] ) {
				continue;
			}
			$label_singular = ucwords(preg_replace('/[_-]+/', ' ', $post_type));
			$label_plural = rtrim($label_singular, 's') . 's';
			$defaults = array(
				'labels' => array(
					'name' => $label_plural,
					'singular_name' => $label_singular,
					'archives' => $label_singular . ' ' . __('Archives'),
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array(
					'slug' => rtrim(strtolower($post_type), 's') . 's',
					'with_front' => false,
				),
				'supports' => array(
					'title',
					'editor',
					'thumbnail',
				),
			);
			$args = wp_parse_args($args, $defaults);
			$result[ $post_type ] = register_post_type($post_type, $args);
		}
		return $result;
	}
}
