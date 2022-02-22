<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name:       Lemon Squeezy Connector
 * Plugin URI:        https://patrickposner.dev
 * Description:       A simple plugin to register users from an LS webhook.
 * Version:           1.0
 * Author:            Patrick Posner
 * Author URI:        https://patrickposner.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ls-connector
 * Domain Path:       /languages
 */

define( 'LSC_PATH', plugin_dir_path( __FILE__ ) );
define( 'LSC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

// localize.
$textdomain_dir = plugin_basename( dirname( __FILE__ ) ) . '/languages';
load_plugin_textdomain( 'ls-connector', false, $textdomain_dir );

// Bootmanager.
if ( ! function_exists( 'lsc_run_plugin' ) ) {
	add_action( 'plugins_loaded', 'lsc_run_plugin' );

	/**
	 * Run plugin
	 *
	 * @return void
	 */
	function lsc_run_plugin() {
		require_once LSC_PATH . 'src/class-lsc-user-meta.php';
		lsc\User_Meta::get_instance();

		require_once LSC_PATH . 'src/class-lsc-webhook.php';
		lsc\Webhook::get_instance();
	}
}
