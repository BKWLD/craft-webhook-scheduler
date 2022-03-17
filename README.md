# craft-webhook-scheduler plugin for Craft CMS 3.x

A Craft plugin that triggers webhooks when scheduled posts become active.

## Requirements

This plugin requires Craft CMS 3.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require bkwld/craft-webhook-scheduler

3. Add to Crontab:

```
* * * * * /usr/bin/php /path-to-project/craft craft-webhook-scheduler/default
```

4. In the Control Panel, go to Settings → Plugins and click the “Install” button for craft-webhook-scheduler.

## Configuring craft-webhook-scheduler

Add Webhooks to the corresponding Site in /admin/craft-webhook-scheduler

Brought to you by [Bukwild](https://bukwild.com)
