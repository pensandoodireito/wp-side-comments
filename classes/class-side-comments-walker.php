<?php

/**
 * Created by PhpStorm.
 * User: josafa filho <josafafilho15@gmail.com>
 * Date: 21/09/15
 * Time: 09:24
 * Walker class to ensure the side comments don't get put into the main comments display
 *
 * @since 0.1
 */

class SideCommentsWalker extends Walker_Comment
{

    // init classwide variables
    var $tree_type = 'comment';
    var $db_fields = array('parent' => 'comment_parent', 'id' => 'comment_ID');

    function start_lvl(&$output, $depth = 0, $args = array())
    {

        $GLOBALS['comment_depth'] = $depth + 1;

        switch ($args['style']) {
            case 'div':
                break;

            case 'ol':
                $output .= '<ol class="children">' . "\n";
                break;

            case 'ul':
            default:
                $output .= '<ul class="children">' . "\n";
                break;
        }

    }/* start_lvl() */

    /** END_LVL
     *  * Ends the children list of after the elements are added. */
    function end_lvl(&$output, $depth = 0, $args = array())
    {

        $GLOBALS['comment_depth'] = $depth + 1;

        switch ($args['style']) {

            case 'div':
                break;

            case 'ol':
                $output .= "</ol><!-- .children -->\n";
                break;

            case 'ul':
            default:
                $output .= "</ul><!-- .children -->\n";
                break;
        }

    }/* end_lvl() */

    /** START_EL */
    function start_el(&$output, $comment, $depth = 0, $args = array(), $id = 0)
    {

        $depth++;
        $GLOBALS['comment_depth'] = $depth;

        $isSideComment = get_comment_meta($comment->comment_ID, 'side-comment-section', true);

        if ($isSideComment) {
            $output .= '';
            return;
        }

        $userID = get_current_user_id();
        $postID = get_the_ID();

        // Now let's see if the current user should be able to see this comment
        $userCanSeeOneToOne = apply_filters('wp_side_comments_one_to_one_comments', true, $userID, $postID, $comment);

        if (!$userCanSeeOneToOne) {
            $output .= '';
            return;
        }

        $GLOBALS['comment'] = $comment;

        if (!empty($args['callback'])) {
            ob_start();
            call_user_func($args['callback'], $comment, $args, $depth);
            $output .= ob_get_clean();
            return;
        }

        if (('pingback' == $comment->comment_type || 'trackback' == $comment->comment_type) && $args['short_ping']) {
            ob_start();
            $this->ping($comment, $depth, $args);
            $output .= ob_get_clean();
        } elseif ('html5' === $args['format']) {
            ob_start();
            $this->html5_comment($comment, $depth, $args);
            $output .= ob_get_clean();
        } else {
            ob_start();
            $this->comment($comment, $depth, $args);
            $output .= ob_get_clean();
        }

    }/* start_el() */

    function end_el(&$output, $comment, $depth = 0, $args = array())
    {

        if (!empty($args['end-callback'])) {
            ob_start();
            call_user_func($args['end-callback'], $comment, $args, $depth);
            $output .= ob_get_clean();
            return;
        }

        if ('div' == $args['style']) {
            $output .= "</div><!-- #comment-## -->\n";
        } else {
            $output .= "</li><!-- #comment-## -->\n";
        }

    }/* end_el() */

}/* class Walker_Comment */