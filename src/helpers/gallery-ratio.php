<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Gallery_Ratio extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		$this->data['gallery_options'] = array(
			'ratio' => array(
				'1-1' => '1:1',
				'4-3' => '4:3',
				'16-9' => '16:9',
				'21-9' => '21:9',
				'3-4' => '3:4',
				'9-16' => '9:16',
				'9-21' => '9:21',
			),
		);
		$this->data['gallery_defaults'] = array(
			'ratio' => '',
			'gap' => 1,
		);
		$this->data['gallery_items'] = 0;
		// Global.
		if ( is_public() ) {
			// Public.
			add_filter('do_shortcode_tag', array( $this, 'public_do_shortcode_tag' ), 20, 4);
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
		} else {
			// Admin.
			add_filter('media_view_settings', array( $this, 'admin_media_view_settings' ), 20, 2);
			add_action('print_media_templates', array( $this, 'admin_print_media_templates' ), 20);
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
		// Backwards compatibility.
		if ( array_key_exists('aspectratio', $attr) ) {
			if ( ! array_key_exists('ratio', $attr) ) {
				$attr['ratio'] = $attr['aspectratio'];
			}
			unset($attr['aspectratio']);
		}
		// Look for a custom attribute.
		if ( empty(array_intersect_key($attr, $this->data['gallery_defaults'])) ) {
			return $output;
		}
		// Ratio detection.
		if ( array_key_exists('ratio', $attr) ) {
			$attr['ratio'] = preg_replace('/[:\/]/s', '-', trim($attr['ratio']));
		}
		// Add classes.
		foreach ( array_keys($this->data['gallery_defaults']) as $key ) {
			if ( ! isset($attr[ $key ]) ) {
				continue;
			}
			$array = array(
				'gallery',
				str_replace('_', '-', $key),
				is_bool($attr[ $key ]) ? null : $attr[ $key ],
			);
			$class = implode('-', array_filter_not($array, 'empty_zero_ok'));
			if ( str_contains($output, ' ' . $class) ) {
				continue;
			}
			$output = wp_html_tag_add_class($output, 'div', $class);
		}
		// Save the item for CSS/JS loading in the footer.
		$this->data['gallery_items']++;
		return $output;
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( empty($this->data['gallery_items']) ) {
			return;
		}
		if ( ! wp_style_is('gallery_common') ) {
			return;
		}
		// Load CSS.
		if ( ! wp_style_is(static::$handle) ) {
			$file = __DIR__ . '/assets/css/gallery-ratio.css';
			if ( $url = get_stylesheet_uri_from_file($file) ) {
				wp_enqueue_style(static::$handle, $url, array( 'gallery_common' ), get_file_version($file), 'screen');
			}
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
		<label for="<?php echo esc_attr(static::$handle); ?>-ratio" class="checkbox-label-inline"><?php esc_html_e('Aspect ratio'); ?></label>
		<select id="<?php echo esc_attr(static::$handle); ?>-ratio" name="ratio" data-setting="ratio" style="float: right; width: 65%;">
			<option value="">--</option>
		<?php foreach ( $this->data['gallery_options']['ratio'] as $key => $value ) : ?>
			<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
		<?php endforeach; ?>
		</select>
	</span>
	<span class="setting">
		<label for="<?php echo esc_attr(static::$handle); ?>-gap" class="checkbox-label-inline"><?php esc_html_e('Gap'); ?></label>
		<input type="number" id="<?php echo esc_attr(static::$handle); ?>-gap" name="gap" data-setting="gap" value="" min="0" max="9" />
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
