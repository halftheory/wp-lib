<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Admin_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		$this->load_functions('wp-admin');
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_filter('gettext_default', array( $this, 'global_gettext_default' ), 20, 3);
		add_action('wp_before_admin_bar_render', array( $this, 'global_wp_before_admin_bar_render' ), 90);
		if ( ! is_public() ) {
			// Admin.
			add_action('admin_init', array( $this, 'admin_init' ), 90);
			add_action('admin_menu', array( $this, 'admin_menu' ), ( 9553 + 10 ));
			add_action('current_screen', array( $this, 'admin_current_screen' ));
			add_filter('heartbeat_settings', array( $this, 'admin_heartbeat_settings' ));
			add_filter('pre_update_option', array( $this, 'admin_pre_update_option' ), 20, 3);
			add_filter('screen_options_show_screen', array( $this, 'admin_screen_options_show_screen' ), 100);
			add_action('wp_dashboard_setup', array( $this, 'admin_wp_dashboard_setup' ));
			add_action('wp_update_nav_menu', array( $this, 'admin_wp_update_nav_menu' ), 20, 2);
			// Bulk add terms.
			$post_types = array_value_unset(get_post_types(array( 'public' => true ), 'names'), 'attachment');
			foreach ( $post_types as $value ) {
				add_filter('bulk_actions-edit-' . $value, array( $this, 'admin_bulk_actions' ), 20);
				add_filter('handle_bulk_actions-edit-' . $value, array( $this, 'admin_handle_bulk_actions' ), 20, 3);
			}
			add_filter('bulk_actions-upload', array( $this, 'admin_bulk_actions' ), 20);
			add_filter('handle_bulk_actions-upload', array( $this, 'admin_handle_bulk_actions' ), 20, 3);
		}
		parent::autoload();
	}

	// Global.

	public function global_gettext_default( $translation, $text, $domain ) {
		// Replace Howdy with Welcome.
		if ( ! is_user_logged_in() ) {
			return $translation;
		}
		if ( $text === 'Howdy, %s' ) {
			$translation = str_replace('Howdy', 'Welcome', $translation);
		}
		return $translation;
	}

	public function global_wp_before_admin_bar_render() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		global $wp_admin_bar;
		if ( ! is_object($wp_admin_bar) ) {
			return;
		}
		if ( ! method_exists($wp_admin_bar, 'get_nodes') ) {
			return;
		}
		$keep = array(
			'my-sites',
			'site-name',
			'languages',
			'edit',
			'view',
			'top-secondary',
		);
		if ( is_administrator() ) {
			$keep[] = 'redis-cache';
		}
		foreach ( $wp_admin_bar->get_nodes() as $key => $value ) {
			if ( in_array($key, $keep) ) {
				continue;
			}
			if ( is_object($value) && property_exists($value, 'parent') && empty($value->parent) ) {
				$wp_admin_bar->remove_menu($key);
			}
		}
	}

	// Admin.

	public function admin_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Disable notices.
		remove_action('admin_notices', 'update_nag', 3);
		remove_action('admin_notices', 'maintenance_nag', 10);
		if ( is_multisite() ) {
			remove_action('admin_notices', 'site_admin_notice');
			remove_action('network_admin_notices', 'site_admin_notice');
			remove_action('network_admin_notices', 'update_nag', 3);
			remove_action('network_admin_notices', 'maintenance_nag', 10);
		}
		// Disable footer.
		add_filter('admin_footer_text', '__return_empty_string', 100);
		add_filter('update_footer', '__return_empty_string', 100);
		// Admin Color Scheme.
		remove_all_actions('admin_color_scheme_picker');
	}

	public function admin_menu( $context = '' ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $context;
		}
		if ( ! is_administrator() ) {
			$array = array(
				'plugins.php',
				'users.php',
				'tools.php',
				'options-general.php',
			);
			foreach ( $array as $value ) {
				ht_remove_menu_page($value);
			}
			remove_submenu_page('index.php', 'update-core.php');
			remove_submenu_page('themes.php', 'themes.php');
			remove_submenu_page('themes.php', 'site-editor.php?path=/patterns');
			remove_submenu_page('themes.php', 'customize.php');
		}
	}

	public function admin_current_screen( $current_screen ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		get_current_screen_id();
	}

	public function admin_heartbeat_settings( $settings ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $settings;
		}
		$settings['interval'] = 120;
		return $settings;
	}

	public function admin_pre_update_option( $value, $option, $old_value ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $value;
		}
		// Try to prevent unnecessary transients.
		$array = array(
			'_transient_dash_',
			'_transient_timeout_dash_',
			'_transient_feed_',
			'_transient_timeout_feed_',
		);
		foreach ( $array as $v ) {
			if ( str_starts_with($option, $v) ) {
				// If the new and old values are the same, no need to update.
				return $old_value;
			}
		}
		return $value;
	}

	public function admin_screen_options_show_screen( $value ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $value;
		}
		return is_administrator();
	}

	public function admin_wp_dashboard_setup() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$array = array(
			'dashboard_browser_nag' => 'normal',
			'dashboard_php_nag' => 'normal',
			'dashboard_site_health' => 'normal',
			'dashboard_activity' => 'normal',
			'dashboard_quick_press' => 'side',
			'dashboard_primary' => 'side',
		);
		foreach ( $array as $id => $context ) {
			remove_meta_box($id, 'dashboard', $context);
		}
	}

	public function admin_wp_update_nav_menu( $menu_id, $menu_data = null ) {
		if ( $menu_data !== null ) {
			// This means the action was fired in nav-menu.php, BEFORE the menu items have been updated, and we should ignore it.
			return;
		}
		if ( ! in_array(get_current_screen_id(), array( 'nav-menus' )) ) {
			return;
		}

		/*
		Update page structure to mirror menu structure:
		1. Front page.
		2. Primary menu items.
		3. Other menus (order alphabetical by menu name).
		4. Other pages (not Front Page or in menus, order page).
		*/
		$page_structure = $page_structure_ids = array();

		$func_reorder_menus = function ( $array ) {
			$array = make_array($array);
			if ( ! empty($array) ) {
				// Let's reorder it based on some strings.
				$top = array();
				foreach ( array( 'primary', 'main', 'header', 'nav' ) as $v ) {
					foreach ( $array as $key => $value ) {
						if ( str_starts_with($key, $v) ) {
							$top[ $key ] = $value;
							break;
						}
					}
				}
				$array = $top + $array;
			}
			return $array;
		};

		$func_add_array_to_parent = function ( &$pages, $parent, $array ) use ( &$func_add_array_to_parent ) {
			$parent = (int) $parent;
			// Parent is top level.
			$parent_key = array_search($parent, array_map('absint', wp_list_pluck($pages, 'ID')), true);
			if ( $parent_key !== false && is_numeric($parent_key) ) {
				$pages[ $parent_key ]['children'][] = $array;
				return $pages[ $parent_key ]['ID'];
			}
			// Search for parent in children.
			foreach ( $pages as $key => &$value ) {
				if ( empty($value['children']) ) {
					continue;
				}
				if ( $result = $func_add_array_to_parent($value['children'], $parent, $array) ) {
					return $result;
				}
			}
			return false;
		};

		// Front page.
		if ( $tmp = get_post_front_page() ) {
			$page_structure[] = array( 'ID' => $tmp->ID, 'title' => $tmp->post_title, 'children' => array() );
			$page_structure_ids[] = $tmp->ID;
		}
		// Menu items.
		$theme_menus = get_nav_menu_locations();
		$other_menus = get_terms(
			array(
				'taxonomy' => 'nav_menu',
				'orderby' => 'name',
				'fields' => 'ids',
				'exclude' => $theme_menus,
			)
		);
		$menus = $func_reorder_menus($theme_menus) + $func_reorder_menus($other_menus);
		foreach ( $menus as $menu ) {
			if ( $items = wp_get_nav_menu_items($menu) ) {
				foreach ( $items as $item ) {
					if ( ! is_object($item) ) {
						continue;
					}
					if ( $item->object !== 'page' ) {
						continue;
					}
					if ( in_array_int($item->object_id, $page_structure_ids) ) {
						continue;
					}
					$title = array_filter(array( $item->post_title, $item->title ));
					$array = array( 'ID' => $item->object_id, 'title' => current($title), 'children' => array() );
					if ( (int) $item->menu_item_parent === 0 ) {
						$page_structure[] = $array;
						$page_structure_ids[] = $item->object_id;
						continue;
					}
					$parent = (int) $item->menu_item_parent;
					$parent_object = get_post_meta($parent, '_menu_item_object', true);
					// Maybe parent is not a page, find next parent until page or top.
					while ( $parent_object !== 'page' ) {
						$parent = (int) get_post_meta($parent, '_menu_item_menu_item_parent', true);
						if ( $parent === 0 ) {
							$parent_object = 'page';
							break;
						}
						$parent_object = get_post_meta($parent, '_menu_item_object', true);
					}
					if ( $parent === 0 ) {
						$page_structure[] = $array;
						$page_structure_ids[] = $item->object_id;
						continue;
					}
					$parent_id = get_post_meta($parent, '_menu_item_object_id', true);
					// Find existing parent.
					if ( $func_add_array_to_parent($page_structure, $parent_id, $array) ) {
						$page_structure_ids[] = $item->object_id;
					}
				}
			}
		}
		// Other pages.
		$args = array(
			'post_type' => 'page',
			'post_status' => 'all',
			'numberposts' => -1,
			'nopaging' => true,
			'orderby' => 'menu_order,post_title',
			'order' => 'ASC',
			'exclude' => $page_structure_ids,
		);
		if ( $posts = ht_get_posts($args) ) {
			foreach ( $posts as $item ) {
				if ( in_array_int($item->ID, $page_structure_ids) ) {
					continue;
				}
				if ( wp_is_post_revision($item) ) {
					continue;
				}
				$array = array( 'ID' => $item->ID, 'title' => $item->post_title, 'children' => array() );
				if ( (int) $item->post_parent === 0 ) {
					$page_structure[] = $array;
					$page_structure_ids[] = $item->ID;
					continue;
				}
				if ( $func_add_array_to_parent($page_structure, $item->post_parent, $array) ) {
					$page_structure_ids[] = $item->ID;
					continue;
				}
				$page_structure[] = $array;
				$page_structure_ids[] = $item->ID;
			}
		}
		if ( empty($page_structure) ) {
			return;
		}
		// Update posts.
		$func_update_posts = function ( $array, $parent = 0 ) use ( &$func_update_posts ) {
			foreach ( $array as $key => $value ) {
				$old = get_post($value['ID'], ARRAY_A);
				if ( ! $old ) {
					continue;
				}
				$new = array(
					'ID' => $value['ID'],
					'post_parent' => $parent,
					'menu_order' => $key,
				);
				// Only update if something has changed.
				if ( (int) $new['menu_order'] !== (int) $old['menu_order'] || (int) $new['post_parent'] !== (int) $old['post_parent'] ) {
					wp_update_post(wp_slash($new));
				}
				if ( ! empty($value['children']) ) {
					$func_update_posts($value['children'], $value['ID']);
				}
			}
		};
		$func_update_posts($page_structure);
	}

	public function admin_bulk_actions( $actions ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $actions;
		}
		if ( ! isset($GLOBALS['typenow']) ) {
			return $actions;
		}
		$taxonomies = get_object_taxonomies($GLOBALS['typenow']);
		if ( empty($taxonomies) ) {
			return $actions;
		}
		sort($taxonomies);
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy' => $taxonomy,
					'hide_empty' => false,
					'fields' => 'ids',
				)
			);
			if ( empty($terms) ) {
				continue;
			}
			$taxonomy_label = get_taxonomy($taxonomy)->labels->singular_name;
			foreach ( $terms as $value ) {
				$actions[ 'add-term-' . $value ] = '+ ' . $taxonomy_label . ': ' . get_single_term_title($value);
			}
			$actions[ 'remove-terms-' . $taxonomy ] = __('Remove All') . ' ' . get_taxonomy($taxonomy)->label;
		}
		return $actions;
	}

	public function admin_handle_bulk_actions( $location, $doaction, $post_ids ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $location;
		}
		if ( str_starts_with($doaction, 'add-term-') ) {
			$term = str_replace_start('add-term-', '', $doaction);
			if ( $term = ht_get_term( (int) $term) ) {
				foreach ( $post_ids as $post_id ) {
					wp_add_object_terms($post_id, $term->term_id, $term->taxonomy);
				}
				$location = add_query_arg('posted', 1, $location);
			}
		} elseif ( str_starts_with($doaction, 'remove-terms-') ) {
			$taxonomy = str_replace_start('remove-terms-', '', $doaction);
			if ( taxonomy_exists($taxonomy) ) {
				foreach ( $post_ids as $post_id ) {
					wp_set_post_terms($post_id, array(), $taxonomy);
				}
				$location = add_query_arg('posted', 1, $location);
			}
		}
		return $location;
	}
}
