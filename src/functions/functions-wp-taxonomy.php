<?php
if ( ! function_exists('get_taxonomy_objects') ) {
	function get_taxonomy_objects( $taxonomy ) {
		if ( $tmp = get_taxonomy($taxonomy) ) {
			if ( isset($tmp->object_type) ) {
				return make_array($tmp->object_type);
			}
		}
		return false;
	}
}

if ( ! function_exists('get_term_default_category') ) {
	function get_term_default_category( $field = null ) {
		$result = false;
		if ( $term = ht_get_term( (int) get_option('default_category'), 'category') ) {
			$result = ( is_object($term) && ! empty($field) && isset($term->$field) ) ? $term->$field : $term;
		}
		return $result;
	}
}

if ( ! function_exists('ht_get_ancestors') ) {
	function ht_get_ancestors( $object_id = null, $object_type = '', $resource_type = '', $child = true ) {
		// Expanded version of https://developer.wordpress.org/reference/functions/get_ancestors/
		list($object_id, $object_type, $resource_type) = parse_ancestors_args($object_id, $object_type, $resource_type);
		$ancestors = array();
		if ( empty($object_id) ) {
			return $ancestors;
		}

		// Child.
		if ( $child ) {
			$tmp = array(
				'object_id' => $object_id,
				'object_type' => $object_type,
				'resource_type' => $resource_type,
			);
			if ( $tmp = parse_ancestors_item($tmp) ) {
				$ancestors[] = $tmp;
			}
		}

		// Ancestors.
		foreach ( get_ancestors($object_id, $object_type, $resource_type) as $tmp_object_id ) {
			$tmp_object_type = $object_type;
			switch ( $resource_type ) {
				case 'post_type':
					$tmp_object_type = ht_get_post_type($tmp_object_id);
					break;
				case 'taxonomy':
					if ( $term = ht_get_term($tmp_object_id) ) {
						$tmp_object_type = $term->taxonomy;
					}
					break;
				default:
					break;
			}
			$tmp = array(
				'object_id' => $tmp_object_id,
				'object_type' => $tmp_object_type,
				'resource_type' => $resource_type,
			);
			if ( $tmp = parse_ancestors_item($tmp) ) {
				$ancestors[] = $tmp;
			}
		}
		// More ancestors.
		switch ( $resource_type ) {
			case 'post_type':
				$tmp = array();
				if ( $post_type_archive = get_post_post_type_archive($object_type) ) {
					$tmp = array(
						'object_id' => $post_type_archive->ID,
						'object_type' => $post_type_archive->post_type,
						'resource_type' => 'post_type',
					);
				} elseif ( $post_type_object = get_post_type_object($object_type) ) {
					$tmp = array(
						'title' => apply_filters('post_type_archive_title', $post_type_object->labels->name, $object_type),
						'url' => get_post_type_archive_link($object_type),
					);
				}
				if ( $tmp = parse_ancestors_item($tmp) ) {
					$ancestors[] = $tmp;
				}
				break;
			case 'taxonomy':
				if ( $taxonomy_archive = get_post_taxonomy_archive($object_type) ) {
					$tmp = array(
						'object_id' => $taxonomy_archive->ID,
						'object_type' => $taxonomy_archive->post_type,
						'resource_type' => 'post_type',
					);
					if ( $tmp = parse_ancestors_item($tmp) ) {
						$ancestors[] = $tmp;
					}
				}
				break;
			default:
				break;
		}

		return $ancestors;
	}
}

if ( ! function_exists('ht_get_term') ) {
	function ht_get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
		$term = get_term($term, $taxonomy, $output, $filter);
		return is_wp_error($term) || empty($term) ? false : $term;
	}
}

