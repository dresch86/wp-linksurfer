<?php
/**
 * LinkSurfer is a plugin for WordPress that checks for dead links in
 * a custom field.
 * 
 * Copyright (C) 2020 by Daniel Resch
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 * 
 * @link              https://github.com/dresch86
 * @since             1.0.0
 * @package           Link Surfer
 *
 * @wordpress-plugin
 * Plugin Name:       LinkSurfer
 * Plugin URI:        https://github.com/dresch86/wp-linksurfer
 * Description:       This plugin scans links in a custom field for quality control.
 * Version:           1.0.0
 * Author:            Daniel Resch
 * Author URI:        https://github.com/dresch86
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:       linksurfer
 * Domain Path:       /languages
 */

if (!defined( 'WPINC' )) {
	die;
}

/**
 * Currently plugin version.
 */
define('LINKSURFER_VERSION', '1.0.0');

/**
 * Absolute path to the plugin directory for convenience.
 */
define('LINKSURFER_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Public facing URL to the plugin directory for convenience.
 */
define('LINKSURFER_PLUGIN_URL', plugin_dir_url(__FILE__));

function log_linksurfer_error() {
	if (WP_DEBUG === true) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-linksurfer-activator.php
 */
function activate_linksurfer($network_wide) {
	require_once LINKSURFER_PLUGIN_PATH . 'includes/class-linksurfer-activator.php';
	LinkSurfer_Activator::activate($network_wide);
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-linksurfer-deactivator.php
 */
function deactivate_linksurfer() {
	require_once LINKSURFER_PLUGIN_PATH . 'includes/class-linksurfer-deactivator.php';
	LinkSurfer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_linksurfer' );
register_deactivation_hook( __FILE__, 'deactivate_linksurfer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require LINKSURFER_PLUGIN_PATH . 'includes/class-linksurfer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_linksurfer() {
	$plugin = new LinkSurfer();
	$plugin->run();
}

run_linksurfer();