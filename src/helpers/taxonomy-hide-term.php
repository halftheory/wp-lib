<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Taxonomy_Hide_Term extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		$this->load_functions('wp-taxonomy');
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		$this->data['taxonomies'] = array();
		$this->data['exclude'] = array();
		if ( is_public() ) {
			add_action('init', array( $this, 'public_init' ), 90);
			add_filter('get_terms_args', array( $this, 'public_get_terms_args' ), 20, 2);
			add_filter('wp_get_object_terms_args', array( $this, 'public_wp_get_object_terms_args' ), 20, 3);
			add_filter('get_terms', array( $this, 'public_get_terms' ), 20, 4);
		} else {
			// Admin.
			add_action('admin_init', array( $this, 'admin_init' ), 90);
			add_action('edited_term_taxonomy', array( $this, 'admin_edited_term_taxonomy' ), 20, 3);
			add_action('delete_term', array( $this, 'admin_delete_term' ), 20, 5);
		}
		parent::autoload();
	}

	// Public.

	public function public_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->data['taxonomies'] = get_taxonomies(array( 'public' => true ), 'names');
		if ( empty($this->data['taxonomies']) ) {
			return;
		}
		$terms = get_terms(
			array(
				'taxonomy' => $this->data['taxonomies'],
				'meta_key' => '_hide',
				'meta_value' => 1,
				'hide_empty' => false,
			)
		);
		if ( ! empty($terms) && ! is_wp_error($terms) ) {
			$this->data['exclude'] = array(
				'taxonomies' => array_unique(wp_list_pluck($terms, 'taxonomy')),
				'term_ids' => array_map('absint', wp_list_pluck($terms, 'term_id')),
			);
			foreach ( $terms as $value ) {
				$tmp = get_terms(
					array(
						'taxonomy' => $value->taxonomy,
						'child_of' => $value->term_id,
						'fields' => 'ids',
						'hide_empty' => false,
					)
				);
				if ( ! empty($tmp) && ! is_wp_error($tmp) ) {
					 $this->data['exclude']['term_ids'] = array_merge($this->data['exclude']['term_ids'], array_map('absint', $tmp));
				}
			}
		}
	}

	public function public_get_terms_args( $args = array(), $taxonomies = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $args;
		}
		if ( empty($this->data['exclude']) ) {
			return $args;
		}
		if ( empty(array_intersect($taxonomies, $this->data['exclude']['taxonomies'])) ) {
			return $args;
		}
		// Skip highly targeted queries.
		if ( isset($args['number']) && absint($args['number']) === 1 ) {
			$skip = true;
			$fields = array( 'taxonomy', 'object_ids', 'include', 'name', 'slug', 'term_taxonomy_id' );
			foreach ( $fields as $field ) {
				if ( array_key_exists($field, $args) ) {
					if ( count(make_array($args[ $field ])) > 1 ) {
						$skip = false;
						break;
					}
				}
			}
			if ( $skip ) {
				return $args;
			}
		}
		$args['exclude'] = isset($args['exclude']) ? array_unique(array_merge(array_map('absint', make_array($args['exclude'])), $this->data['exclude']['term_ids'])) : $this->data['exclude']['term_ids'];
		return $args;
	}

	public function public_wp_get_object_terms_args( $args = array(), $object_ids = array(), $taxonomies = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $args;
		}
		if ( empty($this->data['exclude']) ) {
			return $args;
		}
		if ( empty(array_intersect($taxonomies, $this->data['exclude']['taxonomies'])) ) {
			return $args;
		}
		$args['exclude'] = isset($args['exclude']) ? array_unique(array_merge(array_map('absint', make_array($args['exclude'])), $this->data['exclude']['term_ids'])) : $this->data['exclude']['term_ids'];
		return $args;
	}

	public function public_get_terms( $terms, $taxonomies, $args, $term_query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $terms;
		}
		if ( empty($terms) ) {
			return $terms;
		}
		if ( empty($this->data['exclude']) ) {
			return $terms;
		}
		if ( empty(array_intersect($taxonomies, $this->data['exclude']['taxonomies'])) ) {
			return $terms;
		}
		if ( ! isset($args['fields']) ) {
			return $terms;
		}
		switch ( $args['fields'] ) {
			case 'all':
				$callback = function ( $v ) {
					return ! in_array(absint($v->term_id), $this->data['exclude']['term_ids']);
				};
				$terms = array_filter($terms, $callback);
				break;

			case 'ids':
				$terms = array_diff(array_map('absint', $terms), $this->data['exclude']['term_ids']);
				break;

			default:
				break;
		}
		return $terms;
	}

	// Admin.

	public function admin_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->data['taxonomies'] = get_taxonomies(array( 'public' => true ), 'names');
		if ( empty($this->data['taxonomies']) ) {
			return;
		}
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
		$term = get_term_by('term_taxonomy_id', $tt_id, $taxonomy);
		if ( ! $term ) {
			return;
		}
		// Update term, only on Edit>Tag page.
		if ( isset($_POST, $_POST['_wpnonce']) ) {
			if ( wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-tag_' . $term->term_id) ) {
				if ( array_key_exists('hide', $_POST) ) {
					if ( is_true($_POST['hide']) ) {
						update_term_meta($term->term_id, '_hide', 1);
					} else {
						delete_term_meta($term->term_id, '_hide');
					}
				}
			}
		}
	}

	public function admin_delete_term( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		delete_term_meta($term, '_hide');
	}

	public function admin_taxonomy_edit_form_fields( $tag, $taxonomy ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Only public taxonomies.
		if ( ! in_array($taxonomy, $this->data['taxonomies']) ) {
			return;
		}
		$term = ht_get_term($tag, $taxonomy);
		if ( ! $term ) {
			return;
		}
		?>
<tr class="form-field <?php echo esc_attr(static::$handle); ?>">
	<th scope="row"><label for="hide"><?php esc_html_e('Visibility'); ?></label></th>
	<td><select name="hide" id="hide">
		<?php
		$options = array(
			0 => __('Public'),
			1 => __('Hidden'),
		);
		$current_value = is_true(get_term_meta($term->term_id, '_hide', true));
		foreach ( $options as $key => $value ) {
			$selected = $current_value === is_true($key) ? ' selected="selected"' : '';
			?>
			<option value="<?php echo esc_attr($key); ?>"<?php echo $selected; ?>><?php echo esc_html($value); ?></option>
			<?php
		}
		?>
	</select></td>
</tr>
		<?php
	}
}
