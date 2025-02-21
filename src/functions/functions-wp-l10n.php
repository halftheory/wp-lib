<?php
if ( ! function_exists('get_current_locale') ) {
	function get_current_locale( $default = 'en_US', $field = 'locale' ) {
		if ( function_exists('pll_current_language') && pll_current_language() ) {
			// Polylang.
			$result = pll_current_language($field);
		} elseif ( function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE') && ! empty(ICL_LANGUAGE_CODE) && ICL_LANGUAGE_CODE !== 'all' ) {
			// WPML.
			$result = apply_filters('wpml_current_language', null);
		} else {
			// WP.
			$result = get_locale();
		}
		$result = empty($result) ? $default : str_replace('-', '_', $result);
		if ( $field === 'slug' ) {
			$result = substr($result, 0, 2);
		}
		return $result;
	}
}

if ( ! function_exists('get_language_name') ) {
	function get_language_name( $locale = null ) {
		if ( is_null($locale) ) {
			$locale = get_current_locale();
		}
		if ( $locale === 'en_US' ) {
			return __('English (US)');
		}
		$result = $locale;
		if ( ! function_exists('wp_get_available_translations') ) {
			require_once path_join(ABSPATH, 'wp-admin/includes/translation-install.php');
		}
		if ( function_exists('wp_get_available_translations') ) {
			$translations = wp_get_available_translations();
			if ( isset($translations[ $locale ], $translations[ $locale ]['native_name']) ) {
				$result = $translations[ $locale ]['native_name'];
			}
		}
		return $result;
	}
}
