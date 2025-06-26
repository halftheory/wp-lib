<?php
if ( ! function_exists('get_tag_attributes') ) {
	function get_tag_attributes() {
		return array(
			'action',
			'alt',
			'border',
			'class',
			'cols',
			'content',
			'data-*',
			'datetime',
			'height',
			'href',
			'id',
			'itemid',
			'itemprop',
			'itemscope',
			'itemtype',
			'lang',
			'maxlength',
			'method',
			'name',
			'rel',
			'role',
			'rows',
			'src',
			'style',
			'target',
			'title',
			'type',
			'value',
			'width',
		);
	}
}

if ( ! function_exists('get_text_tags') ) {
	function get_text_tags() {
		return array(
			'h1',
			'h2',
			'h3',
			'h4',
			'h5',
			'h6',
			'p',
			'br',
			'b',
			'blockquote',
			'cite',
			'del',
			'ins',
			'em',
			'i',
			'mark',
			'q',
			's',
			'small',
			'strong',
			'time',
			'u',
			'span',
		);
	}
}

if ( ! function_exists('get_void_tags') ) {
	function get_void_tags() {
		return array(
			'area',
			'base',
			'basefont',
			'br',
			'col',
			'command',
			'embed',
			'frame',
			'hr',
			'img',
			'input',
			'keygen',
			'link',
			'menuitem',
			'meta',
			'param',
			'source',
			'track',
			'wbr',
		);
	}
}

if ( ! function_exists('maybe_specialchars_decode') ) {
	function maybe_specialchars_decode( $string ) {
		if ( empty(trim($string)) ) {
			return $string;
		}
		$decode = null;
		$flags = null;
		if ( str_contains($string, '&#039;') ) {
			$decode = true;
			$flags = ENT_QUOTES;
		} elseif ( str_contains($string, '&lt;') ) {
			if ( substr_count($string, '&lt;') > substr_count($string, '<') || preg_match('/&lt;\/[\s]*[\w]+[\s]*&gt;/is', $string) ) {
				$decode = true;
				$flags = ENT_NOQUOTES;
			}
		}
		if ( $decode ) {
			$string = html_entity_decode($string, $flags, get_encoding());
			if ( function_exists('wp_specialchars_decode') ) {
				$string = wp_specialchars_decode($string, $flags);
			}
		}
		return $string;
	}
}

if ( ! function_exists('remove_excess_space') ) {
	function remove_excess_space( $string ) {
		if ( empty($string) ) {
			return $string;
		}
		$string = maybe_specialchars_decode($string);
		$string = replace_spaces($string);
		if ( str_contains($string, '</') ) {
			// no space after opening tag or before closing tag.
			$array = array(
				'h1',
				'h2',
				'h3',
				'h4',
				'h5',
				'h6',
				'p',
				'blockquote',
				'cite',
				'li',
			);
			foreach ( $array as $value ) {
				$string = preg_replace('/(<' . $value . '[ ]*>|<' . $value . ' [^>]+>)[\s]*/s', '$1', $string);
				$string = preg_replace('/[\s]*(<\/[ ]*' . $value . '>)/s', '$1', $string);
			}
		}
		if ( str_contains($string, '<br') ) {
			// no br at start/end.
			$string = preg_replace('/^[\s]*<br[\/ ]*>[\s]*/is', '', $string);
			$string = preg_replace('/[\s]*<br[\/ ]*>[\s]*$/is', '', $string);
			// limit to max 2 brs.
			$string = preg_replace('/(<br[\/ ]*>[\s]*){3,}/is', '$1$1', $string);
			// no br directly next to p tags.
			$string = preg_replace('/(<[\/ ]*p[\s]*>|<p [^>]+>)[\s]*<br[\/ ]*>[\s]*/is', '$1', $string);
			$string = preg_replace('/[\s]*<br[\/ ]*>[\s]*(<[\/ ]*p[\s]*>|<p [^>]+>)/is', '$1', $string);
		}
		// no tabs next to newlines.
		$string = preg_replace("/[\t ]*([\n\r]+)[\t ]*/s", '$1', $string);
		// limit repeating newlines and spaces.
		$string = preg_replace("/(\n|\r){3,}/s", "\n\n", $string);
		$string = preg_replace('/[ ]{2,}/s', ' ', $string);
		return trim($string);
	}
}

