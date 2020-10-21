# Moodle unowebconv plugin

This Repository contains the code for the `unowebconv` plugin, an alternative to the unoconv moodle plugin, that uses a webservice for the document conversions.

## How to use

To install this plugin in moodle there are two ways:

1. Install the plugin from zip (recommended)
   _Important:_ only create a zip from the unowebconv folder (e.g. `unowebconv.zip`)

2. Copy the folder `unowebconv` and its contents to `files/converter/` within the moodle installation and run a database upgrade.

After the plugin was installed sucessfully, do not forget to provide a path pointing to the corresponding webservice, after that the plugin is enabled and ready to use.

## Compatibility

The current version of this [plugin](https://github.com/team-parallax/unowebconv-plugin) (v1.0.1) works with version `0.4.2` of the unoconv-webservice (more information on versions below).

All versions currently mentioned in this document specify versions of the plugin itself or versions of the [team-parallax/unoconv-webservice](https://github.com/team-parallax/unoconv-webservice) (referred to as unoconv-webservice)


| Plugin | Webservice |
| --- | --- |
| 1.0.0 | 0.4.2 |
| 1.0.1 | 0.4.2 |

## Changes

Changes in different versions:

__1.0.0__: release candidate

__1.0.1__: updated labels for settings page, automatic disabling of unoconv