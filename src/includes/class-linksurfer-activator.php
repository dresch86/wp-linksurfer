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
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LinkSurfer
 * @subpackage LinkSurfer/includes
 * @author     Daniel Resch <primesoftwarenetworks@gmail.com>
 */
class LinkSurfer_Activator {
	/**
	 * @since    1.0.0
	 */
	public static function activate($network_wide) {
		global $wpdb;
		$linksurfer_settings_table = $wpdb->base_prefix . 'linksurfer_settings';
		$linksurfer_log_table = $wpdb->base_prefix . 'linksurfer_log';

		if ($wpdb->get_var('SHOW TABLES LIKE `' . $linksurfer_settings_table . '`') != $linksurfer_settings_table) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $linksurfer_settings_table (
				`setting_id` TINYINT(255) UNSIGNED NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(255) NOT NULL,
				`value` VARCHAR(255) NOT NULL,
				PRIMARY KEY  (setting_id)
			) $charset_collate;
			CREATE TABLE $linksurfer_log_table (
				`log_id` INT(255) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`url` VARCHAR(255) NOT NULL,
				`status` SMALLINT(255) UNSIGNED NOT NULL,
				`date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (log_id)
			) $charset_collate;";
	
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			$init_settings = 
			[
				'setting_id' => NULL,
				'name' => 'email_report_to',
				'value' => get_option('admin_email')
			];

			$wpdb->insert($linksurfer_settings_table, $init_settings);

			$init_settings = 
			[
				'setting_id' => NULL,
				'name' => 'email_report_enabled',
				'value' => '1'
			];

			$wpdb->insert($linksurfer_settings_table, $init_settings);

			$init_settings = 
			[
				'setting_id' => NULL,
				'name' => 'acf_field_name',
				'value' => 'location_website'
			];

			$wpdb->insert($linksurfer_settings_table, $init_settings);

			$init_settings =
			[
				'setting_id' => NULL,
				'name' => 'last_run_by_cron',
				'value' => wp_date('Y-m-d H:i:s')
			];

			$wpdb->insert($linksurfer_settings_table, $init_settings);

			$init_settings =
			[
				'setting_id' => NULL,
				'name' => 'post_type',
				'value' => 'location'
			];

			$wpdb->insert($linksurfer_settings_table, $init_settings);
		}
	}
}