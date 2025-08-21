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
		$this->load_functions('wp-taxonomy,taxonomy-thumbnails');
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( ! is_public() ) {
			// Admin.
			$this->data['taxonomies'] = array();
			add_action('admin_init', array( $this, 'admin_init' ), 90);
			add_action('edited_term_taxonomy', array( $this, 'admin_edited_term_taxonomy' ), 20, 3);
			add_action('delete_term', array( $this, 'admin_delete_term' ), 20, 5);
		}
		parent::autoload();
	}

	// Admin.

	public function admin_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->data['taxonomies'] = get_taxonomies(array( 'public' => true ), 'names');
		foreach ( $this->data['taxonomies'] as $taxonomy ) {
			add_action($taxonomy . '_edit_form_fields', array( $this, 'admin_taxonomy_edit_form_fields' ), 20, 2);
		}
	}

	public function admin_edited_term_taxonomy( $tt_id, $taxonomy, $args = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Only public taxonomies.
		if ( ! in_array($taxonomy, $this->data['taxonomies']) ) {
			return;
		}
		$term = ht_get_term(get_term_by('term_taxonomy_id', $tt_id, $taxonomy));
		if ( ! $term ) {
			return;
		}
		$args = array(
			'post_type' => 'any',
			'post_status' => 'any',
			'fields' => 'ids',
			'orderby' => 'date,post_title',
			'order' => 'DESC',
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $term->term_id,
				),
			),
			'numberposts' => 10,
		);
		$posts = ht_get_posts($args);
		if ( ! $posts ) {
			return;
		}
		// Get potential thumbnails.
		$array = array();
		foreach ( $posts as $value ) {
			$tmp = null;
			switch ( ht_get_post_type($value) ) {
				case 'attachment':
					$tmp = $value;
					break;
				default:
					$tmp = get_post_thumbnail_id($value);
					break;
			}
			if ( $tmp ) {
				$array[] = $tmp;
			}
		}
		if ( empty($array) ) {
			delete_term_thumbnail($term);
			return;
		}
		$array = array_map('absint', $array);
		// Get existing thumbnails.
		$terms_thumbnails = array();
		$terms = get_terms(
			array(
				'taxonomy' => $taxonomy,
				'fields' => 'ids',
				'hide_empty' => false,
				'exclude' => $term->term_id,
				'meta_query' => array(
					array(
						'key' => '_thumbnail_id',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		if ( ! empty($terms) && ! is_wp_error($terms) ) {
			$terms = array_filter($terms, 'is_term_publicly_viewable');
			$terms_thumbnails = array_combine(array_map('absint', $terms), array_map('absint', array_map('get_term_thumbnail_id', $terms)));
		}
		// Update thumbnail - prefer unique values.
		foreach ( $array as $value ) {
			if ( in_array($value, $terms_thumbnails) ) {
				continue;
			}
			$result = set_term_thumbnail($term, $value);
			if ( is_numeric($result) || $result === true ) {
				return;
			}
		}
		// Update it anyway.
		foreach ( $array as $value ) {
			$result = set_term_thumbnail($term, $value);
			if ( is_numeric($result) || $result === true ) {
				return;
			}
		}
	}

	public function admin_delete_term( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		delete_term_thumbnail($term);
	}

	public function admin_taxonomy_edit_form_fields( $tag, $taxonomy ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( $attachment_id = get_term_thumbnail_id($tag) ) {
			$this->load_functions('wp-media');
			if ( $img = get_image_context('img', $attachment_id, 'thumbnail', array( 'id' => 'thumbnail' )) ) {
				if ( $link = get_edit_post_link($attachment_id) ) {
					$img = '<a href="' . esc_url($link) . '" title="' . esc_attr__('Edit') . '">' . $img . '</a>';
				}
				?>
<tr class="form-field <?php echo esc_attr(static::$handle); ?>">
	<th scope="row"><label for="thumbnail"><?php esc_html_e('Thumbnail'); ?></label></th>
	<td><?php echo $img; ?></td>
</tr>
				<?php
			}
		}
	}
}
