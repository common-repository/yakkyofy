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
class Settings_Page_UI extends Engine\Base {

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function initialize() {
		if ( !parent::initialize() ) {
			return;
		}

		if ( !\current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		\add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab' ), 50 );
		\add_action( 'woocommerce_settings_tabs_yakkyofy', array( $this, 'render_tab_fields' ) );
	}

	public function render_tab_fields() {
		$step = 0;

		if ( !$this->is_account() ) {
			$step = 1;
		}

		if ( $this->is_account() && $this->is_store() ) {
			$step = 2;
		}

		if ( !\is_ssl() || !$this->is_real_domain() || !$this->is_rest_avalaible() ) {
			$step = 0;
		}

		?>
		<div class="yak-settings-wrap">
		<?php

		if ( $step === 1 ) {
            ?>
			<section class="yak-row yak-login">
			<img src="<?php echo \esc_html( \plugin_dir_url( __FILE__ ) ); ?>../assets/img/yak_login.svg">
				<div class="yak-content">
					<h2><?php \esc_html_e( 'Connect your Yakkyofy Account', 'yakkyofy' ); ?></h2>
					<p><?php \esc_html_e( 'Login into your yakkyofy account to connect your store.', 'yakkyofy' ); ?></p>
					<div class="yak-form">
						<label for="yakkyofy_user"><?php \esc_html_e( 'Username', 'yakkyofy' ); ?></label><input name="yakkyofy_user" type="text" value="">
						<label for="yakkyofy_password"><?php \esc_html_e( 'Password', 'yakkyofy' ); ?></label><input name="yakkyofy_password" id="yakkyofy_password" type="password" value="">
						<button class="yak-button" id="yak-submit" type="submit">Login</button>
					</div>
					<p class="yak-small">
						<?php \esc_html_e( 'Don\'t have an account? ', 'yakkyofy' ); ?>
						<a href="<?php echo YAKKYOFY_DASHBOARD; ?>" target="_blank"><?php \esc_html_e( 'Sign up now', 'yakkyofy' ); ?></a>
						<?php \esc_html_e( ' and come back here to login.', 'yakkyofy' ); ?>
					</p>
					<?php
						if ( \get_option( 'yakkyofy-user-id' ) === 'wrong' ) {
							?>
								<p class="yak-error-message">
									<b><?php \esc_html_e( 'Connection failed!', 'yakkyofy' ); ?></b>
									<?php \esc_html_e( get_option( 'yakkyofy-error' ), 'yakkyofy' ); ?>
								</p>
							<?php
						}
					?>
				</div>
			</section>
		<?php } elseif ( $step === 2 ) { ?>
			<section class="yak-row yak-connected">
				<img src="<?php echo \esc_html( \plugin_dir_url( __FILE__ ) ); ?>../assets/img/yak_success.svg">
				<div class="yak-content">
					<h2><?php \esc_html_e( 'You\'re all set!', 'yakkyofy' ); ?></h2>
					<p><?php \esc_html_e( 'Congratulation, your store is fully connected with Yakkyofy. You are now able to import products and sync orders.', 'yakkyofy' ); ?></p>
					<a class="yak-button" href="<?php echo YAKKYOFY_DASHBOARD; ?>" target="_blank"><?php \esc_html_e( 'Go to Dashboard', 'yakkyofy' ); ?></a><button class="yak-button yak-reset"><?php \esc_html_e( 'Reset Connection', 'yakkyofy' ); ?></button>
				</div>
				</section>
        <?php } else { ?>
			<section class="yak-row yak-error">
				<img src="<?php echo \esc_html( \plugin_dir_url( __FILE__ ) ); ?>../assets/img/yak_error.svg">
				<div class="yak-content">
					<h2><?php \esc_html_e( 'Something went wrong', 'yakkyofy' ); ?></h2>
					<p><?php \esc_html_e( 'An error occurred during the connection of your store. Please check your credentials and the report below.', 'yakkyofy' ); ?></p>
					<button class="yak-button" id="report-toggle"><?php \esc_html_e( 'Check Connection Report', 'yakkyofy' ); ?></button>
					<button class="yak-button yak-reset"><?php \esc_html_e( 'Reset Connection and Retry', 'yakkyofy' ); ?></button>
					<p class="yak-small"><?php echo \sprintf(\__( 'If the problem persist, please <a href="%s" target="_blank">login your yakkyofy account</a> and contact our customer support.', 'yakkyofy' ), YAKKYOFY_DASHBOARD); //phpcs:ignore ?></p>
				</div>
				<div class="yak-report" id="yak-report">
					<h2><?php \esc_html_e( 'WooCommerce Mandatory Requirements:', 'yakkyofy' ); ?></h2>
					<?php
					$this->print_status( \__( 'SSL (HTTPS)', 'yakkyofy' ), \__( 'Please install a valid SSL certificate to encrypt data exchanged with your website. <a href="https://docs.woocommerce.com/document/ssl-and-https/" target="_blank">Learn more</a>.', 'yakkyofy' ), 'is_ssl' );
					$this->print_status( \__( 'URL Reachable', 'yakkyofy' ), \__( 'Your website must be online, public, and without any redirect.', 'yakkyofy' ), array( $this, 'is_real_domain' ) );
					$this->print_status( \__( 'WordPress Rest API', 'yakkyofy' ), \__( 'These interfaces allow other systems to interact with your website and must be active. <a href="https://developer.wordpress.org/rest-api/" target="_blank">Learn more</a>.', 'yakkyofy' ), array( $this, 'is_rest_avalaible' ) );
					?>
					<h2><?php \esc_html_e( 'Check the Health of your Yakkyofy’s Connection:', 'yakkyofy' ); ?></h2>
					<?php
					$this->print_status( \__( 'Yakkyofy’s Account Connection', 'yakkyofy' ), \__( 'You need to login with an active Yakkyofy’s account, if you don’t have one register now.', 'yakkyofy' ), array( $this, 'is_account' ) );
					$this->print_status( \__( 'Store Connection', 'yakkyofy' ), \__( 'Your store can be connected only to ONE Yakkyofy’s account, if you see an error message contact the assistance.', 'yakkyofy' ), array( $this, 'is_store' ) );
					?>
				</div>
			</section>
			<?php
		}

		?>
		</div>
		<?php
    }

	/**
	 * Return if Yakkyofy Account is configured
     *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_account() {
		return (bool) \get_option( 'yakkyofy-refresh-token' );
	}

	/**
	 * Return if Yakkyofy Store is configured
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_store() {
		return (bool) \get_option( 'yakkyofy-status' );
	}

	/**
	 * Return the Rest status
     *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_rest_avalaible() {
		return !empty( \rest_get_server()->get_routes() ) && !empty( \get_option( 'permalink_structure' ) );
	}

	/**
	 * Return the real domain
     *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_real_domain() {
		if ( empty( \get_option( 'yakkyofy-real-website' ) ) ) {
			$request = \curl_init(); // phpcs:ignore
			\curl_setopt( $request, CURLOPT_URL, \get_option( 'siteurl' ) ); // phpcs:ignore
			\curl_setopt( $request, CURLOPT_RETURNTRANSFER, true ); // phpcs:ignore
			\curl_setopt( $request, CURLOPT_FOLLOWLOCATION, true ); // phpcs:ignore
			\curl_exec( $request ); // phpcs:ignore
			$redirected_url = \curl_getinfo( $request, CURLINFO_EFFECTIVE_URL ); // phpcs:ignore
			\curl_close( $request ); // phpcs:ignore

			\update_option( 'yakkyofy-real-website', \preg_replace( '(^https?://)', '', $redirected_url ), false );
		}

		return true;
	}

	/**
	 * Print the custom WooCommerce field
	 *
	 * @param string       $text Text to print.
	 * @param string       $desc Text to print in case of error.
	 * @param array|string $callback Callback to execute and get the status.
	 */
	public function print_status( string $text, string $desc, $callback ) {
		$status = '<div class="yak-report-row"><h4 class="yak-status">' . $text . '</h4><span class="dashicons dashicons-yes-alt yak-success"></span></div>';

		if ( !\call_user_func( $callback ) ) {
			$status = '<div class="yak-report-row"><h4 class="yak-status">' . $text . '</h4><span class="dashicons dashicons-no-alt yak-failed"></span><span class="yak-small">' . $desc . '</span></div>';
		}

		echo $status; // phpcs:ignore
	}

	/**
	 * Add new item in WooCommerce's settings tab list
     *
	 * @since 1.0.0
     * @param array $settings_tabs Array of items.
	 * @return array
	 */
	public static function add_tab( array $settings_tabs ) {
        $settings_tabs['yakkyofy'] = \__( 'Yakkyofy', 'yakkyofy' );

        return $settings_tabs;
	}

}
