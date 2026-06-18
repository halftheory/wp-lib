<?php
$array = array(
	'functions-wp-load.php',
);
foreach ( $array as $value ) {
	if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . $value) ) {
		include_once __DIR__ . DIRECTORY_SEPARATOR . $value;
	}
}

if ( ! function_exists('get_file_version') ) {
	function get_file_version( $file, $default = null ) {
		if ( ! file_exists($file) || ! is_file($file) ) {
			return $default;
		}
		if ( is_development() ) {
			return filemtime($file);
		}
		// Maybe parent theme?
		if ( is_child_theme() && str_starts_with($file, get_template_directory()) ) {
			$tmp = wp_get_theme()->parent()->get('Version');
			if ( ! empty($tmp) ) {
				return $tmp;
			}
		}
		return ht_get_theme_data('Version', $default);
	}
}

if ( ! function_exists('get_stylesheet_uri_from_file') ) {
	function get_stylesheet_uri_from_file( $file ) {
		if ( ! path_is_absolute($file) ) {
			return false;
		}
		if ( ! file_exists($file) || ! is_file($file) ) {
			return false;
		}
		$file = maybe_restore_symlink_path($file);
		if ( str_starts_with($file, get_stylesheet_directory()) ) {
			return str_replace(trailingslashit(get_stylesheet_directory()), trailingslashit(get_stylesheet_directory_uri()), $file);
		}
		if ( str_starts_with($file, get_template_directory()) ) {
			return str_replace(trailingslashit(get_template_directory()), trailingslashit(get_template_directory_uri()), $file);
		}
		if ( str_starts_with($file, ABSPATH) ) {
			return str_replace(trailingslashit(ABSPATH), trailingslashit(wp_guess_url()), $file);
		}
		// Search for files linked inside stylesheet/template directories.
		$search = array(
			'vendor',
			'node_modules',
			'src',
			'assets',
			'app',
		);
		$tmp = DIRECTORY_SEPARATOR . ht_basename(get_stylesheet_directory()) . DIRECTORY_SEPARATOR;
		if ( str_contains($file, $tmp) ) {
			$tmp = explode($tmp, $file, 2);
			$end = end($tmp);
			if ( file_exists(path_join(get_stylesheet_directory(), $end)) ) {
				return trailingslashit(get_stylesheet_directory_uri()) . $end;
			}
		}
		foreach ( $search as &$value ) {
			$value = DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR;
			if ( str_contains($file, $value) ) {
				$tmp = explode($value, $file, 2);
				$end = $value . end($tmp);
				if ( file_exists(untrailingslashit(get_stylesheet_directory()) . $end) ) {
					return untrailingslashit(get_stylesheet_directory_uri()) . $end;
				}
			}
		}
		$tmp = DIRECTORY_SEPARATOR . ht_basename(get_template_directory()) . DIRECTORY_SEPARATOR;
		if ( str_contains($file, $tmp) ) {
			$tmp = explode($tmp, $file, 2);
			$end = end($tmp);
			if ( file_exists(path_join(get_template_directory(), $end)) ) {
				return trailingslashit(get_template_directory_uri()) . $end;
			}
		}
		foreach ( $search as $value ) {
			if ( str_contains($file, $value) ) {
				$tmp = explode($value, $file, 2);
				$end = $value . end($tmp);
				if ( file_exists(untrailingslashit(get_template_directory()) . $end) ) {
					return untrailingslashit(get_template_directory_uri()) . $end;
				}
			}
		}
		return false;
	}
}

if ( ! function_exists('get_theme_colors') ) {
	function get_theme_colors( $keys = array() ) {
 		$data = ht_get_theme_data();
		if ( empty($data) ) {
			return array();
		}
		$array = array();
		foreach ( $data as $key => $value ) {
			if ( empty($value) ) {
				continue;
			}
			if ( str_contains($key, 'color') || str_contains($key, 'Color') ) {
				$array[ $key ] = $value;
			}
		}
		if ( empty($keys) ) {
			return $array;
		}
		$result = array_fill_keys(array_values($keys), null);
		foreach ( $result as $key => &$value ) {
			if ( isset($data[ $key ]) ) {
				$value = $data[ $key ];
				if ( array_key_exists($key, $array) ) {
					unset($array[ $key ]);
				}
			}
		}
		foreach ( $result as $key => &$value ) {
			if ( $value ) {
				continue;
			}
			if ( ! empty($array) ) {
				$value = array_shift($array);
			}
		}
		return array_filter($result);
	}
}

