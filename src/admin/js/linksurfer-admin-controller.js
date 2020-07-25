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
 * */
function LinkSurferAdminController() {
    jQuery('#linksurfer-settings-button').click(this.showSettingsPanel.bind(this));
    jQuery('#linksurfer-doaction-bottom').click(this.doAction.bind(this));
    jQuery('#linksurfer-doaction-top').click(this.doAction.bind(this));

    this.elSettingsBox = jQuery('#linksurfer_settings_box');
    this.elSettingsRespBox = jQuery('#linksurfer_upd_settings_response');
    this.elBulkActionRespBox = jQuery('#linksurfer_bulk_action_response');

    if (jQuery('#linksurfer-logs').length) {
        jQuery('#linksurfer-log-item-select-all').click(this.selectAllLogEntries.bind(this));
    }
}

LinkSurferAdminController.prototype.showSettingsPanel = function() {
    jQuery(this.elSettingsBox).dialog({
        title: 'LinkSurfer Settings',
        dialogClass: 'wp-dialog',
        autoOpen: false,
        draggable: false,
        width: 'auto',
        modal: true,
        resizable: false,
        closeOnEscape: true,
        position: {
            my: "center",
            at: "center",
            of: window
        },
        buttons: {
            "Save": () => {
                this.saveSettings();
            },
            "Close": () => {
                jQuery(this.elSettingsBox).dialog('close');
            }
        },
        open: () => {
            // close dialog by clicking the overlay behind it
            jQuery('.ui-widget-overlay').bind('click', () => {
                jQuery(this.elSettingsBox).dialog('close');
            });
        },
        create: () => {
            // style fix for WordPress admin
            jQuery('.ui-dialog-titlebar-close').addClass('ui-button');
        },
        close: () => {
            this.elSettingsRespBox.removeClass('linksurfer-show-result');
            this.elSettingsRespBox.removeClass('linksurfer-show-result-error');
        }
    });

    jQuery(this.elSettingsBox).dialog('open');
}

LinkSurferAdminController.prototype.saveSettings = function() {
    let elPostType = jQuery('#post_type_slug');
    let elACFField = jQuery('#acf_field_slug');
    let elRecipientList = jQuery('#recipient_list');
    let elEmailReportEnabled = jQuery('#email_report_enabled');
    let iEmailReports = 0;

    if (elEmailReportEnabled.is(':checked')) {
        iEmailReports = 1;
    } 

    jQuery.post(
        linksurfer_admin_client.ajax_url, 
        {
            "action"               : "linksurfer",
            "operation"            : "save-settings",
            "wp_nonce"             : linksurfer_admin_client.nonce_save_settings,
            "post_type_slug"       : elPostType.val().trim(),
            "acf_field_slug"       : elACFField.val().trim(),
            "email_report_enabled" : iEmailReports,
            "recipient_list"       : elRecipientList.val().trim()
        }).done(response => {
            let aResult = JSON.parse(response);

            if (aResult[0] == 200) {
                if ((typeof aResult[1]) == 'string') {
                    this.elSettingsRespBox.text(aResult[1]);
                    this.elSettingsRespBox.addClass('linksurfer-show-result');
                } else {
                    if (aResult[1].errors == 0) {
                        this.elSettingsRespBox.text('Settings updated successfully!');
                        this.elSettingsRespBox.addClass('linksurfer-show-result');
                    } else {
                        this.elSettingsRespBox.text('Failed to save ' + aResult[1].errors + ' setting(s)!');
                        this.elSettingsRespBox.addClass('linksurfer-show-result');
                        this.elSettingsRespBox.addClass('linksurfer-show-result-error');
                    }
                }
            } else {
                this.elSettingsRespBox.text('Malformed request!');
                this.elSettingsRespBox.addClass('linksurfer-show-result');
                this.elSettingsRespBox.addClass('linksurfer-show-result-error');
            }
        });    
}

LinkSurferAdminController.prototype.deleteLogEntries = function(idsToDel) {
    jQuery.post(
        linksurfer_admin_client.ajax_url, 
        {
            "action"    : "linksurfer",
            "operation" : "delete-logs",
            "wp_nonce"  : linksurfer_admin_client.nonce_delete_logs,
            "log_ids"   : JSON.stringify(idsToDel)
        }).done(response => {
            let aResult = JSON.parse(response);

            if (aResult[0] == 200) {
                this.elBulkActionRespBox.text('Successfully deleted ' + aResult[1] + ' log entries!');
                this.elBulkActionRespBox.addClass('linksurfer-show-result');
                setTimeout(() => location.reload(true), 1000);
            } else if (aResult[0] == 400) {
                this.elBulkActionRespBox.text('Error - Failed to delete logs!');
                this.elBulkActionRespBox.addClass('linksurfer-show-result');
                this.elBulkActionRespBox.addClass('linksurfer-show-result-error');
            } else {
                this.elBulkActionRespBox.text('Error - ' + aResult[1]);
                this.elBulkActionRespBox.addClass('linksurfer-show-result');
                this.elBulkActionRespBox.addClass('linksurfer-show-result-error');
            }
        });
}

LinkSurferAdminController.prototype.selectAllLogEntries = function(event) {
    let elsLogItems = jQuery('input[name="log_items"]');

    if (jQuery(event.target).is(':checked')) {
        elsLogItems.each((index, element) => {
            jQuery(element).prop('checked', true);
        });
    } else {
        elsLogItems.each((index, element) => {
            jQuery(element).prop('checked', false);
        });
    }
}

LinkSurferAdminController.prototype.doAction = function(event) {
    this.elBulkActionRespBox.removeClass('linksurfer-show-result-error');
    this.elBulkActionRespBox.removeClass('linksurfer-show-result');
    let elBulkActionOp = null;

    if (event.target.id == 'linksurfer-doaction-bottom') {
        elBulkActionOp = jQuery('#linksurfer-ba-bottom');
    } else {
        elBulkActionOp = jQuery('#linksurfer-ba-top');
    }

    let elsSelectedLogItems = jQuery('input[name="log_items"]:checked');
    let aLogsSel = [];

    elsSelectedLogItems.each((index, element) => {
        aLogsSel.push(jQuery(element).val());
    });

    if (aLogsSel.length > 0) {
        if (elBulkActionOp.val() == 'Delete') {
            this.deleteLogEntries(aLogsSel);
        } else if (elBulkActionOp.val() == 'Download') {
            this.elBulkActionRespBox.text('This feature is not yet implemented!');
            this.elBulkActionRespBox.addClass('linksurfer-show-result');
        } else {
            this.elBulkActionRespBox.text('Please choose a valid action to perform!');
            this.elBulkActionRespBox.addClass('linksurfer-show-result');
            this.elBulkActionRespBox.addClass('linksurfer-show-result-error');
        }
    } else {
        this.elBulkActionRespBox.text('Please select at least one log entry first!');
        this.elBulkActionRespBox.addClass('linksurfer-show-result');
        this.elBulkActionRespBox.addClass('linksurfer-show-result-error');
    }
}

jQuery(document).ready(() => {
    var lsacAdminHandler = new LinkSurferAdminController();
});