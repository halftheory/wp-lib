<?php
namespace Halftheory\Lib\helpers\classes;

if ( ! class_exists('Walker_Nav_Menu') ) {
	require_once path_join(ABSPATH, 'wp-includes/class-walker-nav-menu.php');
}
use Walker_Nav_Menu;

#[AllowDynamicProperties]
class Walker_Nav_Menu_Social extends Walker_Nav_Menu {

	private $privacy_policy_url;

	// @fortawesome/fontawesome-free/css/brands.css
	public $fa_brands = array(
		'fa-monero',
		'fa-hooli',
		'fa-yelp',
		'fa-cc-visa',
		'fa-lastfm',
		'fa-shopware',
		'fa-creative-commons-nc',
		'fa-aws',
		'fa-redhat',
		'fa-yoast',
		'fa-cloudflare',
		'fa-ups',
		'fa-pixiv',
		'fa-wpexplorer',
		'fa-dyalog',
		'fa-bity',
		'fa-stackpath',
		'fa-buysellads',
		'fa-first-order',
		'fa-modx',
		'fa-guilded',
		'fa-vnv',
		'fa-square-js',
		'fa-js-square',
		'fa-microsoft',
		'fa-qq',
		'fa-orcid',
		'fa-java',
		'fa-invision',
		'fa-creative-commons-pd-alt',
		'fa-centercode',
		'fa-glide-g',
		'fa-drupal',
		'fa-jxl',
		'fa-dart-lang',
		'fa-hire-a-helper',
		'fa-creative-commons-by',
		'fa-unity',
		'fa-whmcs',
		'fa-rocketchat',
		'fa-vk',
		'fa-untappd',
		'fa-mailchimp',
		'fa-css3-alt',
		'fa-square-reddit',
		'fa-reddit-square',
		'fa-vimeo-v',
		'fa-contao',
		'fa-square-font-awesome',
		'fa-deskpro',
		'fa-brave',
		'fa-sistrix',
		'fa-square-instagram',
		'fa-instagram-square',
		'fa-battle-net',
		'fa-the-red-yeti',
		'fa-square-hacker-news',
		'fa-hacker-news-square',
		'fa-edge',
		'fa-threads',
		'fa-napster',
		'fa-square-snapchat',
		'fa-snapchat-square',
		'fa-google-plus-g',
		'fa-artstation',
		'fa-markdown',
		'fa-sourcetree',
		'fa-google-plus',
		'fa-diaspora',
		'fa-foursquare',
		'fa-stack-overflow',
		'fa-github-alt',
		'fa-phoenix-squadron',
		'fa-pagelines',
		'fa-algolia',
		'fa-red-river',
		'fa-creative-commons-sa',
		'fa-safari',
		'fa-google',
		'fa-square-font-awesome-stroke',
		'fa-font-awesome-alt',
		'fa-atlassian',
		'fa-linkedin-in',
		'fa-digital-ocean',
		'fa-nimblr',
		'fa-chromecast',
		'fa-evernote',
		'fa-hacker-news',
		'fa-creative-commons-sampling',
		'fa-adversal',
		'fa-creative-commons',
		'fa-watchman-monitoring',
		'fa-fonticons',
		'fa-weixin',
		'fa-shirtsinbulk',
		'fa-codepen',
		'fa-git-alt',
		'fa-lyft',
		'fa-rev',
		'fa-windows',
		'fa-wizards-of-the-coast',
		'fa-square-viadeo',
		'fa-viadeo-square',
		'fa-meetup',
		'fa-centos',
		'fa-adn',
		'fa-cloudsmith',
		'fa-opensuse',
		'fa-pied-piper-alt',
		'fa-square-dribbble',
		'fa-dribbble-square',
		'fa-codiepie',
		'fa-node',
		'fa-mix',
		'fa-steam',
		'fa-cc-apple-pay',
		'fa-scribd',
		'fa-debian',
		'fa-openid',
		'fa-instalod',
		'fa-files-pinwheel',
		'fa-expeditedssl',
		'fa-sellcast',
		'fa-square-twitter',
		'fa-twitter-square',
		'fa-r-project',
		'fa-delicious',
		'fa-freebsd',
		'fa-vuejs',
		'fa-accusoft',
		'fa-ioxhost',
		'fa-fonticons-fi',
		'fa-app-store',
		'fa-cc-mastercard',
		'fa-itunes-note',
		'fa-golang',
		'fa-kickstarter',
		'fa-square-kickstarter',
		'fa-grav',
		'fa-weibo',
		'fa-uncharted',
		'fa-firstdraft',
		'fa-square-youtube',
		'fa-youtube-square',
		'fa-wikipedia-w',
		'fa-wpressr',
		'fa-rendact',
		'fa-angellist',
		'fa-galactic-republic',
		'fa-nfc-directional',
		'fa-skype',
		'fa-joget',
		'fa-fedora',
		'fa-stripe-s',
		'fa-meta',
		'fa-laravel',
		'fa-hotjar',
		'fa-bluetooth-b',
		'fa-square-letterboxd',
		'fa-sticker-mule',
		'fa-creative-commons-zero',
		'fa-hips',
		'fa-css',
		'fa-behance',
		'fa-reddit',
		'fa-discord',
		'fa-chrome',
		'fa-app-store-ios',
		'fa-cc-discover',
		'fa-wpbeginner',
		'fa-confluence',
		'fa-shoelace',
		'fa-mdb',
		'fa-dochub',
		'fa-accessible-icon',
		'fa-ebay',
		'fa-amazon',
		'fa-unsplash',
		'fa-yarn',
		'fa-square-steam',
		'fa-steam-square',
		'fa-500px',
		'fa-square-vimeo',
		'fa-vimeo-square',
		'fa-asymmetrik',
		'fa-font-awesome',
		'fa-font-awesome-flag',
		'fa-font-awesome-logo-full',
		'fa-gratipay',
		'fa-apple',
		'fa-hive',
		'fa-gitkraken',
		'fa-keybase',
		'fa-apple-pay',
		'fa-padlet',
		'fa-amazon-pay',
		'fa-square-github',
		'fa-github-square',
		'fa-stumbleupon',
		'fa-fedex',
		'fa-phoenix-framework',
		'fa-shopify',
		'fa-neos',
		'fa-square-threads',
		'fa-hackerrank',
		'fa-researchgate',
		'fa-swift',
		'fa-angular',
		'fa-speakap',
		'fa-angrycreative',
		'fa-y-combinator',
		'fa-empire',
		'fa-envira',
		'fa-google-scholar',
		'fa-square-gitlab',
		'fa-gitlab-square',
		'fa-studiovinari',
		'fa-pied-piper',
		'fa-wordpress',
		'fa-product-hunt',
		'fa-firefox',
		'fa-linode',
		'fa-goodreads',
		'fa-square-odnoklassniki',
		'fa-odnoklassniki-square',
		'fa-jsfiddle',
		'fa-sith',
		'fa-themeisle',
		'fa-page4',
		'fa-hashnode',
		'fa-react',
		'fa-cc-paypal',
		'fa-squarespace',
		'fa-cc-stripe',
		'fa-creative-commons-share',
		'fa-bitcoin',
		'fa-keycdn',
		'fa-opera',
		'fa-itch-io',
		'fa-umbraco',
		'fa-galactic-senate',
		'fa-ubuntu',
		'fa-draft2digital',
		'fa-stripe',
		'fa-houzz',
		'fa-gg',
		'fa-dhl',
		'fa-square-pinterest',
		'fa-pinterest-square',
		'fa-xing',
		'fa-blackberry',
		'fa-creative-commons-pd',
		'fa-playstation',
		'fa-quinscape',
		'fa-less',
		'fa-blogger-b',
		'fa-opencart',
		'fa-vine',
		'fa-signal-messenger',
		'fa-paypal',
		'fa-gitlab',
		'fa-typo3',
		'fa-reddit-alien',
		'fa-yahoo',
		'fa-dailymotion',
		'fa-affiliatetheme',
		'fa-pied-piper-pp',
		'fa-bootstrap',
		'fa-odnoklassniki',
		'fa-nfc-symbol',
		'fa-mintbit',
		'fa-ethereum',
		'fa-speaker-deck',
		'fa-creative-commons-nc-eu',
		'fa-patreon',
		'fa-avianex',
		'fa-ello',
		'fa-gofore',
		'fa-bimobject',
		'fa-brave-reverse',
		'fa-facebook-f',
		'fa-square-google-plus',
		'fa-google-plus-square',
		'fa-web-awesome',
		'fa-mandalorian',
		'fa-first-order-alt',
		'fa-osi',
		'fa-google-wallet',
		'fa-d-and-d-beyond',
		'fa-periscope',
		'fa-fulcrum',
		'fa-cloudscale',
		'fa-forumbee',
		'fa-mizuni',
		'fa-schlix',
		'fa-square-xing',
		'fa-xing-square',
		'fa-bandcamp',
		'fa-wpforms',
		'fa-cloudversify',
		'fa-usps',
		'fa-megaport',
		'fa-magento',
		'fa-spotify',
		'fa-optin-monster',
		'fa-fly',
		'fa-square-bluesky',
		'fa-aviato',
		'fa-itunes',
		'fa-cuttlefish',
		'fa-blogger',
		'fa-flickr',
		'fa-viber',
		'fa-soundcloud',
		'fa-digg',
		'fa-tencent-weibo',
		'fa-letterboxd',
		'fa-symfony',
		'fa-maxcdn',
		'fa-etsy',
		'fa-facebook-messenger',
		'fa-audible',
		'fa-think-peaks',
		'fa-bilibili',
		'fa-erlang',
		'fa-x-twitter',
		'fa-cotton-bureau',
		'fa-dashcube',
		'fa-42-group',
		'fa-innosoft',
		'fa-stack-exchange',
		'fa-elementor',
		'fa-square-pied-piper',
		'fa-pied-piper-square',
		'fa-creative-commons-nd',
		'fa-palfed',
		'fa-superpowers',
		'fa-resolving',
		'fa-xbox',
		'fa-square-web-awesome-stroke',
		'fa-searchengin',
		'fa-tiktok',
		'fa-square-facebook',
		'fa-facebook-square',
		'fa-renren',
		'fa-linux',
		'fa-glide',
		'fa-linkedin',
		'fa-hubspot',
		'fa-deploydog',
		'fa-twitch',
		'fa-flutter',
		'fa-ravelry',
		'fa-mixer',
		'fa-square-lastfm',
		'fa-lastfm-square',
		'fa-vimeo',
		'fa-mendeley',
		'fa-uniregistry',
		'fa-figma',
		'fa-creative-commons-remix',
		'fa-cc-amazon-pay',
		'fa-dropbox',
		'fa-instagram',
		'fa-cmplid',
		'fa-upwork',
		'fa-facebook',
		'fa-gripfire',
		'fa-jedi-order',
		'fa-uikit',
		'fa-fort-awesome-alt',
		'fa-phabricator',
		'fa-ussunnah',
		'fa-earlybirds',
		'fa-trade-federation',
		'fa-autoprefixer',
		'fa-whatsapp',
		'fa-square-upwork',
		'fa-slideshare',
		'fa-google-play',
		'fa-viadeo',
		'fa-line',
		'fa-google-drive',
		'fa-servicestack',
		'fa-simplybuilt',
		'fa-bitbucket',
		'fa-imdb',
		'fa-deezer',
		'fa-raspberry-pi',
		'fa-jira',
		'fa-docker',
		'fa-screenpal',
		'fa-bluetooth',
		'fa-gitter',
		'fa-d-and-d',
		'fa-microblog',
		'fa-cc-diners-club',
		'fa-gg-circle',
		'fa-pied-piper-hat',
		'fa-kickstarter-k',
		'fa-yandex',
		'fa-readme',
		'fa-html5',
		'fa-sellsy',
		'fa-square-web-awesome',
		'fa-sass',
		'fa-wirsindhandwerk',
		'fa-wsh',
		'fa-buromobelexperte',
		'fa-salesforce',
		'fa-octopus-deploy',
		'fa-medapps',
		'fa-ns8',
		'fa-pinterest-p',
		'fa-apper',
		'fa-fort-awesome',
		'fa-waze',
		'fa-bluesky',
		'fa-cc-jcb',
		'fa-snapchat',
		'fa-snapchat-ghost',
		'fa-fantasy-flight-games',
		'fa-rust',
		'fa-wix',
		'fa-square-behance',
		'fa-behance-square',
		'fa-supple',
		'fa-webflow',
		'fa-rebel',
		'fa-css3',
		'fa-staylinked',
		'fa-kaggle',
		'fa-space-awesome',
		'fa-deviantart',
		'fa-cpanel',
		'fa-goodreads-g',
		'fa-square-git',
		'fa-git-square',
		'fa-square-tumblr',
		'fa-tumblr-square',
		'fa-trello',
		'fa-creative-commons-nc-jp',
		'fa-get-pocket',
		'fa-perbyte',
		'fa-grunt',
		'fa-weebly',
		'fa-connectdevelop',
		'fa-leanpub',
		'fa-black-tie',
		'fa-themeco',
		'fa-python',
		'fa-android',
		'fa-bots',
		'fa-free-code-camp',
		'fa-hornbill',
		'fa-js',
		'fa-ideal',
		'fa-git',
		'fa-dev',
		'fa-sketch',
		'fa-yandex-international',
		'fa-cc-amex',
		'fa-uber',
		'fa-github',
		'fa-php',
		'fa-alipay',
		'fa-youtube',
		'fa-skyatlas',
		'fa-firefox-browser',
		'fa-replyd',
		'fa-suse',
		'fa-jenkins',
		'fa-twitter',
		'fa-rockrms',
		'fa-pinterest',
		'fa-buffer',
		'fa-npm',
		'fa-yammer',
		'fa-btc',
		'fa-dribbble',
		'fa-stumbleupon-circle',
		'fa-internet-explorer',
		'fa-stubber',
		'fa-telegram',
		'fa-telegram-plane',
		'fa-old-republic',
		'fa-odysee',
		'fa-square-whatsapp',
		'fa-whatsapp-square',
		'fa-node-js',
		'fa-edge-legacy',
		'fa-slack',
		'fa-slack-hash',
		'fa-medrt',
		'fa-usb',
		'fa-tumblr',
		'fa-vaadin',
		'fa-quora',
		'fa-square-x-twitter',
		'fa-reacteurope',
		'fa-medium',
		'fa-medium-m',
		'fa-amilia',
		'fa-mixcloud',
		'fa-flipboard',
		'fa-viacoin',
		'fa-critical-role',
		'fa-sitrox',
		'fa-discourse',
		'fa-joomla',
		'fa-mastodon',
		'fa-airbnb',
		'fa-wolf-pack-battalion',
		'fa-buy-n-large',
		'fa-gulp',
		'fa-creative-commons-sampling-plus',
		'fa-strava',
		'fa-ember',
		'fa-canadian-maple-leaf',
		'fa-teamspeak',
		'fa-pushed',
		'fa-wordpress-simple',
		'fa-nutritionix',
		'fa-wodu',
		'fa-google-pay',
		'fa-intercom',
		'fa-zhihu',
		'fa-korvue',
		'fa-pix',
		'fa-steam-symbol',
	);

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		// Restores the more descriptive, specific name for use within this method.
		$menu_item = $data_object;

		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}
		$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

		$classes   = empty( $menu_item->classes ) ? array() : (array) $menu_item->classes;
		$classes[] = 'menu-item-' . $menu_item->ID;

		/**
		 * Filters the arguments for a single nav menu item.
		 *
		 * @since 4.4.0
		 *
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param WP_Post  $menu_item Menu item data object.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$args = apply_filters( 'nav_menu_item_args', $args, $menu_item, $depth );

		// Get the icon classes.
		$fa_classes = $this->get_fa_classes($menu_item);

		// Remove icon classes from the <li>.
		if ( $fa_classes ) {
			$classes = array_diff($classes, $fa_classes);
		}

		/**
		 * Filters the CSS classes applied to a menu item's list item element.
		 *
		 * @since 3.0.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param string[] $classes   Array of the CSS classes that are applied to the menu item's `<li>` element.
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $menu_item, $args, $depth ) );

		/**
		 * Filters the ID attribute applied to a menu item's list item element.
		 *
		 * @since 3.0.1
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param string   $menu_item_id The ID attribute applied to the menu item's `<li>` element.
		 * @param WP_Post  $menu_item    The current menu item.
		 * @param stdClass $args         An object of wp_nav_menu() arguments.
		 * @param int      $depth        Depth of menu item. Used for padding.
		 */
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $menu_item->ID, $menu_item, $args, $depth );

		$li_atts          = array();
		$li_atts['id']    = ! empty( $id ) ? $id : '';
		$li_atts['class'] = ! empty( $class_names ) ? $class_names : '';

		/**
		 * Filters the HTML attributes applied to a menu's list item element.
		 *
		 * @since 6.3.0
		 *
		 * @param array $li_atts {
		 *     The HTML attributes applied to the menu item's `<li>` element, empty strings are ignored.
		 *
		 *     @type string $class        HTML CSS class attribute.
		 *     @type string $id           HTML id attribute.
		 * }
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$li_atts       = apply_filters( 'nav_menu_item_attributes', $li_atts, $menu_item, $args, $depth );
		$li_attributes = $this->build_atts( $li_atts );

		$output .= $indent . '<li' . $li_attributes . '>';

		/** This filter is documented in wp-includes/post-template.php */
		$title = apply_filters( 'the_title', $menu_item->title, $menu_item->ID );

		// Save filtered value before filtering again.
		$the_title_filtered = $title;

		// Replace title with icon.
		if ( $fa_classes ) {
			$title = '<i class="' . esc_attr(implode(' ', $fa_classes)) . '" title="' . esc_attr($title) . '"></i>';
		}

		/**
		 * Filters a menu item's title.
		 *
		 * @since 4.4.0
		 *
		 * @param string   $title     The menu item's title.
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$title = apply_filters( 'nav_menu_item_title', $title, $menu_item, $args, $depth );

		$atts           = array();
		$atts['target'] = ! empty( $menu_item->target ) ? $menu_item->target : '';
		$atts['rel']    = ! empty( $menu_item->xfn ) ? $menu_item->xfn : '';

		if ( ! empty( $menu_item->url ) ) {
			if ( $this->privacy_policy_url === $menu_item->url ) {
				$atts['rel'] = empty( $atts['rel'] ) ? 'privacy-policy' : $atts['rel'] . ' privacy-policy';
			}

			$atts['href'] = $menu_item->url;
		} else {
			$atts['href'] = '';
		}

		$atts['aria-current'] = $menu_item->current ? 'page' : '';

		// Add title attribute only if it does not match the link text (before or after filtering).
		if ( ! empty( $menu_item->attr_title )
			&& trim( strtolower( $menu_item->attr_title ) ) !== trim( strtolower( $menu_item->title ) )
			&& trim( strtolower( $menu_item->attr_title ) ) !== trim( strtolower( $the_title_filtered ) )
			&& trim( strtolower( $menu_item->attr_title ) ) !== trim( strtolower( $title ) )
		) {
			$atts['title'] = $menu_item->attr_title;
		} else {
			$atts['title'] = '';
		}

		/**
		 * Filters the HTML attributes applied to a menu item's anchor element.
		 *
		 * @since 3.6.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param array $atts {
		 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
		 *
		 *     @type string $title        Title attribute.
		 *     @type string $target       Target attribute.
		 *     @type string $rel          The rel attribute.
		 *     @type string $href         The href attribute.
		 *     @type string $aria-current The aria-current attribute.
		 * }
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$atts       = apply_filters( 'nav_menu_link_attributes', $atts, $menu_item, $args, $depth );
		$attributes = $this->build_atts( $atts );

		$item_output  = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . $title . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		/**
		 * Filters a menu item's starting output.
		 *
		 * The menu item's starting output only includes `$args->before`, the opening `<a>`,
		 * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
		 * no filter for modifying the opening and closing `<li>` for a menu item.
		 *
		 * @since 3.0.0
		 *
		 * @param string   $item_output The menu item's starting HTML output.
		 * @param WP_Post  $menu_item   Menu item data object.
		 * @param int      $depth       Depth of menu item. Used for padding.
		 * @param stdClass $args        An object of wp_nav_menu() arguments.
		 */
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $menu_item, $depth, $args );
	}

	public function get_fa_classes( $menu_item ) {
		$fa_classes = array( 'fa-brands' );
		// Menu classes - Find exact brand.
		$tmp = array_intersect($menu_item->classes, $this->fa_brands);
		if ( ! empty($tmp) ) {
			return array_merge($fa_classes, $tmp);
		}
		// Menu classes - Find other icons (maybe not brands).
		$tmp = array();
		foreach ( $menu_item->classes as $value ) {
			if ( str_starts_with($value, 'fa-') ) {
				$tmp[] = $value;
			}
		}
		if ( count($tmp) > 0 ) {
			return array_unique($tmp);
		}
		// Match Title, URL.
		$matches = array( $menu_item->title );
		$host = str_replace_start('www.', '', wp_parse_url($menu_item->url, PHP_URL_HOST));
		$host_home = str_replace_start('www.', '', wp_parse_url(home_url(), PHP_URL_HOST));
		if ( ! str_contains($host_home, $host) ) {
			$tmp = explode('.', $host);
			$matches[] = current($tmp);
		}
		$matches = array_unique(array_map('sanitize_title', $matches));
		$tmp = array();
		foreach ( $matches as $match ) {
			// Exact.
			$callback = function ( $value ) use ( $match ) {
				return $value === $match || $value === 'fa-' . $match;
			};
			$tmp = array_filter($this->fa_brands, $callback);
			if ( ! empty($tmp) ) {
				break;
			}
			// Partial.
			$callback = function ( $value ) use ( $match ) {
				return str_contains($value, $match);
			};
			$tmp = array_filter($this->fa_brands, $callback);
			if ( ! empty($tmp) ) {
				break;
			}
		}
		if ( ! empty($tmp) ) {
			$tmp = sort_shortest_first($tmp);
			$fa_classes[] = current($tmp);
			return array_unique($fa_classes);
		}
		return false;
	}
}
