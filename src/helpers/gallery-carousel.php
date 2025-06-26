<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Gallery_Carousel extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		$this->data['gallery_options'] = array(
			'extend_width' => array(
				'3-5' => '3/5',
				'2-3' => '2/3',
				'3-4' => '3/4',
				'4-5' => '4/5',
			),
		);
		$this->data['gallery_defaults'] = array(
			'carousel' => false,
			'carousel_arrows' => false,
			'carousel_autoplay' => true,
			'carousel_autoplayspeed' => 3000,
			'carousel_centermode' => false,
			'carousel_dots' => false,
			'carousel_fade' => false,
			'carousel_infinite' => true,
			'carousel_slidestoshow' => 1,
			'carousel_speed' => 500, // set 0 to disable transitions.
			'extend_width' => '',
		);
		$this->data['gallery_items'] = array();
		// Global.
		if ( is_public() ) {
			// Public.
			add_filter('shortcode_atts_gallery', array( $this, 'public_shortcode_atts_gallery' ), 30, 4);
			add_filter('do_shortcode_tag', array( $this, 'public_do_shortcode_tag' ), 30, 4);
			add_action('get_footer', array( $this, 'public_get_footer' ), 30, 2);
		} else {
			// Admin.
			add_filter('media_view_settings', array( $this, 'admin_media_view_settings' ), 40, 2);
			add_action('print_media_templates', array( $this, 'admin_print_media_templates' ), 40);
		}
		parent::autoload();
	}

	// Public.

	public function public_shortcode_atts_gallery( $out, $pairs, $atts, $shortcode ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $out;
		}
		$carousel = array_key_exists('carousel', $atts) ? is_true($atts['carousel']) : false;
		if ( ! $carousel ) {
			return $out;
		}
		// Add more defaults.
		$defaults = array(
			'size' => 'large',
			'link' => 'file',
		);
		foreach ( $defaults as $key => $value ) {
			if ( ! isset($atts[ $key ]) ) {
				$out[ $key ] = $value;
			}
		}
		// Force the following values.
		$out['columns'] = 1;
		return $out;
	}

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
		// Carousel detection.
		$attr['carousel'] = array_key_exists('carousel', $attr) ? is_true($attr['carousel']) : false;
		if ( ! $attr['carousel'] ) {
			return $output;
		}
		// extend_width detection.
		if ( array_key_exists('extend_width', $attr) ) {
			$attr['extend_width'] = preg_replace('/[:\/]/s', '-', trim($attr['extend_width']));
		}
		// Add more defaults.
		if ( isset($attr['extend_width']) ) {
			$attr['carousel_arrows'] = false;
			$attr['carousel_slidestoshow'] = 2;
		}
		$defaults = array(
			'ratio' => '16-9',
			'gap' => 0,
		);
		foreach ( $defaults as $key => $value ) {
			if ( ! isset($attr[ $key ]) ) {
				$attr[ $key ] = $value;
			}
		}
		// Get selector.
		$selector = null;
		if ( preg_match_all("/^<div id=['\"]([^'\"]+)['\"]/is", $output, $matches, PREG_SET_ORDER) ) {
			$selector = trim(current($matches)[1]);
		}
		if ( empty($selector) ) {
			return $output;
		}
		// Add classes.
		foreach ( array( 'carousel', 'extend_width', 'ratio', 'gap' ) as $value ) {
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
		unset($attr['ratio'], $attr['gap']);
		// Save the item for CSS/JS loading in the footer.
		if ( ! isset($this->data['gallery_items'][ $selector ]) ) {
			$this->data['gallery_items'][ $selector ] = $this->shortcode_attr_to_slick_settings($attr);
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
		if ( ! wp_style_is('gallery_common') ) {
			return;
		}
		$array = array(
			'package' => 'slick-carousel',
			'version' => '1.8.1',
		);
		// css.
		$fallback = __DIR__ . '/assets/dist/slick-carousel/slick/slick.css';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'slick/slick.css' ), $fallback) ) {
			wp_enqueue_style($array['package'], $url, array(), $array['version'], 'screen');
		}
		$fallback = __DIR__ . '/assets/dist/slick-carousel/slick/slick-theme.css';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'slick/slick-theme.css' ), $fallback) ) {
			wp_enqueue_style($array['package'] . '-theme', $url, array( $array['package'] ), $array['version'], 'screen');
		}
		$file = __DIR__ . '/assets/css/gallery-carousel.css';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_style(static::$handle, $url, array( 'gallery_common', $array['package'], $array['package'] . '-theme' ), get_file_version($file), 'screen');
		}
		// js.
		$fallback = __DIR__ . '/assets/dist/slick-carousel/slick/slick.min.js';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'slick/slick.min.js' ), $fallback) ) {
			wp_enqueue_script($array['package'], $url, array( 'jquery' ), $array['version'], true);
		}
		$file = __DIR__ . '/assets/js/gallery-carousel' . min_scripts() . '.js';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_script(static::$handle, $url, array( 'jquery', $array['package'] ), get_file_version($file), true);
			wp_localize_script(static::$handle, 'gallery_carousel', array( 'handle' => static::$handle ) + $this->data['gallery_items']);
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
		$gallery_defaults = $this->data['gallery_defaults'];
		unset($gallery_defaults['carousel'], $gallery_defaults['extend_width']);
		?>
