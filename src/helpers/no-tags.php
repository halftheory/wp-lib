<?php
namespace Halftheory\Lib\helpers;

if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Remove_Taxonomy.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Remove_Taxonomy.php';
}
use Halftheory\Lib\helpers\classes\Remove_Taxonomy;

#[AllowDynamicProperties]
class No_Tags extends Remove_Taxonomy {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $taxonomy = 'post_tag' ) {
		parent::__construct($autoload, $taxonomy);
	}
}
