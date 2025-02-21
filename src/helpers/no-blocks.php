<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class No_Blocks extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( is_public() ) {
			// Public.
			add_action('init', array( $this, 'public_init' ), 8); // priority important!
			add_action('wp', array( $this, 'public_wp' ));
			add_filter('get_the_excerpt', array( $this, 'public_get_the_excerpt' ), 9, 2);
			add_action('wp_footer', array( $this, 'public_wp_footer' ));
		}
		parent::autoload();
	}

	// Global.

	// Public.

	public function public_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// reduce required files.
		remove_action('init', '_register_core_block_patterns_and_categories');
		remove_action('init', 'register_core_block_style_handles', 9);
		remove_action('init', 'register_core_block_types_from_metadata');
	}

	public function public_wp( $wp ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// remove default filters.
		add_filter('should_load_block_editor_scripts_and_styles', '__return_false');
		remove_action('wp_enqueue_scripts', 'wp_common_block_scripts_and_styles'); // contains .screen-reader class and many inline .wp-block* styles.
		remove_filter('the_content', 'do_blocks', 9);
		remove_filter('widget_block_content', 'do_blocks', 9);
		remove_action('init', array( 'WP_Block_Supports', 'init' ), 22);
		remove_all_actions('enqueue_block_assets');
	}

	public function public_get_the_excerpt( $excerpt = '', $post = null ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $excerpt;
		}
		return excerpt_remove_blocks($excerpt);
	}

	public function public_wp_footer() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			 return;
		}
		wp_dequeue_style('core-block-supports');
	}

	// Admin.
}
