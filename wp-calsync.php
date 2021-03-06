<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              -
 * @since             1.0.0
 * @package           Wp_Calsync
 *
 * @wordpress-plugin
 * Plugin Name:       Modern Apes gig importer
 * Plugin URI:        https://github.com/Matwilk/wp-calsync.git
 * Description:       Imports dates from the Modern Apes Google calendar into the site's gig dates.
 * Version:           1.0.0
 * Author:            Matthew Wilkinson
 * Author URI:        -
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-calsync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-calsync-activator.php
 */
function activate_wp_calsync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-calsync-activator.php';
	Wp_Calsync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-calsync-deactivator.php
 */
function deactivate_wp_calsync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-calsync-deactivator.php';
	Wp_Calsync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_calsync' );
register_deactivation_hook( __FILE__, 'deactivate_wp_calsync' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-calsync.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_calsync() {

	$plugin = new Wp_Calsync();
	$plugin->run();

}
run_wp_calsync();
