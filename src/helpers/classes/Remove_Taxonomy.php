<?php
namespace Halftheory\Lib\helpers\classes;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Remove_Taxonomy extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $taxonomy = null ) {
		$this->data['taxonomy'] = $taxonomy;
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
		if ( ! $this->data['taxonomy'] ) {
			return;
		}
		if ( ! taxonomy_exists($this->data['taxonomy']) ) {
			return;
		}
		if ( $tmp = get_taxonomy($this->data['taxonomy']) ) {
			if ( isset($tmp->object_type) ) {
				foreach ( make_array($tmp->object_type) as $value ) {
					unregister_taxonomy_for_object_type($tmp->name, $value);
				}
			}
			if ( ! isset($tmp->_builtin) || ! $tmp->_builtin ) {
				unregister_taxonomy($this->data['taxonomy']);
			}
		}
		global $wp_taxonomies;
		if ( isset($wp_taxonomies[ $this->data['taxonomy'] ]) ) {
			$wp_taxonomies[ $this->data['taxonomy'] ]->public = false;
			$wp_taxonomies[ $this->data['taxonomy'] ]->publicly_queryable = false;
			foreach ( $wp_taxonomies[ $this->data['taxonomy'] ] as $key => &$value ) {
				if ( str_starts_with($key, 'show_') && is_bool($value) ) {
					$value = false;
				}
			}
		}
	}
}
