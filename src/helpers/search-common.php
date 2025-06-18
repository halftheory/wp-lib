<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Search_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		if ( is_public() ) {
			// Public.
			add_filter('request', array( $this, 'public_request' ), 20);
			add_action('template_redirect', array( $this, 'public_template_redirect' ), 20);
		}
		parent::autoload();
	}

	// Public.

	public function public_request( $query_vars = array() ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $query_vars;
		}
		// Skip targeted queries.
		$array = array(
			'p',
			'subpost_id',
			'attachment_id',
			'pagename',
			'page_id',
			'tag_id',
			'category__in',
			'post__in',
			'post_name__in',
			'tag__in',
			'tag_slug__in',
			'author__in',
			'year',
			'monthnum',
			'w',
			'day',
			'hour',
			'minute',
			'second',
			'm',
			'date_query',
			'taxonomy',
		);
		foreach ( $array as $value ) {
			if ( array_key_exists($value, $query_vars) ) {
				return $query_vars;
			}
		}
		if ( isset($query_vars['s']) ) {
			// Clean the search string.
			$query_vars['s'] = trim(str_replace('%20', ' ', replace_spaces($query_vars['s'])));
			// Add most public post types.
			if ( ! array_key_exists('post_type', $query_vars) ) {
				$query_vars['post_type'] = array_values(array_diff(get_post_types(array( 'public' => true ), 'names'), array( 'attachment', 'revision' )));
			}
		}
		return $query_vars;
	}

	public function public_template_redirect() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// Fix search URLs.
		if ( is_search() && isset($_GET['s']) ) {
			if ( ! empty($_GET['s']) ) {
				$search_slug = rawurlencode(sanitize_text_field(get_search_query()));
				$replace_pairs = array(
					'%2F' => '/',
					'%2C' => ',',
					'%c2%a0' => ' ',
					'%C2%A0' => ' ',
					'%e2%80%93' => '-',
					'%E2%80%93' => '-',
					'%e2%80%94' => '-',
					'%E2%80%94' => '-',
					'&nbsp;' => ' ',
					'&#160;' => ' ',
					'&ndash;' => '-',
					'&#8211;' => '-',
					'&mdash;' => '-',
					'&#8212;' => '-',
				);
				$search_slug = strtr($search_slug, $replace_pairs);
				$url = home_url('/' . sanitize_title(__('Search')) . '/') . $search_slug;
			} else {
				$url = home_url();
			}
			if ( ht_wp_redirect($url) ) {
				exit;
			}
		}
	}

	// Functions.

	public function search_form( $args = array(), $display = true ) {
		// https://developer.wordpress.org/reference/functions/get_search_form/
		// theme()->get_helper('search-common')->search_form();
		$defaults = array(
			'autocomplete' => false,
			'autofocus' => true,
			'id' => 's',
			'placeholder' => null,
			'button' => null,
		);
		$args = wp_parse_args($args, $defaults);

		$html = get_search_form(array( 'echo' => false ));

		$replace_pairs = array();

		if ( ! is_true($args['autocomplete']) ) {
			$replace_pairs['<input type="search" '] = '<input type="search" autocomplete="off" ';
			$replace_pairs['<input type="text" '] = '<input type="text" autocomplete="off" ';
		}

		if ( is_true($args['autofocus']) ) {
			if ( isset($replace_pairs['<input type="search" ']) ) {
				$replace_pairs['<input type="search" '] .= 'autofocus ';
			} else {
				$replace_pairs['<input type="search" '] = '<input type="search" autofocus ';
			}
			if ( isset($replace_pairs['<input type="text" ']) ) {
				$replace_pairs['<input type="text" '] .= 'autofocus ';
			} else {
				$replace_pairs['<input type="text" '] = '<input type="text" autofocus ';
			}
		}

		$id = esc_attr($args['id']);
		if ( $args['id'] === 'unique' ) {
			$id = function_exists('wp_unique_id') ? wp_unique_id('search-form-') : 'search-form-' . microtime();
		}
		if ( $id !== 's' ) {
			$replace_pairs[' for="s"'] = ' for="' . $id . '"';
			$replace_pairs[' id="s"'] = ' id="' . $id . '"';
		}

		$html = strtr($html, $replace_pairs);

		if ( is_string($args['placeholder']) ) {
			$html = preg_replace('/( placeholder=\")[^\"]*(\")/is', '$1' . esc_attr($args['placeholder']) . '$2', $html, 1);
		}

		if ( is_string($args['button']) ) {
			$button = '';
			switch ( $args['button'] ) {
				case 'fontawesome':
				case 'fa':
					$button = '<button type="submit" class="search-button fa fa-search" role="button"><span class="screen-reader-text">' . __('Search') . '</span></button>';
					break;

				default:
					if ( str_contains($args['button'], '<') ) {
						$button = $args['button'];
					}
					break;
			}
			if ( $button ) {
				$html = preg_replace('/<input type=\"submit\"[^>]*>/is', $button, $html, 1);
			}
		}

		if ( $display ) {
			echo $html;
		} else {
			return $html;
		}
	}
}
