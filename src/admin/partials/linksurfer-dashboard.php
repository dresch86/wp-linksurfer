<?php
/**********************************************************************
LinkSurfer is a plugin for WordPress that checks for dead links in
a custom field.

Copyright (C) 2020 by Daniel Resch

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
***********************************************************************/

$linksurfer_dashboard = <<<HTML
    <div id="linksurfer-dashboard">
        <div id="linksurfer-admin-header">
            <h1>LinkSurfer Overview</h1>
            <button id="linksurfer-settings-button" type="button">Settings</button>
        </div>
        <p>
            The LinkSurfer WP-CLI job was last run on <span class="linksurfer-emphasized-date">{$last_run_date}</span>. To check or change the interval, please contact the system administrator as the job can only be configured via the system scheduler (i.e. cron, systemd, etc).
        </p>
        <div id="linksurfer-actions-box-top" class="linksurfer-hbox linksurfer-bulk-actions{$hide_actions}">
            <select id="linksurfer-ba-top" name="linksurfer-ba-top">
                <option value="BulkActions">Bulk Actions</option>
                <option value="Delete">Delete</option>
                <option value="Download">Download</option>
            </select>
            <input type="submit" id="linksurfer-doaction-top" class="button action linksurfer-doaction" value="Apply">
        </div>
        <div>
            {$dashboard_content}
        </div>
        <div id="linksurfer-actions-box-bottom" class="linksurfer-hbox linksurfer-bulk-actions{$hide_actions}">
            <select id="linksurfer-ba-bottom" name="linksurfer-ba-bottom">
                <option value="BulkActions">Bulk Actions</option>
                <option value="Delete">Delete</option>
                <option value="Download">Download</option>
            </select>
            <input type="submit" id="linksurfer-doaction-bottom" class="button action linksurfer-doaction" value="Apply">
        </div>
        <form id="linksurfer_settings_box" class="linksurfer-vbox linksurfer-hidden">
            <table id="linksurfer_settings">
                <tr>
                    <td>Post Type Slug:</td>
                    <td><input type="text" id="post_type_slug" name="post_type_slug" value="{$current_pt_slug}"></td>
                </tr>
                <tr>
                    <td>ACF Field Slug:</td>
                    <td><input type="text" id="acf_field_slug" name="acf_field_slug" value="{$current_acf_slug}"></td>
                </tr>
                <tr>
                    <td>E-Mail Report:</td>
                    <td><input type="checkbox" id="email_report_enabled" name="email_report_enabled" value="1"{$current_email_report_enabled}></td>
                </tr>
                <tr>
                    <td>Report Recipients:</td>
                    <td><textarea id="recipient_list" name="recipient_list">$current_emails</textarea></td>
                </tr>
                <tr>
                    <td></td>
                    <td><div class="linksurfer-footnote">(One address per line)</div></td>
                </tr>
            </table>
            <div id="linksurfer_upd_settings_response" class="linksurfer-response linksurfer-hidden"></div>
        </form>
        <div id="linksurfer_bulk_action_response" class="linksurfer-response linksurfer-hidden"></div>
    </div>
HTML;