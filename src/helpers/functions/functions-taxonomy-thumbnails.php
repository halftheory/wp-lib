<?php
if ( is_readable(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions-wp-taxonomy.php') ) {
	include_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions-wp-taxonomy.php';
}

if ( ! function_exists('delete_term_thumbnail') ) {
	function delete_term_thumbnail( $term ) {
		$term = ht_get_term($term);
		if ( $term ) {
			return delete_term_meta($term->term_id, '_thumbnail_id');
		}
		return false;
	}
}

if ( ! function_exists('get_term_thumbnail_id') ) {
	function get_term_thumbnail_id( $term ) {
		$term = ht_get_term($term);
		if ( ! $term ) {
			return false;
		}
		$thumbnail_id = get_term_meta($term->term_id, '_thumbnail_id', true);
		return empty($thumbnail_id) ? false : (int) $thumbnail_id;
	}
}

if ( ! function_exists('get_the_term_thumbnail') ) {
	function get_the_term_thumbnail( $term, $size = 'post-thumbnail', $attr = array() ) {
		$term = ht_get_term($term);
		if ( ! $term ) {
			return '';
		}
		$term_thumbnail_id = get_term_thumbnail_id($term);
		if ( $term_thumbnail_id ) {
			$html = wp_get_attachment_image($term_thumbnail_id, $size, false, $attr);
		} else {
			$html = '';
		}
		return $html;
	}
}

if ( ! function_exists('has_term_thumbnail') ) {
	function has_term_thumbnail( $term ) {
		$thumbnail_id  = get_term_thumbnail_id($term);
		return (bool) $thumbnail_id;
	}
}

if ( ! function_exists('set_term_thumbnail') ) {
	function set_term_thumbnail( $term, $thumbnail_id ) {
		$term = get_term($term);
		$thumbnail_id = absint($thumbnail_id);
		if ( $term && $thumbnail_id && get_post($thumbnail_id) ) {
			if ( wp_get_attachment_image($thumbnail_id, 'thumbnail') ) {
				return update_term_meta($term->term_id, '_thumbnail_id', $thumbnail_id);
			} else {
				return delete_term_meta($term->term_id, '_thumbnail_id');
			}
		}
		return false;
	}
}

if ( ! function_exists('the_term_thumbnail') ) {
	function the_term_thumbnail( $term, $size = 'post-thumbnail', $attr = array() ) {
		echo get_the_term_thumbnail($term, $size, $attr);
	}
}
