<?php
namespace Halftheory\Lib\helpers;

use Halftheory\Lib\Filters;

#[AllowDynamicProperties]
class Menus_Smartmenus extends Filters {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $menu = 'primary', $wp_nav_menu_args = array(), $smartmenus_options = array() ) {
		$this->data['menu'] = $menu;
		$this->data['wp_nav_menu_args'] = $wp_nav_menu_args;
		$this->data['smartmenus_options'] = $smartmenus_options;
		parent::__construct($autoload);
	}

	protected function autoload() {
		if ( is_public() ) {
			// Public.
			add_filter('nav_menu_submenu_css_class', array( $this, 'public_nav_menu_submenu_css_class' ), 20, 3);
			add_filter('nav_menu_item_attributes', array( $this, 'public_nav_menu_item_attributes' ), 20, 4);
			add_filter('nav_menu_link_attributes', array( $this, 'public_nav_menu_link_attributes' ), 20, 4);
			add_action('get_footer', array( $this, 'public_get_footer' ), 20, 2);
			add_action('wp_footer', array( $this, 'public_wp_footer' ));
		}
		parent::autoload();
	}

	// Public.

	public function public_nav_menu_submenu_css_class( $classes, $args, $depth ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $classes;
		}
		if ( empty($this->data['menu']) ) {
			return $classes;
		}
		if ( is_object($args) && $args->menu_class === 'sm-nav' ) {
			$classes = array_value_unset($classes, 'sub-menu');
			$classes[] = 'sm-sub';
		}
		return array_unique($classes);
	}

	public function public_nav_menu_item_attributes( $atts, $menu_item, $args, $depth ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $atts;
		}
		if ( empty($this->data['menu']) ) {
			return $atts;
		}
		if ( is_object($args) && $args->menu_class === 'sm-nav' ) {
			$array = isset($atts['class']) ? explode(' ', $atts['class']) : array();
			$array[] = (int) $depth === 0 ? 'sm-nav-item' : 'sm-sub-item';
			$array = array_value_unset($array, 'menu-item');
			$atts['class'] = implode(' ', array_unique($array));
		}
		return $atts;
	}

	public function public_nav_menu_link_attributes( $atts, $menu_item, $args, $depth ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return $atts;
		}
		if ( empty($this->data['menu']) ) {
			return $atts;
		}
		if ( is_object($args) && $args->menu_class === 'sm-nav' ) {
			$array = isset($atts['class']) ? explode(' ', $atts['class']) : array();
			$array[] = (int) $depth === 0 ? 'sm-nav-link' : 'sm-sub-link';
			if ( is_object($menu_item) && isset($menu_item->classes) && is_array($menu_item->classes) ) {
				if ( in_array('menu-item-has-children', $menu_item->classes) ) {
					$array[] = 'sm-sub-toggler';
				}
			}
			$atts['class'] = implode(' ', array_unique($array));
		}
		return $atts;
	}

	public function public_get_footer( $name, $args ) {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! has_nav_menu($this->data['menu']) ) {
			return;
		}
		$array = array(
			'package' => 'smartmenus',
			'version' => '2.0.0-alpha.1',
		);
		// CSS.
		$fallback = __DIR__ . '/assets/dist/smartmenus/dist/css/smartmenus-only-layout-and-theme-collapsible' . min_scripts() . '.css';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'dist/css/smartmenus-only-layout-and-theme-collapsible' . min_scripts() . '.css' ), $fallback) ) {
			wp_enqueue_style($array['package'], $url, array(), $array['version'], 'screen');
		}
		$file = __DIR__ . '/assets/css/menus-smartmenus.css';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_style(static::$handle, $url, array(), get_file_version($file), 'screen');
		}
		// JS.
		$fallback = __DIR__ . '/assets/dist/smartmenus/dist/js/smartmenus.browser' . min_scripts() . '.js';
		if ( $url = get_uri_from_npm($array + array( 'file' => 'dist/js/smartmenus.browser' . min_scripts() . '.js' ), $fallback) ) {
			wp_enqueue_script($array['package'], $url, array(), $array['version'], true);
		}
		$file = __DIR__ . '/assets/js/smartmenus-init' . min_scripts() . '.js';
		if ( $url = get_stylesheet_uri_from_file($file) ) {
			wp_enqueue_script(static::$handle, $url, array( 'jquery', $array['package'] ), get_file_version($file), true);
			wp_localize_script(static::$handle, 'menus_smartmenus', array( 'menu' => $this->data['menu'], 'options' => $this->data['smartmenus_options'] ));
		}
	}

	public function public_wp_footer() {
		if ( ! $this->is_filter_active(__FUNCTION__) ) {
			return;
		}
		if ( ! has_nav_menu($this->data['menu']) ) {
			return;
		}
		if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'Smartmenus_Walker_Nav_Menu.php') ) {
			include_once __DIR__ . DIRECTORY_SEPARATOR . 'Smartmenus_Walker_Nav_Menu.php';
		}
		$defaults = array(
			'theme_location' => $this->data['menu'],
			'menu_class' => 'sm-nav',
			'menu_id' => 'menu-' . $this->data['menu'],
			'container' => '',
			'depth' => 3,
			'item_spacing' => is_development() ? 'preserve' : 'discard',
			'walker' => class_exists('Halftheory\Lib\helpers\Menus_Smartmenus\Smartmenus_Walker_Nav_Menu') ? new \Halftheory\Lib\helpers\Menus_Smartmenus\Smartmenus_Walker_Nav_Menu() : '',
		);
		$args = wp_parse_args($this->data['wp_nav_menu_args'], $defaults);
		?>
