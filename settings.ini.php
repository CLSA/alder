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
$SETTINGS['general']['build'] = 'd8ab14b';

// the location of alder internal path
$SETTINGS['path']['APPLICATION'] = str_replace( '/settings.ini.php', '', __FILE__ );

// the directory where image files are located
$SETTINGS['path']['IMAGES'] = NULL;
$SETTINGS['url']['IMAGES'] = NULL;
