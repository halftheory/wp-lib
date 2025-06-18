<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Gallery_Packery extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		$this->data['gallery_defaults'] = array(
			'gapless' => false,
		);
		$this->data['gallery_items'] = array();
		// Global.
		if ( is_public() ) {
			// Public.
			add_filter('do_shortcode_tag', array( $this, 'public_do_shortcode_tag' ), 30, 4);
			add_action('get_footer', array( $this, 'public_get_footer' ), 30, 2);
		} else {
			// Admin.
			add_filter('media_view_settings', array( $this, 'admin_media_view_settings' ), 30, 2);
			add_action('print_media_templates', array( $this, 'admin_print_media_templates' ), 30);
		}
		parent::autoload();
	}

	// Public.

	public function public_do_shortcode_tag( $output, $tag, $attr, $m ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $output;
		}
		if ( $tag !== 'gallery' ) {
			return $output;
		}
		if ( ! str_starts_with($output, '<div id=') ) {
			return $output;
		}
		if ( did_action('get_header') === 0 || did_action('loop_start') === 0 || is_feed() ) {
			return $output;
		}
		$attr['gapless'] = array_key_exists('gapless', $attr) ? is_true($attr['gapless']) : false;
		if ( ! $attr['gapless'] ) {
			return $output;
		}
		// Get selector.
		$selector = null;
		if ( preg_match_all("/^<div id=['\"]([^'\"]+)['\"]/is", $output, $matches, PREG_SET_ORDER) ) {
			$selector = '#' . trim(current($matches)[1]);
		}
		if ( empty($selector) ) {
			return $output;
		}
		// Add classes.
		foreach ( array( 'gapless' ) as $value ) {
			if ( ! isset($attr[ $value ]) ) {
				continue;
			}
			$array = array(
				'gallery',
				str_replace('_', '-', $value),
				is_bool($attr[ $value ]) ? null : $attr[ $value ],
			);
			$class = implode('-', array_filter_not($array, 'empty_zero_ok'));
			if ( str_contains($output, ' ' . $class) ) {
				continue;
			}
			$output = wp_html_tag_add_class($output, 'div', $class);
		}
		// Save the item for CSS/JS loading in the footer.
		if ( ! isset($this->data['gallery_items'][ $selector ]) ) {
			$this->data['gallery_items'][ $selector ] = array();
		}
		return $output;
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( empty($this->data['gallery_items']) ) {
			return;
		}
		$array = array(
			'package' => 'packery',
			'version' => '3.0.0',
		);
		// css.
		$file = __DIR__ . '/assets/css/gallery-packery.css';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			$deps = wp_style_is('gallery_common') ? array( 'gallery_common' ) : array();
			wp_enqueue_style(static::$handle, $url, $deps, get_file_version($file), 'screen');
		}
		// js.
		$fallback = __DIR__ . '/assets/dist/packery/dist/packery.pkgd.min.js';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'dist/packery.pkgd.min.js' ), $fallback) ) {
			wp_enqueue_script($array['package'], $url, array( 'jquery' ), $array['version'], true);
		}
		$file = __DIR__ . '/assets/js/gallery-packery' . min_scripts() . '.js';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_script(static::$handle, $url, array( 'jquery', $array['package'] ), get_file_version($file), true);
			wp_localize_script(static::$handle, 'gallery_packery', $this->data['gallery_items']);
		}
	}

	// Admin.

	public function admin_media_view_settings( $settings, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $settings;
		}
		if ( isset($settings['galleryDefaults']) ) {
			$settings['galleryDefaults'] = wp_parse_args($settings['galleryDefaults'], $this->data['gallery_defaults']);
		}
		return $settings;
	}

	public function admin_print_media_templates() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		?>
<script type="text/html" id="tmpl-<?php echo esc_attr(static::$handle); ?>">
	<span class="setting"><hr /></span>
	<span class="setting">
		<input type="checkbox" id="<?php echo esc_attr(static::$handle); ?>-active" name="gapless" data-setting="gapless" />
		<label for="<?php echo esc_attr(static::$handle); ?>-active" class="checkbox-label-inline"><?php esc_html_e('Gapless Layout'); ?></label>
	</span>
</script>
<script>
jQuery(document).ready(function () {
	_.extend(wp.media.galleryDefaults, <?php echo array_to_js_object($this->data['gallery_defaults'], OBJECT); ?>);
});
</script>
		<?php
	}
}