<script type="text/html" id="tmpl-<?php echo esc_attr(static::$handle); ?>">
	<span class="setting">
		<hr />
		<h2 style="margin-top: 0;"><?php esc_html_e('Carousel Settings'); ?></h2>
	</span>
	<span class="setting">
		<input type="checkbox" id="<?php echo esc_attr(static::$handle); ?>-active" name="carousel" data-setting="carousel" />
		<label for="<?php echo esc_attr(static::$handle); ?>-active" class="checkbox-label-inline"><?php esc_html_e('Create Carousel?'); ?></label>
	</span>
		<?php
		foreach ( $gallery_defaults as $key => $value ) {
			$id = static::$handle . '-' . $key;
			$label = ucfirst(str_replace('carousel_', '', $key));
			?>
	<span class="setting">
		<?php if ( is_bool($value) ) : ?>
		<label for="<?php echo esc_attr($id); ?>" class="checkbox-label-inline" style="min-width: 33%;"><?php echo esc_html($label); ?></label>
		<input type="checkbox" id="<?php echo esc_attr($id); ?>" data-setting="<?php echo esc_attr($key); ?>" />
		<?php elseif ( is_float($value) || is_int($value) ) : ?>
		<label for="<?php echo esc_attr($id); ?>" class="checkbox-label-inline"><?php echo esc_html($label); ?></label>
		<input type="number" id="<?php echo esc_attr($id); ?>" data-setting="<?php echo esc_attr($key); ?>" value="" />
		<?php else : ?>
		<label for="<?php echo esc_attr($id); ?>" class="checkbox-label-inline"><?php echo esc_html($label); ?></label>
		<input type="text" id="<?php echo esc_attr($id); ?>" data-setting="<?php echo esc_attr($key); ?>" value="" />
		<?php endif; ?>
	</span>
			<?php
		}
		?>
	<span class="setting">
		<label for="<?php echo esc_attr(static::$handle); ?>-extend-width" class="checkbox-label-inline"><?php esc_html_e('Extend width'); ?></label>
		<select id="<?php echo esc_attr(static::$handle); ?>-extend-width" name="extend_width" data-setting="extend_width" style="float: right; width: 65%;">
			<option value="">--</option>
		<?php foreach ( $this->data['gallery_options']['extend_width'] as $key => $value ) : ?>
			<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
		<?php endforeach; ?>
		</select>
	</span>
