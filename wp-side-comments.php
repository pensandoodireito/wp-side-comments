<?php

/*
Plugin Name: WP Side Comments
Plugin URI: http://ctlt.ubc.ca/
Description: Based on aroc's Side Comments .js to enable inline commenting
Author: CTLT Dev, Richard Tape
Author URI: http://ctlt.ubc.ca
Version: 0.1.3
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

define( 'CTLT_WP_SIDE_COMMENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Each plugin's class included below is responsible for register its own hooks and initializers
 */

//includes wp side comments class
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/class-wp-side-comments.php';

//includes wp side comments admin class
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/class-wp-side-comments-admin.php';

//includes the Custom Post Type "Texto em Debate"
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/class-texto-em-debate-post-type.php';

// Widget para exibição na capa
include( CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/comment-front-widget.php' );