<?php

/**
 * Created by PhpStorm.
 * User: josafa filho <josafafilho15@gmail.com>
 * Date: 21/09/15
 * Time: 09:27
 */

//includes comment walker classes
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/class-side-comments-walker.php';

//includes required classes for html parsing
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/simple_html_dom.php';

//includes required classes for comment voting
require_once CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'classes/class-visitor.php';

class CTLT_WP_Side_Comments
{
    /**
     * @var WP_Side_Comments_Visitor
     */
    protected $visitor;

    /**
     * @var int current section id number
     */
    static $currentSectionID = 0;

    /**
     * @var WP_Side_Comments_Admin
     */
    protected $WPSideCommentsAdmin;

    /**
     * Set up our actions and filters
     *
     * @since 0.1
     *
     * @param WP_Side_Comments_Admin $WP_Side_Comments_Admin
     */
    public function __construct(WP_Side_Comments_Admin $WP_Side_Comments_Admin)
    {

        $this->WPSideCommentsAdmin = $WP_Side_Comments_Admin;

        // Load the necessary js/css
        add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts__loadScriptsAndStyles'));

        // Add a filter to the post container
        add_filter('post_class', array($this, 'post_class__addSideCommentsClassToContainer'));

        // Filter content_save_pre to add our specific inline classes
        add_filter('content_save_pre', array($this, 'addSideCommentsClassesToContent'));

        // Set up AJAX handlers for the create a new comment action
        add_action('wp_ajax_add_side_comment', array($this, 'wp_ajax_add_side_comment__AJAXHandler'));
        add_action('wp_ajax_nopriv_add_side_comment', array($this, 'wp_ajax_add_side_comment__AJAXHandler'));

        // Set up AJAX handlers for comment deletion
        add_action('wp_ajax_delete_side_comment', array($this, 'wp_ajax_delete_side_comment__AJAXHandler'));
        add_action('wp_ajax_nopriv_delete_side_comment', array($this, 'wp_ajax_delete_side_comment__AJAXHandler'));

        // Side comments shouldn't be shown in the main comment area at the bototm
        add_filter('wp-hybrid-clf_list_comments_args', array($this, 'list_comments_args__removeSidecommentsFromLinearComments'));

        // When side comments are removed, the totals are wrong on the front-end
        add_filter('get_comments_number', array($this, 'get_comments_number__adjustCommentsNumberToRemoveSidecomments'), 10, 2);

        //Set up AJAX handlers for comment voting
        add_action('wp_ajax_comment_vote_callback', array($this, 'comment_vote_callback'));
        add_action('wp_ajax_nopriv_comment_vote_callback', array($this, 'comment_vote_callback'));

        //Set up Ajax handlers for refresh nonces
        add_action('wp_ajax_refresh_nonce_callback', array($this, 'refresh_nonce_callback'));
        add_action('wp_ajax_nopriv_refresh_nonce_callback', array($this, 'refresh_nonce_callback'));

        // Get the proper template for post type texto-em-debate
        //Set up AJAX handlers for list last comments per section
        add_action('wp_ajax_last_comments_callback', array($this, 'last_comments_callback'));
        add_action('wp_ajax_nopriv_last_comments_callback', array($this, 'last_comments_callback'));

        // Get the proper template for post type texto-em-debate
        add_filter('single_template', array($this, 'get_texto_em_debate_template'));

        //Set up searchable area
        add_filter('the_content', array($this, 'addSearchableClassesToContent'), 51);
    }/* __construct() */

    /**
     * Register and enqueue the necessary scripts and styles
     *
     * @since 0.1
     *
     * @param null
     * @return null
     */

    public function wp_enqueue_scripts__loadScriptsAndStyles()
    {

        // Ensure we're on a post where we want to load our scripts/styles
        $validScreen = $this->weAreOnAValidScreen();

        if (!$validScreen) {
            return;
        }

        $this->enqueueStyle();
        $this->enqueueScripts();
        $this->localizeScripts();

    }/* wp_enqueue_scripts__loadScriptsAndStyles() */

