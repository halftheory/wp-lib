<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Mail_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('phpmailer_init', array( $this, 'global_phpmailer_init' ), 90);
		add_filter('wp_mail_from', array( $this, 'global_wp_mail_from' ), 90);
		add_filter('wp_mail_from_name', array( $this, 'global_wp_mail_from_name' ), 90);
		if ( is_public() ) {
			// Public.
			add_action('wp', array( $this, 'public_wp' ), 90);
		}
		parent::autoload();
	}

	// Global.

    public function global_phpmailer_init( $phpmailer ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( is_development() && method_exists($phpmailer, 'ClearAllRecipients') ) {
			$phpmailer->ClearAllRecipients();
			return;
		}
        // add plain text version.
        if ( $phpmailer->ContentType !== 'text/plain' && empty($phpmailer->AltBody) ) {
            $phpmailer->AltBody = remove_excess_space(wp_strip_all_tags($phpmailer->Body));
        }
        $phpmailer->Body = remove_excess_space($phpmailer->Body);
    }

	public function global_wp_mail_from( $from_email ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $from_email;
		}
		$fallback = false;
		if ( empty($from_email) ) {
			$fallback = true;
		} elseif ( isset($_SERVER['HTTP_HOST']) && strpos($from_email, $_SERVER['HTTP_HOST']) === false ) {
			$fallback = true;
		}
		if ( $fallback ) {
			$tmp = get_option('admin_email');
			if ( ! empty($tmp) ) {
				$from_email = $tmp;
			}
		}
		return $from_email;
	}

	public function global_wp_mail_from_name( $from_name ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $from_name;
		}
		if ( empty($from_name) || strpos($from_name, 'WordPress') !== false ) {
			$tmp = get_option('blogname');
			if ( ! empty($tmp) ) {
				$from_name = $tmp;
			}
		}
		return $from_name;
	}

	// Public.

	public function public_wp() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		// obfuscate-email replacement.
		$filters = array( 'link_description', 'link_notes', 'bloginfo', 'nav_menu_description', 'term_description', 'the_title', 'the_content', 'get_the_excerpt', 'comment_text', 'list_cats', 'widget_text', 'the_author_email', 'get_comment_author_email' );
		foreach ( $filters as $filter ) {
			add_filter($filter, array( $this, 'public_antispambot_filter' ), 90);
		}
	}

	public function public_antispambot_filter( $text ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $text;
		}
		if ( ! content_is_ready_to_display($text, current_filter()) ) {
			return $text;
		}
		return ht_antispambot($text);
	}

	// Admin.
}
