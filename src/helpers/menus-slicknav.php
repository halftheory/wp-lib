<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Menus_Slicknav extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $menu = 'primary', $wp_nav_menu_args = array(), $slicknav_options = array() ) {
		$this->data['menu'] = $menu;
		$this->data['wp_nav_menu_args'] = $wp_nav_menu_args;
		$this->data['slicknav_options'] = $slicknav_options;
		parent::__construct($autoload);
	}

	protected function autoload() {
		if ( is_public() ) {
			// Public.
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
		}
		parent::autoload();
	}

	// Public.

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! has_nav_menu($this->data['menu']) ) {
			return;
		}
		$array = array(
			'package' => 'slicknav',
			'version' => '1.0.8',
		);
		// CSS.
		wp_enqueue_style('dashicons');
		$fallback = __DIR__ . '/assets/dist/slicknav/dist/slicknav' . min_scripts() . '.css';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'dist/slicknav' . min_scripts() . '.css' ), $fallback) ) {
			wp_enqueue_style($array['package'], $url, array(), $array['version'], 'screen');
		}
		$file = __DIR__ . '/assets/css/menus-slicknav.css';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_style(static::$handle, $url, array( $array['package'] ), get_file_version($file), 'screen');
		}
		// JS.
		$fallback = __DIR__ . '/assets/dist/slicknav/dist/jquery.slicknav' . min_scripts() . '.js';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'dist/jquery.slicknav' . min_scripts() . '.js' ), $fallback) ) {
			wp_enqueue_script($array['package'], $url, array( 'jquery' ), $array['version'], true);
		}
		$file = __DIR__ . '/assets/js/menus-slicknav' . min_scripts() . '.js';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_script(static::$handle, $url, array( 'jquery', $array['package'] ), get_file_version($file), true);
			// Format data.
			$logo = '';
			if ( has_custom_logo() ) {
				if ( $tmp = get_image_context('img', get_theme_mod('custom_logo'), 'thumbnail') ) {
					$logo = $tmp . ' ';
				}
			}
			$defaults = array(
				'brand' => '<a href="' . esc_url(network_home_url('/')) . '" rel="home" class="slicknav-brand">' . $logo . get_bloginfo('name') . '</a>',
			);
			$data = array(
				'menu' => $this->data['menu'],
				'options' => wp_parse_args($this->data['slicknav_options'], $defaults),
			);
			wp_localize_script(static::$handle, 'menus_slicknav', $data);
		}
	}

	// Functions.

	public function nav_menu() {
		// theme()->get_helper('menus-slicknav')->nav_menu();
		if ( ! has_nav_menu($this->data['menu']) ) {
			return;
		}
		$defaults = array(
			'theme_location' => $this->data['menu'],
			'menu_class' => '',
			'menu_id' => 'menu-' . $this->data['menu'],
			'container' => '',
			'depth' => 3,
			'item_spacing' => is_development() ? 'preserve' : 'discard',
		);
		$args = wp_parse_args($this->data['wp_nav_menu_args'], $defaults);
		?>
		<nav id="nav-<?php echo esc_attr($this->data['menu']); ?>" role="menu" aria-label="<?php echo esc_attr(get_registered_nav_menus()[ $args['theme_location'] ]); ?>" class="nav-menu nav-slicknav">
			<?php wp_nav_menu($args); ?>
		</nav>
		<?php
	}
}
