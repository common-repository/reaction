<?php


namespace Palasthotel\WordPress\Reaction\Components;

use Palasthotel\WordPress\Reaction\reaction;

/**
 * Class Component
 *
 * @package Palasthotel\WordPress
 * @version 0.1.2
 */
abstract class Component {

	private Reaction $plugin;

	/**
	 * _Component constructor.
	 *
	 * @param Reaction $plugin
	 */
	public function __construct( Reaction $plugin) {
		$this->plugin = $plugin;
		$this->onCreate();
	}

	public function getPlugin(): Reaction {
		return $this->plugin;
	}

	/**
	 * overwrite this method in component implementations
	 */
	public function onCreate(){
		// init your hooks and stuff
	}
}
