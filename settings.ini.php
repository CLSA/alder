<?php
/**
 * settings.ini.php
 * 
 * Defines initialization settings for alder.
 * DO NOT edit this file, to override these settings use settings.local.ini.php instead.
 * Any changes in the local ini file will override the settings found here.
 */

global $SETTINGS;

// tagged version
$SETTINGS['general']['application_name'] = 'alder';
$SETTINGS['general']['instance_name'] = $SETTINGS['general']['application_name'];
$SETTINGS['general']['version'] = '2.9';
$SETTINGS['general']['build'] = '672c225';

// the location of alder internal path
$SETTINGS['path']['APPLICATION'] = str_replace( '/settings.ini.php', '', __FILE__ );

// the directory where image files are located
$SETTINGS['path']['IMAGES'] = NULL;
$SETTINGS['url']['IMAGES'] = NULL;

// where to store the file which tracks when the last sync was performed
$SETTINGS['general']['last_sync_file'] = $SETTINGS['path']['APPLICATION'].'/last_sync';
