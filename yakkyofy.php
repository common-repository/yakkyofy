<?php

/**
 * @package   Yakkyofy
 * @author    Yakkyofy <development@yakkyofy.com>
 * @copyright 2020
 * @license   GPL 2.0+
 * @link      https://yakkyofy.com
 *
 * Plugin Name:     Yakkyofy
 * Plugin URI:      https://yakkyofy.com/features
 * Description:     Yakkyofy completely automates your woocommerce dropshipping store so you can focus on what matters most: marketing.
 * Version:         1.0.9
 * Author:          Yakkyofy
 * Author URI:      https://yakkyofy.com
 * Text Domain:     yakkyofy
 * License:         GPL 2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:     /languages
 * Requires PHP:    7.0
 * WC requires at least: 4.0
 * WC tested up to: 7.6
 * WordPress-Plugin-Boilerplate-Powered: v3.2.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	die('We\'re sorry, but you can not directly access this file.');
}

define('YAKKYOFY_VERSION', '1.0.9');
define('YAKKYOFY_TEXTDOMAIN', 'yakkyofy');
define('YAKKYOFY_NAME', 'Yakkyofy');
define('YAKKYOFY_PLUGIN_ROOT', plugin_dir_path(__FILE__));
define('YAKKYOFY_PLUGIN_ABSOLUTE', __FILE__);
define('YAKKYOFY_API', 'https://api.yakkyofy.com/');
define('YAKKYOFY_DASHBOARD', 'https://app.yakkyofy.com/');

if (version_compare(PHP_VERSION, '7.0.0', '<=')) {
	add_action(
		'admin_init',
		static function () {
			deactivate_plugins(plugin_basename(__FILE__));
		}
	);
	add_action(
		'admin_notices',
		static function () {
			echo wp_kses_post(
				sprintf(
					'<div class="notice notice-error"><p>%s</p></div>',
					__('"Yakkyofy" requires PHP 5.6 or newer.', YAKKYOFY_TEXTDOMAIN)
				)
			);
		}
	);

	// Return early to prevent loading the plugin.
	return;
}

$yakkyofyakkyofy_libraries = require_once YAKKYOFY_PLUGIN_ROOT . 'vendor/autoload.php';

require_once YAKKYOFY_PLUGIN_ROOT . 'functions/functions.php';

$requirements = new \Micropackage\Requirements\Requirements(
	'Yakkyofy',
	array(
		'php'            => '7.0',
		'php_extensions' => array('mbstring'),
		'wp'             => '5.2',
		'plugins'        => array(
			array('name' => 'woocommerce', 'file' => 'woocommerce/woocommerce.php'),
		),
	)
);

if (!$requirements->satisfied()) {
	$requirements->print_notice();

	return;
}

if (!wp_installing()) {
	add_action(
		'plugins_loaded',
		static function () use ($yakkyofyakkyofy_libraries) {
			new \Yakkyofy\Engine\Initialize($yakkyofyakkyofy_libraries);
		}
	);
}
