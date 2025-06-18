<?php
if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'functions-wp.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'functions-wp.php';
}

// Everything referenced in wp-admin/*/*.php

if ( ! function_exists('admin_download_functions_loaded') ) {
	function admin_download_functions_loaded() {
		if ( ! function_exists('wp_tempnam') ) {
			require_once path_join(ABSPATH, 'wp-admin/includes/file.php');
		}
		if ( ! function_exists('media_handle_sideload') ) {
			require_once path_join(ABSPATH, 'wp-admin/includes/media.php');
		}
		if ( ! function_exists('wp_read_image_metadata') ) {
			require_once path_join(ABSPATH, 'wp-admin/includes/image.php');
		}
		return function_exists('wp_tempnam') && function_exists('media_handle_sideload') && function_exists('wp_read_image_metadata');
	}
}

if ( ! function_exists('admin_is_edit_screen') ) {
	function admin_is_edit_screen( $post_types = array() ) {
		$post_types = empty($post_types) ? get_post_types(array(), 'names') : make_array($post_types);
		if ( $current_post_type = get_post_type() ) {
			if ( isset($_REQUEST['action']) && $_REQUEST['action'] === 'edit' ) {
				if ( in_array($current_post_type, $post_types) ) {
					return true;
				}
			}
		}
		if ( $current_screen_id = get_current_screen_id() ) {
			if ( str_starts_with($current_screen_id, 'edit-') ) {
				foreach ( $post_types as $post_type ) {
					if ( $current_screen_id === 'edit-' . $post_type ) {
						return true;
					}
				}
			}
			if ( in_array($current_screen_id, $post_types) ) {
				if ( isset($GLOBALS['pagenow']) && str_starts_with($GLOBALS['pagenow'], $current_screen_id) ) {
					return true;
				}
				if ( isset($GLOBALS['typenow']) && in_array($GLOBALS['typenow'], $post_types) ) {
					return true;
				}
			}
		}
		return false;
	}
}

