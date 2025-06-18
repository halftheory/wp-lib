<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Gallery_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		$this->data['gallery_defaults'] = array(
			'columns' => 4,
			'link' => 'file',
			'size' => 'medium',
		);
		// Global.
		if ( is_public() ) {
			// Public.
			add_action('after_setup_theme', array( $this, 'public_after_setup_theme' ), 20);
			add_action('pre_get_posts', array( $this, 'public_pre_get_posts' ), 20);
			add_filter('the_posts', array( $this, 'public_the_posts' ), 20, 2);
			add_filter('use_default_gallery_style', '__return_false');
			add_filter('shortcode_atts_gallery', array( $this, 'public_shortcode_atts_gallery' ), 10, 4);
			add_action('get_footer', array( $this, 'public_get_footer' ), 10, 2);
		} else {
			// Admin.
			add_action('admin_init', array( $this, 'admin_init' ), 20);
			add_filter('media_view_settings', array( $this, 'admin_media_view_settings' ), 20, 2);
			add_action('print_media_templates', array( $this, 'admin_print_media_templates_last' ), 90);
		}
		parent::autoload();
	}

	// Public.

	public function public_after_setup_theme() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		add_theme_support('html5', array( 'gallery', 'caption', 'style' ));
	}

	public function public_pre_get_posts( $query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// galleries - enable filters so that we can use 'the_posts'.
		if ( did_action('get_header') > 0 && did_action('loop_start') > 0 && ! $query->is_main_query() && $query->get('post_type') === 'attachment' ) {
			global $post;
			if ( is_object($post) && has_shortcode($post->post_content, 'gallery') ) {
				if ( $query->get('suppress_filters') ) {
					$query->set('suppress_filters', false);
				}
			}
		}
	}

	public function public_the_posts( $posts, $query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $posts;
		}
		// galleries - new lines in gallery captions.
		if ( did_action('get_header') > 0 && did_action('loop_start') > 0 && ! $query->is_main_query() && $query->get('post_type') === 'attachment' ) {
			global $post;
			if ( is_object($post) && has_shortcode($post->post_content, 'gallery') ) {
				foreach ( $posts as $key => &$value ) {
					$value->post_excerpt = nl2br(trim($value->post_excerpt));
				}
			}
		}
		return $posts;
	}

	public function public_shortcode_atts_gallery( $out, $pairs, $atts, $shortcode ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $out;
		}
		// https://developer.wordpress.org/reference/functions/shortcode_atts/
		// Overwrite 'pairs' and do this again.
		$pairs = wp_parse_args($this->data['gallery_defaults'], $pairs);
		$out = array();
		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists($name, $atts) ) {
				$out[ $name ] = $atts[ $name ];
			} else {
				$out[ $name ] = $default;
			}
		}
		return $out;
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( did_filter('post_gallery') === 0 && did_filter('gallery_style') === 0 ) {
			return;
		}
		// Load CSS.
		if ( ! wp_style_is(static::$handle) ) {
			$file = __DIR__ . '/assets/css/gallery-common-public.css';
			if ( $url = get_stylesheet_uri_from_file($file) ) {
				wp_enqueue_style(static::$handle, $url, array(), get_file_version($file), 'screen');
			}
		}
	}

	// Admin.

	public function admin_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->load_functions('wp-admin');
		if ( admin_is_edit_screen() ) {
			if ( $url = get_stylesheet_uri_from_file(__DIR__ . '/assets/css/gallery-common-admin.css') ) {
				add_editor_style($url);
			}
		}
	}

	public function admin_media_view_settings( $settings, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $settings;
		}
		if ( isset($settings['galleryDefaults']) ) {
			$settings['galleryDefaults'] = wp_parse_args($settings['galleryDefaults'], $this->data['gallery_defaults']);
		}
		return $settings;
	}

	public function admin_print_media_templates_last() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// https://wordpress.stackexchange.com/questions/182821/add-custom-fields-to-wp-native-gallery-settings
		// https://stackoverflow.com/questions/31378392/changing-the-wordpress-gallery-image-size-default
		?>
<script>
jQuery(document).ready(function () {
	_.extend(wp.media.galleryDefaults, <?php echo array_to_js_object($this->data['gallery_defaults'], OBJECT); ?>);

	wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
        template: function (view) {
        	const defaultTemplate = 'gallery-settings';
			let resultView = wp.media.template(defaultTemplate)(view);
			// Append additional 'gallery' templates.
        	const scripts = jQuery('script[id^="tmpl-gallery"]');
        	if (scripts.length) {
	        	jQuery.each(scripts, function (i) {
	        		let id = jQuery(this).attr('id').replace('tmpl-', '');
	        		if (id !== defaultTemplate) {
	        			resultView += wp.media.template(id)(view);
	        		}
	        	});
        	}
        	// Apply defaults.
			const $resultView = jQuery(jQuery.parseHTML(resultView));
    		jQuery.each(wp.media.gallery.defaults, function (key, value) {
    			const elem = $resultView.find('[data-setting="' + key + '"]').first();
    			if (elem.length) {
    				if (value !== elem.val()) {
						let search = '';
    					if (elem.prop('tagName') === 'SELECT') {
    						search = '<option value="' + value + '">';
    						if (resultView.indexOf(search) !== -1) {
    							resultView = resultView.replace(search, '<option value="' + value + '" selected="selected">');
    						}
    					} else if (elem.prop('tagName') === 'INPUT') {
    						search = 'data-setting="' + key + '" value="' + elem.val() + '"';
    						if (resultView.indexOf(search) !== -1) {
    							resultView = resultView.replace(search, 'data-setting="' + key + '" value="' + value + '"');
    						}
    					}
    				}
    			}
    		});
        	return resultView;
        },
		update: function (key) {
			const $setting = this.$('[data-setting="' + key + '"]');
			if (!$setting.length) {
				return;
			}
			if ($setting.is(':focus')) {
				return;
			}
			const value = this.model.get(key);
			if ($setting.is('input[type="checkbox"]')) {
				$setting.prop('checked', !!value && value !== 'false');
				return;
			}
			$setting.val(value);
		}
    });
});
</script>
		<?php
	}
}
