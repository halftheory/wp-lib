<?php
if ( ! function_exists('is_title_ok') ) {
	function is_title_ok( $title, $bad_titles = array() ) {
		if ( empty(trim($title)) ) {
			return false;
		}
		$defaults = array(
			__('Uncategorized'),
			__('Uncategorised'),
		);
		if ( ht_is_front_page() ) {
			$defaults[] = __('Home');
		}
		if ( is_posts_page() ) {
			$defaults[] = __('Archives');
			$defaults[] = __('Blog');
		}
		if ( is_archive() ) {
			$defaults[] = __('Archives');
		}
		if ( is_search() ) {
			$defaults[] = __('Archives');
		}
		$bad_titles = array_merge($defaults, make_array($bad_titles));
		$bad_titles = array_merge($bad_titles, array_map('strtolower', $bad_titles));
		return in_array( (string) trim($title), $bad_titles) ? false : $title;
	}
}

if ( ! function_exists('ht_get_the_ID') ) {
	function ht_get_the_ID() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = 0;
			if ( is_singular() || ( ! is_singular() && ! in_the_loop() ) ) {
				$_result = get_the_ID();
			} elseif ( ht_is_front_page() ) {
				if ( $tmp = get_post_front_page() ) {
					$_result = (int) $tmp->ID;
				}
			} elseif ( is_posts_page() ) {
				if ( $tmp = get_post_posts_page() ) {
					$_result = (int) $tmp->ID;
				}
			}
			if ( empty($_result) ) {
				// Some plugins like buddypress hide the real post_id in queried_object_id.
				global $wp_query;
				if ( isset($wp_query->queried_object_id) ) {
					$_result = $wp_query->queried_object_id;
				}
				if ( empty($_result) && isset($wp_query->post, $wp_query->post->ID) ) {
					$_result = $wp_query->post->ID;
				}
			}
		}
		return (int) $_result;
	}
}

if ( ! function_exists('match_page_template_slug') ) {
	function match_page_template_slug( $filename, $post = null ) {
		if ( ! $filename ) {
			return false;
		}
		$page_template_slug = get_page_template_slug($post);
		if ( empty($page_template_slug) ) {
			return false;
		}
		$array = array(
			$page_template_slug,
			ht_basename($page_template_slug),
			pathinfo($page_template_slug, PATHINFO_FILENAME),
		);
		return in_array($filename, $array, true);
	}
}

if ( ! function_exists('the_excerpt_fallback') ) {
	function the_excerpt_fallback( $excerpt = '', $post = null, $args = array() ) {
		if ( ! empty_zero_ok($excerpt) ) {
			return $excerpt;
		}
		$post = get_post($post);
		if ( ! $post ) {
			return $excerpt;
		}
		$defaults = array(
			'search' => array(
				'content' => array(
					'callbacks' => array( 'remove_excess_space' ),
					'separator' => '',
					'after' => '',
				),
				'children' => array(
					'callbacks' => array( 'ucfirst', 'trim' ),
					'separator' => '. ',
					'after' => '.',
				),
				'taxonomies' => array(
					'callbacks' => array( 'ucfirst', 'trim' ),
					'separator' => ', ',
					'after' => '.',
				),
			),
			'callbacks' => array( 'trim' ),
			'separator' => '. ',
			'after' => '.',
		);
		if ( isset($args['search']) && is_array($args['search']) ) {
			foreach ( $defaults['search'] as $key => $value ) {
				if ( ! array_key_exists($key, $args['search']) ) {
					$args['search'][ $key ] = $defaults['search'][ $key ];
				} elseif ( $args['search'][ $key ] === true ) {
					$args['search'][ $key ] = $defaults['search'][ $key ];
				}
			}
		}
		$args = wp_parse_args_recursive($args, $defaults, array( 'search' ));
		if ( ! is_array($args['search']) ) {
			return $excerpt;
		}
		$default_separator = array_key_exists('separator', $args) ? $args['separator'] : ' ';
		$search = array();
		foreach ( $args['search'] as $key => $value ) {
			if ( ! is_array($value) ) {
				continue;
			}
			// Find content.
			switch ( $key ) {
				case 'content':
					$tmp = get_the_content('', false, $post);
					if ( empty_zero_ok(trim(strip_shortcodes($tmp))) ) {
						break;
					}
					$search[ $key ] = array( $tmp );
					break;

				case 'children':
					$children_args = array(
						'post_parent' => $post->ID,
						'orderby' => 'menu_order',
						'order' => 'ASC',
						'post_status' => array( 'publish', 'inherit' ),
						'post_type' => array_values(array_diff(get_post_types(array( 'public' => true ), 'names'), array( 'attachment', 'revision' ))),
						'fields' => 'ids',
						'nopaging' => true,
					);
					$tmp = get_children($children_args);
					if ( empty($tmp) ) {
						break;
					}
					$search[ $key ] = array_map('get_the_title', $tmp);
					break;

				case 'taxonomies':
					$tmp = get_the_taxonomies($post->ID, array( 'template' => __('###%s###%l'), 'term_template' => '%2$s' ));
					if ( empty($tmp) ) {
						break;
					}
					$func_striptax = function ( $v = '' ) {
						$v = preg_replace('/^###[^#]*###/i', '', $v);
						return is_title_ok($v) ? $v : '';
					};
					$tmp = array_map($func_striptax, $tmp);
					$tmp = array_filter($tmp);
					if ( empty($tmp) ) {
						break;
					}
					$search[ $key ] = $tmp;
					break;

				default:
					break;
			}
			// Formatting.
			if ( isset($search[ $key ]) ) {
				if ( isset($value['callbacks']) && is_array($value['callbacks']) ) {
					foreach ( $value['callbacks'] as $callback ) {
						if ( ! is_callable($callback) ) {
							continue;
						}
						$search[ $key ] = array_map($callback, $search[ $key ]);
					}
				}
				$separator = array_key_exists('separator', $value) ? $value['separator'] : $default_separator;
				$search[ $key ] = array_map('trim', $search[ $key ], array_fill(0, count($search[ $key ]), $separator));
				$search[ $key ] = implode($separator, $search[ $key ]);
				if ( isset($value['after']) ) {
					$search[ $key ] = rtrim($search[ $key ], $value['after']) . $value['after'];
				}
			}
		}
		if ( empty($search) ) {
			return $excerpt;
		}
		// Final formatting.
		if ( isset($args['callbacks']) && is_array($args['callbacks']) ) {
			foreach ( $args['callbacks'] as $callback ) {
				if ( ! is_callable($callback) ) {
					continue;
				}
				$search = array_map($callback, $search);
			}
		}
		$search = array_map('trim', $search, array_fill(0, count($search), $default_separator));
		$search = implode($default_separator, $search);
		if ( isset($args['after']) ) {
			$search = rtrim($search, $args['after']) . $args['after'];
		}
		return $search;
	}
}
