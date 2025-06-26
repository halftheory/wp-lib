<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Background_Image extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $background_defaults = array(), $post_types = null ) {
		$defaults = array(
			'default-preset' => 'fill',
			'default-position-x' => 'center',
			'default-position-y' => 'top',
			'default-size' => 'cover',
			'default-repeat' => 'no-repeat',
			'default-attachment' => 'fixed',
		);
		$this->data['background_defaults'] = wp_parse_args($background_defaults, $defaults);
		$this->data['post_types'] = is_null($post_types) ? array_values(array_diff(get_post_types(array( 'public' => true ), 'names'), array( 'attachment', 'revision' ))) : make_array($post_types);
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('after_setup_theme', array( $this, 'global_after_setup_theme' ), 20);
		if ( is_public() ) {
			// Public.
			add_filter('theme_mod_background_image', array( $this, 'public_theme_mod_background_image' ), 20);
		} else {
			// Admin.
			add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20);
			add_action('admin_print_styles', array( $this, 'admin_print_styles' ), 20);
			foreach ( $this->data['post_types'] as $value ) {
				add_action('add_meta_boxes_' . $value, array( $this, 'admin_add_meta_boxes' ));
				add_action('save_post_' . $value, array( $this, 'admin_save_post' ), 20, 3);
			}
			add_filter('media_view_settings', array( $this, 'admin_media_view_settings' ), 20, 2);
			add_action('wp_ajax_get_post_background_image_html', array( $this, 'wp_ajax_get_post_background_image_html' ), 1);
		}
		parent::autoload();
	}

	// Global.

	public function global_after_setup_theme() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		add_theme_support('custom-background', $this->data['background_defaults']);
	}

	// Public.

	public function public_theme_mod_background_image( $value ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $value;
		}
		if ( ! current_theme_supports('custom-background') ) {
			return $value;
		}
		if ( did_action('get_header') === 0 ) {
			return $value;
		}
		if ( is_singular() ) {
			$attachment_id = (int) get_post_meta(get_the_ID(), static::$handle . '_id', true);
			if ( $attachment_id > 0 ) {
				if ( $tmp = get_image_context('url', $attachment_id, 'large') ) {
					$value = $tmp;
				}
			}
		}
		return $value;
	}

	// Admin.

	public function admin_enqueue_scripts() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! current_user_can('upload_files') ) {
			return;
		}
		$this->load_functions('wp-admin');
		if ( admin_is_edit_screen($this->data['post_types']) ) {
			$file = __DIR__ . '/assets/js/background-image-media-editor' . min_scripts() . '.js';
			if ( $url = get_stylesheet_uri_from_file($file) ) {
				wp_enqueue_script(static::$handle, $url, array( 'media-editor' ), get_file_version($file), true);
			}
		}
	}

	public function admin_print_styles() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! current_user_can('upload_files') ) {
			return;
		}
		// Based on wp-admin/css/edit.css
		$this->load_functions('wp-admin');
		if ( admin_is_edit_screen($this->data['post_types']) ) {
			?>
<style id="<?php echo esc_attr(static::$handle . '-css'); ?>">
#set-background-image {
	display: inline-block;
	max-width: 100%;
}
#postbackgroundimagediv .inside img {
	max-width: 100%;
	height: auto;
	vertical-align: top;
	background-image: linear-gradient(45deg, #c3c4c7 25%, transparent 25%, transparent 75%, #c3c4c7 75%, #c3c4c7), linear-gradient(45deg, #c3c4c7 25%, transparent 25%, transparent 75%, #c3c4c7 75%, #c3c4c7);
	background-position: 0 0, 10px 10px;
	background-size: 20px 20px;
}
</style>
			<?php
		}
	}

	public function admin_add_meta_boxes( $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( empty($post) ) {
			return;
		}
		if ( ! current_user_can('upload_files') ) {
			return;
		}
		global $current_user;
		if ( ! $current_user->has_cap('edit_theme_options') ) {
			return;
		}
		$callback = function ( $post ) {
			if ( ! is_object($post) ) {
				return;
			}
			if ( ! in_array($post->post_type, $this->data['post_types']) ) {
				return;
			}
			$attachment_id = get_post_meta($post->ID, static::$handle . '_id', true);
			echo $this->post_background_image_html($attachment_id, $post);
		};
		add_meta_box(
			'postbackgroundimagediv',
			__('Background image'),
			$callback,
			$this->data['post_types'],
			'side',
			'low',
			array( '__back_compat_meta_box' => true )
		);
	}

	public function admin_save_post( $post_id, $post, $update = false ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return;
		}
		if ( empty($update) ) {
			return;
		}
		// Update options, only on Edit>Post page.
		if ( isset($_POST, $_POST['_wpnonce']) ) {
			if ( wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-post_' . $post_id) ) {
				$attachment_id = isset($_POST[ static::$handle . '_id' ]) ? (int) $_POST[ static::$handle . '_id' ] : 0;
				if ( $attachment_id > 0 ) {
					update_post_meta($post_id, static::$handle . '_id', $attachment_id);
				} else {
					delete_post_meta($post_id, static::$handle . '_id');
				}
			}
		}
	}

	public function admin_media_view_settings( $settings, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $settings;
		}
		if ( isset($settings['post'], $settings['post']['backgroundImageId']) ) {
			return $settings;
		}
		if ( ! is_object($post) ) {
			return $settings;
		}
		if ( ! in_array($post->post_type, $this->data['post_types']) ) {
			return $settings;
		}
		$attachment_id = (int) get_post_meta($post->ID, static::$handle . '_id', true);
		$settings['post']['backgroundImageId'] = $attachment_id > 0 ? $attachment_id : -1;
		return $settings;
	}

	public function wp_ajax_get_post_background_image_html() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Ajax handler for retrieving HTML for the image.
		if ( ! isset($_POST, $_POST['post_id']) ) {
			wp_die(-1);
		}
		$post_id = (int) $_POST['post_id'];
		check_ajax_referer('update-post_' . $post_id);
		if ( ! current_user_can('edit_post', $post_id) ) {
			wp_die(-1);
		}
		$attachment_id = isset($_POST['background_image_id']) ? (int) $_POST['background_image_id'] : null;
		// For backward compatibility, -1 refers to no image.
		if ( $attachment_id === -1 ) {
			$attachment_id = null;
		}
		$return = $this->post_background_image_html($attachment_id, $post_id);
		wp_send_json_success($return);
	}

	// Functions.

	private function post_background_image_html( $attachment_id = null, $post = null ) {
		// Based on wp-admin/includes/post.php > _wp_post_thumbnail_html()
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		$post               = get_post( $post );
		$set_thumbnail_link = '<p class="hide-if-no-js"><a href="%s" id="set-background-image"%s class="thickbox">%s</a></p>';
		$upload_iframe_src  = get_upload_iframe_src( 'image', $post->ID );

		$content = sprintf(
			$set_thumbnail_link,
			esc_url( $upload_iframe_src ),
			'', // Empty when there's no featured image set, `aria-describedby` attribute otherwise.
			esc_html__('Set background image')
		);

		if ( $attachment_id && get_post( $attachment_id ) ) {
			$size = isset( $_wp_additional_image_sizes['post-thumbnail'] ) ? 'post-thumbnail' : array( 266, 266 );

			$size = apply_filters( 'admin_post_thumbnail_size', $size, $attachment_id, $post );

			$thumbnail_html = wp_get_attachment_image( $attachment_id, $size );

			if ( ! empty( $thumbnail_html ) ) {
				$content  = sprintf(
					$set_thumbnail_link,
					esc_url( $upload_iframe_src ),
					' aria-describedby="set-background-image-desc"',
					$thumbnail_html
				);
				$content .= '<p class="hide-if-no-js howto" id="set-background-image-desc">' . __( 'Click the image to edit or update' ) . '</p>';
				$content .= '<p class="hide-if-no-js"><a href="#" id="remove-background-image">' . esc_html__('Remove background image') . '</a></p>';
			}
		}

		$content .= '<input type="hidden" id="' . static::$handle . '_id" name="' . static::$handle . '_id" value="' . esc_attr( $attachment_id ? $attachment_id : '-1' ) . '" />';
		return $content;
	}
}
