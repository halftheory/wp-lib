<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Taxonomy_Thumbnails extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		$this->load_functions('taxonomy-thumbnails');
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( ! is_public() ) {
			// Admin.
			add_action('edited_term_taxonomy', array( $this, 'admin_edited_term_taxonomy' ), 20, 3);
			add_action('delete_term', array( $this, 'admin_delete_term' ), 20, 5);
		}
		parent::autoload();
	}

	// Admin.

	public function admin_edited_term_taxonomy( $tt_id, $taxonomy, $args = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$term = get_term_by('term_taxonomy_id', $tt_id, $taxonomy);
		if ( ! $term ) {
			return;
		}
		$args = array(
			'post_type' => 'any',
			'post_status' => 'any',
			'fields' => 'ids',
			'nopaging' => true,
			'orderby' => 'date',
			'order' => 'DESC',
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $term->term_id,
				),
			),
			'numberposts' => 20,
		);
		$posts = ht_get_posts($args);
		if ( ! $posts ) {
			return;
		}
		// Update thumbnail.
		$thumbnail_id = null;
		foreach ( $posts as $value ) {
			switch ( ht_get_post_type($value) ) {
				case 'attachment':
					$thumbnail_id = $value;
					break;
				default:
					$thumbnail_id = get_post_thumbnail_id($value);
					break;
			}
			if ( $thumbnail_id ) {
				$result = set_term_thumbnail($term, $thumbnail_id);
				if ( is_numeric($result) || $result === true ) {
					break;
				}
			}
		}
	}

	public function admin_delete_term( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		delete_term_thumbnail($term);
	}
}
