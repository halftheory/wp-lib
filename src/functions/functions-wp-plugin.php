<?php
if ( ! function_exists('get_filter_next_priority') ) {
	function get_filter_next_priority( $tag, $priority_start = 10 ) {
		global $wp_filter;
		$i = $priority_start;
		if ( isset($wp_filter[ $tag ]) ) {
			while ( $wp_filter[ $tag ]->offsetExists($i) === true ) {
				$i++;
			}
		}
		return $i;
	}
}

if ( ! function_exists('ht_get_plugin_data') ) {
	function ht_get_plugin_data( $plugin_file, $key = null, $default = null ) {
		$data = set_plugin_data($plugin_file);
		if ( $key ) {
			return is_array($data) && array_key_exists($key, $data) ? $data[ $key ] : $default;
		}
		return $data;
	}
}

if ( ! function_exists('ht_has_filter') ) {
	function ht_has_filter( $hook_name, $callback = false ) {
		global $wp_filter;
		if ( ! is_array($wp_filter) ) {
			return false;
		}
		if ( ! isset($wp_filter[ $hook_name ]) ) {
			return false;
		}
		$result = $wp_filter[ $hook_name ]->has_filter($hook_name, $callback);
		// Check for class names.
		if ( $result === false && is_array($callback) && count($callback) > 1 ) {
			// Clear the keys.
			$callback = array_values($callback);
			if ( is_string($callback[0]) && is_string($callback[1]) && method_exists($callback[0], $callback[1]) ) {
				foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
					foreach ( $callbacks as $function_key => $callback ) {
						if ( ! is_array($callback) ) {
							continue;
						}
						if ( ! isset($callback['function']) ) {
							continue;
						}
						if ( ! is_array($callback['function']) ) {
							continue;
						}
						if ( count($callback['function']) < 2 ) {
							continue;
						}
						if ( is_object($callback['function'][0]) && is_string($callback['function'][1]) ) {
							if ( is_a($callback['function'][0], $callback[0]) && $callback['function'][1] === $callback[1] ) {
								return $priority;
							}
						}
					}
				}
			}
		}
		return $result;
	}
}

if ( ! function_exists('ht_remove_filter') ) {
	function ht_remove_filter( $hook_name, $callback, $priority = 10 ) {
		global $wp_filter;
		if ( ! is_array($wp_filter) ) {
			return false;
		}
		if ( ! isset($wp_filter[ $hook_name ]) ) {
			return false;
		}
		$result = $wp_filter[ $hook_name ]->remove_filter($hook_name, $callback, $priority);
		// Check for class names.
		if ( $result === false && is_array($callback) && count($callback) > 1 ) {
			// Clear the keys.
			$callback = array_values($callback);
			if ( is_string($callback[0]) && is_string($callback[1]) && method_exists($callback[0], $callback[1]) && isset($wp_filter[ $hook_name ]->callbacks[ $priority ]) ) {
				foreach ( $wp_filter[ $hook_name ]->callbacks[ $priority ] as $function_key => $callback ) {
					if ( ! is_array($callback) ) {
						continue;
					}
					if ( ! isset($callback['function']) ) {
						continue;
					}
					if ( ! is_array($callback['function']) ) {
						continue;
					}
					if ( count($callback['function']) < 2 ) {
						continue;
					}
					if ( is_object($callback['function'][0]) && is_string($callback['function'][1]) ) {
						if ( is_a($callback['function'][0], $callback[0]) && $callback['function'][1] === $callback[1] ) {
							unset($wp_filter[ $hook_name ]->callbacks[ $priority ][ $function_key ]);
							$result = true;
						}
					}
				}
			}
		}
		// Remove empty filters.
		if ( ! $wp_filter[ $hook_name ]->callbacks ) {
			unset($wp_filter[ $hook_name ]);
		}
		return $result;
	}
}

if ( ! function_exists('set_plugin_data') ) {
	function set_plugin_data( $plugin_file, $array = array() ) {
		static $_data = array();
		if ( ! array_key_exists($plugin_file, $_data) ) {
			$_data[ $plugin_file ] = array();
			$plugin_basename = plugin_basename($plugin_file);
			$defaults = array(
				'Name' => ucwords(str_replace('_', ' ', ht_dirname($plugin_basename))),
				'DomainPath' => file_exists(path_join(ht_dirname($plugin_file), 'languages')) ? path_join(ht_dirname($plugin_file), 'languages') : null,
				'Network' => false,
				'Title' => ucwords(str_replace('_', ' ', ht_dirname($plugin_basename))),
				'handle' => ht_dirname($plugin_basename),
				'plugin_basename' => $plugin_basename,
			);
			if ( is_multisite() ) {
				if ( ! function_exists('is_plugin_active_for_network') ) {
					require_once path_join(ABSPATH, 'wp-admin/includes/plugin.php');
				}
				$defaults['Network'] = is_plugin_active_for_network($plugin_basename);
			}
			if ( ! function_exists('get_plugin_data') ) {
				require_once path_join(ABSPATH, 'wp-admin/includes/plugin.php');
			}
			$_data[ $plugin_file ] = get_plugin_data($plugin_file);
			foreach ( $_data[ $plugin_file ] as $key => &$value ) {
				if ( ! $value && array_key_exists($key, $defaults) ) {
					$value = $defaults[ $key ];
				}
			}
			$_data[ $plugin_file ] = wp_parse_args($_data[ $plugin_file ], $defaults);
		}
		$_data[ $plugin_file ] = wp_parse_args($array, $_data[ $plugin_file ]);
		return $_data[ $plugin_file ];
	}
}
