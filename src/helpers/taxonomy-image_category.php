<?php
namespace Halftheory\Lib\helpers;

if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Add_Taxonomy.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Add_Taxonomy.php';
}
use Halftheory\Lib\helpers\classes\Add_Taxonomy;
use WP_Query;

#[AllowDynamicProperties]
class Taxonomy_Image_Category extends Add_Taxonomy {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $taxonomy = 'image_category', $object_type = 'attachment', $taxonomy_args = null, $include_children = false ) {
		$defaults = array(
			'labels' => array(
				'name' => is_public() ? __('Images') : __('Image Categories'),
				'singular_name' => is_public() ? __('Category') : __('Image Category'),
			),
			'public' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite' => array(
				'slug' => sanitize_title(__('Images')),
				'hierarchical' => true,
				'with_front' => false,
			),
			'update_count_callback' => '_update_generic_term_count',
		);
		$taxonomy_args = is_array($taxonomy_args) ? $taxonomy_args : $defaults;
		parent::__construct($autoload, $taxonomy, $object_type, $taxonomy_args, $include_children);
	}

	protected function autoload() {
		// Global.
		add_filter('attachment_link', array( $this, 'global_attachment_link' ), 20, 2);
		if ( is_public() ) {
			// Public.
			add_filter('request', array( $this, 'public_request' ));
			add_filter('wp_get_attachment_link_attributes', array( $this, 'public_wp_get_attachment_link_attributes' ), 20, 2);
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
		}
		parent::autoload();
	}

	// Global.

	public function global_attachment_link( $link, $post_id ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $link;
		}
		if ( ! $this->is_taxonomy_active() ) {
			return $link;
		}
		// Rewrite links for a single attachment.
		global $wp_rewrite;
		if ( ! $wp_rewrite->use_trailing_slashes ) {
			return $link;
		}
		if ( ! is_post_publicly_viewable($post_id) ) {
			return $link;
		}
		$terms = array_filter(wp_get_post_terms($post_id, get_post_taxonomies($post_id)), 'is_term_publicly_viewable');
		if ( empty($terms) ) {
			return $link;
		}
		// Attachment can only have this taxonomy.
		$tmp = array_unique(wp_list_pluck($terms, 'taxonomy'));
		if ( count($tmp) === 1 && current($tmp) === $this->data['taxonomy'] ) {
			if ( count($terms) === 1 ) {
				// One term.
				$link = trailingslashit(get_term_link(current($terms))) . get_post_field('post_name', $post_id, 'raw') . '/';
			} else {
				// Many possible terms.
				$slugs = get_taxonomies_slugs();
				if ( isset($slugs[ $this->data['taxonomy'] ]) ) {
					$link = home_url(path_join($slugs[ $this->data['taxonomy'] ], get_post_field('post_name', $post_id, 'raw')) . '/');
				}
			}
		}
		return $link;
	}

	// Public.

	public function public_request( $query_vars = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $query_vars;
		}
		if ( ! $this->is_taxonomy_active() ) {
			return $query_vars;
		}

		// Redirect requests for a single attachment.
		global $wp_rewrite;
		if ( $wp_rewrite->use_trailing_slashes && isset($query_vars[ $this->data['taxonomy'] ]) ) {
			$parts = explode('/', trim($query_vars[ $this->data['taxonomy'] ], '/'));
			$post_slug = array_pop($parts);
			// Check it's not a term.
			if ( ! get_term_by('slug', $post_slug, $this->data['taxonomy']) ) {
				// Get post.
				$post_obj = ht_get_page_by_path($post_slug, OBJECT, 'attachment');
				if ( $post_obj && is_post_publicly_viewable($post_obj) ) {
					// Get term.
					$terms = array_filter(wp_get_post_terms($post_obj->ID, $this->data['taxonomy']), 'is_term_publicly_viewable');
					if ( ! empty($terms) ) {
						reset($terms);
						$term_obj = current($terms);
						if ( $term_slug = array_pop($parts) ) {
							$key = array_search($term_slug, wp_list_pluck($terms, 'slug'));
							if ( $key !== false ) {
								$term_obj = $terms[ $key ];
							}
						}
						// Get page.
						$page = null;
						$args = $query_vars;
						$args[ $this->data['taxonomy'] ] = $term_obj->slug;
						$args['fields'] = 'ids';
						$args['post_status'] = array( 'publish', 'inherit' ); // Add post_status for attachment.
						$query = new WP_Query($args);
						if ( isset($query->posts, $query->max_num_pages) && ! empty($query->posts) ) {
							if ( in_array_int($post_obj->ID, $query->posts) ) {
								$page = 1;
							} else {
								$max_num_pages = (int) $query->max_num_pages;
								$paged = 2;
								while ( $paged <= $max_num_pages ) {
									$args['paged'] = $paged;
									$query = new WP_Query($args);
									if ( ! isset($query->posts) || empty($query->posts) ) {
										break;
									}
									if ( in_array_int($post_obj->ID, $query->posts) ) {
										$page = $paged;
										break;
									}
									$paged++;
								}
							}
						}
						if ( $page ) {
							// URL = Term link / Page # Name.
							$url = get_term_link($term_obj);
							if ( $page > 1 ) {
								$url .= 'page/' . $page . '/';
							}
							// Hash.
							$url .= '#' . $post_slug;
							if ( ht_wp_redirect($url) ) {
								exit;
							}
						}
						unset($query, $args, $term_obj);
					}
					unset($terms);
				}
				unset($post_obj);
			}
			unset($parts);
		}

		// Collect defaults.
		// Exclude attachments without a term.
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'fields' => 'ids',
			'nopaging' => true,
			'tax_query' => array(),
		);
		foreach ( get_object_taxonomies('attachment') as $value ) {
			$args['tax_query'][] = array(
				'taxonomy' => $value,
				'operator' => 'NOT EXISTS',
			);
		}
		if ( count($args['tax_query']) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}
		self::remove_filter('public_pre_get_posts');
		if ( $tmp = ht_get_posts($args) ) {
			$this->data['query_vars_defaults']['post__not_in'] = $tmp;
		}
		self::add_filter('public_pre_get_posts');

		return $query_vars;
	}

	public function public_pre_get_posts( $query ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( did_filter('request') === 0 ) {
			return;
		}
		// Add post_status for attachments.
		if ( in_array('attachment', make_array($query->get('post_type'))) && ! in_array('inherit', make_array($query->get('post_status'))) ) {
			$tmp = make_array($query->get('post_status'));
			$tmp = array_value_unset($tmp, 'any');
			$tmp = array_unique(array_merge($tmp, array( 'publish', 'inherit' )));
			$query->set('post_status', $tmp);
		}
		parent::public_pre_get_posts($query);
	}

	public function public_wp_get_attachment_link_attributes( $attributes, $id ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $attributes;
		}
		if ( isset($attributes['id']) ) {
			return $attributes;
		}
		if ( ! $this->is_taxonomy_active() ) {
			return $attributes;
		}
		if ( ! in_array($this->data['taxonomy'], get_post_taxonomies($id)) ) {
			return $attributes;
		}
		// Add the 'id' attribute.
		$attributes['id'] = get_post_field('post_name', $id, 'raw');
		return $attributes;
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! $this->is_taxonomy_active() ) {
			return;
		}
		$queried_object = get_queried_object();
		if ( ( is_a($queried_object, 'WP_Term') && $queried_object->taxonomy === $this->data['taxonomy'] ) || ! empty(get_query_var($this->data['taxonomy'])) ) {
			// JS.
			$file = __DIR__ . '/assets/js/taxonomy-image_category' . min_scripts() . '.js';
			if ( $url = get_stylesheet_uri_from_file($file) ) {
				wp_enqueue_script(static::$handle, $url, array( 'jquery' ), get_file_version($file), true);
			}
		}
	}

	// Functions.

	public function is_taxonomy_active() {
		if ( ! $this->data['taxonomy'] ) {
			return false;
		}
		if ( ! taxonomy_exists($this->data['taxonomy']) ) {
			return false;
		}
		if ( ! in_array('attachment', $this->data['object_type']) ) {
			return false;
		}
		return true;
	}
}
