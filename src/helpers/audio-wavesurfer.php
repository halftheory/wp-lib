<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Audio_Wavesurfer extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $wavesurfer_options = array() ) {
		$defaults = array(
	        'waveColor' => '#696FC7',
	        'progressColor' => '#A7AAE1',
	        'cursorColor' => '#A7AAE1',
	        'sampleRate' => 22050,
	        'mediaControls' => true,
		);
		if ( empty($wavesurfer_options) ) {
			// Look for colors in theme data.
	        $this->load_functions('wp-theme');
			$wavesurfer_options = get_theme_colors(array( 'waveColor', 'progressColor', 'cursorColor' ));
		}
		$this->data['wavesurfer_options'] = wp_parse_args($wavesurfer_options, $defaults);

		parent::__construct($autoload);
	}

	protected function autoload() {
		$this->data['audio_items'] = array();
		// Global.
		add_filter('wp_audio_shortcode_override', array( $this, 'global_wp_audio_shortcode_override' ), 20, 4);
		if ( is_public() ) {
			// Public.
			add_filter('do_shortcode_tag', array( $this, 'public_do_shortcode_tag' ), 20, 4);
			add_filter('the_content', array( $this, 'public_the_content' ), 100);
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
		} else {
			// Admin.
			add_action('admin_footer', array( $this, 'admin_footer' ), 20);
		}
		parent::autoload();
	}

	// Global.

	public function global_wp_audio_shortcode_override( $html, $attr, $content, $instance ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $html;
		}
		// See function wp_audio_shortcode. Removed class and scripts.
		$post_id = get_post() ? get_the_ID() : 0;

		$audio = null;

		$default_types = wp_get_audio_extensions();
		$defaults_atts = array(
			'src'      => '',
			'loop'     => '',
			'autoplay' => '',
			'muted'    => 'false',
			'preload'  => 'none',
			'style'    => 'width: 100%;',
		);
		foreach ( $default_types as $type ) {
			$defaults_atts[ $type ] = '';
		}

		$atts = shortcode_atts( $defaults_atts, $attr, 'audio' );

		$primary = false;
		if ( ! empty( $atts['src'] ) ) {
			$type = wp_check_filetype( $atts['src'], wp_get_mime_types() );

			if ( ! in_array( strtolower( $type['ext'] ), $default_types, true ) ) {
				return sprintf( '<a class="wp-embedded-audio" href="%s">%s</a>', esc_url( $atts['src'] ), esc_html( $atts['src'] ) );
			}

			$primary = true;
			array_unshift( $default_types, 'src' );
		} else {
			foreach ( $default_types as $ext ) {
				if ( ! empty( $atts[ $ext ] ) ) {
					$type = wp_check_filetype( $atts[ $ext ], wp_get_mime_types() );

					if ( strtolower( $type['ext'] ) === $ext ) {
						$primary = true;
					}
				}
			}
		}

		if ( ! $primary ) {
			$audios = get_attached_media( 'audio', $post_id );

			if ( empty( $audios ) ) {
				return;
			}

			$audio       = reset( $audios );
			$atts['src'] = wp_get_attachment_url( $audio->ID );

			if ( empty( $atts['src'] ) ) {
				return;
			}

			array_unshift( $default_types, 'src' );
		}

		$library = apply_filters( 'wp_audio_shortcode_library', 'mediaelement' );

		$html_atts = array(
			'id'       => sprintf( 'audio-%d-%d', $post_id, $instance ),
			'loop'     => wp_validate_boolean( $atts['loop'] ),
			'autoplay' => wp_validate_boolean( $atts['autoplay'] ),
			'muted'    => wp_validate_boolean( $atts['muted'] ),
			'preload'  => $atts['preload'],
			'style'    => $atts['style'],
		);

		// These ones should just be omitted altogether if they are blank.
		foreach ( array( 'loop', 'autoplay', 'preload', 'muted' ) as $a ) {
			if ( empty( $html_atts[ $a ] ) ) {
				unset( $html_atts[ $a ] );
			}
		}

		$attr_strings = array();

		foreach ( $html_atts as $attribute_name => $attribute_value ) {
			if ( in_array( $attribute_name, array( 'loop', 'autoplay', 'muted' ), true ) && true === $attribute_value ) {
				// Add boolean attributes without a value.
				$attr_strings[] = esc_attr( $attribute_name );
			} elseif ( 'preload' === $attribute_name && ! empty( $attribute_value ) ) {
				// Handle the preload attribute with specific allowed values.
				$allowed_preload_values = array( 'none', 'metadata', 'auto' );
				if ( in_array( $attribute_value, $allowed_preload_values, true ) ) {
					$attr_strings[] = sprintf( '%s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
				}
			} else {
				// For other attributes, include the value.
				$attr_strings[] = sprintf( '%s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
			}
		}

		$html = '';

		if ( 'mediaelement' === $library && 1 === $instance ) {
			$html .= "<!--[if lt IE 9]><script>document.createElement('audio');</script><![endif]-->\n";
		}

		$html .= sprintf( '<audio %s controls="controls">', implode( ' ', $attr_strings ) );

		$fileurl = '';
		$source  = '<source type="%s" src="%s" />';

		foreach ( $default_types as $fallback ) {
			if ( ! empty( $atts[ $fallback ] ) ) {
				if ( empty( $fileurl ) ) {
					$fileurl = $atts[ $fallback ];
				}

				$type  = wp_check_filetype( $atts[ $fallback ], wp_get_mime_types() );
				$url   = add_query_arg( '_', $instance, $atts[ $fallback ] );
				$html .= sprintf( $source, $type['type'], esc_url( $url ) );
			}
		}

		if ( 'mediaelement' === $library ) {
			$html .= wp_mediaelement_fallback( $fileurl );
		}

		$html .= '</audio>';

		$html = apply_filters('wp_audio_shortcode', $html, $atts, $audio, $post_id, $library);

		// Save the item for CSS/JS loading in the footer.
		$this->data['audio_items'][] = $attr;
		return $html;
	}

	// Public.

	public function public_do_shortcode_tag( $output, $tag, $attr, $m ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $output;
		}
		if ( $tag === 'audio' ) {
			// Save the item for CSS/JS loading in the footer.
			$this->data['audio_items'][] = $attr;
		}
		return $output;
	}

	public function public_the_content( $content = '' ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $content;
		}
		if ( str_contains($content, '<audio ') && str_contains($content, '</audio>') ) {
			// Save the item for CSS/JS loading in the footer.
			if ( preg_match_all('/[\s]*(<audio [^>]*>(.*?)<\/[\s]*audio>)[\s]*/is', $content, $matches) ) {
				$this->data['audio_items'] = array_merge($this->data['audio_items'], $matches[1]);
			}
		}
		return $content;
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->enqueue_scripts();
	}

	// Admin.

	public function admin_footer( $data ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->enqueue_scripts();
	}

	// Functions.

	private function enqueue_scripts() {
		if ( empty($this->data['audio_items']) ) {
			return;
		}
		$this->load_functions('wp-theme');
		$array = array(
			'package' => 'wavesurfer.js',
			'version' => '7.12.1',
		);
		// JS.
		$fallback = __DIR__ . '/assets/dist/wavesurfer.js/dist/wavesurfer.min.js';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'dist/wavesurfer.min.js' ), $fallback) ) {
			wp_enqueue_script($array['package'], $url, array(), $array['version'], true);
		}
		$file = __DIR__ . '/assets/js/audio-wavesurfer' . min_scripts() . '.js';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			$this->load_functions('wp-scripts');
			wp_enqueue_script(static::$handle, $url, filter_script_deps(array( 'jquery', $array['package'] )), get_file_version($file), true);
			wp_localize_script(static::$handle, 'audio_wavesurfer', $this->data['wavesurfer_options']);
		}
	}
}
