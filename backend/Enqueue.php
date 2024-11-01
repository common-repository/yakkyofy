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
 * This class contain the Enqueue stuff for the backend
 */
class Enqueue extends Engine\Base {

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function initialize() {
		if ( !parent::initialize() ) {
            return;
		}

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 1.0.0
     * @return mixed Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		if ( isset( $_GET['tab'] ) && 'yakkyofy' === esc_html( $_GET['tab'] ) ) {
   		wp_enqueue_style( YAKKYOFY_TEXTDOMAIN . '-settings-styles', plugins_url( 'assets/css/settings.css', YAKKYOFY_PLUGIN_ABSOLUTE ), array( 'dashicons' ), YAKKYOFY_VERSION );
	}
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 1.0.0
     * @return mixed Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		if ( isset( $_GET['tab'] ) && 'yakkyofy' === esc_html( $_GET['tab'] ) ) {
			wp_enqueue_script( YAKKYOFY_TEXTDOMAIN . '-settings-script', plugins_url( 'assets/js/settings.js', YAKKYOFY_PLUGIN_ABSOLUTE ), array( 'jquery' ), YAKKYOFY_VERSION, false );
		}
	}

}
