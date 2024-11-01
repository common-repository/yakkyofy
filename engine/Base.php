<?php

/**
 * Plugin_name
 *
 * @package   Plugin_name
 * @author    Codeat <daniele@codeat.it>
 * @copyright 2020
 * @license   GPL 2.0+
 * @link      http://codeat.co
 */

namespace Yakkyofy\Engine;

/**
 * Base skeleton of the plugin
 */
class Base {

	/**
     * @var array The settings of the plugin
     */
	public $settings = array();

	/**
	 * Initialize the class
	 */
	public function initialize() {
		$this->settings = yakkyofy_get_settings();

		return true;
	}

}
