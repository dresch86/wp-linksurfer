<?php
if (defined('WP_CLI') && WP_CLI) {
    class LinkSurfer_WP_CLI {
        private $settings = [];
        private $cURLOpts = [];
        private $mainSiteId = 0;
        private $email_alerts = [];
        private $linksurfer_log_table = '';
        private $linksurfer_settings_table = '';

        public function __construct() {
            global $wpdb;

            $this->mainSiteId = get_main_site_id();
            $this->cURLOpts = [
                CURLOPT_HEADER          => true,
                CURLOPT_NOBODY          => true,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FOLLOWLOCATION  => true, 
                CURLOPT_SSL_VERIFYHOST  => false,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_TIMEOUT         => 10
            ];

            $this->linksurfer_settings_table = $wpdb->base_prefix . 'linksurfer_settings';
            $this->linksurfer_log_table = $wpdb->base_prefix . 'linksurfer_log';

            $query = 'SELECT `name`, `value` FROM ' . $this->linksurfer_settings_table;
            $setting_map = $wpdb->get_results($query, ARRAY_A);

            foreach ($setting_map as $setting_map_entry) {
                $this->settings[$setting_map_entry['name']] = $setting_map_entry['value'];
            }
        }

        private function postRunTasks() {
            global $wpdb;

            $wpdb->update($this->linksurfer_settings_table, 
                            ['value'=>wp_date('Y-m-d H:i:s')],
                            ['name'=>'last_run_by_cron'],
                            ['%s']);

            if (count($this->email_alerts) > 0) {
                $recipents = explode("\n", $this->settings['email_report_to']);
                $subject = 'LinkSurfer Alerts for ' . get_bloginfo('name');
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $report_items = implode('', $this->email_alerts);

                // Sets $html_email_report var using HEREDOC
                require_once LINKSURFER_PLUGIN_PATH . 'admin/partials/html_email_report.php';

                foreach ($recipents as $recipent) {
                    wp_mail(trim($recipent), $subject, $html_email_report, $headers);

                    // Pause between recipents to avoid triggering SPAM filters
                    sleep(1);
                }
            }
        }

        private function logHttpError($title, $url, $status) {
            global $wpdb;

            // Check for duplicate entry to save space in the db
            $sql = $wpdb->prepare(('SELECT `log_id` FROM ' . $this->linksurfer_log_table . ' WHERE `title` = %s AND `url` = %s AND `status` = %d'), $title, $url, $status);
            $result = $wpdb->get_row($sql, ARRAY_A);

            if ($result === null) {
                // No entry exists so create one...
                $sql = $wpdb->prepare(('INSERT INTO ' . $this->linksurfer_log_table . ' (`title`, `url`, `status`) VALUES (%s, %s, %d)'), $title, $url, $status);
                $wpdb->query($sql);
            } else {
                // Entry exists so just update datetime
                $wpdb->update($this->linksurfer_log_table, ['date' => wp_date('Y-m-d H:i:s')], ['log_id' => $result['log_id']]);
                WP_CLI::log($title . '\'s URL still in an error state [log_id=' . $result['log_id'] . ']');
            }
        }

        public function check_external_links() {
            global $wpdb;

            if (!defined('DOMAIN_CURRENT_SITE')) {
                WP_CLI::error('DOMAIN_CURRENT_SITE must be defined for LinkSurfer to work properly');
                return;
            }

            if (strlen($this->settings['post_type']) < 1) {
                WP_CLI::error('Post Type must be set to check for ACF fields');
                return;
            }

            if (!is_plugin_active('advanced-custom-fields/acf.php')) {
                WP_CLI::warning('LinkSurfer depends on the Advanced Custom Fields plugin for data');
                return;
            }

            if (is_multisite()) {
                switch_to_blog($this->mainSiteId);
            }

            $posts = new WP_Query(['post_type'=>$this->settings['post_type']]);
            WP_CLI::log('Checking external links...');

            if ($posts->have_posts()) {
                $cURLRes = curl_init();

                while ($posts->have_posts()) {
                    $posts->the_post();
                    $post_id = get_the_ID();
                    $title = get_the_title();

                    // Get URL from ACF field
                    $url = get_field($this->settings['acf_field_name']);

                    if ($url) {
                        $url = trim($url);

                        if (strpos($url, DOMAIN_CURRENT_SITE) === false) {
                            // Not a link whose destination we manage so check it's status

                            $this->cURLOpts[CURLOPT_URL] = $url;
                            curl_reset($cURLRes);
                            
                            curl_setopt_array($cURLRes, $this->cURLOpts);
                            $sOK = curl_exec($cURLRes);
                            $http_status = curl_getinfo($cURLRes, CURLINFO_HTTP_CODE);

                            if ($http_status > 399) {
                                // An HTTP error code was encountered so log it

                                if ($this->settings['email_report_enabled'] == '1') {
                                    $result_to_html  = '<p>' . "\n";
                                    $result_to_html .= '    <h4>' . $title . '</h4>' . "\n";
                                    $result_to_html .= '    <div>HTTP Status: <span class="linksurfer_http_status">' . $http_status . '</span></div>' . "\n";
                                    $result_to_html .= '    <div>URL: <a href="' . $url . '">Click to Visit</a>' . "\n";
                                    $result_to_html .= '</p>' . "\n";
                                    $this->email_alerts[] = $result_to_html;
                                }

                                WP_CLI::log('The link for ' . $title . ' appears to be broken [http_status=' . $http_status . ']');
                                $this->logHttpError($title, $url, $http_status);
                            } 
    
                            // Pause for a second to prevent rapid requests from getting the server blacklisted
                            sleep(1);
                        }
                    } else {
                        WP_CLI::warning('Cannot find ACF [name=' . $this->settings['acf_field_name'] . '] on post [pid=' . $post_id . ']');
                    }
                }

                curl_close($cURLRes);
                $this->postRunTasks();
            } else {
                WP_CLI::debug('No posts with slug [' . $this->settings['post_type'] . '] found...');
            }

            wp_reset_query();
            WP_CLI::log('Finished checking external links...');
        }
    }

    WP_CLI::add_command('linksurfer', 'LinkSurfer_WP_CLI');
}