    private function enqueueStyle()
    {
        //TODO: encontrar uma maneira de não duplicar todo o CSS
        wp_register_style('side-comments-style', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/css/side-comments-full.css');
        wp_add_inline_style('side-comments-style', $this->WPSideCommentsAdmin->getCurrentStyle());

        wp_enqueue_style('side-comments-style');
    }

    private function enqueueScripts()
    {
        wp_register_script('side-comments-script', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/js/side-comments.js', array('jquery'));
        wp_register_script('wp-side-comments-script', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/js/wp-side-comments.js', array('jquery', 'side-comments-script'), null, true);
        wp_register_script('highlight-script', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/js/jquery.highlight-5.js', array('jquery'));
        wp_register_script('texto-em-debate-script', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/js/texto-em-debate.js', array('jquery', 'highlight-script'), null, true);

        wp_enqueue_script('side-comments-script');
        wp_enqueue_script('wp-side-comments-script');
        wp_enqueue_script('highlight-script');
        wp_enqueue_script('texto-em-debate-script');
    }

    private function localizeScripts()
    {
        // Need to get some data for our JS, which we pass to it via localization
        $data = $this->getCommentsData();

        // ENsure we have a nonce for AJAX purposes
        $data['nonce'] = wp_create_nonce('side_comments_nonce');

        //create a nonce for Comment Voting
        $data['voting_nonce'] = wp_create_nonce('side_comments_voting_nonce');

        // We also need the admin url as we need to send an AJAX request to it
        // ToDo: fix this, as we need this to not be https for it to work atm
        $adminAjaxURL = admin_url('admin-ajax.php');
        $nonHTTPS = preg_replace('/^https(?=:\/\/)/i', 'http', $adminAjaxURL);
        $data['ajaxURL'] = $nonHTTPS;

        $data['containerSelector'] = apply_filters('wp_side_comments_container_css_selector', '.commentable-container');

        $data['allowUserInteraction'] = comments_open();

        $templates['comment'] = $this->getCommentTemplate();
        $templates['section'] = $this->getSectionTemplate();

        wp_localize_script('side-comments-script', 'templates', $templates);
        wp_localize_script('wp-side-comments-script', 'commentsData', $data);
    }

    private function getSectionTemplate()
    {
        return $this->WPSideCommentsAdmin->getCurrentSectionTemplate();
    }

    private function getCommentTemplate()
    {
        return $this->WPSideCommentsAdmin->getCurrentCommentTemplate();
    }

    private function checkInteractionAllowed()
    {
        if (!is_user_logged_in() && !$this->WPSideCommentsAdmin->isGuestInteractionAllowed()) {
            wp_send_json_error(array(
                'error_message' => __('Você precisa estar logado para executar esta ação.', 'wp-side-comments')
            ));
        }
    }

    /**
     * Filter the post_class which is output on the containing element of the post
     *
     * @since 0.1
     *
     * @param array $classes current post container classes
     * @return array $classes modified post container classes (with our extra side comments classes)
     */
    public function post_class__addSideCommentsClassToContainer($classes)
    {

        // Ensure we're on a post where we want to load our scripts/styles
        $validScreen = $this->weAreOnAValidScreen();

        if (!$validScreen) {
            return $classes;
        }

        if (!$classes || !is_array($classes)) {

            $classes = array();

        }

        $classes[] = 'commentable-container';

        return $classes;

    }/* post_class__addSideCommentsClassToContainer() */


    /**
     * calculates the next section id value based on existent values
     * @param $section
     */
    private function findCurrentSectionId($section)
    {
        $sectionNumber = filter_var($section->getAttribute('data-section-id'), FILTER_SANITIZE_NUMBER_INT);
        if ($sectionNumber >= self::$currentSectionID) {
            self::$currentSectionID = $sectionNumber;
        }
    }

    /**
     * Add our required classes and attributes to paragraph tags in the_content
     *
     * @since 0.1
     *
     * @param string $content the post content
     * @return string $content modified post content with our classes/attributes
     */
    public function addSideCommentsClassesToContent( $content ) {

        if ( $this->get_current_post_type() == "texto-em-debate" && $content ) {
            $content = str_replace( "\\\"", '"', $content );

            $dom = new simple_html_dom( $content );

            $elements = $dom->find( 'p.commentable-section' );

            foreach ( $elements as $key => $element ) {
                if ( $element->hasAttribute( 'id' ) ) {
                    $this->findCurrentSectionId( $element );
                }
            }

            foreach ( $elements as $element ) {
                if ( ! $element->hasAttribute( 'id' ) || ! $element->hasAttribute( 'data-section-id' ) || $element->getAttribute( 'data-section-id' ) == 0 ) {
                    self::$currentSectionID ++;
                    $element->setAttribute( 'class', 'commentable-section' );
                    $element->setAttribute( 'data-section-id', self::$currentSectionID );
                    $element->setAttribute( 'id', 'commentable-section-' . self::$currentSectionID );
                }
            }

            return $dom->save();
        }

        return $content;
    }

    public function addSearchableClassesToContent( $content ) {

        if ( $this->get_current_post_type() == "texto-em-debate" && $content ) {
            $dom = new simple_html_dom( $content );

            foreach ( $dom->childNodes() as $node ) {
                if ( $node->class == 'commentable-section' ) {
                    $node->innertext = '<span class="searchable-content">' . $node->innertext . '</span>';
                }
            }

            return $dom->save();
        }

        return $content;
    }

    /**
     * gets the current post type in the WordPress Admin
     */
    private function get_current_post_type()
    {
        global $post, $typenow, $current_screen;

        //we have a post so we can just get the post type from that
        if ($post && $post->post_type)
            return $post->post_type;

        //check the global $typenow - set in admin.php
        elseif ($typenow)
            return $typenow;

        //check the global $current_screen object - set in sceen.php
        elseif ($current_screen && $current_screen->post_type)
            return $current_screen->post_type;

        //check the post_type querystring
        elseif (isset($_REQUEST['post_type']))
            return sanitize_key($_REQUEST['post_type']);

        //check if we have the post id
        elseif (isset($_REQUEST['post']))
            return get_post_type($_REQUEST['post']);

        //last attempt try to handle data coming from wp_autosave
        $ajaxData = $_REQUEST['data']['wp_autosave'];

        //check the post_type querystring
        if (isset($ajaxData['post_type']))
            return sanitize_key($ajaxData['post_type']);

        //check if we have the post id
        elseif (isset($ajaxData['post']))
            return get_post_type($ajaxData['post']);

        return null;
    }

    /**
     * side-comments.js requires data to be passed to the JS. This method gathers the information
     * which is then passed to wp_localize_script(). We need information about the user and the comments
     * for the page we're looking at
     *
     * @since 0.1
     *
     * @param int $postID - the ID of the post for which we wish to get comment data
     * @return array $commentData - an associative array of comment data and user data
     */
    private function getCommentsData($postID = false)
    {

        // Fetch the post ID if we haven't been passed one
        if (!$postID) {

            global $post;
            $postID = (isset($post->ID)) ? $post->ID : false;

        }

        if (!$postID) {
            return false;
        }

        $commentsForThisPost = $this->getPostCommentData($postID);

        $detailsAboutCurrentUser = $this->getCurrentUserDetails();

        // start fresh
        $commentData = array();

        // Add our data if we have it
        if ($commentsForThisPost && is_array($commentsForThisPost)) {
            $commentData['comments'] = $commentsForThisPost;
        }

        if ($detailsAboutCurrentUser && is_array($detailsAboutCurrentUser)) {
            $commentData['user'] = $detailsAboutCurrentUser;
        }

        $commentData['postID'] = $postID;

        // Ship it.
        return $commentData;

    }/* getCommentsData() */

    /**
     * Create a friendly display format for comment time
     * @param $comment Comment Object
     * @return string the friendly comment time
     */
    private static function getFriendlyCommentTime($comment)
    {
        $time = strtotime($comment->comment_date_gmt);
        $time_diff = time() - $time;
        if ($time_diff >= 0 && $time_diff < 24 * 60 * 60)
            $display = sprintf(__('%s atrás'), human_time_diff($time));
        else //TODO: ajustar formato para o termo 'às' também ser recuperado do arquivo de tradução
            $display = date_i18n(get_option('date_format') . ' \à\s ' . get_option('time_format'), strtotime($comment->comment_date));

        return $display;
    }

    /**
     * Get data for a single post's comments.
     * When data is saved, the section is saved as comment meta (key = 'section' and value = integer of the section)
     *
     * @since 0.1
     *
     * @param string $param description
     * @return string|int returnDescription
     */

    private static function getPostCommentData($postID = false)
    {

        // Fetch the post ID if we haven't been passed one
        if (!$postID) {

            global $post;
            $postID = (isset($post->ID)) ? $post->ID : false;

        }

        if (!$postID) {
            return false;
        }

        // Build our args for get_comments
        $getCommentArgs = array(
            'post_id' => $postID,
            'status' => 'approve',
            'order' => 'ASC'
        );

        $comments = get_comments($getCommentArgs);

        // Do we have any?
        if (!$comments || !is_array($comments) || empty($comments)) {
            return false;
        }

        // Start fresh
        $sideCommentData = array();

        foreach ($comments as $key => $commentData) {

            $thisCommentID = $commentData->comment_ID;

            $section = get_comment_meta($thisCommentID, 'side-comment-section', true);

            $sideComment = false;
            if ($section && !empty($section)) {
                $sideComment = $section;
            }

            if (!$sideComment) {
                continue;
            }

            if (!isset($sideCommentData[$section])) {
                $sideCommentData[$section] = array();
            }

            $upvotes = get_comment_meta($commentData->comment_ID, WP_Side_Comments_Visitor::KEY_PREFIX . '_upvote', true) ?: 0;
            $downvotes = get_comment_meta($commentData->comment_ID, WP_Side_Comments_Visitor::KEY_PREFIX . '_downvote', true) ?: 0;

            $toAdd = array(
                'authorAvatarUrl' => static::get_avatar_url($commentData->comment_author_email),
                'authorName' => $commentData->comment_author,
                'comment' => $commentData->comment_content,
                'commentID' => $commentData->comment_ID,
                'authorID' => $commentData->user_id,
                'parentID' => $commentData->comment_parent,
                'karma' => $commentData->comment_karma,
                'upvotes' => $upvotes,
                'downvotes' => $downvotes,
                'time' => static::getFriendlyCommentTime($commentData)
            );

            if ($sideComment && $sideComment != '') {
                $toAdd['sideComment'] = $section;
            }

            $sideCommentData[$section][] = $toAdd;

        }

        return $sideCommentData;

    }/* getPostCommentData() */


    /**
     * Get data about the current user that we will need in side-comments js
     *
     * @since 0.1
     *
     * @param null
     * @return array $userDetails data about the user
     */
    private function getCurrentUserDetails()
    {
        $userID = get_current_user_id();

        if ($userID) {
            return static::getUserDetails($userID);
        } elseif ($this->WPSideCommentsAdmin->isGuestInteractionAllowed()) {
            return static::getDefaultuserDetails();
        } else {
            return false;
        }

    }/* getCurrentUserDetails() */


    /**
     * Default user details (Temp method)
     *
     *
     * @since 0.1
     * @todo Remove this in favour of using the correct comment_form() method to produce the markup for the comments form
     *
     * @param string $param description
     * @return string|int returnDescription
     */

    private static function getDefaultuserDetails()
    {

        // Build our output
        $userDetails = array(
            'name' => __('Anônimo', 'wp-side-comments'),
            'avatar' => static::get_avatar_url('test@test.com'),
            'id' => 9999
        );

        return $userDetails;

    }/* getDefaultuserDetails() */


    /**
     * Get data about a specified user ID
     *
     * @since 0.1
     *
     * @param int $userID The ID of a specific user
     * @return array $userDetails details about the specified user
     */

    private static function getUserDetails($userID = false)
    {

        if (!$userID) {
            return false;
        }

        $user = get_user_by('id', $userID);

        if (!$user) {
            return false;
        }

        // We need name, ID and avatar url
        $name = (isset($user->display_name)) ? $user->display_name : $user->user_login;

        $avatarURL = static::get_avatar_url($user->user_email);
        $avatarURL = (isset($getAvatarUrl) && !empty($getAvatarUrl)) ? $getAvatarUrl : includes_url('images/blank.gif');

        // Build our output
        $userDetails = array(
            'name' => $name,
            'avatar' => $avatarURL,
            'id' => $userID
        );

        return apply_filters('wp_side_comments_user_details', $userDetails, $user);

    }/* getUserDetails() */


    /**
     * I hate this method. But, at the moment, there's no proper way to get the URL only for the avatar,
     * so we're relegated to using HTML parsing. Yay. I really hope the patch for this makes it into
     * 4.0 ( https://core.trac.wordpress.org/ticket/21195 )
     *
     * @since 0.1
     *
     * @param string $email the email address of the user for which we're looking for the avatar
     * @return string the url of the avatar
     */

    private static function get_avatar_url($email)
    {

        $avatar_html = get_avatar($email, 24, 'blank');
        // strip the avatar url from the get_avatar img tag.
        preg_match('/src=["|\'](.+)[\&|"|\']/U', $avatar_html, $matches);

        if (isset($matches[1]) && !empty($matches[1])) {
            return esc_url_raw($matches[1]);
        }

        return '';

    }/* json_get_avatar_url() */


    /**
     * AJAX handler for when someone is logged in and trying to make a comment
     *
     * @since 0.1
     *
     * @param string $param description
     * @return string|int returnDescription
     */
    public function wp_ajax_add_side_comment__AJAXHandler()
    {

        if (!wp_verify_nonce($_REQUEST['nonce'], 'side_comments_nonce')) {
            wp_send_json_error(array(
                'error_message' => __('Você não pode executar esta ação. Tente novamente mais tarde.', 'wp-side-comments')
            ));
        }

        $this->checkInteractionAllowed();

        // Collect data sent to us via the AJAX request
        $postID = absint($_REQUEST['postID']);
        $sectionID = absint($_REQUEST['sectionID']);
        $commentText = strip_tags($_REQUEST['comment'], '<p><a><br>');
        $authorName = sanitize_text_field($_REQUEST['authorName']);
        $authorID = absint($_REQUEST['authorId']);
        $parentID = absint($_REQUEST['parentID']);

        $user = get_user_by('id', $authorID);

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (strlen(trim(str_replace('&nbsp;', ' ', $commentText))) == 0) {
            wp_send_json_error(array(
                'error_message' => __('Você não pode enviar um comentário vazio.', 'wp-side-comments')
            ));
        }

        if (!comments_open($postID)) {
            wp_send_json_error(array(
                'error_message' => __('As interações com este texto estão desabilitadas no momento.', 'wp-side-comments')
            ));
        }

        $commentApproval = apply_filters('wp_side_comments_default_comment_approved_status', 1);

        // The data we need for wp_insert_comment
        $wpInsertCommentArgs = array(
            'comment_post_ID' => $postID,
            'comment_author' => $authorName,
            'comment_author_email' => $user ? $user->user_email : null,
            'comment_author_url' => null,
            'comment_content' => $commentText,
            'comment_type' => 'side-comment',
            'comment_parent' => $parentID,
            'user_id' => $authorID,
            'comment_author_IP' => $ip,
            'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
            'comment_date' => null,
            'comment_approved' => $commentApproval
        );

        $newCommentID = wp_insert_comment($wpInsertCommentArgs);

        if ($newCommentID) {

            // Now we have a new comment ID, we need to add the meta for the section, stored as 'side-comment-section'
            update_comment_meta($newCommentID, 'side-comment-section', $sectionID);
            $comment = get_comment($newCommentID);
            // Setup our data which we're echoing
            $result = array(
                'type' => 'success',
                'newCommentID' => $newCommentID,
                'commentApproval' => $commentApproval,
                'commentTime' => static::getFriendlyCommentTime($comment)
            );
        } else {
            // wp_insert_comment failed
            $result = array(
                'error_message' => __('Seu comentário não pôde ser inserido', 'wp-side-comments')
            );
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($result['error_message'])) {
                wp_send_json_error($result);
            } else {
                wp_send_json_success($result);
            }
        } else {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            die;
        }

    }/* wp_ajax_add_side_comment__AJAXHandler() */

    /**
     * AJAX handler for when a comment is deleted
     *
     * @since 0.1
     *
     * @param null
     * @return null
     */
    public function wp_ajax_delete_side_comment__AJAXHandler()
    {

        if (!wp_verify_nonce($_REQUEST['nonce'], 'side_comments_nonce')) {
            exit(__('Nonce check failed', 'wp-side-comments'));
        }

        $this->checkInteractionAllowed();

        // Collect data sent to us via the AJAX request
        $postID = absint($_REQUEST['postID']);
        $commentID = absint($_REQUEST['commentID']);

        // Force delete the comment?
        $forceDelete = apply_filters('wp_side_comments_force_delete_comment', false);

        $hasDeleted = wp_delete_comment($commentID, $forceDelete);

        if ($hasDeleted) {

            // Setup our data which we're echoing
            $result = array(
                'type' => 'success',
                'forceDelete' => $forceDelete
            );

        } else {

            $result = array(
                'type' => 'failure',
                'message' => __('The comment was not deleted', 'wp-side-comments')
            );

        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

            $result = json_encode($result);
            echo $result;

        } else {

            header('Location: ' . $_SERVER['HTTP_REFERER']);

        }

        die();

    }/* wp_ajax_delete_side_comment__AJAXHandler() */

    /**
     * Method to determine if we're on the right place to load our scripts/styles and do our bits and pieces
     * basically, not admin, on a singular post/page and comments are open
     *
     * @since 0.1
     *
     * @param null
     * @return null
     */

    private function weAreOnAValidScreen()
    {
        // We don't have anything for the admin at the moment and comments are only on a single
        if (is_admin() || !is_singular()) {
            return false;
        }

        return true;
    }/* weAreOnAValidScreen() */


    /**
     * Remove side-comments from the linear-comments display - currently for the hybrid comments args
     *
     *
     * @since 0.1
     *
     * @param array $args args for wp_list_comments()
     * @return array $args modified args for wp_list_comments()
     */

    public function list_comments_args__removeSidecommentsFromLinearComments($args)
    {

        if (!apply_filters('wp_side_comments_remove_side_comments_from_linear_comments', true)) {
            return $args;
        }

        $args['walker'] = new SideCommentsWalker();

        return $args;

    }/* list_comments_args__removeSidecommentsFromLinearComments() */


    /**
     * Adjust the comments total to remove side comments from the main list at the bottom of the page (linear comments)
     *
     * @since 0.1
     *
     * @param int $count The number of comments on this post
     * @param int $post_id The post ID
     * @return $count - modified number of comments
     */

    public function get_comments_number__adjustCommentsNumberToRemoveSidecomments($count, $post_id)
    {

        if (is_admin()) {
            return $count;
        }

        $defaultPostTypes = array();

        $postTypesToAdjustCommentsFor = apply_filters('wp_side_comments_get_comments_number_post_types', $defaultPostTypes, $post_id);

        $thisPostsType = get_post_type($post_id);

        if (!in_array($thisPostsType, array_values($postTypesToAdjustCommentsFor))) {
            return $count;
        }

        // If this is being viewed by a student in the group, but not the author...
        $userID = get_current_user_id();

        $continue = apply_filters('wp_side_comments_before_adjust_comment_count', $userID, $post_id, $count);

        if (!$continue) {
            return $count;
        }

        $allComments = static::getPostCommentData($post_id);

        $linearComments = 0;

        if (!$allComments || !is_array($allComments) || empty($allComments)) {
            return $linearComments;
        }

        foreach ($allComments as $key => $comments) {
            if ($key == '') {
                $linearComments = count($comments);
            }
        }

        return $linearComments;

    }/* get_comments_number__adjustCommentsNumberToRemoveSidecomments() */

    /**
     * Ajax handler for the vote action.
     */
    public function comment_vote_callback()
    {
        check_ajax_referer('side_comments_voting_nonce', 'vote_nonce');

        $this->checkInteractionAllowed();

        $postID = absint($_POST['post_id']);
        $commentID = absint($_POST['comment_id']);
        $vote = $_POST['vote'];

        if (!in_array($vote, array('upvote', 'downvote'))) {
            $return = array(
                'error_code' => 'invalid_action',
                'error_message' => 'Ação inválida',
                'comment_id' => $commentID,
            );

            wp_send_json_error($return);
        }

        if (!comments_open($postID)) {
            wp_send_json_error(array(
                'error_message' => __('As interações com este texto estão desabilitadas no momento.', 'wp-side-comments')
            ));
        }

        $result = $this->commentVote($this->getVisitor()->getId(), $commentID, $vote);

        if (array_key_exists('error_message', $result)) {
            wp_send_json_error($result);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Processes the comment vote logic.
     *
     * @param $vote
     * @param $commentID
     *
     * @param $userID
     *
     * @return array
     */
    private function commentVote($userID, $commentID, $vote)
    {

        $voteIsValid = $this->getVisitor()->isVoteValid($commentID, $vote);

        if (is_wp_error($voteIsValid)) {

            $errorCode = $voteIsValid->get_error_code();
            $errorMsg = $voteIsValid->get_error_message($errorCode);

            $return = array(
                'error_code' => $errorCode,
                'error_message' => $errorMsg,
                'comment_id' => $commentID,
            );

            return $return;

        }

        $commentKarma = $this->updateCommentKarma($commentID, $this->getVoteValue($vote));
        $fullKarma = $this->updateFullKarma($commentID, $vote);
        $this->getVisitor()->logVote($commentID, $vote);

        do_action('wp_side_comments_vote', $userID, $commentID, $vote);

        $return = array(
            'success_message' => 'Obrigado pelo seu voto!',
            'weight' => $commentKarma,
            'full_karma' => $fullKarma,
            'comment_id' => $commentID
        );

        return $return;
    }

    /**
     * @return WP_Side_Comments_Visitor
     */
    public function getVisitor()
    {
        return $this->visitor;
    }

    /**
     * Initialize the visitor object.
     *
     * @param WP_Side_Comments_Visitor $visitor
     */
    public function setVisitor($visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * Updates the comment weight value in the database.
     *
     * @param $vote
     * @param $commentID
     *
     * @return int
     */
    private function updateCommentKarma($commentID, $voteValue)
    {

        $comment = get_comment($commentID, ARRAY_A);

        $comment['comment_karma'] += $voteValue;

        wp_update_comment($comment);

        $comment = get_comment($commentID, ARRAY_A);

        /**
         * Fires once a comment has been updated.
         *
         * @param array $comment The comment data array.
         */
        do_action('wp_side_comments_update_comment_weight', $comment);

        return $comment['comment_karma'];
    }

    public function get_texto_em_debate_template($single_template)
    {
        global $post;
        if ($post->post_type == 'texto-em-debate') {
            $single_template = CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'templates/single-texto-em-debate-template.php';
        }
        return $single_template;
    }

    /**
     * Register the full count of upvote/downvote
     * @param $commentID
     * @param $vote
     */
    private function updateFullKarma($commentID, $vote)
    {
        $karma = get_comment_meta($commentID, WP_Side_Comments_Visitor::KEY_PREFIX . '_' . $vote, true);
        if ($karma) {
            $karma++;
        } else {
            $karma = 1;
        }

        update_comment_meta($commentID, WP_Side_Comments_Visitor::KEY_PREFIX . '_' . $vote, $karma);

        return $karma;
    }

    /**
     * Returns the value of an upvote or downvote.
     *
     * @param $type ( 'upvote' or 'downvote' )
     *
     * @return int|mixed|void
     */
    private function getVoteValue($type)
    {
        switch ($type) {
            case 'upvote':
                $value = apply_filters('wp_side_comments_upvote_value', 1);
                break;
            case 'downvote':
                $value = apply_filters('wp_side_comments_downvote_value', -1);
                break;
            default:
                $value = new \WP_Error('invalid_vote_type', 'Tipo de voto inválido');
                break;
        }

        return $value;
    }

    public function refresh_nonce_callback()
    {
        $data['nonce'] = wp_create_nonce('side_comments_nonce');
        $data['voting_nonce'] = wp_create_nonce('side_comments_voting_nonce');
        wp_send_json_success($data);
    }


    public function last_comments_callback()
    {
        check_ajax_referer('side_comments_last_comments_nonce', 'last_comments_nonce');

        $postID = isset($_POST['post_id']) ? absint($_POST['post_id']) : false;

        if (!$postID) {
            wp_send_json_error(array('error_message' => 'Nenhum post_id informado'));
        }

        $result = $this->listLastComments($postID);
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('error_message' => 'Nenhum comentário encontrado'));
        }
    }

    private function listLastComments($postID)
    {
        $blocks = array();

        $sectionIDs = $this->findLastCommentedSections($postID);
        if ($sectionIDs) {
            $comments = $this->findLastComments($postID, $sectionIDs);
            $postSections = $this->getPostSections($postID, $sectionIDs);

            foreach ($sectionIDs as $sectionID) {
                if (isset($comments[$sectionID]) && isset($postSections[$sectionID])) {
                    $block = $this->createLastCommentsBlock($sectionID, $comments[$sectionID], $postSections[$sectionID]);
                    if ($block) {
                        $blocks[] = $block;
                    }
                }
            }
        }
        return $blocks;
    }

    private function createLastCommentsBlock($sectionID, $comments, $postSection)
    {
        $block = array(
            'section_id' => $sectionID,
            'section_text' => $postSection,
            'comments' => array()
        );

        foreach ($comments as $comment) {
            $block['comments'][] = $this->parseComment($comment);
        }

        return $block;
    }

    private function parseComment($comment)
    {
        return array(
            'id' => $comment->comment_ID,
            'author' => $comment->comment_author,
            'comment_text' => $comment->comment_content,
            'date' => $this->getFriendlyCommentTime($comment),
            'timestamp' => $comment->comment_date,
        );
    }

    private function getPostSections($postID, array $sections)
    {
        $postSections = array();
        $post = get_post($postID);

        if (!$post) {
            return false;
        }

        $dom = new simple_html_dom($post->post_content);

        $elements = $dom->find('p');

        foreach ($elements as $key => $element) {
            $sectionID = $element->hasAttribute('data-section-id') ? $element->getAttribute('data-section-id') : false;
            if ($sectionID && in_array($sectionID, $sections)) {
                $postSections[$sectionID] = $element->plaintext;
            }

        }

        return $postSections;
    }

    private function findLastComments($postID, array $sections, $commentsPerSection = 3)
    {
        $args = array(
            'post_id' => $postID,
            'meta_key' => 'side-comment-section',
            'number' => $commentsPerSection
        );

        $comments = array();

        foreach ($sections as $sectionID) {
            $args['meta_value'] = $sectionID;
            $comments[$sectionID] = get_comments($args);
        }

        return $comments;
    }

    private function findLastCommentedSections($postID, $numberOfSections = 3)
    {
        $args = array(
            'post_id' => $postID,
            'meta_key' => 'side-comment-section',
            'number' => 1
        );
        $sections = array();

        for (; $numberOfSections > 0; $numberOfSections--) {
            if ($sections) {
                $args['meta_query'] = $qryArgs = array(
                    'key' => 'side-comment-section',
                    'value' => $sections,
                    'compare' => 'NOT IN'
                );
            }

            $comments = get_comments($args);
            if ($comments)
                $sections[] = $comments[0]->meta_value;
        }

        return $sections;
    }
}/* class CTLT_WP_Side_Comments */

//Plugin initializer
function wpsc_init_side_comments()
{
    global $WPSideCommentsAdmin;
    global $CTLT_WP_Side_Comments;
    $CTLT_WP_Side_Comments = new CTLT_WP_Side_Comments($WPSideCommentsAdmin);

    if (is_user_logged_in()) {
        $visitor = new WP_Side_Comments_Visitor_Member(get_current_user_id());
    } elseif ($WPSideCommentsAdmin->isGuestInteractionAllowed()) {
        $visitor = new WP_Side_Comments_Visitor_Guest($_SERVER['REMOTE_ADDR'], true);
    } else {
        return;
    }

    if (!($CTLT_WP_Side_Comments->getVisitor() instanceof WP_Side_Comments_Visitor)) {
        $CTLT_WP_Side_Comments->setVisitor($visitor);
    }
}

//register initializer hook
add_action('init', 'wpsc_init_side_comments', 11);