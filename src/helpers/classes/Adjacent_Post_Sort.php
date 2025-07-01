<?php
namespace Halftheory\Lib\helpers\classes;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Adjacent_Post_Sort extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $post_type = null, $orderby = null ) {
		$this->data['post_type'] = $post_type;
		$this->data['orderby'] = $orderby;
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( is_public() ) {
			// Public.
			add_filter('get_previous_post_where', array( $this, 'public_get_previous_post_where' ), 20, 5);
			add_filter('get_next_post_where', array( $this, 'public_get_next_post_where' ), 20, 5);
			add_filter('get_previous_post_sort', array( $this, 'public_get_adjacent_post_sort' ), 20, 3);
			add_filter('get_next_post_sort', array( $this, 'public_get_adjacent_post_sort' ), 20, 3);
		} elseif ( $this->data['post_type'] ) {
			// Admin.
			add_filter('manage_' . $this->data['post_type'] . '_posts_columns', array( $this, 'admin_manage_post_posts_columns' ), 20);
			add_action('manage_' . $this->data['post_type'] . '_posts_custom_column', array( $this, 'admin_manage_post_posts_custom_column' ), 20, 2);
			add_filter('manage_edit-' . $this->data['post_type'] . '_sortable_columns', array( $this, 'admin_manage_edit_post_sortable_columns' ), 20);
		}
		parent::autoload();
	}

	// Public.

	public function public_get_previous_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $where;
		}
		if ( ! $this->data['post_type'] ) {
			return $where;
		}
		if ( ! $this->data['orderby'] ) {
			return $where;
		}
		if ( ! is_object($post) ) {
			return $where;
		}
		if ( $post->post_type !== $this->data['post_type'] ) {
			return $where;
		}
		$field = $this->data['orderby'];
		if ( ! property_exists($post, $field) ) {
			return $where;
		}
		if ( empty($post->$field) ) {
			return $where;
		}
		return preg_replace('/^(WHERE) p.post_date [^A]+(AND)/s', '$1 p.' . $field . ' < ' . $post->$field . ' $2', $where);
	}

	public function public_get_next_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $where;
		}
		if ( ! $this->data['post_type'] ) {
			return $where;
		}
		if ( ! $this->data['orderby'] ) {
			return $where;
		}
		if ( ! is_object($post) ) {
			return $where;
		}
		if ( $post->post_type !== $this->data['post_type'] ) {
			return $where;
		}
		$field = $this->data['orderby'];
		if ( ! property_exists($post, $field) ) {
			return $where;
		}
		if ( empty($post->$field) ) {
			return $where;
		}
		return preg_replace('/^(WHERE) p.post_date [^A]+(AND)/s', '$1 p.' . $field . ' > ' . $post->$field . ' $2', $where);
	}

	public function public_get_adjacent_post_sort( $order_by, $post, $order ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $order_by;
		}
		if ( ! $this->data['post_type'] ) {
			return $order_by;
		}
		if ( ! $this->data['orderby'] ) {
			return $order_by;
		}
		if ( ! is_object($post) ) {
			return $order_by;
		}
		if ( $post->post_type !== $this->data['post_type'] ) {
			return $order_by;
		}
		$field = $this->data['orderby'];
		if ( ! property_exists($post, $field) ) {
			return $order_by;
		}
		if ( empty($post->$field) ) {
			return $order_by;
		}
		return str_replace('p.post_date ', 'p.' . $field . ' ' . $order . ', p.post_date ', $order_by);
	}

	// Admin.

	public function admin_manage_post_posts_columns( $posts_columns = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $posts_columns;
		}
		if ( ! $this->data['orderby'] ) {
			return $posts_columns;
		}
		$results = array();
		foreach ( $posts_columns as $key => $value ) {
			$results[ $key ] = $value;
			if ( $key === 'title' ) {
				$results[ $this->data['orderby'] ] = ucwords(preg_replace('/[_-]+/', ' ', $this->data['orderby']));
			}
		}
		return $results;
	}

	public function admin_manage_post_posts_custom_column( $column_name, $post_id = 0 ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! $this->data['orderby'] ) {
			return;
		}
		if ( $column_name === $this->data['orderby'] ) {
			echo esc_html(get_post_field($this->data['orderby'], $post_id));
		}
	}

	public function admin_manage_edit_post_sortable_columns( $sortable_columns = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $sortable_columns;
		}
		if ( ! $this->data['orderby'] ) {
			return $sortable_columns;
		}
		if ( isset($sortable_columns[ $this->data['orderby'] ]) ) {
			return $sortable_columns;
		}
		$sortable_columns[ $this->data['orderby'] ] = array( $this->data['orderby'], true );
		return $sortable_columns;
	}
}
