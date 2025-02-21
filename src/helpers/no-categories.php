<?php
namespace Halftheory\Lib\helpers;

if ( is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Remove_Taxonomy.php') ) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Remove_Taxonomy.php';
}
use Halftheory\Lib\helpers\Remove_Taxonomy;

#[AllowDynamicProperties]
class No_Categories extends Remove_Taxonomy {

	public static $handle;
	protected static $instance;
	protected $data = array();

	protected static $filters = array();

	public function __construct( $autoload = true, $taxonomy = 'category' ) {
		parent::__construct($autoload, $taxonomy);
	}
}