if ( ! function_exists('ht_register_taxonomy') ) {
	function ht_register_taxonomy( $taxonomies, $object_type = null, $args = array() ) {
		$results = array();
		if ( is_null($object_type) ) {
			$object_type = get_post_types(array( 'public' => true ), 'names');
			global $typenow;
			if ( ! empty($typenow) && ! in_array($typenow, $object_type, true) ) {
				$object_type[] = $typenow;
			}
		} else {
			$object_type = make_array($object_type);
		}
		foreach ( make_array($taxonomies) as $taxonomy ) {
			$results[ $taxonomy ] = taxonomy_exists($taxonomy);
			if ( $results[ $taxonomy ] ) {
				continue;
			}
			$plural_name = rtrim($taxonomy, 's') . 's';
			$defaults = array(
				'description' => $taxonomy,
				'label' => ucfirst($plural_name),
				'public' => true,
				'show_ui' => false,
				'show_in_nav_menus' => false,
				'show_in_rest' => false,
				'hierarchical' => true,
				'query_var' => false,
				'rewrite' => array(
					'slug' => $plural_name,
					'with_front' => false,
				),
			);
			$args = wp_parse_args(make_array($args), $defaults);
			$results[ $taxonomy ] = register_taxonomy($taxonomy, $object_type, $args);
		}
		return $results;
	}
}

if ( ! function_exists('parse_ancestors_args') ) {
	function parse_ancestors_args( $object_id = null, $object_type = '', $resource_type = '' ) {
		if ( $object_id && $object_type && $resource_type ) {
			return array( $object_id, $object_type, $resource_type );
		}
		if ( ! $object_id ) {
			$queried_object = get_queried_object();
			if ( is_object($queried_object) ) {
				if ( isset($queried_object->ID, $queried_object->post_type) ) {
					$object_id = $queried_object->ID;
					$object_type = $queried_object->post_type;
					$resource_type = 'post_type';
				} elseif ( isset($queried_object->term_id, $queried_object->taxonomy) ) {
					$object_id = $queried_object->term_id;
					$object_type = $queried_object->taxonomy;
					$resource_type = 'taxonomy';
				}
			}
		}
		if ( empty($object_id) || empty($object_type) ) {
			return array( null, '', '' );
		}
		if ( ! $resource_type ) {
			if ( is_taxonomy_hierarchical($object_type) ) {
				$resource_type = 'taxonomy';
			} elseif ( post_type_exists($object_type) ) {
				$resource_type = 'post_type';
			}
		}
		return array( $object_id, $object_type, $resource_type );
	}
}

if ( ! function_exists('parse_ancestors_item') ) {
	function parse_ancestors_item( $array = array() ) {
		$defaults = array(
			'object_id' => null,
			'object_type' => '',
			'resource_type' => '',
			'prepend' => '',
			'title' => '',
			'append' => '',
			'url' => '',
		);
		$array = wp_parse_args($array, $defaults);
		if ( $array['object_id'] ) {
			switch ( $array['resource_type'] ) {
				case 'post_type':
					if ( ! is_user_logged_in() && ! is_post_publicly_viewable($array['object_id']) ) {
						return false;
					}
					if ( empty($array['title']) ) {
						$array['title'] = current_filter() === 'the_title' ? get_post_field('post_title', $array['object_id']) : get_the_title($array['object_id']);
					}
					if ( empty($array['url']) ) {
						$array['url'] = current_filter() === 'post_link' ? get_post($array['object_id'])->url : get_permalink($array['object_id']);
					}
					break;
				case 'taxonomy':
					if ( ! is_user_logged_in() && ! is_term_publicly_viewable($array['object_id']) ) {
						return false;
					}
					if ( empty($array['title']) ) {
						$array['title'] = get_single_term_title($array['object_id']);
					}
					if ( empty($array['url']) ) {
						$array['url'] = get_term_link($array['object_id']);
					}
					if ( empty($array['prepend']) ) {
						if ( $taxonomy_labels = get_taxonomy_labels(get_taxonomy($array['object_type'])) ) {
							$array['prepend'] = $taxonomy_labels->singular_name;
						}
					}
					break;
				default:
					break;
			}
		}
		if ( empty($array['title']) || empty($array['url']) ) {
			return false;
		}
		foreach ( array( 'prepend', 'title', 'append' ) as $key ) {
			$array[ $key ] = sanitize_text_field($array[ $key ]);
		}
		return $array;
	}
}
