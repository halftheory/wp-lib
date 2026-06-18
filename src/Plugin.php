<?php
namespace Halftheory\Lib;

#[AllowDynamicProperties]
abstract class Plugin extends Module {

	public static $handle;
	protected static $instance;
	protected $data = array();

	public function __construct( $autoload = false ) {
		parent::__construct($autoload);
	}

	protected function autoload() {
		// Filters.

		// Helpers.

		// Plugins.

		// Activate.

		// Deactivate.

		parent::autoload();
	}

	// Functions.
}
