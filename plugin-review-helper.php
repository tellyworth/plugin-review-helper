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
#ini_set( 'error_log', PRH_LOG_FILE );
set_error_handler( __NAMESPACE__ . '\prh_error_handler', E_ALL );
register_shutdown_function( __NAMESPACE__ . '\prh_shutdown_handler' );

add_action( 'admin_menu', __NAMESPACE__ . '\prh_admin_menu' );

function prh_admin_menu() {
	add_menu_page( 'Plugin Review Helper', 'Plugin Review Helper', 'manage_options', 'plugin-review-helper', __NAMESPACE__ . '\prh_admin_page' );
}

function prh_admin_page() {
	?>
	<div class="wrap">
		<h1>Plugin Review Helper</h1>
		<p>Plugin Review Helper is a plugin for reviewing plugins. It makes a plugin moderator's job a little easier. It is intended for use within Playground.</p>
		<p>Plugin Review Helper is a work in progress. It is not yet ready for use.</p>
	</div>
	<?php

	global $menu;
	var_dump( $menu );

	$lines = array_reverse( file( PRH_LOG_FILE ) );

	echo '<table class="widefat striped">';
	echo '<tr><th>Time</th><th>Error</th><th>File</th><th>Line</th><th>What</th><th style="width:70%">Message</th></tr>';
	foreach ( $lines as $line ) {
		$data = json_decode( trim($line) );
		echo '<tr>';
		echo '<td>' . $data[0] . '</td>';
		echo '<td>' . prh_errno_to_string( $data[1] ) . '</td>';
		echo '<td>' . $data[3] . '</td>';
		echo '<td>' . $data[4] . '</td>';
		echo '<td>' . ( $data[5] ?? '' ) . '</td>';
		echo '<td style="overflow:scroll"><pre>' . esc_html($data[2]) . '</pre></td>';
		echo '</tr>';
	}
	echo '</table>';
}
