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
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LinkSurfer
 * @subpackage LinkSurfer/public
 * @author     Daniel Resch <primesoftwarenetworks@gmail.com>
 */
class LinkSurfer_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The plugin's settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    An associative array of settings for this plugin.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		global $wpdb;
		$this->settings = [];
		$this->version = $version;
		$this->plugin_name = $plugin_name;

		$linksurfer_settings_table = $wpdb->base_prefix . 'linksurfer_settings';
		$query = 'SELECT `name`, `value` FROM ' . $linksurfer_settings_table;
		$setting_map = $wpdb->get_results($query, ARRAY_A);

		foreach ($setting_map as $setting_map_entry) {
			$this->settings[$setting_map_entry['name']] = $setting_map_entry['value'];
		}
	}

	private function saveSettings($post_type_slug, $acf_field_slug, $email_report_enabled, $recipient_list) {
		global $wpdb;
		$update_needed = [];
		$linksurfer_settings_table = $wpdb->base_prefix . 'linksurfer_settings';

		if ($recipient_list != $this->settings['email_report_to']) {
			$update_needed['email_report_to'] = $recipient_list;
		}

		if ($email_report_enabled != $this->settings['email_report_enabled']) {
			$update_needed['email_report_enabled'] = $email_report_enabled;
		}

		if ($acf_field_slug != $this->settings['acf_field_name']) {
			$update_needed['acf_field_name'] = $acf_field_slug;
		}

		if ($post_type_slug != $this->settings['post_type']) {
			$update_needed['post_type'] = $post_type_slug;
		}

		if (count($update_needed) > 0) {
			$total_rows_upd = 0;
			$total_errors = 0;

			foreach ($update_needed as $setting_name => $setting_new_val) {
				$sql = $wpdb->prepare('UPDATE ' . $linksurfer_settings_table . ' SET `value` = %s WHERE `name` = %s', [$setting_new_val, $setting_name]);
				
				if ($wpdb->query($sql) !== false) {
					++$total_rows_upd;
				} else {
					++$total_errors;
				}
			}

			return json_encode([200, ['success' => $total_rows_upd, 'errors' => $total_errors]]);
		} else {
			return json_encode([200, 'Settings do not need updating!']);
		}
	}

	private function deleteLogs($log_ids) {
		global $wpdb;
		$unser_ids = str_replace('\\', '', $log_ids);
		$unser_ids = str_replace('"', '', $unser_ids);

		if (preg_match('/^\[(?:\d(?:, ?)?)+\]$/i', $unser_ids)) {
			$ids = substr($unser_ids, 1, -1);
			$linksurfer_log_table = $wpdb->base_prefix . 'linksurfer_log';
			$result = $wpdb->query('DELETE FROM ' . $linksurfer_log_table . ' WHERE `log_id` IN(' . $ids . ')');
			
			return json_encode([200, $result]);
		} else {
			return json_encode([400, 'Bad Request']);
		}
	}

	public function display_linksurfer_overview() {
		global $wpdb;

		$linksurfer_log_table = $wpdb->base_prefix . 'linksurfer_log';
		$log_entries = $wpdb->get_results(('SELECT * FROM ' . $linksurfer_log_table), ARRAY_A);

		if ($wpdb->num_rows > 0) {
			$dashboard_content = '<table id="linksurfer-logs">' . "\n";
			$dashboard_content .= '    <thead>' . "\n";
			$dashboard_content .= '        <tr>' . "\n";
			$dashboard_content .= '            <td>Name</td>' . "\n";
			$dashboard_content .= '            <td>HTTP Code</td>' . "\n";
			$dashboard_content .= '            <td>URL</td>' . "\n";
			$dashboard_content .= '            <td>Date</td>' . "\n";
			$dashboard_content .= '            <td><input type="checkbox" id="linksurfer-log-item-select-all" name="linksurfer-log-item-select-all" value="true"></td>' . "\n";
			$dashboard_content .= '        </tr>' . "\n";
			$dashboard_content .= '    </thead>' . "\n";
			$dashboard_content .= '    <tbody>' . "\n";

			foreach ($log_entries as $log_entry) {
				$dashboard_content .= '        <tr>' . "\n";
				$dashboard_content .= '            <td>'.$log_entry['title'].'</td>' . "\n";
				$dashboard_content .= '            <td>'.$log_entry['status'].'</td>' . "\n";
				$dashboard_content .= '            <td><a href="'.$log_entry['url'].'">Check Site</a></td>' . "\n";
				$dashboard_content .= '            <td>'.date('M j, Y', strtotime($log_entry['date'])).'</td>' . "\n";
				$dashboard_content .= '            <td><input type="checkbox" id="log_item_'.$log_entry['log_id'].'" name="log_items" value="'.$log_entry['log_id'].'"></td>' . "\n";
				$dashboard_content .= '        </tr>' . "\n";
			}

			$dashboard_content .= '    </tbody>' . "\n";
			$dashboard_content .= '</table>' . "\n";

			$hide_actions = '';
		} else {
			$dashboard_content = 'It looks like all the links are working properly! Check back periodically for updates in status.';
			$hide_actions = ' linksurfer-hidden';
		}

		$last_run_date = date('M j, Y @ H:i:s', strtotime($this->settings['last_run_by_cron']));

		if ($this->settings['email_report_enabled'] == '1') {
			$current_email_report_enabled = ' checked';
		} else {
			$current_email_report_enabled = '';
		}

		$current_acf_slug = $this->settings['acf_field_name'];
		$current_emails = $this->settings['email_report_to'];
		$current_pt_slug = $this->settings['post_type'];

		// Sets $linksurfer_dashboard var using HEREDOC
		require LINKSURFER_PLUGIN_PATH . 'admin/partials/linksurfer-dashboard.php';
		echo $linksurfer_dashboard;	
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$current_screen = get_current_screen();

		if (strpos($current_screen->base, 'linksurfer') !== false) {
			wp_enqueue_style($this->plugin_name . '-admin', (LINKSURFER_PLUGIN_URL . 'admin/css/linksurfer-admin.css'), array(), $this->version, 'all' );
			wp_enqueue_style($this->plugin_name . '-icofonts', (LINKSURFER_PLUGIN_URL . 'admin/css/icofont.min.css'), array(), $this->version, 'all' );
			wp_enqueue_style('wp-jquery-ui-dialog');
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {
		switch ($hook)
		{
			case 'plugins_page_linksurfer-overview':
				$localize_js = ['ajax_url' => admin_url('admin-ajax.php'),
				'nonce_delete_logs' => wp_create_nonce('delete-logs'),
				'nonce_save_settings' => wp_create_nonce('save-settings')];
				wp_enqueue_script($this->plugin_name . '-admin-controller', (LINKSURFER_PLUGIN_URL . 'admin/js/linksurfer-admin-controller.js'), array('jquery', 'jquery-ui-dialog'), $this->version, false);
				wp_localize_script($this->plugin_name . '-admin-controller', 'linksurfer_admin_client', $localize_js);
				break;
			default:
				break;
		}
	}

	/**
	 * Handle AJAX requests for CRUD operations.
	 *
	 * @since    1.0.0
	 */
	public function handle_ajax_req() {
		if (isset($_POST) 
			&& isset($_POST['wp_nonce'])
			&& isset($_POST['operation'])) 
		{
			$nonce_chk = wp_verify_nonce($_POST['wp_nonce'], $_POST['operation']);

			if (($nonce_chk == 1) xor ($nonce_chk == 2)) {
				switch ($_POST['operation']) {
					case 'save-settings':
						echo $this->saveSettings(trim($_POST['post_type_slug']), 
													trim($_POST['acf_field_slug']), 
													trim($_POST['email_report_enabled']), 
													trim($_POST['recipient_list']));
						break;
					case 'delete-logs':
						echo $this->deleteLogs($_POST['log_ids']);
						break;
					default:
						echo json_encode([501, 'Not Implemented']);
						break;
				}
			}
			else {
				echo json_encode([403, 'Forbidden']);
			}

			wp_die();
		}
		else {
			echo json_encode([400, 'Bad Request']);
			wp_die();
		}
	}

	/**
	 * Create admin menu and submenus.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menus() {
		add_submenu_page('plugins.php', 'LinkSurfer Overview', 'LinkSurfer', 'manage_options', 'linksurfer-overview', array($this, 'display_linksurfer_overview'));
	}
}