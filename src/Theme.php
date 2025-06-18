<?php
namespace Halftheory\Lib;

#[AllowDynamicProperties]
abstract class Theme extends Core {

	public static $handle;
	protected static $instance;
	protected $data = array();

	public function __construct( $autoload = false ) {
		// Exit if accessed directly.
		defined('ABSPATH') || exit(get_called_class());
		// Load.
		$this->load_functions('php,wp-load,wp-theme');
		if ( is_development() ) {
			set_symlinks(get_stylesheet_directory());
		}
		set_encoding(get_bloginfo('charset'));
		parent::__construct($autoload);
	}

	protected function set_handle( $handle = null ) {
		static::$handle = $handle ? $handle : static::$handle;
		if ( $this->is_theme_active() && is_null(static::$handle) ) {
			if ( $tmp = ht_get_theme_data('TextDomain') ) {
				static::$handle = $tmp;
			} elseif ( $tmp = ht_get_theme_data('Name') ) {
				static::$handle = $tmp;
			}
		}
		parent::set_handle();
		if ( $this->is_theme_active() ) {
			set_theme_data(array( 'handle' => static::$handle ));
		}
	}

	protected function autoload() {
		parent::autoload();
	}

	// Functions.

	final public function is_theme_active() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( isset($this->data['_autoload']) && $this->data['_autoload'] ) {
				$tmp = $this->get_class_ancestors();
				if ( ! empty($tmp) ) {
					// First entry should be the called class file.
					reset($tmp);
					if ( str_starts_with(maybe_restore_symlink_path(key($tmp)), safe_path(get_stylesheet_directory())) ) {
						$_result = true;
					} else {
						// In case of symlinks.
						$array = glob(get_stylesheet_directory() . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . ht_basename(key($tmp)));
						if ( ! empty($array) && str_contains(key($tmp), DIRECTORY_SEPARATOR . get_stylesheet() . DIRECTORY_SEPARATOR) ) {
							$_result = true;
						}
					}
				}
			}
		}
		return $_result;
	}

	final public function load_filters( $values, $data_key = '_filters' ) {
		// Could be a single object, or an array of objects.
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
		if ( ! $this->setup_helpers($data_key) ) {
			return false;
		}
		if ( empty($this->data[ $data_key ]) ) {
			return $this->data[ $data_key ];
		}
		$keys = $keys === '*' ? array_keys($this->data[ $data_key ]) : make_array($keys);
		foreach ( $keys as $value ) {
			// key = file path.
			if ( isset($this->data[ $data_key ][ $value ]) ) {
				// Already loaded.
				if ( $this->data[ $data_key ][ $value ]['loaded'] ) {
					continue;
				}
				// Load.
				$this->data[ $data_key ][ $value ]['loaded'] = true;
				$this->data[ $data_key ][ $value ]['class'] = get_class_from_file($value);
				$this->data[ $data_key ][ $value ]['value'] = load_class_from_file($value);
				continue;
			}
			// key = filename.
			if ( $file = array_search($value, wp_list_pluck($this->data[ $data_key ], 'key')) ) {
				// Already loaded.
				if ( $this->data[ $data_key ][ $file ]['loaded'] ) {
					continue;
				}
				// Load.
				$this->data[ $data_key ][ $file ]['loaded'] = true;
				$this->data[ $data_key ][ $file ]['class'] = get_class_from_file($file);
				$this->data[ $data_key ][ $file ]['value'] = load_class_from_file($file);
				continue;
			}
			// Fallback.
			$this->data[ $data_key ][ $value ] = array(
				'key' => $value,
				'file' => null,
				'loaded' => true,
				'class' => null,
				'value' => null,
			);
		}
		$callback = function ( $value ) {
			return is_array($value) && array_key_exists('loaded', $value) ? $value['loaded'] : false;
		};
		return array_filter($this->data[ $data_key ], $callback);
	}

	final public function load_plugins( $keys = '*', $data_key = '_plugins' ) {
		if ( ! $this->setup_plugins($data_key) ) {
			return false;
		}
		return $this->load_helpers($keys, $data_key);
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

	final public function get_helper( $key, $field = null, $data_key = '_helpers' ) {
		if ( empty($key) ) {
			return false;
		}
		if ( ! $this->setup_helpers($data_key) ) {
			return false;
		}
		if ( $file = array_search($key, wp_list_pluck($this->data[ $data_key ], 'key')) ) {
			if ( ! array_key_exists('class', $this->data[ $data_key ][ $file ]) ) {
				$this->data[ $data_key ][ $file ]['class'] = get_class_from_file($file);
			}
			if ( ! array_key_exists('value', $this->data[ $data_key ][ $file ]) ) {
				$this->data[ $data_key ][ $file ]['value'] = null;
			}
			if ( $field ) {
				return array_key_exists($field, $this->data[ $data_key ][ $file ]) ? $this->data[ $data_key ][ $file ][ $field ] : null;
			}
			return $this->data[ $data_key ][ $file ];
		}
		return false;
	}

	final public function get_plugin( $key, $field = null, $data_key = '_plugins' ) {
		if ( empty($key) ) {
			return false;
		}
		if ( ! $this->setup_plugins($data_key) ) {
			return false;
		}
		return $this->get_helper($key, $field, $data_key);
	}

	final public function set_helper( $key, $value, $data_key = '_helpers' ) {
		if ( empty($key) ) {
			return false;
		}
		if ( ! $this->setup_helpers($data_key) ) {
			return false;
		}
		if ( $file = array_search($key, wp_list_pluck($this->data[ $data_key ], 'key')) ) {
			$this->data[ $data_key ][ $file ]['loaded'] = true;
			$this->data[ $data_key ][ $file ]['value'] = $value;
			return true;
		}
		return false;
	}

	final public function set_plugin( $key, $value, $data_key = '_plugins' ) {
		if ( empty($key) ) {
			return false;
		}
		if ( ! $this->setup_plugins($data_key) ) {
			return false;
		}
		return $this->set_helper($key, $value, $data_key);
	}

	private function setup_helpers( $data_key = '_helpers' ) {
		if ( empty($data_key) ) {
			return false;
		}
		if ( array_key_exists($data_key, $this->data) ) {
			return true;
		}
		$this->data[ $data_key ] = array();
		$tmp = array_merge( $this->get_relative_files('helpers/*.php'), $this->get_relative_files('helpers/classes/*.php') );
		if ( ! empty($tmp) ) {
			foreach ( $tmp as $file ) {
				$this->data[ $data_key ][ $file ] = array(
					'key' => pathinfo($file, PATHINFO_FILENAME),
					'file' => $file,
					'loaded' => false,
				);
			}
		}
		return true;
	}

	private function setup_plugins( $data_key = '_plugins' ) {
		if ( empty($data_key) ) {
			return false;
		}
		if ( array_key_exists($data_key, $this->data) ) {
			return true;
		}
		$this->data[ $data_key ] = array();
		// array of keys (file path) => value (plugin path).
		foreach ( get_active_plugins() as $key ) {
			$tmp = $this->get_relative_files('plugins' . DIRECTORY_SEPARATOR . $key);
			if ( ! empty($tmp) ) {
				foreach ( $tmp as $file ) {
					$this->data[ $data_key ][ $file ] = array(
						'key' => $key,
						'file' => $file,
						'loaded' => false,
					);
				}
			}
		}
		return true;
	}
}
