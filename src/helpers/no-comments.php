<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class No_Comments extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('after_setup_theme', array( $this, 'global_after_setup_theme' ), 20);
		add_action('init', array( $this, 'global_init' ), 90);
		add_action('wp_before_admin_bar_render', array( $this, 'global_wp_before_admin_bar_render' ), 20);
		if ( is_public() ) {
			// Public.
			add_action('widgets_init', array( $this, 'public_widgets_init' ), 20);
			add_action('wp_head', array( $this, 'public_wp_head' ), 1);
		} else {
			// Admin.
			add_action('after_switch_theme', array( $this, 'admin_after_switch_theme' ), 10, 2);
			add_action('rewrite_rules_array', array( $this, 'admin_rewrite_rules_array' ), 20);
			add_action('admin_menu', array( $this, 'admin_menu' ), ( 9553 + 10 ));
		}
		parent::autoload();
	}

	// Global.

	public function global_after_setup_theme() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		add_filter('comments_open', '__return_false', 10, 2);
		add_filter('pre_comment_user_ip', '__return_empty_string');
	}

	public function global_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		foreach ( get_post_types(array( 'public' => true ), 'names') as $value ) {
			remove_post_type_support($value, 'comments');
		}
	}

	public function global_wp_before_admin_bar_render() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		global $wp_admin_bar;
		if ( ! is_object($wp_admin_bar) ) {
			return;
		}
		if ( ! method_exists($wp_admin_bar, 'get_node') ) {
			return;
		}
		$tmp = $wp_admin_bar->get_node('comments');
		if ( $tmp ) {
			$wp_admin_bar->remove_node('comments');
		}
	}

	// Public.

	public function public_widgets_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Remove recent comments widget.
		global $wp_widget_factory;
		if ( is_object($wp_widget_factory) && isset($wp_widget_factory->widgets, $wp_widget_factory->widgets['WP_Widget_Recent_Comments']) ) {
			remove_action('wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ));
		}
		unregister_widget('WP_Widget_Recent_Comments');
	}

	public function public_wp_head() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			 return;
		}
		if ( ! comments_open() ) {
			add_filter('feed_links_show_comments_feed', '__return_false');
		}
	}

	// Admin.

	public function admin_after_switch_theme( $old_theme_name = false, $old_theme_class = false ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$defaults = array(
			'default_comment_status' => 'closed',
			'require_name_email' => 1,
			'comment_registration' => 1,
			'comments_notify' => 1,
			'moderation_notify' => 1,
			'comment_moderation' => 1,
			// comment_previously_approved - previously 'comment_whitelist'.
			'comment_previously_approved' => 1,
			'show_avatars' => '',
		);
		foreach ( $defaults as $key => $value ) {
			update_option($key, $value);
		}
	}

	public function admin_rewrite_rules_array( $rules ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $rules;
		}
		global $wp_rewrite;
		if ( ! is_object($wp_rewrite) ) {
			return $rules;
		}
		$remove_endpoints = array(
			$wp_rewrite->comments_pagination_base,
		);
		$remove_startpoints = array(
			$wp_rewrite->comments_base,
		);
		foreach ( $rules as $key => $value ) {
			$remove = false;
			foreach ( $remove_endpoints as $point ) {
				if ( strpos($key, $point) !== false ) {
					$remove = true;
					break;
				}
			}
			if ( $remove ) {
				unset($rules[ $key ]);
				continue;
			}
			foreach ( $remove_startpoints as $point ) {
				if ( str_starts_with($key, $point) ) {
					$remove = true;
					break;
				}
			}
			if ( $remove ) {
				unset($rules[ $key ]);
				continue;
			}
		}
		return $rules;
	}

	public function admin_menu( $context = '' ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->load_functions('wp-admin');
		ht_remove_menu_page('edit-comments.php');
	}
}
