<?php
namespace Halftheory\Lib;

#[AllowDynamicProperties]
abstract class Module extends Core {

	public static $handle;
	protected static $instance;
	protected $data = array();

	public function __construct( $autoload = false ) {
		// Load.
		$this->load_functions('php');
		parent::__construct($autoload);
	}

	protected function autoload() {
		parent::autoload();
	}

	// Functions.

	final public function load_constants( $array, $display_errors = true ) {
		$results = array();
		$errors = array();
		foreach ( make_array($array) as $key => $value ) {
			if ( is_numeric($key) ) {
				continue;
			}
			if ( ! defined($key) ) {
				define($key, $value);
				$results[ $key ] = $value;
				continue;
			} elseif ( constant($key) === $value ) {
				continue;
			} else {
				$errors[ $key ] = array( 'old' => constant($key), 'new' => $value );
			}
		}
		if ( ! empty($errors) && $display_errors ) {
			$this->load_functions('wp-load,wp-pluggable');
			$var_to_string = function ( $value ) {
				if ( is_array($value) ) {
					return 'array';
				} elseif ( is_object($value) ) {
					return 'object';
				} elseif ( is_resource($value) ) {
					return 'resource';
				} elseif ( is_string($value) && empty($value) ) {
					return 'empty string';
				} elseif ( is_string($value) || is_numeric($value) ) {
					return $value;
				} elseif ( is_bool($value) ) {
					return $value ? 'true' : 'false';
				} elseif ( is_null($value) ) {
					return 'null';
				} else {
					return (string) $value;
				}
			};
            $message = 'Cannot redefine the following constants. They have already been defined elsewhere.' . "\n";
            foreach ( $errors as $key => $value ) {
            	$message .= "\n<strong>" . $key . '</strong> => ' . $var_to_string($value['old']) . ' (current) => ' . $var_to_string($value['new']) . ' (new)';
            }
			if ( is_development() ) { // todo
				ht_var_dump($message,0);
				ht_var_dump($errors);
	            throw new \RuntimeException($message);
        	} elseif ( ht_is_user_logged_in() ) {
				$this->load_functions('wp-admin');
        		ht_admin_notices('set', $message, 'error', true, 'is_administrator');
        	}
		}
		return empty($results) ? false : $results;
	}

	final public function load_filters( $values, $data_key = '_filters' ) {
		// Could be a single object, or an array of objects.
		$callback = function ( $v ) {
			if ( is_object($v) ) {
				return $v;
			}
			if ( ! class_exists($v) ) {
				return false;
			}
			$v = is_subclass_of($v, 'Halftheory\Lib\Filters') ? new $v(true) : new $v();
			return is_object($v) ? $v : false;
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
		$callback = function ( $v ) {
			return is_array($v) && array_key_exists('loaded', $v) ? $v['loaded'] : false;
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
		// Array of keys (file path) => value (plugin path).
		$this->load_functions('wp-load');
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
