<?php
namespace WordPressdotorg\Plugin_Review_Helper;

/**
 * Plugin Name: Playground Review Helper
 * Description: Helper plugin for reviewing plugins. Makes a plugin moderator's job a little easier. Intended for use within Playground.
 * Author: Alex Shiels
 * Author URI: https://wordpress.org/
 * Version: 0.3
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
ini_set( 'log_errors', false );
ini_set( 'html_errors', 1 );
// Put the log file somewhere accessible both by code and web.
ini_set( 'error_log', PRH_LOG_FILE );

function prh_exception_error_handler(int $errno, string $errstr, string $errfile = null, int $errline) {
	if (!(error_reporting() & $errno)) {
		// This error code is not included in error_reporting
		return false;
	}

	// Capture a backtrace, since the default error handler doesn't include it for warnings or notices.
	ob_start();
	debug_print_backtrace();
	$backtrace = ob_get_clean();
	error_log( '[' . gmdate('Y-m-d H:i:s e') . '] ' . $errstr . ' in ' . $errfile . ' on line ' . $errline . "\n" . $backtrace, 3, PRH_LOG_FILE );

	return false; // We want to continue with the default error handler.
}

set_error_handler( __NAMESPACE__ . '\prh_exception_error_handler' );

function prh_admin_menu_capture() {
	global $prh_base_menu, $prh_base_submenu;

	// Capture a copy of the menu and submenu globals before plugins add to them.
	$prh_base_menu = $GLOBALS['menu'];
	$prh_base_submenu = $GLOBALS['submenu'];
}

function prh_enqueued_capture() {
	global $prh_enqueued_scripts, $prh_enqueued_styles;
	global $wp_scripts, $wp_styles;

	// Capture a copy of the enqueued scripts and styles before plugins add to them.
	$prh_enqueued_scripts = $wp_scripts->registered;
	$prh_enqueued_styles = $wp_styles->registered;
}

function prh_count_lines_in_file( $filename ) {
	$linecount = 0;
	if ( !file_exists( $filename ) ) {
		return 0;
	}
	$handle = fopen( $filename, 'r' );
	while ( $handle && !feof( $handle ) ) {
		$line = fgets( $handle );
		$linecount++;
	}
	fclose( $handle );

	return $linecount;
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

// Capture an early copy of the enqueued scripts and styles.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\prh_enqueued_capture', PHP_INT_MIN );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\prh_enqueued_capture', PHP_INT_MIN );

// Add submenu.
add_action( 'admin_menu', __NAMESPACE__ . '\prh_admin_menu' );

// Add item to admin bar.
add_action( 'admin_bar_menu', __NAMESPACE__ . '\prh_admin_bar_menu', 100 );

add_action( 'add_menu_classes', __NAMESPACE__ . '\prh_add_menu_classes', 100 );

add_action( 'admin_init', __NAMESPACE__ . '\prh_admin_init' );

// Make _doing_it_wrong() errors noisy.
add_filter( 'doing_it_wrong_run', __NAMESPACE__ . '\prh_doing_it_wrong_run', 10, 3 );


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

// Given the full pathname of a file from a plugin, return the plugin's main file (in 'slug/plugin.php' format).
function prh_get_plugin_file_from_path( $path ) {
	$plugins = get_plugins();
	foreach ( $plugins as $file => $plugin ) {
		$plugin_dir = dirname( WP_CONTENT_DIR . '/plugins/' . $file );
		if ( str_starts_with( $path, $plugin_dir ) ) {
			return $file;
		}
	}

	return false;
}

function prh_admin_page() {
	?>
	<div class="wrap">
		<h1>Plugin Review Helper</h1>
		<p>Plugin Review Helper is a plugin for reviewing plugins. It makes a plugin moderator's job a little easier. It is intended for use within Playground.</p>
		<p>Plugin Review Helper is a work in progress. It is not yet ready for use.</p>
		<p>Error log: <a href="<?php echo esc_url( WP_CONTENT_URL . '/plugin-review-helper.log' ); ?>"><?php echo esc_url( PRH_LOG_FILE ); ?></a> (<?php echo number_format( prh_count_lines_in_file(PRH_LOG_FILE) ); ?> lines)</p>

		<p>WP_DEBUG: <?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'true' : 'false'; ?><br />
		WP_DEBUG_DISPLAY: <?php echo ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) ? 'true' : 'false'; ?><br />
		SCRIPT_DEBUG: <?php echo ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'true' : 'false'; ?><br />
		</p>

		<p>phpinfo(): <a href="<?php echo esc_url( admin_url( 'admin.php?page=plugin-review-helper&phpinfo=1' ) ); ?>">View</a></p>
	</div>
	<?php

	if ( isset( $_GET['phpinfo'] ) ) {
		phpinfo();
	}

	global $wp_scripts, $wp_styles;
	global $prh_enqueued_scripts, $prh_enqueued_styles;
	?>
	<div>
		<h2>Enqueued Scripts</h2>
		<dl>
	<?php foreach ( $wp_scripts->registered as $script ) {
		if ( isset( $prh_enqueued_scripts[ $script->handle ] ) ) {
			continue;
		}
		if ( $script->src && !str_starts_with( $script->src, '/wp-includes/' ) && !str_starts_with( $script->src, '/wp-admin/' ) ) {
			?>
			<dt><?php echo esc_html( $script->handle ); ?></dt>
			<dd><?php echo esc_html( $script->src ); ?></dd>
			<?php
		}
	} ?>
		<h2>Enqueued Styles</h2>
		</dl>
	<?php foreach ( $wp_styles->registered as $style ) {
		if ( isset( $prh_enqueued_styles[ $style->handle ] ) ) {
			continue;
		}
		if ( $style->src && !str_starts_with( $style->src, '/wp-includes/' ) && !str_starts_with( $style->src, '/wp-admin/' ) ) {
			?>
			<dt><?php echo esc_html( $style->handle ); ?></dt>
			<dd><?php echo esc_html( $style->src ); ?></dd>
			<?php
		}
	} ?>

	</div>
		<h2>Hooks</h2>
	<dl><?php

		global $wp_filter;

		foreach ( $wp_filter as $hook => $filter ) {
			foreach ( $filter as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$ref = null;
					if ( is_string( $callback['function'] ) && function_exists( $callback['function'] ) ) {
						$ref = new \ReflectionFunction( $callback['function'] );
					} elseif ( is_array( $callback['function'] ) && is_callable( $callback['function'] ) ) {
						$ref = new \ReflectionMethod( $callback['function'][0], $callback['function'][1] );
					}
					if ( $ref ) {
						if ( \str_starts_with( $ref->getFileName(), WP_CONTENT_DIR . '/plugins/' ) ) {
							$skip = [
								basename( __DIR__ ), // self
								'sqlite-integration',
								'plugin-check',
							];
							foreach ( $skip as $skip_plugin ) {
								if ( \str_starts_with( $ref->getFileName(), WP_CONTENT_DIR . '/plugins/' . $skip_plugin . '/' ) ) {
									// Used by Playground environment
									continue 2;
								}
							}
							$plugin_file = prh_get_plugin_file_from_path( $ref->getFileName() );
							$source_file = substr( $ref->getFileName(), strlen( WP_CONTENT_DIR . '/plugins/' ) );
							$callback_name = ( $ref instanceof \ReflectionMethod ? $ref->getDeclaringClass()->getName() . '::' . $ref->getName() . '()' : $ref->getName() . '()' );

							?>
							<dt><?php echo esc_html( $hook ); ?></dt>
							<dd><?php printf( '<a href="%s">%s</a>', admin_url('plugin-editor.php?file=' . $source_file . '&plugin=' . $plugin_file . '&line=' . $ref->getStartLine() ), esc_html( $callback_name ) ); ?></dd>
							<?php
						}
					}
				}
			}
		}
	?></dl>
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
			$menu[ $key ][0] = '<span style="border: 1px dotted orange;">' . $menu[ $key ][0] . '</span>';
		}
	}

	// There's no corresponding filter for submenus, so we'll modify them in-place here.
	global $prh_base_submenu, $submenu;

	foreach ( $submenu as $key => $items ) {
		foreach ( $items as $item_key => $item ) {
			if ( ! isset( $prh_base_submenu[ $key ][ $item_key ] ) ) {
				$ignore_plugins = [
					'plugin-review-helper',
					'sqlite-integration',
					'plugin-check',
				];

				// Ignore plugins used by Playground.
				if ( in_array( $item[2], $ignore_plugins, true ) ) {
					continue;
				}
				// These are added by core in a deferred action.
				if ( 'theme-editor.php' === $item[2] || 'plugin-editor.php' === $item[2] ) {
					continue;
				}
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

function prh_doing_it_wrong_run( $function_name, $message, $wp_version ) {
	$_message = sprintf(
		'Function %1$s was called <strong>incorrectly</strong>. %2$s %3$s',
		$function_name,
		esc_html( $message ),
		$wp_version
	);
	trigger_error( $_message, E_USER_WARNING );
}