if ( ! function_exists('get_uri_from_npm') ) {
	function get_uri_from_npm( $args, $fallback = null ) {
		$defaults = array(
			'package' => null,
			'version' => null,
			'file' => null,
		);
		$args = wp_parse_args($args, $defaults);

		$function_fallback = function () use ( $fallback ) {
			if ( $fallback ) {
				foreach ( make_array($fallback) as $value ) {
					if ( str_starts_with($value, 'http') || str_starts_with($value, '//') ) {
						return $value;
					}
					if ( $tmp = get_stylesheet_uri_from_file($value) ) {
						return $tmp;
					}
				}
			}
			return false;
		};

		$result = false;

		if ( isset($args['package']) && isset($args['file']) ) {
			// Development.
			if ( is_development() ) {
				$array = array(
					get_stylesheet_directory(),
					'node_modules',
					$args['package'],
					$args['file'],
				);
				if ( $tmp = get_stylesheet_uri_from_file(implode(DIRECTORY_SEPARATOR, $array)) ) {
					// node_modules folder.
					$result = $tmp;
				} elseif ( $tmp = $function_fallback() ) {
					// Fallback, possibly vendor folder.
					$result = $tmp;
				}
			}
			// Production.
			if ( ! $result ) {
				$array = array(
					'//cdn.jsdelivr.net/npm',
					isset($args['version']) ? $args['package'] . '@' . $args['version'] : $args['package'],
					$args['file'],
				);
				$result = implode('/', $array);
			}
		} else {
			$result = $function_fallback();
		}
		return apply_filters('get_uri_from_npm', $result, $args);
	}
}

if ( ! function_exists('ht_get_theme_data') ) {
	function ht_get_theme_data( $key = null, $default = null ) {
		$data = set_theme_data();
		if ( $key ) {
			return is_array($data) && array_key_exists($key, $data) ? $data[ $key ] : $default;
		}
		return $data;
	}
}

if ( ! function_exists('ht_get_theme_root') ) {
	function ht_get_theme_root() {
		if ( ! function_exists('get_theme_root') ) {
			require_once ABSPATH . 'wp-includes/theme.php';
		}
		$array = array(
			get_theme_root(),
			path_join(WP_CONTENT_DIR, get_theme_root()),
			apply_filters('theme_root', path_join(WP_CONTENT_DIR, 'themes')),
		);
		foreach ( $array as $value ) {
			if ( is_dir($value) ) {
				return untrailingslashit($value);
			}
		}
		return false;
	}
}

if ( ! function_exists('min_scripts') ) {
	function min_scripts() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = ( is_development() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) ? '' : '.min';
		}
		return $_result;
	}
}

if ( ! function_exists('set_theme_data') ) {
	function set_theme_data( $array = array() ) {
		static $_data = null;
		if ( is_null($_data) ) {
			$_data = array();
			$defaults = array(
				'Name' => ucwords(str_replace('_', ' ', ht_basename(get_stylesheet_directory()))),
				'ThemeURI' => home_url('/'),
				'Author' => get_the_author(),
				'AuthorURI' => home_url('/'),
				'Status' => 'publish',
				'DomainPath' => file_exists(path_join(get_stylesheet_directory(), 'languages')) ? path_join(get_stylesheet_directory(), 'languages') : null,
				'handle' => get_stylesheet(),
			);
			if ( ! function_exists('wp_get_theme') ) {
				require_once path_join(WPINC, 'theme.php');
			}
			$wp_get_theme = wp_get_theme();
			$keys = array(
				'Name',
				'ThemeURI',
				'Description',
				'Author',
				'AuthorURI',
				'Version',
				'Template',
				'Status',
				'Tags',
				'TextDomain',
				'DomainPath',
				'RequiresWP',
				'RequiresPHP',
				'UpdateURI',
			);
			foreach ( $keys as $key ) {
				$value = $wp_get_theme->get($key);
				if ( ! $value && array_key_exists($key, $defaults) ) {
					$value = $defaults[ $key ];
				}
				$_data[ $key ] = $value;
			}
			$_data = wp_parse_args($_data, $defaults);
		}
		$_data = wp_parse_args($array, $_data);
		return $_data;
	}
}

if ( ! function_exists('theme_textdomain') ) {
	function theme_textdomain() {
		return ht_get_theme_data('TextDomain');
	}
}