</script>
<script>
jQuery(document).ready(function () {
	_.extend(wp.media.galleryDefaults, <?php echo array_to_js_object($this->data['gallery_defaults'], OBJECT); ?>);
});
</script>
		<?php
	}

	// Functions.

	public function get_slick_defaults() {
		return array(
			'accessibility' => true,
			'adaptiveHeight' => false,
			'appendArrows' => 'element',
			'appendDots' => 'element',
			'arrows' => true,
			'asNavFor' => null,
			'prevArrow' => '<button class="slick-prev" aria-label="Previous" type="button">Previous</button>',
			'nextArrow' => '<button class="slick-next" aria-label="Next" type="button">Next</button>',
			'autoplay' => false,
			'autoplaySpeed' => 3000,
			'centerMode' => false,
			'centerPadding' => '50px',
			'cssEase' => 'ease',
			'customPaging' => 'function',
			'dots' => false,
			'dotsClass' => 'slick-dots',
			'draggable' => true,
			'easing' => 'linear',
			'edgeFriction' => 0.35,
			'fade' => false,
			'focusOnSelect' => false,
			'focusOnChange' => false,
			'infinite' => true,
			'initialSlide' => 0,
			'lazyLoad' => 'ondemand',
			'mobileFirst' => false,
			'pauseOnHover' => true,
			'pauseOnFocus' => true,
			'pauseOnDotsHover' => false,
			'respondTo' => 'window',
			'responsive' => 'object',
			'rows' => 1,
			'rtl' => false,
			'slide' => 'element',
			'slidesPerRow' => 1,
			'slidesToShow' => 1,
			'slidesToScroll' => 1,
			'speed' => 500,
			'swipe' => true,
			'swipeToSlide' => false,
			'touchMove' => true,
			'touchThreshold' => 5,
			'useCSS' => true,
			'useTransform' => true,
			'variableWidth' => false,
			'vertical' => false,
			'verticalSwiping' => false,
			'waitForAnimate' => true,
			'zIndex' => 1000,
		);
	}

	private function shortcode_attr_to_slick_settings( $attr = array() ) {
		$results = array();
		$gallery_defaults = $this->data['gallery_defaults'];
		unset($gallery_defaults['carousel'], $gallery_defaults['extend_width']);
		// Only custom attributes.
		$attr = array_intersect_key($attr, $gallery_defaults);
		// Add defaults.
		$attr = wp_parse_args($attr, $gallery_defaults);
		unset($gallery_defaults);
		// Find relevant values.
		$slick_defaults = $this->get_slick_defaults();
		$slick_defaults_keymap = array_combine(array_map('strtolower', array_keys($slick_defaults)), array_keys($slick_defaults));
		foreach ( $attr as $key => $value ) {
			$key = str_replace('carousel_', '', $key);
			if ( array_key_exists($key, $slick_defaults) ) {
				$results[ $key ] = $value;
			} elseif ( array_key_exists($key, $slick_defaults_keymap) ) {
				if ( ! array_key_exists($slick_defaults_keymap[ $key ], $results) ) {
					$results[ $slick_defaults_keymap[ $key ] ] = $value;
				}
			}
		}
		if ( empty($results) ) {
			return $results;
		}
		unset($slick_defaults_keymap);
		// Format values. Remove problematic values.
		foreach ( $results as $key => &$value ) {
			// valid key?
			if ( ! array_key_exists($key, $slick_defaults) ) {
				unset($results[ $key ]);
				continue;
			}
			$default = $slick_defaults[ $key ];
			// js things.
			if ( in_array($default, array( 'element', 'function', 'object' ), true) ) {
				unset($results[ $key ]);
				continue;
			}
			// check data types.
			if ( $value !== $default ) {
				if ( is_null($default) || is_string($default) ) {
					$value = (string) $value;
				} elseif ( is_bool($default) ) {
					$value = is_true($value);
				} elseif ( is_float($default) ) {
					$value = (float) $value;
				} elseif ( is_int($default) ) {
					$value = (int) $value;
				}
			}
			if ( $value === $default ) {
				unset($results[ $key ]);
				continue;
			}
		}
		ksort($results);
		return $results;
	}
}
