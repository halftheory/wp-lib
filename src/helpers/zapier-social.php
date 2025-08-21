<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Zapier_Social extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $tag = 'zap', $taxonomy = 'post_tag' ) {
		$this->data['tag'] = $tag;
		$this->data['taxonomy'] = $taxonomy;
		$this->data['term'] = null;
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('init', array( $this, 'global_init' ), 90);
		if ( is_public() ) {
			// Public.
			add_action('wp', array( $this, 'public_wp' ), 90);
		}
		parent::autoload();

		/*
		Basic setup:
		Schedule trigger
		Webhooks
			GET event
			URL = https://home_url/tag
			Send As JSON
		Facebook / Instagram / Telegram
			Image
			Message
		*/
	}

	// Global.

	public function global_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! $this->data['tag'] || ! $this->data['taxonomy'] ) {
			return;
		}
		if ( ! taxonomy_exists($this->data['taxonomy']) ) {
			return;
		}
		if ( $tmp = get_taxonomy($this->data['taxonomy']) ) {
			if ( isset($tmp->object_type) ) {
				if ( ! in_array('attachment', make_array($tmp->object_type)) ) {
					// Make the taxonomy include media. Hint: You will probably want to 'hide' it later.
					register_taxonomy_for_object_type($tmp->name, 'attachment');
				}
			}
		}
	}

	// Public.

	public function public_wp( $wp ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! $this->data['tag'] || ! $this->data['taxonomy'] ) {
			return;
		}
		if ( ! taxonomy_exists($this->data['taxonomy']) ) {
			return;
		}
		// Only zapier requests.
		$this->load_functions('wp-load');
		$is_zapier_request = is_development() ? true : isset($_SERVER['HTTP_USER_AGENT']) && str_contains($_SERVER['HTTP_USER_AGENT'], 'Zapier');
		if ( ! $is_zapier_request ) {
			return;
		}
		// Only load on https://home_url/tag.
		$this->load_functions('wp-link-template');
		if ( wp_get_url_path() !== $this->data['tag'] ) {
			return;
		}
		// Load the term.
		$this->load_functions('wp-taxonomy');
		$term = ht_get_term(get_term_by('slug', $this->data['tag'], $this->data['taxonomy']));
		if ( ! $term ) {
			return;
		}
		$this->data['term'] = $term;
		// Series of actions.
		$this->zapier_actions();
		exit;
	}

	// Functions.

	protected function zapier_actions() {
		$this->zapier_posts();
		$this->zapier_images();
		$this->zapier_mail();
	}

	protected function zapier_posts() {
		if ( ! $this->data['term'] ) {
			return;
		}
		// Get the oldest post with 'zap'.
		$args = array(
			'post_type' => array_values(array_diff(get_taxonomy_objects($this->data['term']->taxonomy), array( 'attachment', 'revision' ))),
			'post_status' => array( 'publish', 'inherit' ),
			'numberposts' => 10,
			'orderby' => 'date,post_title',
			'order' => 'ASC',
			'tax_query' => array(
				array(
					'taxonomy' => $this->data['term']->taxonomy,
					'field' => 'term_id',
					'terms' => $this->data['term']->term_id,
				),
			),
		);
		$this->load_functions('wp-post');
		$posts = ht_get_posts($args);
		if ( ! $posts ) {
			return;
		}
		$this->load_functions('wp-media,wp-embed,wp-formatting');
		$thumbnail_args = array(
			'min_width' => get_option('thumbnail_size_w', 0),
			'min_height' => get_option('thumbnail_size_h', 0),
		);
		$excerpt_fallback_args = array(
			'search' => array(
				'content' => true,
				'children' => false,
				'taxonomies' => false,
			),
		);
		$excerpt_args = array(
			'html' => false,
			'remove' => array(
				'breaks' => false,
				'email' => true,
				'url' => true,
			),
			'trim' => array(
				'email' => true,
				'url' => true,
				'values' => array(),
			),
		);
		foreach ( $posts as $post ) {
			$image = get_post_thumbnail_context('url', $post, 'large', array(), $thumbnail_args);
			if ( ! $image ) {
				$image = get_post_oembed_object_thumbnail_context('url', $post);
			}
			if ( ! $image ) {
				continue;
			}
			$title = get_the_title($post);
			$tmp = array(
				get_bloginfo('name'),
				is_title_ok($title),
			);
			$tmp = array_unique(array_filter($tmp));
			$message = implode(' - ', $tmp);
			if ( is_post_publicly_viewable($post) ) {
				if ( $tmp = the_excerpt_fallback($post->post_excerpt, $post, $excerpt_fallback_args) ) {
					$args = $excerpt_args;
					$args['trim']['values'] = array_unique(array( $title, $post->post_title ));
					$message .= "\n\n" . get_excerpt($tmp, 1000, $args);
				}
				if ( $tmp = get_permalink($post) ) {
					$message .= "\n\n" . $tmp;
				}
			}
			// Remove the tag and exit.
			$tmp = wp_remove_object_terms($post->ID, $this->data['term']->term_id, $this->data['term']->taxonomy);
			if ( $tmp !== true ) {
				continue;
			}
			$result = array(
				'message' => unwptexturize($message),
				'image' => $image,
			);
			wp_send_json($result);
		}
	}

	protected function zapier_images() {
		if ( ! $this->data['term'] ) {
			return;
		}
		// Get a random image with 'zap'.
		$args = array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'post_status' => array( 'publish', 'inherit' ),
			'numberposts' => 10,
			'orderby' => 'rand',
			'tax_query' => array(
				array(
					'taxonomy' => $this->data['term']->taxonomy,
					'field' => 'term_id',
					'terms' => $this->data['term']->term_id,
				),
			),
		);
		$this->load_functions('wp-post');
		$posts = ht_get_posts($args);
		if ( ! $posts ) {
			return;
		}
		$this->load_functions('wp-media,wp-formatting,wp-post-template');
		$excerpt_fallback_args = array(
			'search' => array(
				'content' => true,
				'children' => false,
				'taxonomies' => false,
			),
		);
		$excerpt_args = array(
			'html' => false,
			'remove' => array(
				'breaks' => false,
				'email' => true,
				'url' => true,
			),
			'trim' => array(
				'email' => true,
				'url' => true,
				'values' => array(),
			),
		);
		foreach ( $posts as $post ) {
			$image = get_image_context('url', $post->ID, 'large');
			if ( ! $image ) {
				continue;
			}
			$title = is_attachment_title_ok(the_title_attribute(array( 'echo' => false, 'post' => $post )), $post);
			if ( ! $title && (int) $post->post_parent > 0 && is_post_publicly_viewable($post->post_parent) ) {
				$title = is_title_ok(the_title_attribute(array( 'echo' => false, 'post' => $post->post_parent )));
			}
			$tmp = array(
				get_bloginfo('name'),
				$title,
			);
			$tmp = array_unique(array_filter($tmp));
			$message = implode(' - ', $tmp);
			if ( is_post_publicly_viewable($post) ) {
				if ( $tmp = the_excerpt_fallback($post->post_excerpt, $post, $excerpt_fallback_args) ) {
					$args = $excerpt_args;
					$args['trim']['values'] = array_unique(array( $title, $post->post_title ));
					$message .= "\n\n" . get_excerpt($tmp, 1000, $args);
				}
				if ( $tmp = get_object_term_links($post) ) {
					$message .= "\n\n" . current($tmp);
				} elseif ( (int) $post->post_parent > 0 && is_post_publicly_viewable($post->post_parent) ) {
					if ( $tmp = get_permalink($post->post_parent) ) {
						$message .= "\n\n" . $tmp;
					}
				}
			}
			// Remove the tag and exit.
			$tmp = wp_remove_object_terms($post->ID, $this->data['term']->term_id, $this->data['term']->taxonomy);
			if ( $tmp !== true ) {
				continue;
			}
			$result = array(
				'message' => unwptexturize($message),
				'image' => $image,
			);
			wp_send_json($result);
		}
	}

	protected function zapier_mail() {
		// Send a warning email.
		$subject = get_bloginfo('name') . ' - ' . static::$handle . ' - ' . __('No results');
		// Translators: URL, Tag.
		$message = wp_sprintf(__('The Zapier Social helper was requested at %1$s but no suitable results for "%2$s" were returned.'), get_current_url(), $this->data['tag']);
		wp_mail(get_option('admin_email'), $subject, $message);
	}
}
