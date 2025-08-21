<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Media_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('after_setup_theme', array( $this, 'global_after_setup_theme' ), 20);
		add_filter('big_image_size_threshold', array( $this, 'global_big_image_size_threshold' ), 10, 4);
		add_filter('intermediate_image_sizes', array( $this, 'global_intermediate_image_sizes' ));
		add_filter('image_downsize', array( $this, 'global_image_downsize' ), 10, 3);
		add_filter('upload_mimes', array( $this, 'global_upload_mimes' ), 90, 2);
		if ( is_public() ) {
			// Public.
			remove_action('wp_head', 'wp_print_auto_sizes_contain_css_fix', 1);
			add_filter('img_caption_shortcode_width', '__return_zero');
			add_filter('wp_get_attachment_url', array( $this, 'public_wp_get_attachment_url' ), 10, 2);
			add_filter('wp_get_attachment_image_attributes', array( $this, 'public_wp_get_attachment_image_attributes' ), 20, 3);
		} else {
			// Admin.
			add_action('after_switch_theme', array( $this, 'admin_after_switch_theme' ), 10, 2);
			add_action('admin_init', array( $this, 'admin_init' ));
			add_filter('media_row_actions', array( $this, 'admin_media_row_actions' ), 20, 3);
			add_action('post_action_correct-path', array( $this, 'admin_post_action_correct_path' ));
			add_action('post_action_regenerate-images', array( $this, 'admin_post_action_regenerate_images' ));
			add_filter('bulk_actions-upload', array( $this, 'admin_bulk_actions_upload' ));
			add_filter('handle_bulk_actions-upload', array( $this, 'admin_handle_bulk_actions_upload' ), 20, 3);
			if ( is_network_admin() ) {
				add_action('wpmu_update_blog_options', array( $this, 'admin_wpmu_update_blog_options' ));
			}
		}
		parent::autoload();
	}

	// Global.

	public function global_after_setup_theme() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 600,
				'width'       => 600,
				'flex-width' => true,
				'flex-height' => true,
			)
		);
		// wp-includes/media.php
		remove_image_size('1536x1536');
		remove_image_size('2048x2048');
	}

	public function global_big_image_size_threshold( $threshold = 2000, $imagesize = array(), $file = '', $attachment_id = 0 ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $threshold;
		}
		$large = get_option('large_size_w');
		if ( ! empty($large) && is_numeric($large) ) {
			$threshold = (int) $large;
		}
		return $threshold;
	}

	public function global_intermediate_image_sizes( $default_sizes = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $default_sizes;
		}
		// remove medium_large.
		if ( in_array('medium', $default_sizes, true) && in_array('large', $default_sizes, true) && in_array('medium_large', $default_sizes, true) ) {
			$default_sizes = array_value_unset($default_sizes, 'medium_large');
			$default_sizes = array_values($default_sizes);
		}
		return $default_sizes;
	}

	public function global_image_downsize( $out = false, $id = 0, $size = 'medium' ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $out;
		}
		// intercept requests for medium_large.
		if ( $size === 'medium_large' ) {
			$sizes = get_intermediate_image_sizes();
			if ( ! in_array('medium_large', $sizes, true) ) {
				if ( in_array('large', $sizes, true) ) {
					$out = image_downsize($id, 'large');
				} elseif ( in_array('medium', $sizes, true) ) {
					$out = image_downsize($id, 'medium');
				}
			}
		}
		return $out;
	}

	public function global_upload_mimes( $t, $user = null ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $t;
		}
		$array = array(
			'svg|svgz' => 'image/svg+xml',
			'webp' => 'image/webp',
		);
		return $t + $array;
	}

	// Public.

	public function public_wp_get_attachment_url( $url, $post_ID ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $url;
		}
		// Fixes bug in 'wp_get_attachment_url' which skips ssl urls when using ajax.
		if ( is_ssl() && 'wp-login.php' !== $GLOBALS['pagenow'] ) {
			$url = set_url_scheme($url);
		}
		return $url;
	}

	public function public_wp_get_attachment_image_attributes( $attr, $attachment, $size ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $attr;
		}
		if ( ! isset($attr['alt']) || empty($attr['alt']) ) {
			$attr['alt'] = get_attachment_alt($attachment);
		}
		return $attr;
	}

	// Admin.

	public function admin_after_switch_theme( $old_theme_name = false, $old_theme_class = false ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$defaults = array(
			'thumbnail_crop' => 1,
			'thumbnail_size_w' => 300,
			'thumbnail_size_h' => 300,
			'medium_size_w' => 600,
			'medium_size_h' => 600,
			'medium_large_size_w' => 1000,
			'medium_large_size_h' => 1000,
			'large_size_w' => 2000,
			'large_size_h' => 2000,
			'uploads_use_yearmonth_folders' => 1,
		);
		foreach ( $defaults as $key => $value ) {
			update_option($key, $value);
		}
	}

	public function admin_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! isset($_REQUEST['action']) ) {
			return;
		}
		$this->load_functions('wp-admin');
		if ( get_current_screen_id() !== 'upload' ) {
			return;
		}
		switch ( $_REQUEST['action'] ) {
			case 'attach_all_images':
				if ( $this->attach_all_images() ) {
					if ( wp_redirect(add_query_arg('posted', 1, admin_url('upload.php'))) ) {
						exit;
					}
				}
				break;
			case 'correct_all_paths':
				if ( $this->correct_all_paths() ) {
					if ( wp_redirect(add_query_arg('posted', 1, admin_url('upload.php'))) ) {
						exit;
					}
				}
				break;
			case 'regenerate_all_images':
				if ( $this->regenerate_all_images() ) {
					if ( wp_redirect(add_query_arg('posted', 1, admin_url('upload.php'))) ) {
						exit;
					}
				}
				break;
			default:
				break;
		}
	}

	public function admin_media_row_actions( $actions = array(), $post = 0, $detached = false ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $actions;
		}
		if ( ! isset($actions['regenerate-images']) && wp_attachment_is_image($post) ) {
			$actions['regenerate-images'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				wp_nonce_url("post.php?action=regenerate-images&amp;post=$post->ID", 'regenerate-images-post_' . $post->ID),
				esc_attr( sprintf( __('Regenerate images for &#8220;%s&#8221;'), $post->post_title) ),
				__('Regenerate Images')
			);
		}
		if ( ! $this->attachment_has_correct_path($post->ID) ) {
			$actions['correct-path'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				wp_nonce_url("post.php?action=correct-path&amp;post=$post->ID", 'correct-path-post_' . $post->ID),
				esc_attr( sprintf( __('Correct path for &#8220;%s&#8221;'), $post->post_title) ),
				__('Correct Path')
			);
		}
		return $actions;
	}

	public function admin_post_action_correct_path( $post_id = 0 ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		check_admin_referer('correct-path-post_' . $post_id);
		global $post;
		$tmp_post = $post_id ? get_post($post_id) : $post;
		if ( ! is_object($tmp_post) ) {
			wp_die(esc_html__('The item you are trying to edit no longer exists.'));
		}
		if ( ! current_user_can('edit_post', $tmp_post->ID) ) {
			wp_die(esc_html__('Sorry, you are not allowed to edit this item.'));
		}
		if ( $this->correct_path($tmp_post->ID) ) {
			if ( wp_redirect(add_query_arg('posted', 1, admin_url('upload.php'))) ) {
				exit;
			}
		}
		wp_die(esc_html__('Path correction failed.'));
	}

	public function admin_post_action_regenerate_images( $post_id = 0 ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		check_admin_referer('regenerate-images-post_' . $post_id);
		global $post;
		$tmp_post = $post_id ? get_post($post_id) : $post;
		if ( ! is_object($tmp_post) ) {
			wp_die(esc_html__('The item you are trying to edit no longer exists.'));
		}
		if ( ! current_user_can('edit_post', $tmp_post->ID) ) {
			wp_die(esc_html__('Sorry, you are not allowed to edit this item.'));
		}
		if ( $this->regenerate_images($tmp_post->ID) ) {
			if ( wp_redirect(add_query_arg('posted', 1, admin_url('upload.php'))) ) {
				exit;
			}
		}
		wp_die(esc_html__('Regenerate images failed.'));
	}

	public function admin_bulk_actions_upload( $actions ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $actions;
		}
		$actions['attach-images'] = __('Attach to Post');
		$actions['regenerate-images'] = __('Regenerate Images');
		return $actions;
	}

	public function admin_handle_bulk_actions_upload( $location, $doaction, $post_ids ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $location;
		}
		switch ( $doaction ) {
			case 'attach-images':
				foreach ( $post_ids as $post_id ) {
					$this->attach_image($post_id);
				}
				$location = add_query_arg('posted', 1, $location);
				break;
			case 'regenerate-images':
				foreach ( $post_ids as $post_id ) {
					$this->regenerate_images($post_id);
				}
				$location = add_query_arg('posted', 1, $location);
				break;
			default:
				break;
		}
		return $location;
	}

	public function admin_wpmu_update_blog_options( $id ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		update_site_option('ms_files_rewriting', true);
		update_blog_option($id, 'upload_url_path', untrailingslashit(get_blog_option($id, 'upload_url_path')));
		update_blog_option($id, 'upload_path', untrailingslashit(get_blog_option($id, 'upload_path')));
	}

	// Functions.

	public function attach_all_images() {
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'numberposts' => -1,
			'nopaging' => true,
			'orderby' => 'modified',
			'fields' => 'ids',
		);
		if ( $post_ids = ht_get_posts($args) ) {
			foreach ( $post_ids as $post_id ) {
				$this->attach_image($post_id);
			}
			return true;
		}
		return false;
	}

	public function attach_image( $attachment_id ) {
		if ( ! wp_attachment_is_image($attachment_id) ) {
			return false;
		}
		if ( (int) get_post_field('post_parent', $attachment_id, 'raw') > 0 ) {
			return false;
		}
		static $_published_posts = null;
		if ( is_null($_published_posts) ) {
			$args = array(
				'post_type' => 'any',
				'post_status' => 'publish',
				'numberposts' => -1,
				'nopaging' => true,
				'orderby' => 'modified',
				'fields' => 'ids',
				'meta_query' => array(
					array(
						'key' => '_thumbnail_id',
						'compare' => 'EXISTS',
					),
				),
			);
			$_published_posts = ht_get_posts($args);
		}
		if ( ! $_published_posts ) {
			return false;
		}
		$args = array(
			'post_type' => 'any',
			'post_status' => 'publish',
			'numberposts' => -1,
			'nopaging' => true,
			'orderby' => 'modified',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_thumbnail_id',
					'value' => $attachment_id,
				),
			),
			'include' => $_published_posts,
		);
		if ( $tmp = ht_get_posts($args) ) {
			$parent_id = reset($tmp);
			global $wpdb;
			$result = $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_parent = %d WHERE post_type = 'attachment' AND ID IN (%d)", $parent_id, $attachment_id));
			if ( isset($result) ) {
				do_action('wp_media_attach_action', 'attach', $attachment_id, $parent_id);
				clean_attachment_cache($attachment_id);
				return true;
			}
		}
		return false;
	}

	public function attachment_get_paths( $attachment_id ) {
		static $_results = array();
		if ( array_key_exists( $attachment_id, $_results) ) {
			return $_results[ $attachment_id ];
		}
		static $_uploads_use_yearmonth_folders = null;
		static $_upload_dir = array();
		if ( is_null($_uploads_use_yearmonth_folders) ) {
			$_uploads_use_yearmonth_folders = get_option('uploads_use_yearmonth_folders');
			$_upload_dir = wp_get_upload_dir();
		}
		$_results[ $attachment_id ] = array(
			'source' => null,
			'destination' => null,
		);
		if ( ht_get_post_type($attachment_id) === 'attachment' ) {
			if ( $path = get_attachment_path($attachment_id) ) {
				$_results[ $attachment_id ]['source'] = $path;
				$destination = $_upload_dir['basedir'];
				if ( ! empty($_uploads_use_yearmonth_folders) ) {
					if ( $datetime = get_post_datetime($attachment_id) ) {
						$destination = path_join($destination, gmdate('Y/m', $datetime->format('U')));
					}
				}
				$destination = path_join($destination, ht_basename($path));
				$_results[ $attachment_id ]['destination'] = $destination;
			}
		}
		return $_results[ $attachment_id ];
	}

	public function attachment_has_correct_path( $attachment_id ) {
		$paths = $this->attachment_get_paths($attachment_id);
		return safe_path($paths['source']) === safe_path($paths['destination']);
	}

	public function correct_all_paths() {
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'numberposts' => -1,
			'nopaging' => true,
			'orderby' => 'modified',
			'fields' => 'ids',
		);
		if ( $post_ids = ht_get_posts($args) ) {
			foreach ( $post_ids as $post_id ) {
				$this->correct_path($post_id);
			}
			return true;
		}
		return false;
	}

	public function correct_path( $attachment_id ) {
		if ( $this->attachment_has_correct_path($attachment_id) ) {
			return false;
		}
		$paths = $this->attachment_get_paths($attachment_id);
		return attachment_move($attachment_id, $paths['destination']);
	}

	public function regenerate_all_images() {
		$query_args = array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'numberposts' => -1,
			'nopaging' => true,
			'orderby' => 'modified',
			'fields' => 'ids',
		);
		if ( $post_ids = ht_get_posts($query_args) ) {
			$args = array(
				'delete_old_sizes' => false,
			);
			foreach ( $post_ids as $post_id ) {
				$this->regenerate_images($post_id, $args);
			}
			return true;
		}
		return false;
	}

	public function regenerate_images( $attachment_id, $args = array() ) {
		if ( ! wp_attachment_is_image($attachment_id) ) {
			return false;
		}
		$this->load_functions('wp-post');
		$file = get_attachment_path($attachment_id);
		if ( ! $file ) {
			return false;
		}
		$metadata_old = wp_get_attachment_metadata($attachment_id);
		if ( ! $metadata_old ) {
			return false;
		}
		$args = wp_parse_args($args,
			array(
				'delete_old_sizes' => true,
			)
		);
		if ( ! function_exists('wp_generate_attachment_metadata') ) {
			require_once path_join(ABSPATH, 'wp-admin/includes/image.php');
		}
		$metadata_new = wp_generate_attachment_metadata($attachment_id, $file);
		// Delete old sizes.
		if ( $args['delete_old_sizes'] ) {
			$this->load_functions('wp-admin');
			$wp_filesystem = ht_wp_filesystem('direct');
			if ( $wp_filesystem ) {
				// Delete old sizes that are still in the metadata.
				if ( isset($metadata_old['sizes']) && is_array($metadata_old['sizes']) ) {
					if ( ! function_exists('get_intermediate_image_sizes') ) {
						require_once path_join(WPINC, 'media.php');
					}
					$sizes_old = array_diff(array_keys($metadata_old['sizes']), get_intermediate_image_sizes());
					if ( ! empty($sizes_old) ) {
						foreach ( $sizes_old as $value ) {
							if ( ! isset($metadata_old['sizes'][ $value ], $metadata_old['sizes'][ $value ]['file']) ) {
								continue;
							}
							if ( $wp_filesystem->delete(path_join(ht_dirname($file), $metadata_old['sizes'][ $value ]['file'])) ) {
								if ( array_key_exists($value, $metadata_new['sizes']) ) {
									unset($metadata_new['sizes'][ $value ]);
								}
							}
						}
						wp_update_attachment_metadata($attachment_id, $metadata_new);
					}
				}
				// Delete old sizes on the file system.
				if ( $files = glob(ht_dirname($file) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME) . '-*x*.' . pathinfo($file, PATHINFO_EXTENSION)) ) {
					$array = wp_list_pluck($metadata_new['sizes'], 'file');
					foreach ( $files as $value ) {
						if ( in_array(ht_basename($value), $array) ) {
							continue;
						}
						$wp_filesystem->delete($value);
					}
				}
			}
		}
		return $metadata_new;
	}
}
