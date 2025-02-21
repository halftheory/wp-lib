<?php
if ( ! function_exists('current_ancestors') ) {
	function current_ancestors( $child = true ) {
		static $_child = null;
		static $_ancestors = null;
		if ( is_null($_child) || is_null($_ancestors) ) {
			list($object_id, $object_type, $resource_type) = parse_ancestors_args();
		}

		// Child.
		if ( $child && is_null($_child) ) {
			$_child = array();
			$current_url = get_current_url();
			$current_page = current_page();
			// Paged?
			if ( $current_page > 1 ) {
				$tmp = array(
					'title' => __('Page') . ' ' . $current_page,
					'url' => $current_url,
				);
				if ( $tmp = parse_ancestors_item($tmp) ) {
					$_child[] = $tmp;
					$current_url = preg_replace('/(\/)' . strtolower(__('Page')) . '\/' . $current_page . '\/?$/s', '$1', $current_url, 1);
				}
			}
			// The current object.
			$tmp = array(
				'url' => $current_url,
			);
			switch ( $resource_type ) {
				case 'post_type':
				case 'taxonomy':
					$tmp['object_id'] = $object_id;
					$tmp['object_type'] = $object_type;
					$tmp['resource_type'] = $resource_type;
					break;
				default:
					if ( is_author() ) {
						$tmp['prepend'] = __('Author');
						$tmp['title'] = get_the_author();
					} elseif ( is_date() ) {
						$date_format = get_option('date_format');
						if ( is_year() ) {
							$date_format = 'Y';
						} elseif ( is_month() ) {
							$date_format = 'F Y';
						}
						$tmp['prepend'] = __('Date');
						$tmp['title'] = get_the_time($date_format);
					} elseif ( is_login_page() ) {
						$tmp['title'] = __('Login');
					} elseif ( is_post_type_archive() ) {
						$tmp['title'] = post_type_archive_title('', false);
					} elseif ( is_signup_page() ) {
						$tmp['title'] = __('Sign Up');
					} elseif ( is_search() ) {
						$tmp['prepend'] = __('Search');
						$tmp['title'] = '"' . get_search_query() . '"';
					} elseif ( is_404() ) {
						$tmp['title'] = __('Page not found');
					}
					break;
			}
			if ( $tmp = parse_ancestors_item($tmp) ) {
				$_child[] = $tmp;
			}
		}

		// Ancestors.
		if ( is_null($_ancestors) ) {
			$_ancestors = array();
			if ( $object_id && $object_type && $resource_type ) {
				$_ancestors = ht_get_ancestors($object_id, $object_type, $resource_type, false);
			} elseif ( is_date() ) {
				// Date.
				if ( $post_type_archive = get_post_post_type_archive(get_post_type()) ) {
					$tmp = array(
						'object_id' => $post_type_archive->ID,
						'object_type' => $post_type_archive->post_type,
						'resource_type' => 'post_type',
					);
					if ( $tmp = parse_ancestors_item($tmp) ) {
						$_ancestors[] = $tmp;
					}
				}
			}
			// Front page.
			$tmp = array(
				'title' => get_bloginfo('name'),
				'append' => get_bloginfo('description'),
				'url' => home_url('/'),
			);
			if ( $front_page = get_post_front_page() ) {
				$tmp['object_id'] = $front_page->ID;
				$tmp['object_type'] = $front_page->post_type;
				$tmp['resource_type'] = 'post_type';
			}
			if ( $tmp = parse_ancestors_item($tmp) ) {
				$_ancestors[] = $tmp;
			}
		}

		return $child ? array_merge($_child, $_ancestors) : $_ancestors;
	}
}

if ( ! function_exists('get_custom_logo_id') ) {
	function get_custom_logo_id( $blog_id = 0 ) {
		static $_results = array();
		$blog_id = empty($blog_id) && is_multisite() ? (int) get_current_blog_id() : (int) $blog_id;
		if ( array_key_exists($blog_id, $_results) ) {
			return $_results[ $blog_id ];
		}
		$switched_blog = false;
		if ( is_multisite() && ! empty( $blog_id ) && get_current_blog_id() !== (int) $blog_id ) {
			switch_to_blog($blog_id);
			$switched_blog = true;
		}
		$_results[ $blog_id ] = has_custom_logo() ? (int) get_theme_mod('custom_logo') : false;
		if ( $switched_blog ) {
			restore_current_blog();
		}
		return $_results[ $blog_id ];
	}
}

if ( ! function_exists('get_keywords') ) {
	function get_keywords( $ancestors = null ) {
		$ancestors = is_array($ancestors) ? $ancestors : current_ancestors();
		$taxonomies = get_taxonomies(array( 'public' => true ), 'names');
		$results = array();
		foreach ( $ancestors as $value ) {
			if ( ! is_array($value) ) {
				continue;
			}
			$results[] = $value['append'];
			$results[] = $value['title'];
			$results[] = $value['prepend'];
			if ( ! $value['object_id'] ) {
				continue;
			}
			switch ( $value['resource_type'] ) {
				case 'post_type':
					// Terms for Post.
					$post_terms = wp_get_post_terms($value['object_id'], $taxonomies);
					if ( ! empty($post_terms) && ! is_wp_error($post_terms) ) {
						foreach ( $post_terms as $term ) {
							if ( ! is_term_publicly_viewable($term) ) {
								continue;
							}
							$results[] = get_single_term_title($term);
						}
					}
					break;
				case 'taxonomy':
					// Post Types for Taxonomy.
					if ( $taxonomy_objects = get_taxonomy_objects($value['object_type']) ) {
						foreach ( $taxonomy_objects as $post_type ) {
							if ( $archive = get_post_post_type_archive($post_type) ) {
								$results[] = get_the_title($archive);
							}
						}
					}
					break;
				default:
					break;
			}
		}
		return array_values(array_filter(array_unique($results)));
	}
}

