<?php
if ( ! function_exists('get_template_tags') ) {
	function get_template_tags() {
		$tag_templates = array(
			'is_404'               => 'get_404_template',
			'is_archive'           => 'get_archive_template',
			'is_attachment'        => 'get_attachment_template',
			'is_author'            => 'get_author_template',
			'is_category'          => 'get_category_template',
			'is_date'              => 'get_date_template',
			'is_embed'             => 'get_embed_template',
			'is_front_page'        => 'get_front_page_template',
			'is_home'              => 'get_home_template',
			'is_page'              => 'get_page_template',
			'is_post_type_archive' => 'get_post_type_archive_template',
			'is_privacy_policy'    => 'get_privacy_policy_template',
			'is_search'            => 'get_search_template',
			'is_single'            => 'get_single_template',
			'is_singular'          => 'get_singular_template',
			'is_tag'               => 'get_tag_template',
			'is_tax'               => 'get_taxonomy_template',
		);
		$order = array(
			'is_front_page',
			'is_privacy_policy',
			'is_search',
			'is_singular',
			'is_home',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_date',
			'is_embed',
			'is_page',
			'is_post_type_archive',
			'is_single',
			'is_tag',
			'is_tax',
			'is_404',
		);
		$tag_templates = array_merge(array_intersect_key(array_flip($order), $tag_templates), $tag_templates);
		$tag_templates = array_filter($tag_templates, 'is_callable');
		return $tag_templates;
	}
}

if ( ! function_exists('get_template_types') ) {
	function get_template_types() {
		// https://developer.wordpress.org/reference/functions/get_query_template/
		return array( '404', 'archive', 'attachment', 'author', 'category', 'date', 'embed', 'frontpage', 'home', 'index', 'page', 'paged', 'privacypolicy', 'search', 'single', 'singular', 'tag', 'taxonomy' );
	}
}

if ( ! function_exists('ht_get_query_template') ) {
	function ht_get_query_template( $wp_query = null ) {
		// Adapted from wp-includes/template-loader.php
		// Try to respect the WordPress Template Hierarchy - https://wphierarchy.com/
		// Loop through each of the template conditionals, and find the appropriate template file.
		$template = null;
		foreach ( get_template_tags() as $tag => $template_getter ) {
			if ( is_object($wp_query) ) {
				if ( $wp_query->$tag ) {
					$template = call_user_func($template_getter);
				}
			} elseif ( is_callable($tag) && call_user_func($tag) ) {
				$template = call_user_func($template_getter);
			}
			if ( $template ) {
				break;
			}
		}
		if ( ! $template ) {
			$template = get_index_template();
		}
		return apply_filters('template_include', $template);
	}
}
