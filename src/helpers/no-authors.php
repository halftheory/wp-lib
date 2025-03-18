<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class No_Authors extends Filters {

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
			add_action('template_redirect', array( $this, 'public_template_redirect' ), 20);
			add_filter('the_author', array( $this, 'public_the_author' ), 90);
			add_filter('author_link', array( $this, 'public_author_link' ), 90, 3);
			add_filter('the_author_link', array( $this, 'public_the_author_link' ), 90, 3);
			add_filter('get_comment_author_url', array( $this, 'public_get_comment_author_url' ), 90, 3);
		} else {
			// Admin.
			add_filter('manage_media_columns', array( $this, 'admin_manage_media_columns' ), 20, 2);
			add_filter('manage_pages_columns', array( $this, 'admin_manage_pages_columns' ), 20);
			add_filter('manage_posts_columns', array( $this, 'admin_manage_posts_columns' ), 20, 2);
			add_action('rewrite_rules_array', array( $this, 'admin_rewrite_rules_array' ), 20);
		}
		parent::autoload();
	}

	// Public.

	public function public_template_redirect() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( is_author() ) {
			// redirect author pages.
			$url = null;
			if ( $tmp = get_post_posts_page() ) {
				$url = get_permalink($tmp);
			} elseif ( $tmp = get_post_front_page() ) {
				$url = get_permalink($tmp);
			} else {
				$url = home_url();
			}
			if ( ht_wp_redirect($url) ) {
				exit;
			}
		}
	}

	public function public_the_author( $display_name ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $display_name;
		}
		// change to blogname.
		return get_bloginfo('name');
	}

    public function public_author_link( $link, $author_id, $author_nicename ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $link;
		}
		// change to home url.
        return home_url();
    }

    public function public_the_author_link( $link, $author_url, $authordata ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $link;
		}
		// change to home url.
        if ( ! empty($link) ) {
            if ( is_object($authordata) && (int) $authordata->ID > 0 ) {
                $link = str_replace(esc_url($author_url), esc_url(home_url()), $link);
            }
        }
        return $link;
    }

    public function public_get_comment_author_url( $url, $id, $comment ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $url;
		}
		// change to home url.
        if ( ! empty($url) ) {
            if ( is_object($comment) && (int) $comment->user_id > 0 ) {
                $url = home_url();
            }
        }
        return $url;
    }

	// Admin.

	public function admin_manage_media_columns( $posts_columns, $detached = true ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $posts_columns;
		}
		if ( isset($posts_columns['author']) ) {
			unset($posts_columns['author']);
		}
		return $posts_columns;
	}

	public function admin_manage_pages_columns( $posts_columns ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $posts_columns;
		}
		if ( isset($posts_columns['author']) ) {
			unset($posts_columns['author']);
		}
		return $posts_columns;
	}

	public function admin_manage_posts_columns( $posts_columns, $post_type ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $posts_columns;
		}
		if ( isset($posts_columns['author']) ) {
			unset($posts_columns['author']);
		}
		return $posts_columns;
	}

	public function admin_rewrite_rules_array( $rules ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $rules;
		}
		global $wp_rewrite;
		if ( ! is_object($wp_rewrite) ) {
			return $rules;
		}
		$remove_startpoints = array(
			$wp_rewrite->author_base,
		);
		foreach ( $rules as $key => $value ) {
			$remove = false;
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
}
