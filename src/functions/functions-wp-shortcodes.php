<?php
if ( ! function_exists('ht_strip_shortcodes') ) {
	function ht_strip_shortcodes( $content, $tagnames = array(), $strict = false ) {
		if ( ! str_contains($content, '[') ) {
			return $content;
		}
		if ( empty($tagnames) ) {
			// Remove all.
			if ( str_contains($content, '<script') || preg_match('/<[a-z]+ [^>\[\]]+\[[^>]+>/is', $content) ) {
				// inline scripts, [] inside a html tag.
				$content = strip_shortcodes($content);
			} else {
				// more than 4 letters.
				$content = preg_replace('/\[[^\]]{5,}\]/s', '', $content);
			}
		} else {
			// Remove selected.
			$tagnames = make_array($tagnames);
			if ( ! $strict ) {
				global $shortcode_tags;
				if ( is_array($shortcode_tags) ) {
					$shortcode_tags_keys = array_keys($shortcode_tags);
					foreach ( $tagnames as $value ) {
						foreach ( $shortcode_tags_keys as $v ) {
							if ( stripos($v, $value) !== false ) {
								$tagnames[] = $v;
							}
						}
					}
				}
			}
			$tagnames = array_unique($tagnames);
			$content = preg_replace('/' . get_shortcode_regex($tagnames) . '/', '', $content);
		}
		return $content;
	}
}
