<?php
namespace Halftheory\Lib\helpers;

if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Remove_Post_Type.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Remove_Post_Type.php';
}
use Halftheory\Lib\helpers\Remove_Post_Type;

#[AllowDynamicProperties]
class No_Posts extends Remove_Post_Type {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $post_type = 'post' ) {
		parent::__construct($autoload, $post_type);
	}
}
