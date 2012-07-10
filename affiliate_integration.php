<?php
/**
 * Plugin Name: Affiliate Platform Integration
 * Description: Integration between WP Affiliate Platform and Jigoshop plugins.
 * Version: 1.0
 * Author: Roberto Hiribarne Guedes hiribarne@gmail.com
 * License: GPL2
 **/

global $vf_affiliate_integration_db_version;
$vf_affiliate_integration_db_version = "1.0";

register_activation_hook(__FILE__, 'vf_affiliate_integration_install');

function vf_affiliate_integration_install() {
	global $wpdb;
	global $vf_affiliate_integration_version;

	$table_name = $wpdb->prefix . "order_affiliate";

	$sql = "CREATE TABLE $table_name (id mediumint(9) NOT NULL AUTO_INCREMENT, order_id mediumint(9) NOT NULL, affiliate_id varchar(128) NOT NULL, UNIQUE KEY id (id));";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option("vf_affiliate_integration_db_version", $vf_affiliate_integration_version);
	
	error_log($sql);	
}

/**
 * Order Status changed from pending to on-hold - TRACK THE ORDER AND AFFILIATE
 **/
add_action('order_status_pending_to_on-hold', 'vf_affiliate_integration_track_order_affiliate');

function vf_affiliate_integration_track_order_affiliate( $order_id ) {
	global $wpdb;

	if (isset($_COOKIE['ap_id'])) {
		$order = &new jigoshop_order( $order_id );

		$table_name = $wpdb->prefix . "order_affiliate";
		$result = $wpdb->insert($table_name, array('order_id' => $order->id, 'affiliate_id' => $_COOKIE['ap_id']));
		error_log("tracked succesfully");
	}
}

/**
 * Order Status completed - AWARD THE AFFILIATE COMMISSION
 **/
add_action('order_status_completed', 'vf_affiliate_integration_jigoshop_award_commission2');

function vf_affiliate_integration_jigoshop_award_commission2( $order_id ) {
	global $wpdb;

	$order = &new jigoshop_order( $order_id );
	$referer = null;

	$order_affiliate = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix . "order_affiliate WHERE order_id = " . $order_id);
	
	if (isset($order_affiliate->affiliate_id) && !empty($order_affiliate->affiliate_id)) {
		$referer = $order_affiliate->affiliate_id;
	} else if (!empty($_SESSION['ap_id'])) {
		$referer = $_SESSION['ap_id'];
	} else if (isset($_COOKIE['ap_id'])) {
		$referer = $_COOKIE['ap_id'];
	}

    if (isset($order) && !empty($referer)) {
            $sale_amt = $order->order_total;
            $item_id = $order->id;
            $buyer_email = $order->billing_email;
            $ap_id = $referer;
			$txn_id = null;
			//do_action('wp_affiliate_process_cart_commission', array("referer" => $referer, "sale_amt" => $sale_amt, "txn_id" => $unique_transaction_id, "buyer_email" => $email));
			wp_aff_award_commission($ap_id, $sale_amt, $txn_id, $item_id, $buyer_email);	
    }
}
