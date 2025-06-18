<?php
if ( ! function_exists('get_oembed_providers_hosts') ) {
	function get_oembed_providers_hosts() {
		static $_results = null;
		if ( is_null($_results) ) {
			$_results = array();
			$oembed = _wp_oembed_get_object();
			if ( is_object($oembed) && isset($oembed->providers) ) {
				$_results = $oembed->providers;
				// Reduce the list to host and path.
				$callback = function ( $value ) {
					return wp_parse_url($value[0], PHP_URL_HOST) . wp_parse_url($value[0], PHP_URL_PATH);
				};
				$_results = array_values(sort_longest_first(array_unique(array_map($callback, $_results))));
			}
		}
		return $_results;
	}
}

if ( ! function_exists('get_oembed_providers_hosts_by_type') ) {
	function get_oembed_providers_hosts_by_type( $object_types, $args = array() ) {
		$hosts = get_oembed_providers_hosts();
		if ( empty($hosts) ) {
			return $hosts;
		}
		// Pass shortened hostnames in the $args parameter to target hosts by media type.
		$defaults = array(
			'audio' => array(
				'mixcloud.com',
				'soundcloud.com',
				'spotify.com',
			),
			'image' => array(
				'flickr.com',
				'imgur.com',
				'pinterest.com',
				'reddit.com',
				'tumblr.com',
			),
			'video' => array(
				'dailymotion.com',
				'screencast.com',
				'tiktok.com',
				'vimeo.com',
				'wordpress.tv',
				'youtube.com',
			),
		);
		if ( empty($args) ) {
			$args = $defaults;
		} else {
			$keys = array_unique(array_merge(array_keys($defaults), array_keys($args)));
			sort($keys);
			$array = array();
			foreach ( $keys as $key ) {
				if ( isset($args[ $key ], $defaults[ $key ]) ) {
					$array[ $key ] = wp_parse_args($args[ $key ], $defaults[ $key ]);
				} elseif ( isset($args[ $key ]) ) {
					$array[ $key ] = $args[ $key ];
				} elseif ( isset($defaults[ $key ]) ) {
					$array[ $key ] = $defaults[ $key ];
				}
			}
			$args = $array;
		}
		// Find the hosts.
		$results = array();
		foreach ( make_array($object_types) as $type ) {
			if ( array_key_exists($type, $args) ) {
				foreach ( $args[ $type ] as $value ) {
					foreach ( $hosts as $host ) {
						if ( str_contains($host, $value) ) {
							$results[] = $host;
							$results[] = $value;
						}
					}
				}
			}
		}
		return array_values(sort_longest_first(array_unique($results)));
	}
}

if ( ! function_exists('get_post_oembed_object_thumbnail') ) {
	function get_post_oembed_object_thumbnail( $post = null, $object_types = null, $hosts_args = array() ) {
		$url = get_post_oembed_object_url($post, $object_types, $hosts_args);
		if ( ! $url ) {
			return false;
		}
		// Store results in a static var. key = url, value = object.
		static $_results = array();
		if ( array_key_exists( $url, $_results) ) {
			return $_results[ $url ];
		}
		$_results[ $url ] = false;
		$oembed = _wp_oembed_get_object();
		if ( is_object($oembed) ) {
			$url_discover = untrailingslashit(set_url_scheme($url, 'http'));
			if ( $data = $oembed->get_data($url_discover, array( 'discover' => false )) ) {
				if ( is_object($data) ) {
					if ( isset($data->thumbnail_url) && ! empty($data->thumbnail_url) && ! str_contains($data->thumbnail_url, 'placeholder') ) {
						$_results[ $url ] = $data;
						if ( ! isset($_results[ $url ]->url) ) {
							$_results[ $url ]->url = $url;
						}
					}
				}
			}
		}
		return $_results[ $url ];
	}
}

if ( ! function_exists('get_post_oembed_object_thumbnail_context') ) {
	function get_post_oembed_object_thumbnail_context( $context, $post = null, $attr = array(), $object_types = null, $hosts_args = array() ) {
		$thumbnail = get_post_oembed_object_thumbnail($post, $object_types, $hosts_args);
		if ( ! $thumbnail ) {
			return false;
		}
		$defaults = array(
			'src' => isset($thumbnail->thumbnail_url) ? set_url_scheme($thumbnail->thumbnail_url) : '',
			'alt' => '',
			'width' => isset($thumbnail->thumbnail_width) ? absint($thumbnail->thumbnail_width) : '',
			'height' => isset($thumbnail->thumbnail_height) ? absint($thumbnail->thumbnail_height) : '',
		);
		$attr = wp_parse_args($attr, $defaults);
		$result = null;
		switch ( $context ) {
			case 'url':
				$result = $attr['src'];
				break;
			case 'attr':
				$result = $attr;
				break;
			case 'src':
				$result = array(
					$attr['src'],
					$attr['width'],
					$attr['height'],
				);
				break;
			case 'img':
				if ( $img = wp_html_tag_set_attributes('<img />', null, $attr) ) {
					$result = $img;
				}
				break;
			case 'link':
				if ( $img = wp_html_tag_set_attributes('<img />', null, $attr) ) {
					$result = '<a href="' . esc_url(set_url_scheme($thumbnail->url)) . '">' . $img . '</a>';
				}
				break;
			default:
				break;
		}
		return $result;
	}
}

