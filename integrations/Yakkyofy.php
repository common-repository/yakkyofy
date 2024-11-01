<?php

/**
 * Yakkyofy
 *
 * @package   Yakkyofy
 * @author    Codeat <daniele@codeat.it>
 * @copyright 2020
 * @license   GPL 2.0+
 * @link      http://codeat.co
 */

namespace Yakkyofy\Integrations;

use Yakkyofy\Engine;

/**
 * The various Cron of this plugin
 */
class Yakkyofy extends Engine\Base
{

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function initialize()
	{
		/*
		 * Load CronPlus
		 */
		$args = array(
			'recurrence'       => 'daily',
			'schedule'         => 'schedule',
			'name'             => 'yakkyofy_cron',
			'cb'               => array($this, 'reset_refresh_token_cron'),
			'plugin_root_file' => 'yakkyofy.php',
		);

		$cronplus = new \CronPlus($args);
		$cronplus->schedule_event();
		add_action('rest_api_init', array($this, 'order_fulfillment_rest'));
		add_action('rest_api_init', array($this, 'check_health_rest'));
	}

	/**
	 * Reset yakkyofy Tokens daily
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function reset_refresh_token_cron()
	{
		$response = yakkyofy_request(
			'api/refresh-token',
			array(),
			array(
				'x-refresh-token' => get_option('yakkyofy-refresh-token'),
			)
		);

		if ($response->error) {
			delete_option('yakkyofy-refresh-token');
			delete_option('yakkyofy-access-token');

			return false;
		}

		if (!isset($response->data->token)) {
			return false;
		}

		update_option('yakkyofy-access-token', $response->data->token);
		update_option('yakkyofy-refresh-token', $response->data->refreshToken); // phpcs:ignore WordPress.NamingConventions.ValidVariableName


		$response = yakkyofy_request(
			'install/verify',
			array('shop' => get_option('yakkyofy-real-website')),
			array('x-access-token' => get_option('yakkyofy-access-token'))
		);

		if ($response->error) {
			update_option('yakkyofy-status', false);

			return false;
		}

		if (isset($response->data->exist) && $response->data->exist) {
			insert_yakkyofy_webhooks();
			update_option('yakkyofy-status', true);

			return true;
		}

		return false;
	}

	/**
	 * Creates a route to fulfull orders
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function order_fulfillment_rest()
	{
		register_rest_route(
			'wc/v3',
			'/yakkyofy/fulfillment',
			array(
				'methods'  => \WP_REST_Server::EDITABLE,
				'callback' => array($this, 'order_fulfillment'),
				'permission_callback' => array($this, 'check_auth'),
				'args'     => array(
					'notifyCustomer' => array(
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'trackings'      => array(
						'default' => array(),
					),
				),
			)
		);
	}

	/**
	 * Creates a route to check connection health
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function check_health_rest()
	{
		register_rest_route(
			'wc/v3',
			'/yakkyofy/health',
			array(
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => array($this, 'check_health'),
				'permission_callback' => array($this, 'check_auth')
			)
		);
	}

	public function check_auth(object $data)
	{
		global $wpdb;

		$data = $data->get_params();

		$consumer_key		= wc_api_hash(sanitize_text_field($data['consumer_key']));
		$consumer_secret	= sanitize_text_field($data['consumer_secret']);

		$key = $wpdb->get_row($wpdb->prepare("
			SELECT consumer_key, consumer_secret
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = %s AND consumer_secret = %s
		", $consumer_key, $consumer_secret), ARRAY_A);

		if (!is_null($key)) {
			return true;
		}

		return false;
	}

	/**
	 * Fill the order with data
	 *
	 * @param object $data Values.
	 * @return array
	 */
	public function order_fulfillment(object $data)
	{
		$data   = $data->get_params();
		$orders = array();

		$new_meta_key = "Tracking Number";
		$old_meta_key = "Yakkyofy Tracking Number";

		foreach ($data['trackings'] as $item) {
			$existing_old_meta = \wc_get_order_item_meta($item['itemId'], $old_meta_key);
			$existing_new_meta = \wc_get_order_item_meta($item['itemId'], $new_meta_key);

			// if the old meta key exists, delete it and crate the new one with the new key and value
			if (isset($existing_old_meta) && !empty($existing_old_meta)) {
				$deleted = \wc_delete_order_item_meta($item['itemId'], $old_meta_key);
				if (!$deleted)
					return array('success' => false, 'message' => 'failed to delete old-key tracking number');

				$outcome = \wc_add_order_item_meta($item['itemId'], $new_meta_key, $item['trackingNumber'], true);
				if (0 === $outcome)
					return array('success' => false, 'message' => 'failed to create new-key tracking number');
			}
			// if the new meta key exists, update it with the new value. Since update sometimes crashes, first delete and then create
			else if (isset($existing_new_meta) && !empty($existing_new_meta)) {
				$deleted = \wc_delete_order_item_meta($item['itemId'], $new_meta_key);
				if (!$deleted)
					return array('success' => false, 'message' => 'failed to delete new-key tracking number');

				$outcome = \wc_add_order_item_meta($item['itemId'], $new_meta_key, $item['trackingNumber'], true);
				if (0 === $outcome)
					return array('success' => false, 'message' => 'failed to create new-key tracking number');
			}
			// if the no meta key exists, create it with the new key and value
			else {
				$outcome = \wc_add_order_item_meta($item['itemId'], $new_meta_key, $item['trackingNumber'], true);
				if (0 === $outcome)
					return array('success' => false, 'message' => 'failed to create tracking number');
			}

			$order = \wc_get_order_id_by_order_item_id($item['itemId']);

			// TODO: this is counting the items and it's useless... isn't it?
			if (!isset($orders[$order]))
				$orders[$order] = 0;

			$orders[$order] += 1;
		}

		foreach ($orders as $order_id => $count) {
			$order  = \wc_get_order($order_id);
			$items  = $order->get_items();
			$items_shipped = 0;
			$items_count = count($items);

			foreach ($items as $item) {
				$product = $item->get_data();
				if (!is_yakkyofy_product($product['product_id'])) continue;

				$tracking_old_meta = \wc_get_order_item_meta($item->get_id(), $old_meta_key);
				$tracking_new_meta = \wc_get_order_item_meta($item->get_id(), $new_meta_key);
				if (isset($tracking_old_meta) && !empty($tracking_old_meta) || isset($tracking_new_meta) && !empty($tracking_new_meta)) {
					$items_shipped += 1;
				}
			}

			if ($items_shipped !== $items_count) continue;

			$order->set_status('completed');
			$order->save();
		}

		return array('success' => true);
	}

	/**
	 * Get the current connection status
	 *
	 * @param object $data Values.
	 * @return array
	 */
	public function check_health(object $data)
	{
		global $wp_version;
		global $woocommerce;

		$rest_available = !empty(\rest_get_server()->get_routes()) && !empty(\get_option('permalink_structure'));
		$woo_version = class_exists('WooCommerce') ? $woocommerce->version : null;

		return array(
			'success' 		=> true,
			'rest'			=> $rest_available,
			'wp_version'	=> $wp_version,
			'woo_version'	=> $woo_version,
			'yk_version'	=> YAKKYOFY_VERSION,
			'php_version'	=> PHP_VERSION,
			'plugin_path'	=> YAKKYOFY_PLUGIN_ABSOLUTE,
			'permalink'		=> get_option('permalink_structure'),
			'siteurl'		=> get_option('siteurl'),
			'real_siteurl'	=> get_option('yakkyofy-real-website'),
			'refresh_token'	=> get_option('yakkyofy-refresh-token'),
			'access_token'	=> get_option('yakkyofy-access-token'),
			'user_id'		=> get_option('yakkyofy-user-id'),
		);
	}
}
