<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Minify extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $minify_path = '', $args = array() ) {
		$this->data['minify_path'] = $minify_path;
		$this->data['args'] = $args;
		$this->data['defaults'] = array(
			'css' => true,
			'js' => true,
		);
		$this->load_functions('wp');
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( is_public() ) {
			// Public.
			// Start buffer before HTML output.
			$priority = array( 90 );
			if ( function_exists('get_filter_next_priority') ) {
				$priority[] = get_filter_next_priority('get_header');
			}
			add_action('get_header', array( $this, 'public_get_header' ), max($priority));
		} else {
			// Admin.
		}
		parent::autoload();
	}

	// Public.

	public function public_get_header( $name = '' ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( is_feed() ) {
			return;
		}
		// Only when top level buffer.
		if ( ob_get_level() !== 1 ) {
			return;
		}
		if ( $this->init() ) {
			ob_start(array( &$this, 'sanitize_output' ));
		}
	}

	// Functions.

	private function init() {
		// Incompatible with cache plugins.
		$cache_plugins = array(
			'w3-total-cache',
			'wp-fastest-cache',
			'wp-optimize',
			'wp-super-cache',
		);
		foreach ( get_active_plugins() as $plugin ) {
			foreach ( $cache_plugins as $value ) {
				if ( str_contains($plugin, $value) ) {
					return false;
				}
			}
		}
		// Find the path to the Minify library.
		if ( empty($this->data['minify_path']) ) {
			// Search for Minify - child theme, parent theme, ABSPATH, DOCUMENT_ROOT.
			$search = array(
				get_stylesheet_directory(),
				get_template_directory(),
				ht_dirname(ABSPATH),
				wp_unslash($_SERVER['DOCUMENT_ROOT']),
			);
			// Folder names - minify, min.
			foreach ( array_unique($search) as $base ) {
				$array = array(
					path_join($base, 'minify'),
					path_join($base, 'min'),
				);
				foreach ( $array as $value ) {
					if ( is_dir($value) && file_exists($value . '/lib/Minify/HTML.php') ) {
						$this->data['minify_path'] = $value;
						break;
					}
				}
				if ( $this->data['minify_path'] ) {
					break;
				}
			}
			if ( empty($this->data['minify_path']) ) {
				return false;
			}
		}
		if ( ! is_readable(path_join($this->data['minify_path'], 'lib/Minify/HTML.php')) ) {
			return false;
		}
		require_once path_join($this->data['minify_path'], 'lib/Minify/HTML.php');
		if ( ! class_exists('Minify_HTML') ) {
			return false;
		}
		return true;
	}

	private function sanitize_output( $buffer ) {
		$args = wp_parse_args($this->data['args'], $this->data['defaults']);
		$options = array();
		// CSS.
		if ( $args['css'] ) {
			$classes = array(
				'Minify_CSS_Compressor' => 'lib/Minify/CSS/Compressor.php',
				'Minify_CommentPreserver' => 'lib/Minify/CommentPreserver.php',
				'Minify_CSS_UriRewriter' => 'lib/Minify/CSS/UriRewriter.php',
				'Minify_CSS' => 'lib/Minify/CSS.php',
			);
			foreach ( $classes as $class => $path ) {
				if ( ! class_exists($class) && is_readable(path_join($this->data['minify_path'], $path)) ) {
					require_once path_join($this->data['minify_path'], $path);
				}
				if ( class_exists($class) ) {
					unset($classes[ $class ]);
				}
			}
			if ( empty($classes) && method_exists('Minify_CSS', 'minify') ) {
				$options['cssMinifier'] = array( 'Minify_CSS', 'minify' );
			}
		}
		// JS.
		if ( $args['js'] ) {
			$classes = array(
				'JShrink\Minifier' => 'lib/Minify/JS/JShrink/src/JShrink/Minifier.php',
				'Minify\JS\JShrink' => 'lib/Minify/JS/JShrink.php',
			);
			foreach ( $classes as $class => $path ) {
				if ( ! class_exists($class) && is_readable(path_join($this->data['minify_path'], $path)) ) {
					require_once path_join($this->data['minify_path'], $path);
				}
				if ( class_exists($class) ) {
					unset($classes[ $class ]);
				}
			}
			if ( empty($classes) && method_exists('Minify\JS\JShrink', 'minify') ) {
				$options['jsMinifier'] = array( 'Minify\JS\JShrink', 'minify' );
				// $options['jsMinifier'] = array( '\Minify\JS\JShrink', 'minify' );
				// Crashes! Disabled for now...
				// /lib/Minify/JS/JShrink/src/JShrink/Minifier.php
				// > $jshrink = new Minifier(); // namespace issue?
			}
		}
		$buffer = Minify_HTML::minify($buffer, $options);
		return $buffer;
	}
}