if ( ! function_exists('get_post_oembed_object_url') ) {
	function get_post_oembed_object_url( $post = null, $object_types = null, $hosts_args = array() ) {
		$post = get_post($post);
		if ( ! $post ) {
			return false;
		}
		$urls = get_urls($post->post_content);
		if ( ! $urls ) {
			return false;
		}
		$oembed = _wp_oembed_get_object();
		if ( ! is_object($oembed) ) {
			return false;
		}
		$hosts = $object_types ? get_oembed_providers_hosts_by_type($object_types, $hosts_args) : get_oembed_providers_hosts();
		if ( empty($hosts) ) {
			return false;
		}
		foreach ( $urls as $url ) {
			foreach ( $hosts as $host ) {
				if ( str_contains($url, $host) ) {
					if ( $oembed->get_data($url, array( 'discover' => false )) ) {
						return $url;
					}
				}
			}
		}
		return false;
	}
}

if ( ! function_exists('has_post_oembed_audio') ) {
	function has_post_oembed_audio( $post = null ) {
		return has_post_oembed_object($post, 'audio');
	}
}

if ( ! function_exists('has_post_oembed_image') ) {
	function has_post_oembed_image( $post = null ) {
		return has_post_oembed_object($post, 'image');
	}
}

if ( ! function_exists('has_post_oembed_object') ) {
	function has_post_oembed_object( $post = null, $object_types = null, $hosts_args = array() ) {
		$url = get_post_oembed_object_url($post, $object_types, $hosts_args);
		return (bool) $url;
	}
}

if ( ! function_exists('has_post_oembed_thumbnail') ) {
	function has_post_oembed_thumbnail( $post = null, $object_types = null, $hosts_args = array() ) {
		$thumbnail = get_post_oembed_object_thumbnail($post, $object_types, $hosts_args);
		return (bool) $thumbnail;
	}
}

if ( ! function_exists('has_post_oembed_video') ) {
	function has_post_oembed_video( $post = null ) {
		return has_post_oembed_object($post, 'video');
	}
}

if ( ! function_exists('the_post_oembed_thumbnail') ) {
	function the_post_oembed_thumbnail( $attr = array(), $object_types = null, $hosts_args = array() ) {
		$attr = get_post_oembed_object_thumbnail_context('attr', null, $attr, $object_types, $hosts_args);
		if ( ! $attr ) {
			return;
		}
		// Add more attributes.
		$defaults = array(
			'alt' => the_title_attribute('echo=0'),
		);
		$div_class = array(
			'post-oembed-thumbnail',
		);
		if ( $attr['width'] && $attr['height'] ) {
			if ( $orientation = get_image_orientation($attr['width'], $attr['height']) ) {
				$defaults['class'] = $orientation;
			}
		}
		$attr = wp_parse_args($attr, $defaults);
		if ( isset($attr['class']) ) {
			$div_class[] = trim($attr['class']);
		}
		$img = wp_html_tag_set_attributes('<img />', null, $attr);
		if ( ! $img ) {
			return;
		}
		$label = $attr['alt'] ? wp_sprintf('%s: "%s"', __('Image'), $attr['alt']) : __('Image');
		if ( is_singular() ) {
			// Singular.
			$div_class[] = 'singular';
			?>
			<div class="<?php echo esc_attr(implode(' ', $div_class)); ?>" role="img" aria-label="<?php echo esc_attr($label); ?>">
				<a href="<?php echo esc_url($attr['src']); ?>" rel="lightbox"><?php echo wp_kses_post($img); ?></a>
			</div>
			<?php
		} else {
			// Archives.
			?>
			<div class="<?php echo esc_attr(implode(' ', $div_class)); ?>" role="img" aria-label="<?php echo esc_attr($label); ?>">
				<a href="<?php the_permalink(); ?>"><?php echo wp_kses_post($img); ?></a>
			</div>
			<?php
		}
	}
}
