<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Video_Featured extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $post_types = null ) {
		$this->data['post_types'] = is_array($post_types) ? $post_types : array_values(array_diff(get_post_types(array( 'public' => true ), 'names'), array( 'attachment', 'revision' )));
		$this->load_functions('video-common,video-featured');
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( ! is_public() ) {
			// Admin.
			add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20);
			foreach ( $this->data['post_types'] as $value ) {
				add_action('add_meta_boxes_' . $value, array( $this, 'admin_add_meta_boxes' ));
				add_action('save_post_' . $value, array( $this, 'admin_save_post' ), 20, 3);
			}
			add_action('after_delete_post', array( $this, 'admin_after_delete_post' ), 20, 2);
			add_filter('media_view_settings', array( $this, 'admin_media_view_settings' ), 20, 2);
			add_action('wp_ajax_get_post_video_html', array( $this, 'wp_ajax_get_post_video_html' ), 1);
		}
		parent::autoload();
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
			$file = __DIR__ . '/assets/js/video-featured-media-editor' . min_scripts() . '.js';
			if ( $url = get_stylesheet_uri_from_file($file) ) {
				wp_enqueue_script(static::$handle, $url, array( 'media-editor' ), get_file_version($file), true);
			}
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
		$callback = function ( $post ) {
			if ( ! is_object($post) ) {
				return;
			}
			if ( ! in_array($post->post_type, $this->data['post_types']) ) {
				return;
			}
			echo $this->post_video_html(get_post_video_id($post), $post);
		};
		add_meta_box(
			'postvideodiv',
			__('Featured video'),
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
				$attachment_id = isset($_POST['video_id']) ? (int) $_POST['video_id'] : 0;
				if ( $attachment_id > 0 ) {
					set_post_video($post, $attachment_id);
				} else {
					delete_post_video($post);
				}
			}
		}
	}

	public function admin_after_delete_post( $post_id, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( in_array($post->post_type, $this->data['post_types']) ) {
			delete_post_video($post);
		}
	}

	public function admin_media_view_settings( $settings, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $settings;
		}
		if ( isset($settings['post'], $settings['post']['videoFeaturedId']) ) {
			return $settings;
		}
		if ( ! is_object($post) ) {
			return $settings;
		}
		if ( ! in_array($post->post_type, $this->data['post_types']) ) {
			return $settings;
		}
		$attachment_id = (int) get_post_video_id($post);
		$settings['post']['videoFeaturedId'] = $attachment_id > 0 ? $attachment_id : -1;
		return $settings;
	}

	public function wp_ajax_get_post_video_html() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Ajax handler for retrieving HTML for the featured video.
		if ( ! isset($_POST, $_POST['post_id']) ) {
			wp_die(-1);
		}
		$post_id = (int) $_POST['post_id'];
		check_ajax_referer('update-post_' . $post_id);
		if ( ! current_user_can('edit_post', $post_id) ) {
			wp_die(-1);
		}
		$attachment_id = isset($_POST['video_id']) ? (int) $_POST['video_id'] : null;
		// For backward compatibility, -1 refers to no featured video.
		if ( $attachment_id === -1 ) {
			$attachment_id = null;
		}
		$return = $this->post_video_html($attachment_id, $post_id);
		wp_send_json_success($return);
	}

	// Functions.

	private function post_video_html( $attachment_id = null, $post = null ) {
		// Based on wp-admin/includes/post.php > _wp_post_thumbnail_html.
		$post = get_post($post);
		if ( ! $post ) {
			return '';
		}
		$upload_iframe_src = get_upload_iframe_src('video', $post->ID);

		$content = '';
		$video_html = '';

		if ( $attachment_id && get_post($attachment_id) ) {
			$size = has_image_size('post-thumbnail') ? 'post-thumbnail' : array( 266, 266 );
			$array = array(
				has_post_thumbnail($attachment_id) ? get_the_post_thumbnail($attachment_id, $size) : wp_get_attachment_image($attachment_id, $size, true),
				get_the_title($attachment_id),
			);
			$array = array_map('trim', $array);
			$video_html = implode('<br />', array_filter($array));
		}

		if ( ! empty($video_html) ) {
			$content = wp_sprintf(
				'<p class="hide-if-no-js"><a href="%s" id="set-post-video"%s class="thickbox" style="text-decoration: none; color: inherit; font-weight: bold; text-align: center; display: block;">%s</a></p>',
				esc_url($upload_iframe_src),
				' aria-describedby="set-post-video-desc"',
				$video_html
			);
			$content .= '<p class="hide-if-no-js howto" id="set-post-video-desc">' . esc_html__('Click the icon to update') . '</p>';
			$content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-video">' . esc_html__('Remove featured video') . '</a></p>';
		} else {
			$content = wp_sprintf(
				'<p class="hide-if-no-js"><a href="%s" id="set-post-video"%s class="thickbox">%s</a></p>',
				esc_url($upload_iframe_src),
				'', // Empty when there's no featured image set, `aria-describedby` attribute otherwise.
				esc_html__('Set featured video')
			);
		}

		$content .= '<input type="hidden" id="video_id" name="video_id" value="' . esc_attr( $attachment_id ? $attachment_id : '-1' ) . '" />';
		return $content;
	}
}
