<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://127.0.0.1
 * @since             1.0.0
 * @package           Ech_Consultant
 *
 * @wordpress-plugin
 * Plugin Name:       ECH Consultant
 * Plugin URI:        https://127.0.0.1
 * Description:       This is a description of the plugin.
 * Version:           1.0.0
 * Author:            Rowan Chang
 * Author URI:        https://127.0.0.1/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ech-consultant
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ECH_CONSULTANT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ech-consultant-activator.php
 */
function activate_ech_consultant() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ech-consultant-activator.php';
	Ech_Consultant_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ech-consultant-deactivator.php
 */
function deactivate_ech_consultant() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ech-consultant-deactivator.php';
	Ech_Consultant_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ech_consultant' );
register_deactivation_hook( __FILE__, 'deactivate_ech_consultant' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ech-consultant.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ech_consultant() {

	$plugin = new Ech_Consultant();
	$plugin->run();

}
run_ech_consultant();