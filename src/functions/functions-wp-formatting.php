<?php
if ( ! function_exists('get_allowed_html_tags') ) {
	function get_allowed_html_tags( $tags = array(), $format = 'wp_kses' ) {
		$all_attributes = array_fill_keys(get_tag_attributes(), true);
		$results = array();
		foreach ( make_array($tags) as $key => $value ) {
			$tmp = null;
			// specific tag => attributes.
			if ( ! is_numeric($key) ) {
				if ( $value === '*' || is_true($value) ) {
					$tmp = array( $key => $all_attributes );
				} else {
					$tmp = array( $key => make_array($value) );
				}
				$results = array_merge($tmp, $results);
				continue;
			}
			// collections.
			switch ( $value ) {
				// add wp values - https://developer.wordpress.org/reference/functions/wp_kses_allowed_html/
				case 'post':
				case 'user_description':
				case 'pre_user_description':
				case 'strip':
				case 'entities':
				case 'data':
					$tmp = wp_kses_allowed_html($value);
					break;
				// https://www.w3schools.com/tags/ref_byfunc.asp
				case 'formatting':
					$array = array(
						'h1',
						'h2',
						'h3',
						'h4',
						'h5',
						'h6',
						'p',
						'br',
						'hr',
						'abbr',
						'address',
						'b',
						'bdi',
						'bdo',
						'blockquote',
						'cite',
						'code',
						'del',
						'dfn',
						'em',
						'i',
						'ins',
						'kbd',
						'mark',
						'meter',
						'pre',
						'progress',
						'q',
						'rp',
						'rt',
						'ruby',
						's',
						'samp',
						'small',
						'strong',
						'sub',
						'sup',
						'template',
						'time',
						'u',
						'var',
						'wbr',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				case 'form':
					$array = array(
						'form',
						'input',
						'label',
						'select',
						'textarea',
						'button',
						'fieldset',
						'legend',
						'datalist',
						'output',
						'option',
						'optgroup',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				case 'frame':
					$array = array(
						'frame',
						'frameset',
						'noframes',
						'iframe',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				case 'image':
					$array = array(
						'img',
						'map',
						'area',
						'canvas',
						'figcaption',
						'figure',
						'picture',
						'svg',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				case 'media':
					$array = array(
						'audio',
						'source',
						'track',
						'video',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				case 'link':
					$array = array(
						'a',
						'link',
						'nav',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				case 'list':
					$array = array(
						'menu',
						'ul',
						'ol',
						'li',
						'dir',
						'dl',
						'dt',
						'dd',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				case 'table':
					$array = array(
						'table',
						'caption',
						'th',
						'tr',
						'td',
						'thead',
						'tbody',
						'tfoot',
						'col',
						'colgroup',
					);
					$tmp = array_fill_keys($array, $all_attributes);
					break;
				// custom.
				case 'text':
					$tmp = array_fill_keys(get_text_tags(), $all_attributes);
					break;
				case 'void':
					$tmp = array_fill_keys(get_void_tags(), $all_attributes);
					break;
				default:
					break;
			}
			if ( $tmp ) {
				$results = array_merge($tmp, $results);
			}
		}
		switch ( $format ) {
			case 'array':
				$results = array_keys($results);
				sort($results);
				break;
			case 'csv':
				$results = array_keys($results);
				sort($results);
				$results = empty($results) ? '' : implode(',', $results);
				break;
			case 'string':
				$results = array_keys($results);
				sort($results);
				$results = empty($results) ? null : '<' . implode('><', $results) . '>';
				break;
			case 'wp_kses':
			default:
				ksort($results);
				break;
		}
		return $results;
	}
}

if ( ! function_exists('get_excerpt') ) {
	function get_excerpt( $string, $length = 250, $args = array() ) {
		if ( empty(trim($string)) ) {
			return $string;
		}
		$defaults = array(
			'allowed_tags' => array_merge(array( 'a' ), get_text_tags()),
			'append' => array(
				'short' => '.',
				'long' => '...',
				'always' => '',
			),
			'html' => true,
			'remove' => array(
				'breaks' => false,
				'email' => false,
				'url' => false,
			),
			'replace_tags' => array(
				'b' => 'strong',
				'blockquote' => 'em',
				'cite' => 'em',
				'code' => 'em',
				'h1' => 'strong',
				'h2' => 'strong',
				'h3' => 'strong',
				'h4' => 'strong',
				'h5' => 'strong',
				'h6' => 'strong',
				'i' => 'em',
				'q' => 'em',
			),
			'trim' => array(
				'email' => false,
				'url' => false,
				'values' => array(),
			),
		);
		$args = wp_parse_args_recursive($args, $defaults, array( 'allowed_tags', 'replace_tags' ));

		$regex_patterns = array(
			'email' => '([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})',
			'last_character' => '\w' . preg_quote(':;@&%=+$?_.-#/>)»', '/'),
			'space' => '[\s]*',
			'tag_open' => '<[\w]+[^>]*>',
			'url' => '((https?|ftp)://)([a-z0-9+!*(),;?&=\$_.-]+(:[a-z0-9+!*(),;?&=\$_.-]+)?@)?([a-z0-9-.]*)\.([a-z]{2,13})(:[0-9]{2,5})?(/([a-z0-9+\$_%-]\.?)+)*/?(\?[a-z+&\$_.-][a-z0-9;:@&%=+/\$_.-]*)?(#[a-z_.-][a-z0-9+$%_.-]*)?',
			'url_www' => 'www\.([a-z0-9-.]*)\.([a-z]{2,13})(:[0-9]{2,5})?(/([a-z0-9+\$_%-]\.?)+)*/?(\?[a-z+&\$_.-][a-z0-9;:@&%=+/\$_.-]*)?(#[a-z_.-][a-z0-9+$%_.-]*)?',
		);

		$string = maybe_specialchars_decode($string);

		// Remove what we don't need.
		// no tabs.
		$string = preg_replace('/[\t]+/s', ' ', $string);
		// no script/style tags.
		foreach ( array( 'script', 'style' ) as $tag ) {
			$string = strip_tag($string, $tag, false);
		}
		// no shortcodes.
		$string = ht_strip_shortcodes($string);
		// no emojis.
		$string = preg_replace('/&#(8[0-9]{3}|9[0-9]{3}|1[0-9]{4});/s', '', $string);
		// remove repeating symbols, emojis.
		$no_repeat = array( '&lt;', '&gt;', '&amp;', '&ndash;', '&bull;', '&sect;', '&hearts;', '&hellip;', '...', '++', '--', '~~', '##', '**', '==', '__', '_ ', '//' );
		foreach ( $no_repeat as $value ) {
			if ( str_contains($string, $value) ) {
				$string = preg_replace('/(' . preg_quote($value, '/') . $regex_patterns['space'] . '){2,}/s', '$1', $string);
			}
		}
		foreach ( $args['remove'] as $key => $value ) {
			switch ( $key ) {
				case 'breaks':
					if ( ! $value ) {
						break;
					}
					$string = preg_replace('/[\r\n ]+/s', ' ', $string);
					// insert a space next to newlines just in case.
					$block_tags = array(
						'blockquote',
						'br',
						'cite',
						'code',
						'div',
						'h1',
						'h2',
						'h3',
						'h4',
						'h5',
						'h6',
						'hr',
						'li',
						'ol',
						'p',
						'ul',
					);
					foreach ( $block_tags as $tag ) {
						$string = preg_replace('/(<[\s\/]*' . $tag . '[\s\/]*>|<' . $tag . ' [^>]+>)' . $regex_patterns['space'] . '/is', '$1 ', $string);
					}
					$string = strip_tag($string, 'br');
					break;

				case 'email':
					if ( ! $value ) {
						break;
					}
					$string = preg_replace('/' . preg_quote($regex_patterns['email'], '/') . $regex_patterns['space'] . '/is', '', $string);
					break;

				case 'url':
					if ( ! $value ) {
						break;
					}
					if ( $tmp = get_urls($string) ) {
						$string = str_replace($tmp, '', $string);
					}
					$string = preg_replace('/' . preg_quote($regex_patterns['url'], '/') . $regex_patterns['space'] . '/is', '', $string);
					$string = preg_replace('/' . preg_quote($regex_patterns['url_www'], '/') . $regex_patterns['space'] . '/is', '', $string);
					break;

				default:
					break;
			}
		}

		// Remove tags.
		if ( $args['html'] ) {
			// HTML.
			$args['replace_tags'] = make_array($args['replace_tags']);
			$string = replace_tags($string, $args['replace_tags']);
			$args['allowed_tags'] = make_array($args['allowed_tags']);
			$string = strip_tags($string, $args['allowed_tags']);
		} else {
			// Plaintext.
			$string = strip_tags($string);
		}

		// Trim the start.
		$regex_array = array();
		foreach ( $args['trim'] as $key => $value ) {
			switch ( $key ) {
				case 'email':
					if ( ! $value ) {
						break;
					}
					$regex_array[] = $regex_patterns['email'];
					if ( $args['html'] ) {
						$regex_array[] = $regex_patterns['tag_open'] . $regex_patterns['email'];
					}
					break;

				case 'url':
					if ( ! $value ) {
						break;
					}
					$regex_array[] = $regex_patterns['url'];
					$regex_array[] = $regex_patterns['url_www'];
					if ( $args['html'] ) {
						$regex_array[] = $regex_patterns['tag_open'] . $regex_patterns['url'];
						$regex_array[] = $regex_patterns['tag_open'] . $regex_patterns['url_www'];
					}
					break;

				case 'values':
					if ( ! is_array($value) ) {
						break;
					}
					$value = array_map('maybe_specialchars_decode', $value);
					$value = array_map('trim', $value);
					$value = array_merge($value, array_map('unwptexturize', $value));
					$value = array_unique($value);
					$value = array_filter($value);
					foreach ( $value as $v ) {
						$regex_array[] = $v;
						if ( $args['html'] ) {
							$regex_array[] = $regex_patterns['tag_open'] . $regex_patterns['space'] . preg_quote($v, '/');
							$regex_array[] = $regex_patterns['tag_open'] . $regex_patterns['tag_open'] . $regex_patterns['space'] . preg_quote($v, '/');
						}
					}
					break;

				default:
					break;
			}
		}
		if ( ! empty($regex_array) ) {
			$regex_run = true;
			while ( $regex_run ) {
				$regex_run = false;
				foreach ( $regex_array as $value ) {
					if ( strlen($string) < strlen($value) ) {
						break;
					}
					if ( str_starts_with($string, $value) ) {
						// Probably titles.
						$string = str_replace_start($value . $regex_patterns['space'], '', $string);
						$regex_run = true;
					} elseif ( preg_match('/^' . $regex_patterns['space'] . $value . '/i', $string, $matches) ) {
						// Probably URLs.
						$string = preg_replace('/^' . $regex_patterns['space'] . preg_quote($matches[0], '/') . '/i', '', $string, 1);
						$regex_run = true;
					}
				}
			}
		}

		// Get current length.
		if ( $args['html'] ) {
			$string = trim_content($string);
			$tmp = $string;
			foreach ( $args['allowed_tags'] as $tag ) {
				$tmp = strip_tag($tmp, $tag);
			}
			$tmp = remove_excess_space($tmp);
			$string_length = mb_strlen($tmp, '8bit');
		} else {
			$string = remove_excess_space($string);
			$string_length = mb_strlen($string, '8bit');
		}

		// Decisions!
		if ( $string_length === 0 ) {
			return $string;
		}

		$append_strings = array_fill_keys(array_keys($defaults['append']), '');
		foreach ( $args['append'] as $key => $value ) {
			if ( empty_zero_ok($value) ) {
				continue;
			}
			$append_strings[ $key ] = $args['html'] && $value === '...' ? __('&hellip;') : (string) $value;
		}

		$func = function ( $string, $append ) use ( $regex_patterns ) {
			if ( empty($append) ) {
				return $string;
			}
			// Remove unwanted final characters.
			$string = preg_replace('/[^' . $regex_patterns['last_character'] . ']+' . $regex_patterns['space'] . '$/is', '', $string);
			// Add a space if the last word is a URL - avoids conflicts with make_clickable.
			if ( $array = preg_split('/[\s,;]+/s', $string) ) {
				$tmp = end($array);
				if ( str_starts_with($tmp, 'http') ) {
					$string .= ' ';
				} elseif ( $tmp !== make_clickable($tmp) ) {
					$string .= ' ';
				}
			}
			return rtrim($string, '.') . $append;
		};

		if ( $string_length <= $length ) {
			// Length is ok.
			$string = $func($string, $append_strings['short'] . $append_strings['always']);
		} else {
			// Reduce length.
			$length_new = mb_strrpos(mb_substr($string, 0, $length, get_encoding()), ' ');
			$string = mb_substr($string, 0, $length_new, get_encoding());
			// Check if we cut in the middle of a tag.
			if ( $args['html'] ) {
				$string = preg_replace('/<[\s\/]*(' . implode('|', $args['allowed_tags']) . ')[^>]*$/is', '', $string);
			}
			$string = $func($string, $append_strings['long'] . $append_strings['always']);
		}

		// Final cleanup.
		if ( $args['html'] ) {
			// Add line breaks?
			if ( array_key_exists('breaks', $args['remove']) && ! $args['remove']['breaks'] && ! str_contains($string, '<br') ) {
				$string = nl2br($string);
			}
			// Close open tags?
			if ( function_exists('force_balance_tags') ) {
				$string = force_balance_tags($string);
			} elseif ( class_exists('DOMDocument') ) {
				// Puts plaintext in a <p>.
				$dom = new DOMDocument();
				$dom->loadHTML(htmlentities($string, ENT_COMPAT, get_encoding()));
				$string = strip_tags(html_entity_decode($dom->saveHTML()), $allowed_tags);
			}
			$string = remove_excess_space($string);
		}

		return $string;
	}
}

if ( ! function_exists('ht_antispambot') ) {
	function ht_antispambot( $text ) {
		if ( ! str_contains($text, '@') ) {
			return $text;
		}
		$text = ' ' . $text . ' ';
		$email_regex = '([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})';
		$cb_not_inside_tags = function ( $matches ) {
			$email_name = $matches[1];
			$email_host = $matches[2];
			$email_name = substr(chunk_split( bin2hex( " $email_name" ), 2, ';&#x' ), 3, -3);
			$email_host = substr(chunk_split( bin2hex( " $email_host" ), 2, ';&#x' ), 3, -3);
			$at = '&#x40;<span style="display: none;">null</span>';
			$email = $email_name . $at . $email_host;
			return $email;
		};
		$text = preg_replace_callback("#(?!<.*?)$email_regex(?![^<>]*?>)#i", $cb_not_inside_tags, $text);
		$cb = function ( $matches ) {
			$email_name = $matches[1];
			$email_host = $matches[2];
			$email_name = substr(chunk_split( bin2hex( " $email_name" ), 2, ';&#x' ), 3, -3);
			$email_host = substr(chunk_split( bin2hex( " $email_host" ), 2, ';&#x' ), 3, -3);
			$at = '&#x40;';
			$email = $email_name . $at . $email_host;
			return $email;
		};
		$text = preg_replace_callback("#$email_regex#i", $cb, $text);
		return trim($text);
	}
}

if ( ! function_exists('ht_esc_textarea') ) {
	function ht_esc_textarea( $text ) {
		// https://developer.wordpress.org/reference/functions/esc_textarea/
		// if flags is only 'ENT_QUOTES' strings with special characters like ascii art will return empty.
		$safe_text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, get_bloginfo('charset'));
		return apply_filters('esc_textarea', $safe_text, $text);
	}
}

if ( ! function_exists('sanitize_email_name') ) {
	function sanitize_email_name( $name ) {
		return preg_replace('/[,?\'"&]/is', '', sanitize_text_field($name));
	}
}

if ( ! function_exists('unwptexturize') ) {
	function unwptexturize( $text ) {
		if ( empty($text) ) {
			return $text;
		}
		// Reverse wptexturize.
		$replace_pairs = array(
			'&#8220;' => '"',
			'&#8221;' => '"',
			'&#8243;' => '"',
			'&#8217;' => "'",
			'&#8216;' => "'",
			'&#8242;' => "'",
			'&#8211;' => '-',
			'&#8212;' => '-',
			'&#8230;' => '...',
		);
		return strtr($text, $replace_pairs);
	}
}
