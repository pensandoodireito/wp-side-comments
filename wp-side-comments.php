<?php

/*
Plugin Name: WP Side Comments
Plugin URI: http://ctlt.ubc.ca/
Description: Based on aroc's Side Comments .js to enable inline commenting
Author: CTLT Dev, Richard Tape
Author URI: http://ctlt.ubc.ca
Version: 0.1.3
*/

if (!defined('ABSPATH')) {
    die('-1');
}

define('CTLT_WP_SIDE_COMMENTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH', plugin_dir_path(__FILE__));

//includes comment walker classes
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/class-wp-side-comments.php';

//includes required classes for comment voting
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/class-visitor.php';

//Inclui o arquivo de configurações do Custom Post Type "Texto em Debate'
include(CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'cpt-texto-em-debate.php');

// Widget para exibição na capa
include(CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/comment-front-widget.php');

function wp_side_comments_init()
{
    global $CTLT_WP_Side_Comments;
    $CTLT_WP_Side_Comments = new CTLT_WP_Side_Comments();

    //TODO: vamos bloquear os votos de usuários não logados?
    if (is_user_logged_in()) {
        $visitor = new WP_Side_Comments_Visitor_Member(get_current_user_id());
    } else {
        $visitor = new WP_Side_Comments_Visitor_Guest($_SERVER['REMOTE_ADDR']);
    }

    if (!($CTLT_WP_Side_Comments->getVisitor() instanceof WP_Side_Comments_Visitor)) {
        $CTLT_WP_Side_Comments->setVisitor($visitor);
    }
}

add_action('plugins_loaded', 'wp_side_comments_init');