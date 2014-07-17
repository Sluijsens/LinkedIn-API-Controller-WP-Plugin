<?php
/**
 * Plugin Name: LinkedIn API Controller
 * Plugin URI: http://www.netwerven.nl
 * Description: A controller plugin to handle the linkedin api.
 * Version: 1.0
 * Author: Bryan Slop (Sluijsens)
 * Author URI: http://bryan-slop.nl
 */

define( "LIAC_ROOT", dirname( __FILE__ ) );

include_once( 'php/lib/fpdf/fpdf.php' );
require( 'php/classes/class-fpdf-html.php' );
require( 'php/classes/class-linkedin-api-controller.php' );
require( 'php/classes/class-liac-data.php' );

include_once( 'php/classes/class-liac-admin.php' );
include_once( 'php/functions/main_functions.php' );

load_plugin_textdomain( "liac", false, dirname( plugin_basename( __FILE__ ) ) . "/languages/" );

$https = ( isset( $_SERVER['HTTPS'] ) && "on" == $_SERVER['HTTPS'] ) ? "https://" : "http://";