<nav id="nav-<?php echo esc_attr($this->data['menu']); ?>" role="menu" aria-label="<?php echo esc_attr(get_registered_nav_menus()[ $args['theme_location'] ]); ?>" class="sm-navbar sm-navbar--offcanvas-right sm-navbar--offcanvas-only sm-navbar--collapsible-only">
	<span class="sm-toggler-state" id="menu"></span>

	<div class="sm-toggler">
		<a href="#menu" class="sm-toggler-anchor sm-toggler-anchor--show" role="button" aria-label="<?php esc_attr_e('Open'); ?> <?php echo esc_attr(get_registered_nav_menus()[ $args['theme_location'] ]); ?>">
			<span class="sm-toggler-icon sm-toggler-icon--show"></span>
		</a>
		<a href="#" class="sm-toggler-anchor sm-toggler-anchor--hide" role="button" aria-label="<?php esc_attr_e('Close'); ?> <?php echo esc_attr(get_registered_nav_menus()[ $args['theme_location'] ]); ?>">
			<span class="sm-toggler-icon sm-toggler-icon--hide"></span>
		</a>
	</div>

	<a class="sm-offcanvas-overlay" href="#" aria-hidden="true" tabindex="-1"></a>

	<div class="sm-offcanvas">
		<div class="sm-offcanvas-title">
			<?php
            $logo = '';
            if ( has_custom_logo() ) {
                if ( $tmp = get_image_context('img', get_theme_mod('custom_logo'), 'thumbnail') ) {
                    $logo = $tmp . ' ';
                }
            }
			?>
			<a href="<?php echo esc_url(network_home_url('/')); ?>" rel="home" class="sm-brand"><?php echo $logo; ?><?php bloginfo('name'); ?></a>
			<a href="#" class="sm-toggler-anchor sm-toggler-anchor--hide" role="button" aria-label="<?php esc_attr_e('Close menu'); ?>"><span class="sm-toggler-icon sm-toggler-icon--hide"></span></a>
		</div>
		<?php wp_nav_menu($args); ?>
	</div>
</nav>
		<?php
	}

	// Functions.

	public function menu_button( $type = 'smartmenus' ) {
		// theme()->get_helper('menus-smartmenus')->menu_button();
		if ( ! has_nav_menu($this->data['menu']) ) {
			return;
		}
		switch ( $type ) {
			case 'fontawesome':
			case 'fa':
				?>
<a class="fa fa-bars sm-toggler-anchor--show" href="#menu" role="button"><span class="screen-reader-text"><?php esc_html_e('Menu'); ?></span></a>
				<?php
				break;

			case 'smartmenus':
			case 'sm':
			default:
				?>
<div class="sm-toggler">
	<a href="#menu" class="sm-toggler-anchor sm-toggler-anchor--show" role="button" aria-label="<?php esc_attr_e('Open menu'); ?>">
		<span class="sm-toggler-icon sm-toggler-icon--show"></span>
	</a>
	<a href="#" class="sm-toggler-anchor sm-toggler-anchor--hide" role="button" aria-label="<?php esc_attr_e('Close menu'); ?>">
		<span class="sm-toggler-icon sm-toggler-icon--hide"></span>
	</a>
</div>
				<?php
				break;
		}
	}
}
