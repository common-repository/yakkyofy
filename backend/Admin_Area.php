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
class Admin_Area extends Engine\Base {

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function initialize() {
		if ( !parent::initialize() ) {
			return;
		}

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . YAKKYOFY_TEXTDOMAIN . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
		$post_columns = new \CPT_columns( 'shop_order' );
		$post_columns->add_column(
			'yakkyofy_ship',
			array(
			'label'    => __( 'Yakkyofy Ship Status', 'yakkyofy' ),
			'type'     => 'custom_value',
			'sortable' => true,
			'callback' => array( $this, 'order_shipment_status' ),
			'def'      => '',
			'order'    => '-1',
			)
		);

		add_action('admin_bar_menu', array( $this, 'add_toolbar_items' ), 100);
	}

	public function add_toolbar_items($admin_bar){
		$admin_bar->add_menu( array(
			'id'    => 'yakkyofy',
			'title' => 'Yakkyofy Dashboard',
			'href'  => YAKKYOFY_DASHBOARD,
			'meta'  => array(
				'title' => __('Open the Yakkyofy Dashboard'),
			),
		));
	}

	/**
     * Show the Yakkyofy shipment status
     *
     * @since 1.0.0
     * @param int $order_id Order id.
     * @return string
     */
	public function order_shipment_status( $order_id ) {
		$order  = \wc_get_order( $order_id );
		$items  = $order->get_items();
		$yakkyo = $yakkyo_shipped = 0;

		$new_meta_key = "Tracking Number";
		$old_meta_key = "Yakkyofy Tracking Number";

		foreach ( $items as $item ) {
			$product = $item->get_data();
			if ( is_yakkyofy_product( $product['product_id'] ) ) {
				$yakkyo++;

				$existing_old_meta = \wc_get_order_item_meta($item->get_id(), $old_meta_key);
				$existing_new_meta = \wc_get_order_item_meta($item->get_id(), $new_meta_key);

				if ( !empty($existing_old_meta) || !empty($existing_new_meta) ) {
					$yakkyo_shipped++;
				}
			}
		}

		if ( $yakkyo !== 0 ) {
			return $yakkyo_shipped . '/' . $yakkyo . '  ' . __( 'products', 'yakkyofy' );
		}

		return '';
	}

    /**
     * Add settings action link to the plugins page.
     *
     * @since 1.0.0
     * @param array $links Array of links.
     * @return array
     */
    public function add_action_links( array $links ) {
        return array_merge(
            array(
                'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . YAKKYOFY_TEXTDOMAIN ) . '">' . __( 'Settings', 'yakkyofy' ) . '</a>',
            ),
            $links
        );
    }

}
