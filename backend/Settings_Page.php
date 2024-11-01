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

namespace Yakkyofy\Backend;

use Yakkyofy\Engine;

/**
 * Create the settings page in the backend
 */
class Settings_Page extends Engine\Base
{

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function initialize()
	{
		if (!parent::initialize()) {
			return;
		}

		if (!\current_user_can('manage_woocommerce')) {
			return false;
		}

		\add_action('admin_init', array($this, 'generate_yakkyofy_token'));
		\add_action('admin_init', array($this, 'reset_data'));

		$this->notifications();
	}

	/**
	 * Notify users based on the Yakkyofy status
	 *
	 * @return bool
	 */
	public function notifications()
	{
		if (!\current_user_can('manage_woocommerce')) {
			return false;
		}

		if (!\get_option('yakkyofy-refresh-token') || !\get_option('yakkyofy-status')) {
			/* translators: link to settings page */
			\wpdesk_wp_notice(\sprintf(\__('Connect your store with Yakkyofy. Go to the <a href="%s">settings</a>!', YAKKYOFY_TEXTDOMAIN), \admin_url('admin.php?page=wc-settings&tab=' . YAKKYOFY_TEXTDOMAIN)), 'error');
		}

		if (isset($_GET['page'], $_GET['tab'], $_GET['connectionOutcome']) && $_GET['page'] === 'wc-settings' && $_GET['tab'] === 'yakkyofy' && $_GET['connectionOutcome'] === 'success') { //phpcs:ignore
			\insert_yakkyofy_webhooks();
			\update_option('yakkyofy-status', true);
		}
	}

	/**
	 * Generate the Yakkyofy token and shop pairing
	 *
	 * @return bool
	 */
	public function generate_yakkyofy_token()
	{
		if (!isset($_POST['yakkyofy_user'], $_POST['yakkyofy_password']) || empty($_POST['yakkyofy_user']) || empty($_POST['yakkyofy_password'])) { //phpcs:ignore
			return false;
		}

		$response = \yakkyofy_request(
			'api/login',
			array(
				'email'    => $_POST['yakkyofy_user'], // phpcs:ignore WordPress.Security
				'password' => wp_unslash($_POST['yakkyofy_password']), // phpcs:ignore WordPress.Security
			)
		);

		if ($response->error) {
			\update_option('yakkyofy-user-id', 'wrong');
			\update_option('yakkyofy-error', $response->data->get_error_message() ?? 'Something went wrong');

			return false;
		}

		if (!isset($response->data->success) || !$response->data->success) {
			\update_option('yakkyofy-user-id', 'wrong');
			\update_option('yakkyofy-error', $response->data->error ?? 'Failed to authenticate. Check your credentials');

			return false;
		}

		\update_option('yakkyofy-access-token', $response->data->token, false);
		\update_option('yakkyofy-refresh-token', $response->data->refreshToken, false); //phpcs:ignore WordPress.NamingConventions.ValidVariableName

		$decodejwt = \json_decode(\base64_decode(\str_replace('_', '/', \str_replace('-', '+', \explode('.', $response->data->token)[1])), true)); // phpcs:ignore WordPress.PHP
		\update_option('yakkyofy-user-id', $decodejwt->sub, false);

		$response = \yakkyofy_get_request(
			'woocommerce/oauth?shop=' . \get_option('yakkyofy-real-website') . '&user_id=' . \get_option('yakkyofy-user-id')
		);

		if ($response->error) {
			\update_option('yakkyofy-user-id', 'wrong');
			\update_option('yakkyofy-error', $response->data->get_error_message() ?? 'Something went wrong during store connection');

			return false;
		}

		if (!isset($response->data->success) || !$response->data->success || isset($response->data->error) && 'shop already exists' !== $response->data->error) {
			\update_option('yakkyofy-user-id', 'wrong');
			\update_option('yakkyofy-error', $response->data->error ?? 'Something went wrong while connecting the store');

			return false;
		}

		\update_option('yakkyofy-status', true);


		if (!isset($response->data->error) && !\function_exists('codecept_debug')) {
			\header('Location: ' . $response->data->url);

			return true;
		}

		return true;
	}

	/**
	 * Reset Yakkyofy options
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function reset_data()
	{
		if (!isset($_GET['yakkyofy_reset']) || empty($_GET['yakkyofy_reset'])) { //phpcs:ignore
			return false;
		}

		if (isset($_POST['yakkyofy_user'])) { //phpcs:ignore
			return false;
		}

		// reset store on yakkyofy side
		$headers  = array('x-access-token' => \get_option('yakkyofy-access-token'));
		$response = \yakkyofy_request(
			'api/shops',
			array(
				'shop' => \get_option('yakkyofy-real-website'),
			),
			$headers,
			'GET'
		);

		if (!$response->error && isset($response->data->data) && !empty($response->data->data) && isset($response->data->data[0])) {
			$shop = $response->data->data[0];
			\yakkyofy_request(
				'api/shop/status/' . $shop->_id,
				array(
					'active' => false,
				),
				$headers,
				'PUT'
			);
		}

		\delete_option('yakkyofy-refresh-token');
		\delete_option('yakkyofy-access-token');
		\delete_option('yakkyofy-status');
		\delete_option('yakkyofy-user-id');
		\delete_option('yakkyofy-error');
		\delete_option('yakkyofy-real-website');

		return true;
	}
}
