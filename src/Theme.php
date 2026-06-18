<?php
namespace Halftheory\Lib;

#[AllowDynamicProperties]
abstract class Theme extends Module {

	public static $handle;
	protected static $instance;
	protected $data = array();

	public function __construct( $autoload = false ) {
		// Load.
		$this->load_functions('php,wp-theme');
		set_encoding(get_bloginfo('charset'));
		if ( is_development() ) {
			set_symlinks(get_stylesheet_directory());
		}
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
		// Filters.

		// Helpers.

		// Plugins.

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
}
