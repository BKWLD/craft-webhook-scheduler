# craft-webhook-scheduler plugin for Craft CMS 3.x

Craft Webhook Scheduler

![Screenshot](resources/img/icon.svg)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require bkwld/craft-webhook-scheduler

3. Add to Crontab:

```
* * * * * /usr/bin/php /path-to-project/clifbar/craft-cms/craft craft-webhook-scheduler/default
```

4. In the Control Panel, go to Settings → Plugins and click the “Install” button for craft-webhook-scheduler.

## Configuring craft-webhook-scheduler

Add Webhooks to the corresponding Site in /admin/craft-webhook-scheduler

Brought to you by [Bukwild](https://bukwild.com)
