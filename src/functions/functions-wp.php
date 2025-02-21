<?php
if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'functions-php.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'functions-php.php';
}
$array = array(
	// wp-includes/[basename].php
	'functions-wp-capabilities.php',
	'functions-wp-embed.php',
	'functions-wp-formatting.php',
	'functions-wp-functions.php',
	'functions-wp-general-template.php',
	'functions-wp-html-api.php',
	'functions-wp-l10n.php',
	'functions-wp-link-template.php',
	'functions-wp-load.php',
	'functions-wp-media.php',
	'functions-wp-ms-blogs.php',
	'functions-wp-nav-menu.php',
	'functions-wp-pluggable.php',
	'functions-wp-plugin.php',
	'functions-wp-post-template.php',
	'functions-wp-post-thumbnail-template.php',
	'functions-wp-post.php',
	'functions-wp-query.php',
	'functions-wp-rest-api.php',
	'functions-wp-shortcodes.php',
	'functions-wp-taxonomy.php',
	'functions-wp-template.php',
	'functions-wp-theme.php',
);
foreach ( $array as $value ) {
	if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . $value) ) {
		include_once __DIR__ . DIRECTORY_SEPARATOR . $value;
	}
}
