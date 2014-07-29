<?php
/**
 * Plugin Name: LinkedIn API Controller
 * Plugin URI: http://www.bryan-slop.nl
 * Description: A controller plugin to handle the linkedin api.
 * Version: 1.0
 * Author: Bryan Slop (Sluijsens)
 * Author URI: http://bryan-slop.nl
 */

define( "LIAC_ROOT", dirname( __FILE__ ) );
define( "LIAC_ROOT_URI", plugins_url( basename( LIAC_ROOT ) ) );

include_once( 'php/lib/fpdf/fpdf.php' );
require( 'php/classes/class-fpdf-html.php' );
require( 'php/classes/class-linkedin-api-controller.php' );
require( 'php/classes/class-liac-data.php' );

include_once( 'php/classes/class-liac-admin.php' );
include_once( 'php/functions/main_functions.php' );

load_plugin_textdomain( "liac", false, dirname( plugin_basename( __FILE__ ) ) . "/languages/" );

$https = ( isset( $_SERVER['HTTPS'] ) && "on" == $_SERVER['HTTPS'] ) ? "https://" : "http://";

function plugin_enqueue_scripts() {
    wp_register_style( "liac-main_style", LIAC_ROOT_URI."/style/css/main.css" );
    wp_enqueue_style( "liac-main_style" );
    
    var_dump(wp_style_is( "liac-main_style", "enqueued" ));
}
add_action( "wp_enqueue_scripts", "plugin_enqueue_scripts" );