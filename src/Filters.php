<?php
namespace Halftheory\Lib;

#[AllowDynamicProperties]
abstract class Filters extends Core {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = false ) {
		// Exit if accessed directly.
		defined('ABSPATH') || exit(get_called_class());
		// Load.
		$this->load_functions(array( 'php', 'wp' ));
		$this->add_filters_all();
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( is_public() ) {
			// Public.
		} else {
			// Admin.
		}
		parent::autoload();
	}

	// Functions.

	public function add_filters_all( $prefix = null ) {
		if ( ! array_key_exists('_filters_all', $this->data) ) {
			$this->data['_filters_all'] = array();
			// collect all relevant methods into a list of filters.
			$prefixes = array( 'global_', 'public_', 'admin_', 'rest_', 'wp_' );
			foreach ( get_class_methods(get_called_class()) as $value ) {
				foreach ( $prefixes as $p ) {
					if ( str_starts_with($value, $p) ) {
						$this->data['_filters_all'][] = $value;
						break;
					}
				}
			}
		}
		if ( empty($prefix) ) {
			static::$filters = $this->data['_filters_all'];
		} elseif ( is_string($prefix) ) {
			foreach ( $this->data['_filters_all'] as $value ) {
				if ( str_starts_with($value, $prefix) ) {
					static::$filters[] = $value;
				}
			}
			static::$filters = array_values(array_unique(array_filter(static::$filters)));
		}
		return true;
	}

	public static function add_filter( $filter ) {
		if ( ! method_exists(get_called_class(), $filter) ) {
			return false;
		}
		if ( ! in_array($filter, static::$filters, true) ) {
			static::$filters[] = $filter;
			static::$filters = array_values(static::$filters);
		}
		return true;
	}

	public static function remove_filters_all( $prefix = null ) {
		if ( empty($prefix) ) {
			static::$filters = array();
		} elseif ( is_string($prefix) ) {
			foreach ( static::$filters as $key => $value ) {
				if ( str_starts_with($value, $prefix) ) {
					unset(static::$filters[ $key ]);
				}
			}
			static::$filters = array_values(static::$filters);
		}
		return true;
	}

	public static function remove_filter( $filter ) {
		if ( ! in_array($filter, static::$filters, true) ) {
			return false;
		}
		static::$filters = array_values(array_value_unset(static::$filters, $filter));
		return true;
	}

	protected function is_filter_active( $filter ) {
		static::$filters = apply_filters(static::$handle, static::$filters);
		return in_array($filter, static::$filters, true);
	}

	// Global.

	// Public.

	// Admin.
}
