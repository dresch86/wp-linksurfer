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

$html_email_report = <<<HTML
<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>LinkSurfer Report</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <style>
            .linksurfer_http_status 
            {
                color: red;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <p>Dear System Admin,<br>LinkSurfer received error response(s) code for the links below...</p>
        {$report_items}
    </body>
</html>
HTML;