if ( ! function_exists('replace_spaces') ) {
	function replace_spaces( $string ) {
		// Replace weird spaces.
		return is_string($string) ? str_replace(array( '&nbsp;', '&#160;', "\xc2\xa0", ' ' ), ' ', $string) : $string;
	}
}

if ( ! function_exists('replace_tags') ) {
	function replace_tags( $string, $replace_pairs ) {
		if ( empty(trim($string)) || empty($replace_pairs) ) {
			return $string;
		}
		$string = maybe_specialchars_decode($string);
		if ( ! str_contains($string, '<') ) {
			return $string;
		}
		foreach ( $replace_pairs as $old => $new ) {
			$replacement = empty($new) ? '' : '$1' . $new . '$2';
			$string = preg_replace('/(<[\/]?)\s*' . $old . '\s*([\/]?>)/is', $replacement, $string);
			$string = preg_replace('/(<[\/]?)' . $old . '( [^>]*>)/is', $replacement, $string);
			$string = preg_replace('/(<[\/]?)' . $old . '( [^\/]*\/>)/is', $replacement, $string);
		}
		return $string;
	}
}

if ( ! function_exists('str_replace_start') ) {
	function str_replace_start( $pattern, $replacement, $subject ) {
		return preg_replace('/^' . preg_quote($pattern, '/') . '/s', $replacement, $subject, 1);
	}
}

if ( ! function_exists('strip_tag') ) {
	function strip_tag( $string, $tag, $content = true ) {
		if ( empty(trim($string)) || empty($tag) ) {
			return $string;
		}
		$string = maybe_specialchars_decode($string);
		if ( ! str_contains($string, '<' . $tag) ) {
			return $string;
		}
		$replacement = $content ? '$1' : '';
		// Has closing tag.
		$string = preg_replace('/[\s]*<' . $tag . ' [^>]*>(.*?)<\/[\s]*' . $tag . '>[\s]*/is', $replacement, $string);
		$string = preg_replace('/[\s]*<' . $tag . '>(.*?)<\/[\s]*' . $tag . '>[\s]*/is', $replacement, $string);
		// No closing tag.
		$string = preg_replace('/[\s]*<' . $tag . ' [^>]+>/is', '', $string);
		$string = preg_replace('/[\s]*<' . $tag . '[\/]?>/is', '', $string);
		return $string;
	}
}

if ( ! function_exists('strip_tags_keep_comments') ) {
	function strip_tags_keep_comments( $string, $allowed_tags = null ) {
		if ( empty(trim($string)) ) {
			return $string;
		}
		$replace_pairs = array(
			'<!--' => '###COMMENT_OPEN###',
			'-->' => '###COMMENT_CLOSE###',
		);
		$string = strtr($string, $replace_pairs);
		$string = strip_tags($string, $allowed_tags);
		$string = str_replace($replace_pairs, array_keys($replace_pairs), $string);
		return $string;
	}
}

if ( ! function_exists('tag_add_class') ) {
	function tag_add_class( $string, $class ) {
		if ( empty(trim($string)) || empty($class) ) {
			return $string;
		}
		$string = maybe_specialchars_decode($string);
		if ( ! str_contains($string, '<') ) {
			return $string;
		}
		// Only modify the first valid tag.
		if ( preg_match_all('/(<[a-z]+\s*>|<[a-z]+ [^>]+>|<[a-z]+\s*\/>|<[a-z]+ [^\/]+\/>)/is', $string, $matches) ) {
			$tag_old = null;
			foreach ( $matches[1] as $value ) {
				if ( str_ends_with($value, '/>') ) {
					$void_tag = preg_replace('/^[^<]*<([a-z]+)[^\/]*\/>.*$/is', '$1', $value, 1);
					if ( ! in_array($void_tag, get_void_tags()) ) {
						continue;
					}
				}
				$tag_old = $value;
				break;
			}
			if ( $tag_old ) {
				$tag_new = null;
				if ( ! str_contains($tag_old, ' class=') ) {
					$class_html = ' class="' . htmlspecialchars($class, ENT_QUOTES, get_encoding(), false) . '"';
					$tag_new = preg_replace('/\s*([\/]?>)$/is', $class_html . '$1', $tag_old, 1);
				} elseif ( preg_match_all('/( class=["\'])([^"\']*)(["\'])/is', $tag_old, $class_matches) ) {
					$classes = explode(' ', trim(current($class_matches[2])));
					$classes[] = $class;
					$classes = array_filter(array_unique($classes));
					$tag_new = str_replace(current($class_matches[0]), current($class_matches[1]) . implode(' ', $classes) . current($class_matches[3]), $tag_old);
				}
				if ( $tag_new ) {
					$string = preg_replace('/' . preg_quote($tag_old, '/') . '/is', $tag_new, $string, 1);
				}
			}
		}
		return $string;
	}
}

