<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Microdata extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		$this->load_functions('microdata');
		parent::__construct($autoload);
	}

	protected function autoload() {
		if ( is_public() ) {
			// Public.
			add_filter('wp_nav_menu', array( $this, 'public_wp_nav_menu' ), 20, 2);
			add_filter('wp_nav_menu_args', array( $this, 'public_wp_nav_menu_args' ), 20, 1);
			add_filter('nav_menu_item_attributes', array( $this, 'public_nav_menu_item_attributes' ), 20, 4);
			add_filter('wp_kses_allowed_html', array( $this, 'public_wp_kses_allowed_html' ), 20, 2);
		}
		parent::autoload();
	}

	// Public.

	public function public_wp_nav_menu( $nav_menu, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $nav_menu;
		}
		if ( isset($args->container) && ! empty($args->container) && str_starts_with($nav_menu, '<' . $args->container) ) {
			$array = array(
				'role' => 'navigation',
			);
			if ( ! isset($args->container_aria_label) || empty($args->container_aria_label) ) {
				$slug = isset($args->menu, $args->menu->slug) ? $args->menu->slug : $args->theme_location;
				$this->load_functions('wp-nav-menu');
				if ( $tmp = get_nav_menu_description($slug) ) {
					$array['aria-label'] = $tmp;
				}
			}
			$this->load_functions('wp-html-api');
			$nav_menu = wp_html_tag_set_attributes($nav_menu, $args->container, $array);
		}
		return $nav_menu;
	}

	public function public_wp_nav_menu_args( $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $args;
		}
		if ( isset($args['items_wrap']) && ! empty($args['items_wrap']) ) {
			$array = array(
				'role' => 'menu',
				'aria-label' => __('Menu Items'),
			);
			$slug = isset($args['menu']) && ! empty($args['menu']) && ! is_numeric($args['menu']) ? $args['menu'] : $args['theme_location'];
			$this->load_functions('wp-nav-menu');
			if ( $tmp = get_nav_menu_description($slug) ) {
				$array['aria-label'] = $tmp . ' ' . __('Items');
			}
			foreach ( $array as $key => $value ) {
				if ( ! str_contains($args['items_wrap'], ' ' . $key . '=') ) {
					$args['items_wrap'] = preg_replace('/(<[\w]+[^>]*)>/is', '$1 ' . $key . '="' . esc_attr($value) . '">', $args['items_wrap'], 1);
				}
			}
		}
		return $args;
	}

	public function public_nav_menu_item_attributes( $li_atts, $menu_item, $args, $depth ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $li_atts;
		}
		if ( ! isset($li_atts['role']) ) {
			$li_atts['role'] = 'menuitem';
		}
		return $li_atts;
	}

	public function public_wp_kses_allowed_html( $html, $context ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $html;
		}
		if ( $context !== 'post' ) {
			return $html;
		}
		$array = array(
			'itemprop' => true,
			'itemscope' => true,
			'itemtype' => true,
		);
		$callback = function ( $v ) use ( $array ) {
			$v = $v + $array;
			ksort($v);
			return $v;
		};
		$html = array_map($callback, $html);
		return $html;
	}
}
