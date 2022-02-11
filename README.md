# craft-entries-scheduler plugin for Craft CMS 3.x

Craft Entries Scheduler

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require bukwild/craft-entries-scheduler

3. Add to Crontab:

```
* * * * * /usr/bin/php /path-to-project/clifbar/craft-cms/craft craft-entries-scheduler/default
```

4. In the Control Panel, go to Settings → Plugins and click the “Install” button for craft-entries-scheduler.

## Configuring craft-entries-scheduler

Add Webhooks to the corresponding Site in /admin/craft-entries-scheduler

Brought to you by [Bukwild](https://bukwild.com)
