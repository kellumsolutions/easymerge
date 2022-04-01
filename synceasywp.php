<?php
/**
 * Plugin Name: EasyMerge
 * Description: Easily merge database changes from a production environment to dev environments.
 * Author: Kellum Solutions
 * Version: 1.0.0
 * Author URI: https://www.kellumsolutions.com/
 *
 * TODO: 
 * 	- Add filter function to rules conditions, so data can be imported based on a 
 *	 user defined function.
 */
 

 
if(!defined('ABSPATH')) exit; // Exit if accessed directly

if ( ! defined( 'SEZ_PLUGIN_FILE' ) ) {
	define( 'SEZ_PLUGIN_FILE', __FILE__ );
}

if( !class_exists( 'SyncEasy' ) ) {
	include_once dirname( SEZ_PLUGIN_FILE ) . '/includes/class-synceasy.php';
}

function SEZ(){
    return SyncEasy::instance();
}

$GLOBALS['synceasy'] = SEZ();