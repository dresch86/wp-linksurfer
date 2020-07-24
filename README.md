# __LinkSurfer__

This plugin scans posts for an Advanced Custom Field that contains a URL. The URL is then accessed via cURL to get a response code. If an error response code is encountered, a log entry is created to alert system admins of a broken link. The link checker requires the WP-CLI, so links can be checked using a system scheduling tool (i.e. cron, systemd, etc).

## __Build Instructions__
### Requirements:
* NodeJS
* WP-CLI
* [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/)
* nps (global)
* nps-utils (global)
* _Optional: PNPM_

### Tasks:
* Build and package - _nps build_
* Build plugin only - _nps plugin_
* Archive current build - _nps archive_

### Build Steps:
1. Git clone `https://github.com/dresch86/wp-linksurfer`
1. `cd` into cloned directory
1. `(p)npm install`
1. `nps build`
1. Install zip file in the build directory using the WordPress plugin manager

### __Future Goals:__
- [ ] Functionality to download log entries