<?php
namespace Halftheory\Lib\helpers\classes;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Add_Taxonomy extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $taxonomy = null, $object_type = null, $taxonomy_args = array(), $include_children = false ) {
		$this->data['taxonomy'] = $taxonomy;
		$this->data['object_type'] = $object_type ? make_array($object_type) : null;
		$this->data['taxonomy_args'] = $taxonomy_args;
		$this->data['include_children'] = is_true($include_children);
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('init', array( $this, 'global_init' ), 20);
		if ( is_public() ) {
			// Public.
			$this->data['query_vars_defaults'] = array();
			add_filter('pre_get_posts', array( $this, 'public_pre_get_posts' ), 90);
			add_action('parse_tax_query', array( $this, 'public_parse_tax_query' ), 20);
		} else {
			// Admin.
			$this->data['sortable_columns'] = array();
			if ( $this->data['taxonomy'] && $this->data['object_type'] ) {
				switch ( $this->data['taxonomy'] ) {
					case 'category':
						$value = 'categories';
						break;
					case 'post_tag':
						$value = 'tags';
						break;
					default:
						$value = 'taxonomy-' . $this->data['taxonomy'];
						break;
				}
				foreach ( $this->data['object_type'] as $object_type ) {
					switch ( $object_type ) {
						case 'attachment':
							$key = 'manage_upload_sortable_columns';
							break;
						default:
							$key = 'manage_edit-' . $object_type . '_sortable_columns';
							break;
					}
					// Key = filter, value = column.
					$this->data['sortable_columns'][ $key ] = $value;
					add_filter($key, array( $this, 'admin_manage_sortable_columns' ), 20);
				}
			}
			add_filter('posts_clauses', array( $this, 'admin_posts_clauses' ), 20, 2);
		}
		parent::autoload();
	}

	// Global.

	public function global_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! $this->data['taxonomy'] ) {
			return;
		}
		if ( ! $this->data['object_type'] ) {
			return;
		}
		$tmp = ht_register_taxonomy($this->data['taxonomy'], $this->data['object_type'], $this->data['taxonomy_args']);
		if ( isset($tmp[ $this->data['taxonomy'] ]) ) {
			foreach ( $this->data['object_type'] as $object_type ) {
				register_taxonomy_for_object_type($this->data['taxonomy'], $object_type);
			}
		}
	}

	// Public.

	public function public_pre_get_posts( $query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( did_filter('request') === 0 ) {
			return;
		}
		// Apply the defaults.
		if ( ! empty($this->data['query_vars_defaults']) ) {
			foreach ( $this->data['query_vars_defaults'] as $key => $value ) {
				$tmp = make_array($query->get($key));
				if ( in_array('any', $tmp) ) {
					continue;
				}
				$tmp = array_unique(array_merge($tmp, $value));
				$query->set($key, array_values($tmp));
			}
		}
	}

	public function public_parse_tax_query( $query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( did_filter('request') === 0 ) {
			return;
		}
		// Should tax queries for parent terms include items belonging to child terms?
		if ( $query->is_tax() && isset($query->tax_query, $query->tax_query->queries) ) {
			if ( $queried_object = $query->get_queried_object() ) {
				if ( is_a($queried_object, 'WP_Term') ) {
					foreach ( $query->tax_query->queries as $key => &$array ) {
						if ( ! is_array($array) ) {
							continue;
						}
						if ( isset($array['taxonomy']) && $array['taxonomy'] === $this->data['taxonomy'] && array_key_exists('include_children', $array) ) {
							$array['include_children'] = $this->data['include_children'];
						}
					}
				}
			}
		}
	}

	// Admin.

	public function admin_manage_sortable_columns( $sortable_columns = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $sortable_columns;
		}
		$current_filter = current_filter();
		if ( ! isset($this->data['sortable_columns'], $this->data['sortable_columns'][ $current_filter ]) ) {
			return $sortable_columns;
		}
		if ( isset($sortable_columns[ $this->data['sortable_columns'][ $current_filter ] ]) ) {
			return $sortable_columns;
		}
		$sortable_columns[ $this->data['sortable_columns'][ $current_filter ] ] = array( $this->data['sortable_columns'][ $current_filter ], true );
		return $sortable_columns;
	}

	public function admin_posts_clauses( $clauses, $query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $clauses;
		}
		if ( did_filter('request') === 0 ) {
			return $clauses;
		}
		if ( ! empty($clauses['join']) ) {
			return $clauses;
		}
		if ( ! $query->is_main_query() ) {
			return $clauses;
		}
		if ( empty($this->data['sortable_columns']) ) {
			return $clauses;
		}
    	$orderby = $query->get('orderby');
		if ( ! in_array($orderby, $this->data['sortable_columns']) ) {
			return $clauses;
		}
		// Assume the taxonomy.
		$taxonomy = null;
		if ( str_starts_with($orderby, 'taxonomy-') ) {
			$taxonomy = str_replace_start('taxonomy-', '', $orderby);
		} else {
			$replace_pairs = array(
				'categories' => 'category',
				'tags' => 'post_tag',
			);
			$taxonomy = strtr($orderby, $replace_pairs);
		}
		if ( ! taxonomy_exists($taxonomy) ) {
			return $clauses;
		}
		// Allow sorting by taxonomy term name.
		global $wpdb;
		$tmp = "(taxonomy = '$taxonomy' OR taxonomy IS NULL)";
		$clauses['where'] = empty($clauses['where']) ? $tmp : $clauses['where'] . ' AND ' . $tmp;
        $clauses['groupby'] = "{$wpdb->posts}.ID";
		$clauses['join'] .= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
SQL;
		$tmp = "{$wpdb->terms}.name " . $query->get('order');
		$clauses['orderby'] = empty($clauses['orderby']) ? $tmp : $tmp . ', ' . $clauses['orderby'];
		return $clauses;
	}
}
