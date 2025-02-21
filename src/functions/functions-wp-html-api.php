<?php
if ( ! function_exists('wp_html_tag_add_class') ) {
	function wp_html_tag_add_class( $string, $tag_name, $class ) {
		if ( ! str_contains($string, '<' . $tag_name) ) {
			return $string;
		}
		if ( ! class_exists('WP_HTML_Tag_Processor') ) {
			return $string;
		}
		$processor = new WP_HTML_Tag_Processor($string);
		if ( $processor->next_tag($tag_name) ) {
			$processor->add_class($class);
			$string = $processor->get_updated_html();
		}
		unset($processor);
		return $string;
	}
}

if ( ! function_exists('wp_html_tag_remove_attribute') ) {
	function wp_html_tag_remove_attribute( $string, $tag_name, $name ) {
		if ( ! str_contains($string, '<' . $tag_name) ) {
			return $string;
		}
		if ( ! class_exists('WP_HTML_Tag_Processor') ) {
			return $string;
		}
		$processor = new WP_HTML_Tag_Processor($string);
		if ( $processor->next_tag($tag_name) ) {
			if ( $processor->remove_attribute($name) ) {
				$string = $processor->get_updated_html();
			}
		}
		unset($processor);
		return $string;
	}
}

if ( ! function_exists('wp_html_tag_remove_class') ) {
	function wp_html_tag_remove_class( $string, $tag_name, $class ) {
		if ( ! str_contains($string, '<' . $tag_name) ) {
			return $string;
		}
		if ( ! str_contains($string, $class) ) {
			return $string;
		}
		if ( ! class_exists('WP_HTML_Tag_Processor') ) {
			return $string;
		}
		$processor = new WP_HTML_Tag_Processor($string);
		if ( $processor->next_tag($tag_name) ) {
			$processor->remove_class($class);
			$string = $processor->get_updated_html();
		}
		unset($processor);
		return $string;
	}
}

if ( ! function_exists('wp_html_tag_set_attributes') ) {
	function wp_html_tag_set_attributes( $string, $tag_name, $pairs ) {
		if ( ! str_contains($string, '<' . $tag_name) ) {
			return $string;
		}
		if ( ! class_exists('WP_HTML_Tag_Processor') ) {
			return $string;
		}
		$processor = new WP_HTML_Tag_Processor($string);
		if ( $processor->next_tag($tag_name) ) {
			$update = false;
			foreach ( $pairs as $name => $value ) {
				if ( $processor->set_attribute($name, $value) ) {
					$update = true;
				}
			}
			if ( $update ) {
				$string = $processor->get_updated_html();
			}
		}
		unset($processor);
		return $string;
	}
}
