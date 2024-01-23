<?php
namespace WordPressdotorg\Plugin_Review_Helper;

/**
 * Plugin Name: Playground Review Helper
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

function prh_admin_menu_capture() {
	global $prh_base_menu, $prh_base_submenu;

	// Capture a copy of the menu and submenu globals before plugins add to them.
	$prh_base_menu = $GLOBALS['menu'];
	$prh_base_submenu = $GLOBALS['submenu'];
}

function prh_admin_menu() {
	add_submenu_page( 'tools.php', 'Plugin Review Helper', 'Plugin Review Helper', 'manage_options', 'plugin-review-helper', __NAMESPACE__ . '\prh_admin_page' );
}

function prh_admin_bar_menu( $wp_admin_bar ) {
	$wp_admin_bar->add_node( array(
		'id'    => 'plugin-review-helper',
		'title' => 'Plugin Review Helper',
		'href'  => admin_url( 'admin.php?page=plugin-review-helper' ),
		'parent' => 'top-secondary',
	) );
}

// Capture an early copy of the menu and submenu globals.
add_action( 'admin_menu', __NAMESPACE__ . '\prh_admin_menu_capture', PHP_INT_MIN );

// Add submenu.
add_action( 'admin_menu', __NAMESPACE__ . '\prh_admin_menu' );

// Add item to admin bar.
add_action( 'admin_bar_menu', __NAMESPACE__ . '\prh_admin_bar_menu', 100 );

add_action( 'add_menu_classes', __NAMESPACE__ . '\prh_add_menu_classes', 100 );

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

	global $wp_scripts, $wp_styles;
	?>
	<pre><code>
	<?php foreach ( $wp_scripts->registered as $script ) {
		if ( $script->src && !str_starts_with( $script->src, '/wp-includes/' ) && !str_starts_with( $script->src, '/wp-admin/' ) ) {
			var_dump( $script );
		}
	} ?>
	<?php foreach ( $wp_styles->registered as $style ) {
		if ( $style->src && !str_starts_with( $style->src, '/wp-includes/' ) && !str_starts_with( $style->src, '/wp-admin/' ) ) {
			var_dump( $style );
		}
	} ?>

	</code></pre>
	<?php
}

function prh_array_diff_assoc_recursive( $array1, $array2 ) {
	$diff = [];
	// Check for keys in array1 that are not in array2.
	foreach ( $array1 as $key => $value ) {
		if ( ! array_key_exists( $key, $array2 ) ) {
			$diff[ $key ] = $value;
		} elseif ( is_array( $value ) ) {
			// Check for keys in array1 that are arrays, and recurse.
			$new_diff = prh_array_diff_assoc_recursive( $value, $array2[ $key ] );
			if ( ! empty( $new_diff ) ) {
				$diff[ $key ] = $new_diff;
			}
		} elseif ( $value !== $array2[ $key ] ) {
			// Check for keys in array1 that are not arrays, and are not equal to the corresponding value in array2.
			$diff[ $key ] = $value;
		}
	}

	return $diff;
}

function prh_add_menu_classes( $menu ) {
	global $prh_base_menu, $prh_base_submenu;

	#var_dump( __FUNCTION__, count( $prh_base_menu ), count( $prh_base_submenu ) );
	$slugs = [];

	foreach ( $menu as $key => $item ) {
		$slugs[ $item[2] ] = $key;
		if ( ! isset( $prh_base_menu[ $key ] ) ) {
			$menu[ $key ][4] .= ' menu-item-new';
		}
	}

	// There's no corresponding filter for submenus, so we'll modify them in-place here.
	global $prh_base_submenu, $submenu;

	foreach ( $submenu as $key => $items ) {
		foreach ( $items as $item_key => $item ) {
			if ( ! isset( $prh_base_submenu[ $key ][ $item_key ] ) ) {
				// Highlight the submenu item.
				#$submenu[ $key ][ $item_key ][0] .= ' <span class="menu-counter"><span class="count">!</span></span>';
				$submenu[ $key ][ $item_key ][0] = '<span style="border: 1px dotted orange;">' . $submenu[ $key ][ $item_key ][0] . '</span>';
				if ( isset( $slugs[ $key ] ) ) {
					// Also highlight the parent menu item.
					$menu[ $slugs[ $key ] ][0] = '<span style="border: 1px dotted orange;">' . $menu[ $slugs[ $key ] ][0] . '</span>';
					// Unset the slug so we don't add the icon twice.
					unset( $slugs[ $key ] );
				}
			}
		}
	}

	return $menu;
}