<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Remove_Post_Type extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $post_type = null ) {
		$this->data['post_type'] = $post_type ? $post_type : null;
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('init', array( $this, 'global_init' ), 20);
		parent::autoload();
	}

	// Global.

	public function global_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! $this->data['post_type'] ) {
			return;
		}
		if ( ! post_type_exists($this->data['post_type']) ) {
			return;
		}
		if ( $tmp = get_post_type_object($this->data['post_type']) ) {
			if ( isset($tmp->taxonomies) ) {
				foreach ( make_array($tmp->taxonomies) as $value ) {
					unregister_taxonomy_for_object_type($value, $tmp->name);
				}
			}
			if ( ! isset($tmp->_builtin) || ! $tmp->_builtin ) {
				unregister_post_type($this->data['post_type']);
			}
		}
		global $wp_post_types;
		if ( isset($wp_post_types[ $this->data['post_type'] ]) ) {
			$wp_post_types[ $this->data['post_type'] ]->public = false;
			$wp_post_types[ $this->data['post_type'] ]->publicly_queryable = false;
			foreach ( $wp_post_types[ $this->data['post_type'] ] as $key => &$value ) {
				if ( str_starts_with($key, 'show_') && is_bool($value) ) {
					$value = false;
				}
			}
		}
	}
}
