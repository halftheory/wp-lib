<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class I18n_Common extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Global.
		add_action('init', array( $this, 'global_init' ));
		if ( is_public() ) {
			// Public.
			add_action('wp_enqueue_scripts', array( $this, 'public_wp_enqueue_scripts' ), 1000);
			add_action('get_footer', array( $this, 'public_get_footer' ), 1000, 2);
		} else {
			// Admin.
			add_action('admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ));
		}
		parent::autoload();
	}

	// Global.

	public function global_init() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			 return;
		}
		$path = $this->get_domain_path();
		if ( ! $path ) {
			 return;
		}
		$domain = ht_get_theme_data('TextDomain');
		$locale = function_exists('determine_locale') ? determine_locale() : get_user_locale();
		$locale = (string) apply_filters('theme_locale', $locale, $domain);
		$mofile = wp_sprintf(
			'%s' . DIRECTORY_SEPARATOR . '%s-%s.mo',
			$path,
			$domain,
			$locale
		);
		if ( is_readable($mofile) ) {
			// Prevent the loading of existing translations within the 'wp-content/languages' folder.
			unload_textdomain($domain);
			load_textdomain($domain, $mofile, $locale);
		}
	}

	// Public.

	public function public_wp_enqueue_scripts() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_script_translations();
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		$this->set_script_translations();
	}

	// Admin.

	public function admin_print_footer_scripts() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! function_exists('pll_languages_list') ) {
			return;
		}
		// Polylang - add/edit a language. (admin.php?page=mlang)
		if ( isset($GLOBALS['pagenow'], $_GET['page'], $_REQUEST['pll_action']) && $GLOBALS['pagenow'] === 'admin.php' && $_GET['page'] === 'mlang' ) {
			if ( in_array(sanitize_key($_REQUEST['pll_action']), array( 'edit', 'update', 'add' )) ) {
				$array = pll_languages_list(array( 'fields' => 'locale' ));
				$this->maybe_create_pofiles($array);
			}
		}
	}

	// Functions.

	public function get_domain_path() {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = false;
			if ( $path = ht_get_theme_data('DomainPath') ) {
				if ( is_dir($path) ) {
					 $_result = untrailingslashit($path);
				}
			}
		}
		return $_result;
	}

	public function maybe_create_pofiles( $array ) {
		$array = array_filter($array);
		if ( empty($array) ) {
			return;
		}
		$path = $this->get_domain_path();
		if ( ! $path ) {
			return;
		}
		$domain = ht_get_theme_data('TextDomain');
		$potfile = wp_sprintf(
			'%s' . DIRECTORY_SEPARATOR . '%s.pot',
			$path,
			$domain
		);
		if ( ! file_exists($potfile) ) {
			return;
		}
		$wp_filesystem = ht_wp_filesystem('direct');
		if ( ! $wp_filesystem ) {
			return;
		}
		foreach ( $array as $locale ) {
			$pofile = wp_sprintf(
				'%s' . DIRECTORY_SEPARATOR . '%s-%s.po',
				$path,
				$domain,
				$locale
			);
			if ( file_exists($pofile) ) {
				return;
			}
			$wp_filesystem->copy($potfile, $pofile);
		}
	}

	public function set_script_translations( $handle = '' ) {
		$handle = empty($handle) ? ht_get_theme_data('handle') : $handle;
		static $_results = array();
		if ( array_key_exists($handle, $_results) ) {
			return $_results[ $handle ];
		}
		// https://developer.wordpress.org/block-editor/how-to-guides/internationalization/#how-to-use-i18n-in-javascript
		foreach ( array( 'wp-i18n', $handle ) as $value ) {
			if ( ! wp_script_is($value) ) {
				return false;
			}
		}
		$_results[ $handle ] = false;
		if ( $path = $this->get_domain_path() ) {
			wp_set_script_translations($handle, ht_get_theme_data('TextDomain'), $path);
			$_results[ $handle ] = true;
		}
	}
}
