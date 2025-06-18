<?php
namespace Halftheory\Lib\helpers\classes;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Shortcode_Control extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		parent::autoload();
	}

	// Functions.

	public function shortcode_control( $shortcode, $hooks = array(), $default_filters = array() ) {
		// See: https://github.com/chiedolabs/shortcode-wpautop-control/blob/master/shortcode-wpautop-control.php
		// This function should be called by the 'init' action or later.
		if ( ! shortcode_exists($shortcode) ) {
			return;
		}

		if ( empty($hooks) ) {
			$hooks = array(
				'the_content',
				'the_excerpt',
				'widget_text_content',
				'widget_block_content',
			);
		}

		if ( empty($default_filters) ) {
			$default_filters = array(
				'do_blocks',
				'wptexturize',
				'wpautop',
				'shortcode_unautop',
				'prepend_attachment',
				'wp_replace_insecure_home_url',
				'do_shortcode',
				'wp_filter_content_tags',
				'convert_smilies',
			);
			global $wp_embed;
			if ( is_object($wp_embed) ) {
				$tmp = array(
					array( $wp_embed, 'run_shortcode' ),
					array( $wp_embed, 'autoembed' ),
				);
				$default_filters = array_merge($tmp, $default_filters);
			}
		}

		$callback = function ( $content = '' ) use ( $shortcode, $default_filters ) {
			if ( ! has_shortcode($content, $shortcode) ) {
				return $content;
			}
			// Determine which filters are applied, and remove them.
			$current_filter = current_filter();
			$filters = $default_filters;
			foreach ( $filters as $key => $value ) {
				if ( $tmp = has_filter($current_filter, $value) ) {
					remove_filter($current_filter, $value, $tmp);
					continue;
				}
				unset($filters[ $key ]);
			}
			if ( empty($filters) ) {
				return $content;
			}
			// Get content around the shortcode.
			$parts_old = preg_split('/' . get_shortcode_regex(array( $shortcode )) . '/is', $content, -1, PREG_SPLIT_NO_EMPTY);
			$parts_new = $parts_old;
			// Reapply the relevant filters.
			foreach ( $filters as $value ) {
				if ( is_callable($value) ) {
					$parts_new = array_map($value, $parts_new);
				}
			}
			$content = strtr($content, array_combine($parts_old, $parts_new));
			return $content;
		};

		foreach ( $hooks as $hook_name ) {
			// Get ahead of the lowest priority.
			$array = array();
			foreach ( $default_filters as $value ) {
				if ( $tmp = has_filter($hook_name, $value) ) {
					$array[] = $tmp;
				}
			}
			$priority = empty($array) ? 8 : min($array) - 1;
			add_filter($hook_name, $callback, $priority);
		}
	}
}
