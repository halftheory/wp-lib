<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Search_Fuzzysort extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $fuzzysort_options = array(), $search_data_args = array(), $search_data_posts_args = array() ) {
		$defaults = array(
			'all' => false,
			'key' => 'title',
			'limit' => 10,
			'threshold' => 0.5,
		);
		$this->data['fuzzysort_options'] = wp_parse_args($fuzzysort_options, $defaults);

		$defaults = array(
			'authors' => false,
			'feeds' => false,
			'links' => false,
			'posts' => true,
			'terms' => true,
		);
		$this->data['search_data_args'] = wp_parse_args($search_data_args, $defaults);

		$this->data['search_data_posts_args'] = $search_data_posts_args;

		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_filter('upload_mimes', array( $this, 'global_upload_mimes' ), 90);
		if ( is_public() ) {
			// Public.
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
			add_action('wp_print_footer_scripts', array( $this, 'public_wp_print_footer_scripts' ), 90);
		} else {
			// Admin.
			add_action('delete_attachment', array( $this, 'admin_delete_attachment' ), 20, 2);
			// Trigger update of search file.
			// Authors.
			add_action('profile_update', array( $this, 'admin_profile_update' ), 20, 3);
			add_action('deleted_user', array( $this, 'admin_deleted_user' ), 20, 3);
			// Links.
			add_action('add_link', array( $this, 'admin_add_link' ), 20);
			add_action('edit_link', array( $this, 'admin_add_link' ), 20);
			add_action('deleted_link', array( $this, 'admin_add_link' ), 20);
			// Media.
			add_action('add_attachment', array( $this, 'admin_add_attachment' ), 20);
			add_action('attachment_updated', array( $this, 'admin_attachment_updated' ), 20, 3);
			// Posts.
			add_action('save_post', array( $this, 'admin_save_post' ), 20, 3);
			add_action('deleted_post', array( $this, 'admin_deleted_post' ), 20, 2);
			// Terms.
			add_action('edited_term_taxonomy', array( $this, 'admin_edited_term_taxonomy' ), 20, 3);
			add_action('delete_term', array( $this, 'admin_delete_term' ), 20, 5);
		}
		parent::autoload();
	}

	// Global.

	public function global_upload_mimes( $mimes ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $mimes;
		}
		$array = array(
			'json' => 'application/json',
		);
		foreach ( $array as $key => $value ) {
			if ( ! isset($mimes[ $key ]) ) {
				$mimes[ $key ] = $value;
			}
		}
		return $mimes;
	}

	// Public.

	public function public_pre_get_posts( $query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( did_filter('request') === 0 ) {
			return;
		}
		// Hide the json file from public eyes.
		if ( $id = $this->get_json_id() ) {
			$tmp = make_array($query->get('post__not_in'));
			$tmp[] = $id;
			$query->set('post__not_in', array_values(array_unique($tmp)));
		}
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// JSON.
		$json_url = $this->get_json_url();
		if ( ! $json_url ) {
			return;
		}
		// CSS.
		wp_enqueue_style('dashicons');
		$file = __DIR__ . '/assets/css/search-fuzzysort.css';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_style(static::$handle, $url, array(), get_file_version($file), 'screen');
		}
		// JS.
		$array = array(
			'package' => 'fuzzysort',
			'version' => '3.1.0',
		);
		$fallback = __DIR__ . '/assets/dist/fuzzysort/fuzzysort' . min_scripts() . '.js';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'fuzzysort' . min_scripts() . '.js' ), $fallback) ) {
			wp_enqueue_script($array['package'], $url, array(), $array['version'], true);
		}
		$file = __DIR__ . '/assets/js/search-fuzzysort' . min_scripts() . '.js';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_script(static::$handle, $url, array( 'jquery', $array['package'] ), get_file_version($file), true);
			// Format data.
			$data = array(
				'selector' => 'input.search-field',
				'jsonUrl' => $json_url,
				'searchUrl' => ht_get_search_link(),
				'options' => $this->data['fuzzysort_options'],
				'__' => array(
					'viewAllResults' => __('View all results'),
				),
			);
			wp_localize_script(static::$handle, 'search_fuzzysort', $data);
		}
	}

	public function public_wp_print_footer_scripts() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Don't use the 'shutdown' action, as it seems to always fire twice.
		if ( did_action(current_filter()) > 1 ) {
			return;
		}
		if ( $value = get_transient(static::$handle) ) {
			if ( is_numeric($value) && time() > $value ) {
				if ( $this->update_search_file() ) {
					delete_transient(static::$handle);
				}
			}
		}
	}

	// Admin.

	public function admin_delete_attachment( $post_id, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( $this->get_json_id() === (int) $post_id ) {
			delete_option(static::$handle . '_json');
			delete_transient(static::$handle);
			self::remove_filter('admin_deleted_post');
		}
	}

	public function admin_profile_update( $user_id, $old_user_data, $userdata ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_deleted_user( $id, $reassign, $user ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_add_link( $link_id ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_add_attachment( $post_id ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_attachment_updated( $post_id, $post_after, $post_before ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_save_post( $post_id, $post, $update ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_deleted_post( $post_id, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_edited_term_taxonomy( $tt_id, $taxonomy, $args = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}
	public function admin_delete_term( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_transient();
	}

	// Functions.

	public function get_json_id() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( $tmp = get_option(static::$handle . '_json') ) {
				$_result = absint($tmp);
			}
		}
		return $_result;
	}

	public function get_json_path() {
		if ( $id = $this->get_json_id() ) {
			return get_attachment_path($id);
		}
		return false;
	}

	public function get_json_url() {
		if ( $id = $this->get_json_id() ) {
			return wp_get_attachment_url($id);
		}
		return false;
	}

	public function get_search_data( $args = null, $format = 'json', $posts_args = null ) {
		$args = is_array($args) ? $args : $this->data['search_data_args'];

		$func_format_item = function ( $value ) {
			$item = array(
				'title' => '',
				'url' => '',
				'types' => array(),
			);
			$value = wp_parse_args($value, $item);
			$value['title'] = unwptexturize($value['title']);
			$value['url'] = esc_url($value['url']);
			$value['types'] = array_map('unwptexturize', $value['types']);
			// Resolve duplicate titles.
			static $_titles = array();
			if ( ! in_array($value['title'], $_titles) ) {
				array_push($_titles, $value['title']);
				return $value;
			}
			if ( ! empty($value['types']) ) {
				foreach ( array_reverse($value['types']) as $type ) {
					$title = $value['title'] . ' (' . $type . ')';
					if ( ! in_array($title, $_titles) ) {
						$value['title'] = $title;
						array_push($_titles, $title);
						break;
					}
				}
			}
			return $value;
		};

		// Collect links.
		$array = array();
		foreach ( $args as $key => $value ) {
			if ( ! $value ) {
				continue;
			}
			switch ( $key ) {
				case 'authors':
					$author_args = array(
						'orderby' => 'display_name',
						'order' => 'ASC',
						'fields' => 'ids',
					);
					$authors = get_users($author_args);
					if ( ! $authors ) {
						break;
					}
					foreach ( $authors as $author_id ) {
						$author = get_userdata($author_id);
						$url = get_author_posts_url($author->ID, $author->user_nicename);
						if ( empty($url) ) {
							continue;
						}
						$tmp = array(
							'title' => $author->display_name,
							'url' => $url,
							'types' => array( __('Author') ),
						);
						$array[] = $func_format_item($tmp);
					}
					break;

				case 'feeds':
					if ( ! current_theme_supports('automatic-feed-links') ) {
						break;
					}
					$feeds_args = array(
						/* translators: Separator between site name and feed type in feed links. */
						'separator' => _x('&raquo;', 'feed link'),
						/* translators: 1: Site title, 2: Separator (raquo). */
						'feedtitle' => __('%1$s %2$s Feed'),
						/* translators: 1: Site title, 2: Separator (raquo). */
						'comstitle' => __('%1$s %2$s Comments Feed'),
					);
					$feeds_args = apply_filters('feed_links_args', $feeds_args);
					if ( apply_filters('feed_links_show_posts_feed', true) ) {
						$tmp = array(
							'title' => sprintf($feeds_args['feedtitle'], get_bloginfo('name'), $feeds_args['separator']),
							'url' => get_feed_link(),
							'types' => array( __('Feed') ),
						);
						$array[] = $func_format_item($tmp);
					}
					if ( apply_filters('feed_links_show_comments_feed', true) ) {
						$tmp = array(
							'title' => sprintf($feeds_args['comstitle'], get_bloginfo('name'), $feeds_args['separator']),
							'url' => get_feed_link('comments_' . get_default_feed()),
							'types' => array( __('Feed') ),
						);
						$array[] = $func_format_item($tmp);
					}
					break;

				case 'links':
					$links_args = array(
						'orderby' => 'name',
						'order' => 'ASC',
						'limit' => -1,
						'hide_invisible' => true,
					);
					$links = get_bookmarks($links_args);
					if ( ! $links ) {
						break;
					}
					$link_category = taxonomy_exists('link_category');
					foreach ( $links as $link ) {
						$tmp = array(
							'title' => $link->link_name,
							'url' => $link->link_url,
							'types' => array( __('Link') ),
						);
						if ( $link_category ) {
							if ( $link_cats = wp_get_link_cats($link->link_id) ) {
								foreach ( $link_cats as $term ) {
									if ( ! is_term_publicly_viewable($term) ) {
										continue;
									}
									$tmp['types'][] = get_single_term_title($term);
								}
							}
						}
						$array[] = $func_format_item($tmp);
					}
					break;

				case 'posts':
					$posts_args = is_array($posts_args) ? $posts_args : $this->data['search_data_posts_args'];
					$posts_args = array_diff_key($posts_args, array( 'fields' => null, 'nopaging' => null ));
					$defaults = array(
						'post_type' => array_values(array_value_unset(get_post_types(array( 'public' => true ), 'names'), 'attachment')),
						'fields' => 'ids',
						'nopaging' => true,
						'orderby' => 'title',
						'order' => 'ASC',
					);
					$posts_args = wp_parse_args($posts_args, $defaults);
					if ( $id = $this->get_json_id() ) {
						$posts_args['exclude'] = isset($posts_args['exclude']) ? array_merge(make_array($posts_args['exclude']), array( $id )) : array( $id );
					}
					$posts = ht_get_posts($posts_args);
					if ( ! $posts ) {
						break;
					}
					$post_type_labels = array();
					$taxonomies = get_taxonomies(array( 'public' => true ), 'names');
					foreach ( $posts as $post_id ) {
						if ( ! is_post_publicly_viewable($post_id) ) {
							continue;
						}
						$url = get_permalink($post_id);
						if ( empty($url) ) {
							continue;
						}
						$tmp = array(
							'title' => get_the_title($post_id),
							'url' => $url,
							'types' => array( __('Post') ),
						);
						if ( $post_type = ht_get_post_type($post_id) ) {
							if ( isset($post_type_labels[ $post_type ]) ) {
								$tmp['types'][] = $post_type_labels[ $post_type ];
							} elseif ( $post_type_obj = get_post_type_object($post_type) ) {
								$post_type_label = apply_filters('post_type_archive_title', $post_type_obj->labels->singular_name, $post_type);
								if ( ! empty($post_type_label) ) {
									$post_type_labels[ $post_type ] = $post_type_label;
									$tmp['types'][] = $post_type_label;
								}
							}
						}
						$post_terms = wp_get_post_terms($post_id, $taxonomies);
						if ( ! empty($post_terms) && ! is_wp_error($post_terms) ) {
							foreach ( $post_terms as $term ) {
								if ( ! is_term_publicly_viewable($term) ) {
									continue;
								}
								$tmp['types'][] = get_single_term_title($term);
							}
						}
						$array[] = $func_format_item($tmp);
					}
					break;

				case 'terms':
					$terms_args = array(
						'taxonomy' => get_taxonomies(array( 'public' => true ), 'names'),
						'fields' => 'ids',
						'orderby' => 'name',
						'order' => 'ASC',
					);
					$terms = get_terms($terms_args);
					if ( ! $terms || is_wp_error($terms) ) {
						break;
					}
					$taxonomy_labels = array();
					foreach ( $terms as $term_id ) {
						if ( ! is_term_publicly_viewable($term_id) ) {
							continue;
						}
						$url = get_term_link($term_id);
						if ( empty($url) || is_wp_error($url) ) {
							continue;
						}
						$term = get_term($term_id);
						if ( empty($term) || is_wp_error($term) ) {
							continue;
						}
						$tmp = array(
							'title' => get_single_term_title($term),
							'url' => $url,
							'types' => array( __('Term') ),
						);
						if ( isset($term->taxonomy) ) {
							if ( isset($taxonomy_labels[ $term->taxonomy ]) ) {
								$tmp['types'][] = $taxonomy_labels[ $term->taxonomy ];
							} elseif ( $taxonomy_obj = get_taxonomy($term->taxonomy) ) {
								$labels = apply_filters('taxonomy_labels_' . $term->taxonomy, $taxonomy_obj->labels);
								$taxonomy_label = $labels->singular_name;
								if ( ! empty($taxonomy_label) ) {
									$taxonomy_labels[ $post_type ] = $taxonomy_label;
									$tmp['types'][] = $taxonomy_label;
								}
							}
						}
						$array[] = $func_format_item($tmp);
					}
					break;

				default:
					break;
			}
		}
		if ( empty($array) ) {
			return false;
		}
		$array = wp_list_sort($array, 'title');

		// Format the results.
		$result = '';
		switch ( $format ) {
			case 'csv':
				$func = function ( $value ) {
					return '"' . addcslashes($value, '"') . '"';
				};
				$result .= $func(__('Title')) . ',' . $func(__('URL')) . ',' . $func(__('Types')) . "\n";
				foreach ( $array as $value ) {
					$result .= $func($value['title']) . ',' . $func($value['url']) . ',' . $func(implode(',', $value['types'])) . "\n";
				}
				break;

			case 'json':
				$result = json_encode($array);
				$result = str_replace(']},{"title":"', "]},\n{\"title\":\"", $result);
				break;

			case 'serialize':
				$result = serialize($array);
				$result = str_replace('}}i:', "}}\ni:", $result);
				break;

			case 'text':
				$sep = __('|||');
				foreach ( $array as $value ) {
					$result .= $value['title'] . $sep . $value['url'] . "\n";
				}
				break;

			default:
				break;
		}
		return empty($result) ? false : $result;
	}

	public function set_transient( $minutes = 5 ) {
		set_transient(static::$handle, time() + ( MINUTE_IN_SECONDS * absint($minutes) ));
	}

	public function update_search_file() {
		$data = $this->get_search_data($this->data['search_data_args']);
		if ( ! $data ) {
			return false;
		}
		$this->load_functions('wp-admin');
		// Update.
		if ( $filename = $this->get_json_path() ) {
			$success = ht_wp_filesystem('direct')->put_contents($filename, $data);
			unset($data);
			if ( $success ) {
				$post_date = current_time('mysql');
				$postarr = array(
					'ID' => $this->get_json_id(),
					'post_modified' => $post_date,
					'post_modified_gmt' => get_gmt_from_date($post_date),
				);
				self::remove_filter('admin_attachment_updated');
				wp_update_post(wp_slash($postarr), true);
				self::add_filter('admin_attachment_updated');
			}
			return $success ? $filename : false;
		}
		// New file.
		if ( ! admin_download_functions_loaded() ) {
			return false;
		}
		// 1. Create tmp file.
		$basename = static::$handle . '.json';
		$tmpfname = wp_tempnam($basename);
		if ( ! $tmpfname || is_wp_error($tmpfname) ) {
			if ( file_exists($tmpfname) ) {
				@unlink($tmpfname);
			}
			return false;
		}
		// 2. Put contents.
		$success = ht_wp_filesystem('direct')->put_contents($tmpfname, $data);
		unset($data);
		if ( ! $success || is_wp_error($success) ) {
			if ( file_exists($tmpfname) ) {
				@unlink($tmpfname);
			}
			return false;
		}
		// 3. Move to WP media library.
		$file_array = array(
			'name' => $basename,
			'tmp_name' => $tmpfname,
		);
		if ( $tmp = email_exists(get_option('admin_email')) ) {
			$post_author = $tmp;
		} else {
			$post_author = get_current_user_id();
		}
		$post_data = array(
			'post_author' => $post_author,
			'post_excerpt' => __('System file: helper "search-fuzzysort".'),
			'post_date' => current_time('mysql'), // Ensures the file is uploaded into the correct year/month folder.
		);
		$id = media_handle_sideload($file_array, 0, $basename, $post_data);
		if ( ! $id || is_wp_error($id) ) {
			if ( file_exists($tmpfname) ) {
				@unlink($tmpfname);
			}
			return false;
		}
		// 4. Record the ID for later updates.
		update_option(static::$handle . '_json', $id, false);
		return get_attachment_path($id);
	}
}