if ( ! function_exists('tag_remove_class') ) {
	function tag_remove_class( $string, $class ) {
		if ( empty(trim($string)) || empty($class) ) {
			return $string;
		}
		if ( ! str_contains($string, $class) ) {
			return $string;
		}
		$string = maybe_specialchars_decode($string);
		if ( ! str_contains($string, '<') ) {
			return $string;
		}
		if ( ! str_contains($string, ' class=') ) {
			return $string;
		}
		// Only modify the first valid tag.
		if ( preg_match_all('/(<[a-z]+\s*>|<[a-z]+ [^>]+>|<[a-z]+\s*\/>|<[a-z]+ [^\/]+\/>)/is', $string, $matches) ) {
			$tag_old = null;
			foreach ( $matches[1] as $value ) {
				if ( str_ends_with($value, '/>') ) {
					$void_tag = preg_replace('/^[^<]*<([a-z]+)[^\/]*\/>.*$/is', '$1', $value, 1);
					if ( ! in_array($void_tag, get_void_tags()) ) {
						continue;
					}
				}
				if ( str_contains($value, ' class=') ) {
					$tag_old = $value;
					break;
				}
			}
			if ( $tag_old ) {
				$tag_new = null;
				if ( preg_match_all('/( class=["\'])([^"\']*)(["\'])/is', $tag_old, $class_matches) ) {
					$classes = explode(' ', trim(current($class_matches[2])));
					$classes = array_value_unset($classes, $class);
					$classes = array_filter(array_unique($classes));
					$tag_new = str_replace(current($class_matches[0]), current($class_matches[1]) . implode(' ', $classes) . current($class_matches[3]), $tag_old);
				}
				if ( $tag_new ) {
					$string = preg_replace('/' . preg_quote($tag_old, '/') . '/is', $tag_new, $string, 1);
				}
			}
		}
		return $string;
	}
}

if ( ! function_exists('trim_content') ) {
	function trim_content( $string ) {
		if ( empty($string) ) {
			return $string;
		}
		$string = maybe_specialchars_decode($string);
		$string = replace_spaces($string);
		if ( str_contains($string, '<br') ) {
			$string = preg_replace('/^[\s]*<br[\/ ]*>[\s]*/is', '', $string);
			$string = preg_replace('/[\s]*<br[\/ ]*>[\s]*$/is', '', $string);
		}
		// Remove empty tags at the start/end.
		$tags = array_merge(array( 'a' ), array_value_unset(get_text_tags(), 'br'));
		foreach ( $tags as $tag ) {
			if ( str_contains($string, '<' . $tag) || str_contains($string, $tag . '>') ) {
				$string = preg_replace('/^[\s]*(<' . $tag . '[\s]*>|<' . $tag . ' [^>]+>)[\s]*<\/[\s]*' . $tag . '>[\s]*/is', '', $string);
				$string = preg_replace('/[\s]*(<' . $tag . '[\s]*>|<' . $tag . ' [^>]+>)[\s]*<\/[\s]*' . $tag . '>[\s]*$/is', '', $string);
			}
		}
		return trim($string);
	}
}

if ( ! function_exists('trim_quotes') ) {
	function trim_quotes( $value ) {
		if ( is_array($value) ) {
			return array_map(__FUNCTION__, $value);
		}
		return is_string($value) ? trim($value, " \n\r\t\v\0'" . '"') : $value;
	}
}
