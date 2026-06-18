<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Headless extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		add_action('parse_request', array( $this, 'global_parse_request' ), 90);
		parent::autoload();
	}

	// Global.

	public function global_parse_request( $wp ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! defined('DOING_CRON') && ! defined('REST_REQUEST') && ! is_admin() && ( empty($wp->query_vars['rest_oauth1']) && ! defined('GRAPHQL_HTTP_REQUEST') ) ) {
            if ( wp_redirect(get_admin_url()) ) {
                exit;
            }
		}
	}
}
