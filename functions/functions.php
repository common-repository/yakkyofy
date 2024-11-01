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

define('YAKKYOFY_VERSION', '1.0.9');
define('YAKKYOFY_TEXTDOMAIN', 'yakkyofy');
define('YAKKYOFY_NAME', 'Yakkyofy');
define('YAKKYOFY_PLUGIN_ROOT', plugin_dir_path(__FILE__));
define('YAKKYOFY_PLUGIN_ABSOLUTE', __FILE__);
define('YAKKYOFY_API', 'https://api.yakkyofy.com/');
define('YAKKYOFY_DASHBOARD', 'https://app.yakkyofy.com/');


/**
 * Get the settings of the plugin in a filterable way
 *
 * @since 1.0.0
 * @return array
 */
function yakkyofy_get_settings()
{
	return apply_filters('yakkyofy_get_settings', get_option(YAKKYOFY_TEXTDOMAIN . '-settings'));
}

/**
 * Return the POST request to Yakkyofy endpoints
 *
 * @since 1.0.0
 * @param string $endpoint Endpoint namespace.
 * @param array  $body     Post fields to send.
 * @param array  $headers  Headers of the request.
 * @param array  $method   HTTP method used for the request.
 * @return object
 */
function yakkyofy_request(string $endpoint, array $body, array $headers = array(), $method = 'POST')
{
	$headers['Content-Type'] = 'application/json';

	$response = wp_remote_post(
		YAKKYOFY_API . $endpoint . '/',
		array(
			'method'  => $method,
			'timeout' => 45,
			'body'    => json_encode($body),
			'headers' => $headers,
			'data_format' => 'body'
		)
	);


	if (is_wp_error($response))
		return (object)["error" => true, "data" => $response];


	$response = json_decode(wp_remote_retrieve_body($response));

	return (object)["error" => false, "data" => $response];
}

/**
 * Return the GET request to Yakkyofy endpoints
 *
 * @since 1.0.0
 * @param string $endpoint Endpoint namespace.
 * @param array  $headers  Headers of the request.
 * @return object
 */
function yakkyofy_get_request(string $endpoint, array $headers = array())
{
	$response = wp_remote_get(
		YAKKYOFY_API . $endpoint,
		array(
			'timeout' => 45,
			'headers' => $headers,
		)
	);

	if (is_wp_error($response)) {
		return (object)["error" => true, "data" => $response];
	}

	$response = json_decode(wp_remote_retrieve_body($response));

	return (object)["error" => false, "data" => $response];
}

/**
 * Check using the SKU if the product is by Yakkyofy
 *
 * @param string|int|\WC_Product $product Product.
 * @return bool
 */
function is_yakkyofy_product($product)
{
	$sku = '';

	if (is_int((int) $product)) {
		$product = wc_get_product($product);
	}

	if (is_a($product, 'WC_Product')) {
		$sku = $product->get_sku();
	}

	return 'YK' === substr($sku, 0, 2);
}

function insert_yakkyofy_webhooks()
{
	if (!current_user_can('manage_woocommerce')) {
		return;
	}

	$webhooks       = array(
		'order.created'   => 'woocommerce/ordercreated',
		'order.updated'   => 'woocommerce/orderupdated',
		'order.deleted'   => 'woocommerce/orderdeleted',
		'product.deleted' => 'woocommerce/productdeleted',
	);
	$webhooks_saved = new WC_Webhook_Data_Store;
	$webhooks_saved = $webhooks_saved->get_webhooks_ids('active');

	foreach ($webhooks as $type => $url) {
		$already_added = false;

		foreach ($webhooks_saved as $webhook_saved) {
			$webhook = new WC_Webhook($webhook_saved);

			if ($webhook->get_delivery_url() !== YAKKYOFY_API . $url) {
				continue;
			}

			$already_added = true;
		}

		if ($already_added) {
			continue;
		}

		$webhook = new WC_Webhook;
		$webhook->set_user_id(get_current_user_id());
		$webhook->set_topic($type);
		$webhook->set_name('Yakkyofy for ' . $type);
		$webhook->set_secret('secret');
		$webhook->set_delivery_url(YAKKYOFY_API . $url);
		$webhook->set_status('active');
		$webhook->save();
	}
}