if ( ! function_exists('admin_is_menu_page') ) {
	function admin_is_menu_page( $page ) {
		if ( is_public() ) {
			return false;
		}
		if ( $current_screen_id = get_current_screen_id() ) {
			if ( str_contains($current_screen_id, $page) ) {
				return true;
			}
		}
		if ( isset($_SERVER['QUERY_STRING']) ) {
			if ( str_contains(wp_unslash($_SERVER['QUERY_STRING']), $page) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists('admin_menu_arrange') ) {
	function admin_menu_arrange( $menu_slugs = array() ) {
		$result = false;
		if ( ! isset($GLOBALS['menu']) ) {
			return $result;
		}
		if ( ! is_array($GLOBALS['menu']) ) {
			return $result;
		}
		if ( is_public() ) {
			return $result;
		}
		$menu_new = $submenu_new = array();
		$has_submenu = isset($GLOBALS['submenu']) && is_array($GLOBALS['submenu']);
		$menu_positions = array_keys($menu_slugs);
		foreach ( $GLOBALS['menu'] as $value ) {
			// array key 2 most exact - separators, plugins, etc.
			if ( ! array_key_exists($value[2], $menu_slugs) ) {
				ht_remove_menu_page($value[2]);
				continue;
			}
			$i = array_search($value[2], $menu_positions, true);
			$menu_new[ $i ] = $value;
			// submenus.
			if ( ! $has_submenu ) {
				continue;
			}
			$key = $value[2];
			if ( ! isset($GLOBALS['submenu'][ $key ]) ) {
				continue;
			}
			if ( empty($menu_slugs[ $key ]) ) {
				foreach ( $GLOBALS['submenu'][ $key ] as $subvalue ) {
					remove_submenu_page($key, $subvalue[2]);
				}
			} elseif ( $menu_slugs[ $key ] === '*' ) {
				$submenu_new[ $key ] = $GLOBALS['submenu'][ $key ];
			} else {
				$menu_slugs[ $key ] = make_array($menu_slugs[ $key ]);
				foreach ( $GLOBALS['submenu'][ $key ] as $subvalue ) {
					if ( in_array($subvalue[2], $menu_slugs[ $key ]) ) {
						$j = array_search($subvalue[2], $menu_slugs[ $key ], true);
						if ( is_numeric($j) ) {
							if ( ! isset($submenu_new[ $key ]) ) {
								$submenu_new[ $key ] = array();
							}
							$submenu_new[ $key ][ $j ] = $subvalue;
						}
					} else {
						remove_submenu_page($key, $subvalue[2]);
					}
				}
			}
		}
		if ( ! empty($menu_new) ) {
			ksort($menu_new);
			$GLOBALS['menu'] = $menu_new;
			$result = true;
			if ( ! empty($submenu_new) ) {
				foreach ( $submenu_new as $key => $value ) {
					ksort($submenu_new[ $key ]);
				}
				$GLOBALS['submenu'] = $submenu_new;
			}
		}
		return $result;
	}
}

if ( ! function_exists('attachment_change_filename') ) {
	function attachment_change_filename( $attachment_id, $filename ) {
		if ( ht_get_post_type($attachment_id) !== 'attachment' ) {
			return false;
		}
		$path = get_attachment_path($attachment_id);
		if ( ! $path ) {
			return false;
		}
		$filename = sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION));
		if ( in_array($filename, array( ht_basename($path), pathinfo($path, PATHINFO_FILENAME) )) ) {
			// no change.
			return false;
		}
		$destination = path_join(ht_dirname($path), wp_unique_filename(ht_dirname($path), $filename));
		return attachment_move($attachment_id, $destination) ? $destination : false;
	}
}

if ( ! function_exists('attachment_move') ) {
	function attachment_move( $attachment_id, $destination, $overwrite = false, &$posts_updated = array() ) {
		if ( ht_get_post_type($attachment_id) !== 'attachment' ) {
			return false;
		}
		$post_old = get_post($attachment_id);
		if ( empty($post_old) ) {
			return false;
		}
		$path_old = get_attachment_path($attachment_id);
		if ( ! $path_old ) {
			return false;
		}
		$wp_filesystem = function_exists('ht_wp_filesystem') ? ht_wp_filesystem('direct') : WP_Filesystem();
		if ( ! $wp_filesystem ) {
			return false;
		}

		// 1. get a list of all files to move, sources + destinations.
		// 2. try to copy them to destination, update db.
		// 3. if any fail to copy, or db errors, roll back changes and delete new files.
		// 4. on success delete old files, return a list of updated posts.

		$files = array();
		$upload_dir = wp_get_upload_dir();

		// functions.
		$get_file_path = function ( $path, $exists = false ) use ( $upload_dir ) {
			if ( empty($path) || ! is_string($path) ) {
				return false;
			}
			$path = str_starts_with($path, $upload_dir['basedir']) ? $path : path_join($upload_dir['basedir'], $path);
			return ( $exists && ! file_exists($path) ) ? false : $path;
		};
		$get_relative_upload_path = function ( $path ) use ( $upload_dir ) {
			if ( function_exists('_wp_relative_upload_path') ) {
				return _wp_relative_upload_path($path);
			}
			if ( str_starts_with($path, $upload_dir['basedir']) ) {
				$path = str_replace_start($upload_dir['basedir'], '', $path);
				$path = ltrim($path, DIRECTORY_SEPARATOR);
			}
			return $path;
		};
		$get_relative_upload_dir = function ( $path ) use ( $get_relative_upload_path ) {
			$dir = ht_dirname($get_relative_upload_path($path));
			if ( $dir === '.' || $dir === '..' ) {
				return '';
			}
			return trim($dir, DIRECTORY_SEPARATOR);
		};
		$get_destination_filename = function ( $source, $files ) {
			$filename = ht_basename($source);
			if ( ! isset($files['original'], $files['original']['source_base'], $files['original']['destination_base']) ) {
				return $filename;
			}
			if ( $filename === $files['original']['source_base'] ) {
				$filename = $files['original']['destination_base'];
			} elseif ( preg_match_all('/^' . pathinfo($files['original']['source_base'], PATHINFO_FILENAME) . '(\-[\w]+)\.' . pathinfo($source, PATHINFO_EXTENSION) . '$/s', $filename, $matches) ) {
				if ( isset($matches[1][0]) ) {
					$filename = pathinfo($files['original']['destination_base'], PATHINFO_FILENAME) . $matches[1][0] . '.' . pathinfo($files['original']['destination_base'], PATHINFO_EXTENSION);
				}
			}
			return $filename;
		};

		// get sources + destinations.
		$destination = path_join(ht_dirname($destination), sanitize_file_name(ht_basename($destination)));
		$path_new = $get_file_path($destination);
		if ( ! $path_new ) {
			return false;
		}
		$files = array(
			'original' => array(
				'source' => $path_old,
				'source_dir' => ht_dirname($path_old),
				'source_base' => ht_basename($path_old),
				'destination' => $path_new,
				'destination_dir' => ht_dirname($path_new),
				'destination_base' => ht_basename($path_new),
			),
		);
		// could be scaled, edited - don't use 'get_attached_file', returns double slashes.
		if ( $tmp = get_post_meta($attachment_id, '_wp_attached_file', true) ) {
			$post_old->_wp_attached_file = $tmp;
			if ( $source = $get_file_path($tmp, true) ) {
				$dest = path_join($files['original']['destination_dir'], $get_destination_filename($source, $files));
				$files['_wp_attached_file'] = array(
					'source' => $source,
					'destination' => $dest,
				);
			}
		}
		// other sizes.
		if ( $array = wp_get_attachment_metadata($attachment_id) ) {
			$post_old->_wp_attachment_metadata = $array;
			$source_dir = $upload_dir['basedir'];
			if ( isset($array['file']) ) {
				$source_dir = path_join($source_dir, $get_relative_upload_dir($array['file']));
				// use for files below.
				if ( $source = $get_file_path($array['file'], true) ) {
					$dest = path_join($files['original']['destination_dir'], $get_destination_filename($source, $files));
					$files['_wp_attachment_metadata__file'] = array(
						'source' => $source,
						'destination' => $dest,
					);
				}
			}
			if ( isset($array['original_image']) ) {
				if ( $source = $get_file_path(path_join($source_dir, $array['original_image']), true) ) {
					$dest = path_join($files['original']['destination_dir'], $get_destination_filename($source, $files));
					$files['_wp_attachment_metadata__original_image'] = array(
						'source' => $source,
						'destination' => $dest,
					);
				}
			}
			if ( isset($array['sizes']) ) {
				foreach ( $array['sizes'] as $size => $a ) {
					if ( isset($a['file']) ) {
						if ( $source = $get_file_path(path_join($source_dir, $a['file']), true) ) {
							$dest = path_join($files['original']['destination_dir'], $get_destination_filename($source, $files));
							$files[ '_wp_attachment_metadata__sizes__' . $size ] = array(
								'source' => $source,
								'destination' => $dest,
							);
						}
					}
				}
			}
		}

		// mkdir.
		if ( ! $wp_filesystem->is_dir($files['original']['destination_dir']) ) {
			if ( ! $wp_filesystem->mkdir($files['original']['destination_dir'], FS_CHMOD_DIR) ) {
				if ( ! wp_mkdir_p($files['original']['destination_dir']) ) {
					return false;
				}
			}
		}

		// copy.
		$files_copied = array();
		$error = false;
		foreach ( $files as $array ) {
			if ( $array['source'] === $array['destination'] ) {
				continue;
			}
			if ( array_key_exists($array['source'], $files_copied) ) {
				if ( $files_copied[ $array['source'] ] === $array['destination'] ) {
					continue;
				}
			}
			if ( ! $overwrite && file_exists($array['destination']) ) {
				$files_copied[ $array['source'] ] = $array['destination'];
				continue;
			}
			if ( $wp_filesystem->copy($array['source'], $array['destination'], $overwrite, FS_CHMOD_FILE) ) {
				$files_copied[ $array['source'] ] = $array['destination'];
			} else {
				// If copy failed, chmod file to 0644 and try again.
				$wp_filesystem->chmod($array['source'], FS_CHMOD_FILE);
				$wp_filesystem->chmod($array['destination'], FS_CHMOD_FILE);
				if ( $wp_filesystem->copy($array['source'], $array['destination'], $overwrite, FS_CHMOD_FILE) ) {
					$files_copied[ $array['source'] ] = $array['destination'];
				} else {
					$error = true;
					break;
				}
			}
		}
		// function - rollback.
		$rollback_copy = function () use ( $files_copied, $wp_filesystem ) {
			foreach ( $files_copied as $source => $dest ) {
				$wp_filesystem->delete($dest);
			}
		};
		if ( $error ) {
			$rollback_copy();
			return false;
		}

		// get urls.
		$post_old->permalink = get_permalink($post_old);
		foreach ( $files as $key => $array ) {
			$files[ $key ]['source_url'] = set_url_scheme(str_replace_start(trailingslashit($upload_dir['basedir']), trailingslashit($upload_dir['baseurl']), $array['source']), 'https');
			$files[ $key ]['destination_url'] = set_url_scheme(str_replace_start(trailingslashit($upload_dir['basedir']), trailingslashit($upload_dir['baseurl']), $array['destination']), 'https');
		}

		// update db - posts.
		$post_date = current_time('mysql');
		$postarr = array(
			'ID' => $attachment_id,
			'post_modified' => $post_date,
			'post_modified_gmt' => get_gmt_from_date($post_date),
			'guid' => set_url_scheme($files['original']['destination_url']),
		);
		// update automatic post_title, post_name.
		if ( $files['original']['source_base'] !== $files['original']['destination_base'] ) {
			$replace_pairs = array(
				$files['original']['source_base'] => $files['original']['destination_base'],
				pathinfo($files['original']['source_base'], PATHINFO_FILENAME) => pathinfo($files['original']['destination_base'], PATHINFO_FILENAME),
			);
			$replace_pairs = $replace_pairs + array_combine(str_replace(array( '.', '-', '_' ), ' ', array_keys($replace_pairs)), str_replace(array( '.', '-', '_' ), ' ', $replace_pairs));
			if ( isset($replace_pairs[ $post_old->post_title ]) ) {
				$postarr['post_title'] = strtr($post_old->post_title, $replace_pairs);
			}
			$replace_pairs = array_combine(array_map('sanitize_title', array_keys($replace_pairs)), array_map('sanitize_title', $replace_pairs));
			if ( isset($replace_pairs[ $post_old->post_name ]) ) {
				$postarr['post_name'] = strtr($post_old->post_name, $replace_pairs);
			}
		}
		$result = wp_update_post(wp_slash($postarr), true);
		global $wpdb;
		// this only works when called after 'wp_update_post'.
		$wpdb->update($wpdb->posts, array( 'guid' => $postarr['guid'] ), array( 'ID' => $postarr['ID'] ));
		// function - rollback.
		$rollback_posts = function () use ( $post_old, $postarr, $wpdb ) {
			$array = array();
			foreach ( $postarr as $key => $value ) {
				if ( property_exists($post_old, $key) ) {
					$array[ $key ] = $post_old->$key;
				}
			}
			wp_update_post(wp_slash($array), true);
			$wpdb->update($wpdb->posts, array( 'guid' => $post_old->guid ), array( 'ID' => $post_old->ID ));
		};
		if ( empty($result) || is_wp_error($result) ) {
			$rollback_copy();
			$rollback_posts();
			return false;
		}

		// postmeta - _wp_attached_file.
		$rollback_wp_attached_file = function () use ( $attachment_id, $post_old ) {
			if ( isset($post_old->_wp_attached_file) ) {
				update_post_meta($attachment_id, '_wp_attached_file', $post_old->_wp_attached_file);
			}
		};
		if ( isset($post_old->_wp_attached_file, $files['_wp_attached_file']) ) {
			$result = update_attached_file($attachment_id, $files['_wp_attached_file']['destination']);
			if ( $result === false ) {
				// same values also return false.
				if ( $post_old->_wp_attached_file !== $get_relative_upload_path($files['_wp_attached_file']['destination']) ) {
					$rollback_copy();
					$rollback_posts();
					$rollback_wp_attached_file();
					return false;
				}
			}
		}

		// postmeta - _wp_attachment_metadata.
		$rollback_wp_attachment_metadata = function () use ( $attachment_id, $post_old ) {
			if ( isset($post_old->_wp_attachment_metadata) ) {
				wp_update_attachment_metadata($attachment_id, $post_old->_wp_attachment_metadata);
			}
		};
		if ( isset($post_old->_wp_attachment_metadata) ) {
			$array = $post_old->_wp_attachment_metadata;
			// dir change only in 'file'.
			if ( isset($array['file'], $files['_wp_attachment_metadata__file']) ) {
				$array['file'] = $get_relative_upload_path($files['_wp_attachment_metadata__file']['destination']);
			}
			if ( isset($array['original_image'], $files['_wp_attachment_metadata__original_image']) ) {
				$array['original_image'] = ht_basename($files['_wp_attachment_metadata__original_image']['destination']);
			}
			if ( isset($array['sizes']) ) {
				foreach ( $array['sizes'] as $size => $a ) {
					if ( isset($a['file'], $files[ '_wp_attachment_metadata__sizes__' . $size ]) ) {
						$array['sizes'][ $size ]['file'] = ht_basename($files[ '_wp_attachment_metadata__sizes__' . $size ]['destination']);
					}
				}
			}
			$result = wp_update_attachment_metadata($attachment_id, $array);
			if ( $result === false ) {
				// same values also return false.
				if ( serialize($post_old->_wp_attachment_metadata) !== serialize($array) ) {
					$rollback_copy();
					$rollback_posts();
					$rollback_wp_attached_file();
					$rollback_wp_attachment_metadata();
					return false;
				}
			}
		}

		// file + db operations ok! now delete originals.
		foreach ( $files_copied as $source => $dest ) {
			$wp_filesystem->delete($source);
		}

		$post_new = get_post($attachment_id);
		$post_new->permalink = get_permalink($post_new);

		// replace urls in post_content, post_excerpt.
		$search_basenames = array();
		if ( $post_old->post_name !== $post_new->post_name ) {
			$search_basenames[] = $post_old->post_name;
		}
		if ( $post_old->guid !== $post_new->guid ) {
			$search_basenames[] = ht_basename($post_old->guid);
		}
		if ( $post_old->permalink !== $post_new->permalink ) {
			$search_basenames[] = ht_basename($post_old->permalink);
		}
		foreach ( $files as $array ) {
			if ( $array['source_url'] === $array['destination_url'] ) {
				continue;
			}
			$search_basenames[] = ht_basename($array['source']);
		}
		if ( ! empty($search_basenames) ) {
			$search_basenames = array_unique($search_basenames);
			$search = array();
			foreach ( $search_basenames as $value ) {
				$search[] = $wpdb->prepare('%i LIKE %s', 'post_content', '%' . $wpdb->esc_like($value) . '%');
				$search[] = $wpdb->prepare('%i LIKE %s', 'post_excerpt', '%' . $wpdb->esc_like($value) . '%');
			}
			$post_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE %i NOT IN (%s, %s) AND ", 'post_type', 'attachment', 'revision') . '(' . implode(' OR ', $search) . ')');
			if ( ! empty($post_ids) ) {
				$args = array(
					'post_type' => 'any',
					'post_status' => 'any',
					'numberposts' => -1,
					'nopaging' => true,
					'orderby' => 'date',
					'order' => 'ASC',
					'include' => $post_ids,
				);
				if ( $posts = ht_get_posts($args) ) {
					// collect replacements.
					$replace_pairs = array();
					if ( $post_old->guid !== $post_new->guid ) {
						$k = set_url_scheme($post_old->guid, 'https');
						$replace_pairs[ $k ] = set_url_scheme($post_new->guid, 'https');
					}
					if ( $post_old->permalink !== $post_new->permalink ) {
						$k = set_url_scheme($post_old->permalink, 'https');
						$replace_pairs[ $k ] = set_url_scheme($post_new->permalink, 'https');
					}
					foreach ( $files as $array ) {
						if ( $array['source_url'] === $array['destination_url'] ) {
							continue;
						}
						if ( isset($replace_pairs[ $array['source_url'] ]) ) {
							continue;
						}
						$replace_pairs[ $array['source_url'] ] = $array['destination_url'];
					}
					if ( ! empty($replace_pairs) ) {
						$replace_pairs = array_unique($replace_pairs);
						$callback = function ( $value ) {
							return set_url_scheme($value, 'http');
						};
						$replace_pairs = $replace_pairs + array_combine(array_map($callback, array_keys($replace_pairs)), array_map($callback, $replace_pairs));
						foreach ( $posts as $post ) {
							$post_content = strtr($post->post_content, $replace_pairs);
							$post_excerpt = strtr($post->post_excerpt, $replace_pairs);
							if ( $post->post_content !== $post_content || $post->post_excerpt !== $post_excerpt ) {
								$postarr = array(
									'ID' => $post->ID,
									'post_modified' => $post_date,
									'post_modified_gmt' => get_gmt_from_date($post_date),
									'post_content' => $post_content,
									'post_excerpt' => $post_excerpt,
								);
								$result = wp_update_post(wp_slash($postarr), true);
								if ( ! empty($result) && ! is_wp_error($result) ) {
									$posts_updated[] = $post->ID;
								}
							}
						}
					}
				}
			}
		}

		// find additional affected posts.
		// - parent.
		if ( ! empty($post_new->post_parent) ) {
			$posts_updated[] = $post_new->post_parent;
		}
		// - thumbnails.
		$post_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_thumbnail_id' AND meta_value = %d", $attachment_id));
		if ( ! empty($post_ids) ) {
			$posts_updated = array_merge($posts_updated, $post_ids);
		}
		// - galleries.
		if ( shortcode_exists('gallery') ) {
			$post_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_content LIKE %s AND post_content LIKE %s", '%' . $wpdb->esc_like('[gallery') . '%', '%' . $wpdb->esc_like($attachment_id) . '%'));
			if ( ! empty($post_ids) ) {
				$args = array(
					'post_type' => 'any',
					'post_status' => 'any',
					'numberposts' => -1,
					'nopaging' => true,
					'orderby' => 'date',
					'order' => 'ASC',
					'include' => $post_ids,
				);
				if ( $posts = ht_get_posts($args) ) {
					foreach ( $posts as $post ) {
						$galleries = get_post_galleries($post, false);
						if ( empty($galleries) ) {
							continue;
						}
						foreach ( $galleries as $value ) {
							if ( isset($value['ids']) ) {
								if ( in_array_int($attachment_id, make_array($value['ids'])) ) {
									$posts_updated[] = $post->ID;
									break;
								}
							}
						}
					}
				}
			}
		}
		// send them to an action for cache update, etc.
		if ( ! empty($posts_updated) ) {
			$posts_updated = array_values(array_unique($posts_updated));
			do_action('attachment_move_posts_updated', $posts_updated, $attachment_id);
		}
		return true;
	}
}

if ( ! function_exists('get_current_screen_id') ) {
	function get_current_screen_id() {
		static $_result = null;
		if ( is_null($_result) ) {
			if ( function_exists('get_current_screen') ) {
				$tmp = get_current_screen();
				if ( is_object($tmp) && isset($tmp->id) ) {
					$_result = $tmp->id;
				}
			}
		}
		$result = $_result;
		if ( empty($result) && isset($GLOBALS['pagenow']) ) {
			$result = pathinfo($GLOBALS['pagenow'], PATHINFO_FILENAME);
		}
		return $result;
	}
}

if ( ! function_exists('ht_remove_menu_page') ) {
	function ht_remove_menu_page( $menu_slug ) {
		$result = false;
		if ( ! isset($GLOBALS['menu']) ) {
			return $result;
		}
		if ( ! is_array($GLOBALS['menu']) ) {
			return $result;
		}
		foreach ( $GLOBALS['menu'] as $value ) {
			// array key 2 most exact - separators, plugins, etc.
			if ( $value[2] === $menu_slug ) {
				// remove all.
				remove_menu_page($value[2]);
				if ( isset($GLOBALS['submenu'], $GLOBALS['submenu'][ $value[2] ]) ) {
					foreach ( $GLOBALS['submenu'][ $value[2] ] as $subvalue ) {
						remove_submenu_page($value[2], $subvalue[2]);
					}
				}
				$result = true;
				break;
			}
		}
		return $result;
	}
}

if ( ! function_exists('ht_wp_filesystem') ) {
	function ht_wp_filesystem( $method = '' ) {
		global $wp_filesystem;
		if ( is_object($wp_filesystem) ) {
			if ( empty($method) || $method === $wp_filesystem->method ) {
				return $wp_filesystem;
			}
		}
		if ( ! function_exists('WP_Filesystem') && is_readable(ABSPATH . 'wp-admin/includes/file.php') ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		// overwrite function 'get_filesystem_method'.
		if ( ! empty($method) ) {
			$do_filter = false;
			if ( ! defined('FS_METHOD') ) {
				$do_filter = true;
			} elseif ( FS_METHOD !== $method ) {
				$do_filter = true;
			}
			if ( $do_filter ) {
				$callback = function ( $method_filter = '', $args = array(), $context = '', $allow_relaxed_file_ownership = false ) use ( $method ) {
					return $method;
				};
				add_filter('filesystem_method', $callback, 10, 4);
			}
		}
		// credentials?
		$args = false;
		if ( function_exists('request_filesystem_credentials') ) {
			$tmp = request_filesystem_credentials(false, $method);
			$args = is_array($tmp) ? $tmp : $args;
		}
		WP_Filesystem($args);
		return $wp_filesystem;
	}
}
