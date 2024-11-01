<?php

/**
 * Yakkyofy
 *
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package   Yakkyofy
 * @author    Codeat <daniele@codeat.it>
 * @copyright 2020
 * @license   GPL 2.0+
 * @link      http://codeat.co
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

require_once('functions/functions.php');

/**
 * Loop for uninstall
 *
 * @return void
 */
function y_uninstall_multisite()
{
	if (is_multisite()) {
		$blogs = get_sites();

		if (!empty($blogs)) {
			foreach ($blogs as $blog) {
				switch_to_blog($blog->blog_id);
				y_uninstall();
				restore_current_blog();
			}

			return;
		}
	}

	y_uninstall();
}

/**
 * What happen on uninstall?
 *
 * @return void
 */
function y_uninstall()
{
	yakkyofy_request(
		'woocommerce/uninstall',
		array(
			'shop' => get_option('yakkyofy-real-website'),
		),
		array(
			'x-refresh-token' => get_option('yakkyofy-refresh-token'),
		)
	);

	delete_option('yakkyofy-real-website');
	delete_option('yakkyofy-user-id');
	delete_option('yakkyofy-error');
	delete_option('yakkyofy-access-token');
	delete_option('yakkyofy-refresh-token');
}

y_uninstall_multisite();
