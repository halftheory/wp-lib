<?php
namespace Halftheory\Lib;

use Halftheory\Lib\Core;

#[AllowDynamicProperties]
abstract class Theme extends Core {

	public static $handle;
	protected static $instance;
	protected $data = array();

	public function __construct( $autoload = false ) {
		// Exit if accessed directly.
		defined('ABSPATH') || exit(get_called_class());
		// Load.
		$this->load_functions(array( 'php', 'wp' ));
		set_encoding(get_bloginfo('charset'));
		parent::__construct($autoload);
	}

	protected function set_handle( $handle = null ) {
		static::$handle = $handle ? $handle : static::$handle;
		if ( is_null(static::$handle) ) {
			if ( $tmp = ht_get_theme_data('TextDomain') ) {
				static::$handle = $tmp;
			} elseif ( $tmp = ht_get_theme_data('Name') ) {
				static::$handle = $tmp;
			}
		}
		parent::set_handle();
		set_theme_data(array( 'handle' => static::$handle ));
	}

	protected function autoload() {
		parent::autoload();
	}

	// Functions.

	final public function load_filters( $values, $data_key = '_filters' ) {
		$callback = function ( $value ) {
			if ( is_object($value) ) {
				return $value;
			}
			if ( ! class_exists($value) ) {
				return false;
			}
			$value = is_subclass_of($value, 'Halftheory\Lib\Filters') ? new $value(true) : new $value();
			return is_object($value) ? $value : false;
		};
		$this->data[ $data_key ] = is_array($values) ? array_filter(array_map($callback, $values)) : $callback($values);
		return ! empty($this->data[ $data_key ]);
	}

	final public function load_helpers( $keys = '*', $data_key = '_helpers' ) {
		if ( ! array_key_exists($data_key, $this->data) ) {
			$this->data[ $data_key ] = array(
				'files' => array(),
				'loaded' => array(),
			);
			// Array of keys (file path) => value (filename).
			$tmp = array_merge( $this->get_relative_files('helpers/*.php'), $this->get_relative_files('helpers/*/*.php') );
			if ( ! empty($tmp) ) {
				$callback = function ( $path ) {
					return pathinfo($path, PATHINFO_FILENAME);
				};
				$this->data[ $data_key ]['files'] = array_combine($tmp, array_map($callback, $tmp));
			}
		}
		if ( empty($this->data[ $data_key ]['files']) ) {
			return $this->data[ $data_key ]['loaded'];
		}
		$keys = $keys === '*' ? array_keys($this->data[ $data_key ]['files']) : make_array($keys);
		foreach ( $keys as $value ) {
			// key = file path. already loaded.
			if ( array_key_exists($value, $this->data[ $data_key ]['loaded']) ) {
				continue;
			}
			// key = file path.
			if ( isset($this->data[ $data_key ]['files'][ $value ]) ) {
				$this->data[ $data_key ]['loaded'][ $value ] = array(
					'key' => $this->data[ $data_key ]['files'][ $value ],
					'value' => load_class_from_file($value),
				);
				unset($this->data[ $data_key ]['files'][ $value ]);
				continue;
			}
			// key = filename.
			if ( in_array($value, $this->data[ $data_key ]['files']) ) {
				$tmp = array_search($value, $this->data[ $data_key ]['files']);
				if ( is_file($tmp) ) {
					$this->data[ $data_key ]['loaded'][ $tmp ] = array(
						'key' => $this->data[ $data_key ]['files'][ $tmp ],
						'value' => load_class_from_file($tmp),
					);
					unset($this->data[ $data_key ]['files'][ $tmp ]);
					continue;
				}
			}
			// fallback.
			$this->data[ $data_key ]['loaded'][ $value ] = array(
				'key' => $value,
				'value' => false,
			);
		}
		return $this->data[ $data_key ]['loaded'];
	}

	final public function load_plugins( $keys = '*', $data_key = '_plugins' ) {
		if ( ! array_key_exists($data_key, $this->data) ) {
			$this->data[ $data_key ] = array(
				'files' => array(),
				'loaded' => array(),
			);
			// array of keys (file path) => value (plugin path).
			foreach ( get_active_plugins() as $value ) {
				$tmp = $this->get_relative_files('plugins' . DIRECTORY_SEPARATOR . $value);
				if ( ! empty($tmp) ) {
					$tmp = array_fill_keys($tmp, $value);
					$this->data[ $data_key ]['files'] = $this->data[ $data_key ]['files'] + $tmp;
				}
			}
		}
		if ( empty($this->data[ $data_key ]['files']) ) {
			return $this->data[ $data_key ]['loaded'];
		}
		$keys = $keys === '*' ? array_keys($this->data[ $data_key ]['files']) : make_array($keys);
		foreach ( $keys as $value ) {
			// key = file path. already loaded.
			if ( array_key_exists($value, $this->data[ $data_key ]['loaded']) ) {
				continue;
			}
			// key = file path.
			if ( isset($this->data[ $data_key ]['files'][ $value ]) ) {
				$this->data[ $data_key ]['loaded'][ $value ] = array(
					'key' => $this->data[ $data_key ]['files'][ $value ],
					'value' => load_class_from_file($value),
				);
				unset($this->data[ $data_key ]['files'][ $value ]);
				continue;
			}
			// key = filename.
			if ( in_array($value, $this->data[ $data_key ]['files']) ) {
				$tmp = array_search($value, $this->data[ $data_key ]['files']);
				if ( is_file($tmp) ) {
					$this->data[ $data_key ]['loaded'][ $tmp ] = array(
						'key' => $this->data[ $data_key ]['files'][ $tmp ],
						'value' => load_class_from_file($tmp),
					);
					unset($this->data[ $data_key ]['files'][ $tmp ]);
					continue;
				}
			}
			// fallback.
			$this->data[ $data_key ]['loaded'][ $value ] = array(
				'key' => $value,
				'value' => false,
			);
		}
		return $this->data[ $data_key ]['loaded'];
	}

	final public function get_filters( $key = null, $data_key = '_filters' ) {
		if ( ! isset($this->data[ $data_key ]) ) {
			return false;
		}
		if ( $key ) {
			return is_array($this->data[ $data_key ]) && array_key_exists($key, $this->data[ $data_key ]) ? $this->data[ $data_key ][ $key ] : false;
		}
		return $this->data[ $data_key ];
	}

	final public function get_helper( $key, $data_key = '_helpers' ) {
		if ( empty($key) || empty($data_key) ) {
			return false;
		}
		if ( ! isset($this->data[ $data_key ], $this->data[ $data_key ]['loaded']) ) {
			return false;
		}
		$tmp = array_search($key, wp_list_pluck($this->data[ $data_key ]['loaded'], 'key'));
		if ( $tmp === false ) {
			return false;
		}
		if ( ! isset($this->data[ $data_key ]['loaded'][ $tmp ], $this->data[ $data_key ]['loaded'][ $tmp ]['value']) ) {
			return false;
		}
		return $this->data[ $data_key ]['loaded'][ $tmp ]['value'];
	}

	final public function get_plugin( $key, $data_key = '_plugins' ) {
		return $this->get_helper($key, $data_key);
	}
}
