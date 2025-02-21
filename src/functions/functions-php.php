<?php
$array = array(
	'functions-php-array.php',
	'functions-php-classobj.php',
	'functions-php-filesystem.php',
	'functions-php-json.php',
	'functions-php-network.php',
	'functions-php-string.php',
	'functions-php-url.php',
	'functions-php-var.php',
);
foreach ( $array as $value ) {
	if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . $value) ) {
		include_once __DIR__ . DIRECTORY_SEPARATOR . $value;
	}
}

if ( ! function_exists('get_encoding') ) {
	function get_encoding() {
		return set_encoding();
	}
}

if ( ! function_exists('set_encoding') ) {
	function set_encoding( $value = null ) {
		static $_result = null;
		if ( is_null($_result) ) {
			$_result = 'UTF-8';
		}
		if ( $value ) {
			$_result = (string) $value;
		}
		return $_result;
	}
}
