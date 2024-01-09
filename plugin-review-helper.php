<?php
namespace WordPressdotorg\Plugin_Review_Helper;

/**
 * Plugin Name: Plugin Review Helper
 * Description: Helper plugin for reviewing plugins. Makes a plugin moderator's job a little easier. Intended for use within Playground.
 * Author: Alex Shiels
 * Author URI: https://wordpress.org/
 * Version: 0.1
 * Tested up to: 6.3
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

 if ( ! defined( 'WPINC' ) ) { die; }

 define('PRH_LOG_FILE', WP_CONTENT_DIR . '/plugin-review-helper.log');

// Disable the WooCommerce setup wizard.
add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );

// Crank up error reporting.
error_reporting( E_ALL );
// Report to both screen and log.
ini_set( 'display_errors', 1 );
ini_set( 'log_errors', 1 );
ini_set( 'html_errors', 1 );
// Put the log file somewhere accessible both by code and web.
ini_set( 'error_log', PRH_LOG_FILE );

add_action( 'admin_menu', __NAMESPACE__ . '\prh_admin_menu' );

function prh_admin_menu() {
	add_menu_page( 'Plugin Review Helper', 'Plugin Review Helper', 'manage_options', 'plugin-review-helper', __NAMESPACE__ . '\prh_admin_page' );
}

add_action( 'admin_init', __NAMESPACE__ . '\prh_admin_init' );

function prh_admin_init() {
	if ( 'wasm' !== strtolower( php_sapi_name() ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\prh_admin_notice' );
	}
}

function prh_admin_notice() {
	?>
	<div class="notice notice-error">
		<p>Plugin Review Helper is intended for use within a WASM environment, not <code><?php echo esc_html( php_sapi_name() ); ?></code>.</p>
	</div>
	<?php
}

function prh_admin_page() {
	?>
	<div class="wrap">
		<h1>Plugin Review Helper</h1>
		<p>Plugin Review Helper is a plugin for reviewing plugins. It makes a plugin moderator's job a little easier. It is intended for use within Playground.</p>
		<p>Plugin Review Helper is a work in progress. It is not yet ready for use.</p>
		<p>Error log: <a href="<?php echo esc_url( WP_CONTENT_URL . '/plugin-review-helper.log' ); ?>"><?php echo esc_url( PRH_LOG_FILE ); ?></a></p>

		<p>phpinfo(): <a href="<?php echo esc_url( admin_url( 'admin.php?page=plugin-review-helper&phpinfo=1' ) ); ?>">View</a></p>
	</div>
	<?php

	if ( isset( $_GET['phpinfo'] ) ) {
		phpinfo();
	}
}