if ( ! function_exists('get_single_term_title') ) {
	function get_single_term_title( $term ) {
		$term = ht_get_term($term);
		if ( ! $term ) {
			return false;
		}
		switch ( $term->taxonomy ) {
			case 'category':
				$filter = 'single_cat_title';
				break;
			case 'post_tag':
				$filter = 'single_tag_title';
				break;
			default:
				$filter = 'single_term_title';
				break;
		}
		return apply_filters($filter, $term->name);
	}
}

if ( ! function_exists('get_site_logo_url_from_site_icon') ) {
	function get_site_logo_url_from_site_icon( $size = 'full', $blog_id = 0 ) {
		if ( ! has_site_icon($blog_id) ) {
			return false;
		}
		$size_icon = $size;
		if ( ! is_int($size_icon) ) {
			$size_icon = 512;
		}
		// Max 512x512.
		$url = get_site_icon_url($size_icon, '', $blog_id);
		if ( str_contains($url, 'cropped-') ) {
			$names = array(
				preg_replace('/^.*?cropped-(.*)$/i', '$1', $url),
				preg_replace('/^.*?cropped-([^\.]*).*$/i', '$1', $url),
			);
			$names = array_merge($names, array_map('sanitize_title', $names) );
			$names = array_unique($names);
			$parent = get_posts(
				array(
					'no_found_rows' => true,
					'post_type' => 'attachment',
					'numberposts' => 1,
					'exclude' => (array) get_option('site_icon'),
					'post_name__in' => $names,
				)
			);
			if ( ! empty($parent) ) {
				$url = wp_get_attachment_image_url($parent[0]->ID, $size);
			}
		}
		return $url;
	}
}

if ( ! function_exists('ht_post_type_archive_title') ) {
	function ht_post_type_archive_title( $prefix = '', $display = true, $post_type = null ) {
		// An expanded version of https://developer.wordpress.org/reference/functions/post_type_archive_title/
		if ( empty($post_type) ) {
			return post_type_archive_title($prefix, $display);
		}
		static $_results = array();
		if ( ! array_key_exists( $post_type, $_results) ) {
			$_results[ $post_type ] = false;
			if ( post_type_exists($post_type) ) {
				if ( $post_type_archive = get_post_post_type_archive($post_type) ) {
					$_results[ $post_type ] = get_the_title($post_type_archive);
				} elseif ( $post_type_object = get_post_type_object($post_type) ) {
					$_results[ $post_type ] = $post_type_object->labels->name;
				}
				$_results[ $post_type ] = apply_filters('post_type_archive_title', $_results[ $post_type ], $post_type);
			}
		}
		if ( $_results[ $post_type ] ) {
			if ( $display ) {
				echo $prefix . $_results[ $post_type ];
			} else {
				return $prefix . $_results[ $post_type ];
			}
		}
	}
}

if ( ! function_exists('is_login_page') ) {
	function is_login_page() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			$wp_login = defined('WP_LOGIN_SCRIPT') ? ltrim(WP_LOGIN_SCRIPT, ' /') : 'wp-login.php';
			if ( isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === $wp_login ) {
				$_result = true;
			} elseif ( isset($_SERVER['PHP_SELF']) && str_contains(wp_unslash($_SERVER['PHP_SELF']), $wp_login) ) {
				$_result = true;
			} elseif ( in_array(path_join(ABSPATH, $wp_login), get_included_files()) ) {
				$_result = true;
			} elseif ( function_exists('wp_login_url') && wp_login_url() === get_current_url() ) {
				$_result = true;
			}
		}
		return $_result;
	}
}

if ( ! function_exists('is_signup_page') ) {
	function is_signup_page() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			// wp-register.php only for backward compatibility.
			if ( isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-signup.php' ) {
				$_result = true;
			} elseif ( isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-register.php' ) {
				$_result = true;
			} elseif ( isset($_SERVER['PHP_SELF']) && str_contains(wp_unslash($_SERVER['PHP_SELF']), 'wp-signup.php') ) {
				$_result = true;
			} elseif ( isset($_SERVER['PHP_SELF']) && str_contains(wp_unslash($_SERVER['PHP_SELF']), 'wp-register.php') ) {
				$_result = true;
			} elseif ( in_array(path_join(ABSPATH, 'wp-signup.php'), get_included_files()) ) {
				$_result = true;
			} elseif ( in_array(path_join(ABSPATH, 'wp-register.php'), get_included_files()) ) {
				$_result = true;
			} elseif ( function_exists('wp_registration_url') && wp_registration_url() === get_current_url() ) {
				$_result = true;
			}
		}
		return $_result;
	}
}
