<?php

/**
 * Created by PhpStorm.
 * User: josafa filho <josafafilho15@gmail.com>
 * Date: 21/09/15
 * Time: 09:59
 */
class WP_Side_Comments_Admin
{

    const SETTINGS_PAGE_SLUG = 'wp-side-comments-settings';
    const SETTINGS_PAGE_TITLE = 'WP Side Comments';
    const SETTINGS_PAGE_NAME = 'wp-side-comments-options-page';

    const SETTINGS_OPTIONS_GROUP = 'wp-side-comments-options-group';
    const SETTINGS_OPTION_NAME = 'wp-side-comments-options';

    const SETTINGS_SECTION_GUESTS_INTERACTION_ID = 'wp-side-comments-allow-guests-interaction';
    const SETTINGS_SECTION_GUESTS_INTERACTION_TITLE = 'Interações de usuários visitantes';

    const SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID = 'wp-side-comments-allow-guests-interaction-field';
    const SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_TITLE = 'Permitir interações de usuários visitantes?';
    const SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_ALLOW = 'S';
    const SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_DENY = 'N';

    private static $SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALID_VALUES = array(
        self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_ALLOW,
        self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_DENY
    );

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles()
    {
        wp_register_style('wp-side-comments-admin-style', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL . 'includes/css/wp-side-comments-admin.css');
        wp_enqueue_style('wp-side-comments-admin-style');
    }

    public function add_plugin_page()
    {
        add_menu_page(
            self::SETTINGS_PAGE_TITLE,
            self::SETTINGS_PAGE_TITLE,
            'manage_options',
            self::SETTINGS_PAGE_SLUG,
            array($this, 'create_admin_page'),
            'dashicons-format-chat'
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        $this->options = get_option(self::SETTINGS_OPTION_NAME, array());
        ?>
        <div class="wrap">
            <h2><?= self::SETTINGS_PAGE_TITLE ?> </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields(self::SETTINGS_OPTIONS_GROUP);
                do_settings_sections(self::SETTINGS_PAGE_NAME);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            self::SETTINGS_OPTIONS_GROUP,
            self::SETTINGS_OPTION_NAME,
            array($this, 'wp_side_comments_input_validate')
        );

        add_settings_section(
            self::SETTINGS_SECTION_GUESTS_INTERACTION_ID,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_TITLE,
            array($this, 'print_section_guests_interaction_info'),
            self::SETTINGS_PAGE_NAME // Page
        );

        add_settings_field(
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_TITLE,
            array($this, 'print_guests_interaction_field_callback'),
            self::SETTINGS_PAGE_NAME,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_ID
        );
    }

    public function wp_side_comments_input_validate($input)
    {
        $validatedInput = array();
        if (isset($input[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID])) {
            $value = $input[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID];
            if (in_array($value, self::$SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALID_VALUES)) {
                $validatedInput[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID] = $value;
            }
        }
        return apply_filters('wp_side_comments_input_validate', $validatedInput, $input);
    }

    /**
     * Print the Section text
     */
    public function print_section_guests_interaction_info()
    {
        print 'Escolha se você deseja permitir que os usuários visitantes interajam nos textos em debate: <br/>
                - Caso você escolha <b>SIM</b> qualquer usuário poderá comentar e votar nos comentários do texto; <br/>
                - Caso escolha <b>NÃO</b> apenas os usuários logados no site poderão comentar e votar nos comentários do texto.';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function print_guests_interaction_field_callback()
    {
        printf(
            '<span class="radio"><input type="radio" id="%s" name="%s[%s]" value="%s" %s>SIM</span>',
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID . '-allow',
            self::SETTINGS_OPTION_NAME,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_ALLOW,
            $this->isGuestInteractionAllowed() ? 'checked' : ''
        );

        printf(
            '<span class="radio"><input type="radio" id="%s" name="%s[%s]" value="%s" %s>NÃO</span> ',
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID . '-deny',
            self::SETTINGS_OPTION_NAME,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_DENY,
            !$this->isGuestInteractionAllowed() ? 'checked' : ''
        );
    }

    public function isGuestInteractionAllowed()
    {
        return isset($this->options[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID])
        && $this->options[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID] == self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_ALLOW;
    }
}

if (is_admin())
    $WPSideCommentsAdmin = new WP_Side_Comments_Admin();