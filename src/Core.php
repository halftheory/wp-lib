<?php
namespace Halftheory\Lib;

use ReflectionClass;

#[AllowDynamicProperties]
abstract class Core {

	public static $handle;
	protected static $instance;
	protected $data = array();

	private function __clone() {
	}

	final public static function get_instance( $autoload = false ) {
		$called_class = get_called_class();
		if ( $autoload === true ) {
			static::$instance = new $called_class($autoload);
		} elseif ( ! isset(static::$instance) ) {
			static::$instance = new $called_class(false);
		}
		return static::$instance;
	}

	public function __construct( $autoload = false ) {
		$this->data['_autoload'] = $autoload;
		$this->set_handle();
		if ( $autoload === true ) {
			$this->autoload();
		}
	}

	protected function set_handle( $handle = null ) {
		static::$handle = $handle ? $handle : static::$handle;
		if ( is_null(static::$handle) ) {
			$array = explode('\\', get_called_class());
			static::$handle = end($array);
		}
		static::$handle = function_exists('sanitize_key') ? sanitize_key(static::$handle) : strtolower(static::$handle);
		static::$handle = preg_replace('/[^\w_-]/', '', static::$handle);
	}

	protected function autoload() {
	}

	// Functions.

	public function get( $key, $default = null ) {
		return $key && array_key_exists($key, $this->data) ? $this->data[ $key ] : $default;
	}

	final public function get_class_ancestors( $called_class = true ) {
		// Returns an array of keys (file path) => value (class name).
		$results = array();
		if ( class_exists('ReflectionClass') ) {
			$array = class_parents($this, false);
			if ( $called_class ) {
				$array = array_merge(array( get_called_class() ), $array);
			}
			foreach ( $array as $value ) {
				$obj = new ReflectionClass($value);
				if ( ! method_exists($obj, 'getFileName') ) {
					continue;
				}
				if ( $file = $obj->getFileName() ) {
					$file = safe_path($file);
					$results[ $file ] = $value;
				}
			}
		}
		return $results;
	}

	final public function get_directory_ancestors() {
		$dirs = function_exists('get_stylesheet_directory') ? safe_path(array( get_stylesheet_directory(), get_template_directory() )) : array();
		$tmp = $this->get_class_ancestors();
		if ( ! empty($tmp) ) {
			$dirs = array_merge($dirs, ht_dirname(array_keys($tmp)));
		}
		$dirs = array_values(array_unique($dirs));
		return $dirs;
	}

	final public function get_relative_files( $pattern, $flags = 0 ) {
		// Returns an array of file paths.
		$pattern = trim($pattern, DIRECTORY_SEPARATOR);
		$results = array();
		foreach ( $this->get_directory_ancestors() as $dir ) {
			$tmp = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pattern, $flags);
			if ( empty($tmp) ) {
				continue;
			}
			$results = array_merge($results, $tmp);
		}
		$results = array_filter(array_unique($results), 'is_readable');
		return $results;
	}

	final public function load_functions( $keys = '*' ) {
		if ( ! $this->pre_functions() ) {
			return false;
		}
		static $_files = array();
		static $_loaded = array();
		static $_classes = array();
		if ( ! in_array(get_called_class(), $_classes) ) {
			// Search for new files every time a new class is called.
			// Array of keys (file path) => value (short name).
			$tmp = array_merge( $this->get_relative_files('functions-*.php'), $this->get_relative_files('functions/*.php') );
			$tmp = array_diff($tmp, array_keys($_files), array_keys($_loaded));
			if ( ! empty($tmp) ) {
				$callback = function ( $path ) {
					return preg_replace('/^functions-/', '', pathinfo($path, PATHINFO_FILENAME), 1);
				};
				$tmp = array_combine($tmp, array_map($callback, $tmp));
				$_files = $_files + $tmp;
			}
			$_classes[] = get_called_class();
		}
		if ( empty($_files) ) {
			return $_loaded;
		}
		$keys = $keys === '*' ? array_keys($_files) : make_array($keys);
		if ( empty($keys) ) {
			return $_loaded;
		}
		foreach ( $keys as $value ) {
			// key = file path. already loaded.
			if ( isset($_loaded[ $value ]) ) {
				continue;
			}
			// key = file path.
			if ( isset($_files[ $value ]) ) {
				include_once $value;
				$_loaded[ $value ] = $_files[ $value ];
				unset($_files[ $value ]);
				continue;
			}
			// key = short name.
			if ( in_array($value, $_files) ) {
				$tmp = array_search($value, $_files);
				if ( is_file($tmp) ) {
					include_once $tmp;
					$_loaded[ $tmp ] = $value;
					unset($_files[ $tmp ]);
				}
			}
		}
		if ( empty($_files) ) {
			return $_loaded;
		}
		// Check if other files are already loaded.
		$included_files = safe_path(get_included_files());
		foreach ( $_files as $key => $value ) {
			if ( in_array($key, $included_files) ) {
				$_loaded[ $key ] = $value;
				unset($_files[ $key ]);
			}
		}
		return $_loaded;
	}

	protected function pre_functions() {
		static $_result = null;
		if ( is_null($_result) ) {
			$array = array();
			$files = array(
				'functions-php-array.php',
				'functions-php-filesystem.php',
				'functions-php-var.php',
			);
			foreach ( $files as $value ) {
				$value = __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . $value;
				if ( is_readable($value) ) {
					include_once $value;
					$array[] = $value;
				}
			}
			$_result = count($array) === count($files);
		}
		return $_result;
	}
}
