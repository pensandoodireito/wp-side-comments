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

    const SETTINGS_SECTION_CUSTOM_TEMPLATES_ID = 'wp-side-comments-custom-templates';
    const SETTINGS_SECTION_CUSTOM_TEMPLATES_TITLE = 'Templates Personalizados';

    const SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID = 'wp-side-comments-custom-templates-field-section';
    const SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_TITLE = 'Personalize o template da seção de comentários:';
    const SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID = 'wp-side-comments-custom-templates-field-comment';
    const SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_TITLE = 'Personalize o template do comentário:';

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
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function init()
    {
        $this->options = get_option(self::SETTINGS_OPTION_NAME, array());
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
        //TODO: recuperar o HTML de outro local
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
            array($this, 'input_validate')
        );

        add_settings_section(
            self::SETTINGS_SECTION_GUESTS_INTERACTION_ID,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_TITLE,
            array($this, 'print_section_guests_interaction_info'),
            self::SETTINGS_PAGE_NAME
        );

        add_settings_field(
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_TITLE,
            array($this, 'print_guests_interaction_field_callback'),
            self::SETTINGS_PAGE_NAME,
            self::SETTINGS_SECTION_GUESTS_INTERACTION_ID
        );

        add_settings_section(
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_ID,
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_TITLE,
            array($this, 'print_section_custom_templates_info'),
            self::SETTINGS_PAGE_NAME
        );

        add_settings_field(
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID,
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_TITLE,
            array($this, 'print_section_custom_templates_field_section'),
            self::SETTINGS_PAGE_NAME,
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_ID
        );

        add_settings_field(
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID,
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_TITLE,
            array($this, 'print_section_custom_templates_field_comment'),
            self::SETTINGS_PAGE_NAME,
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_ID
        );
    }

    /**
     * Validates user input
     * @param $input
     * @return mixed|void
     */
    public function input_validate($input)
    {
        $validatedInput = array();
        if (isset($input[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID])) {
            $value = $input[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID];
            if (in_array($value, self::$SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALID_VALUES)) {
                $validatedInput[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID] = $value;
            } else {
                add_settings_error(self::SETTINGS_OPTION_NAME, 'invalid_value', 'Por favor escolha uma opção válida no campo "' . self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_TITLE . '".', $type = 'error');
            }
        }

        if (isset($input[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID])) {
            $value = $input[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID];
            $validatedInput[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID] = $value;
        }

        if (isset($input[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID])) {
            $value = $input[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID];
            $validatedInput[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID] = $value;
        }

        return apply_filters('wp_side_comments_input_validate', $validatedInput, $input);
    }

    /**
     * Display the validation errors and update messages
     */
    function admin_notices()
    {
        settings_errors();
    }

    /**
     * Print the guests interaction section text
     */
    public function print_section_guests_interaction_info()
    {
        //TODO: recuperar texto de outro lugar
        print 'Escolha se você deseja permitir que os usuários visitantes interajam nos textos em debate: <br/>
                - Caso você escolha <b>SIM</b> qualquer usuário poderá comentar e votar nos comentários do texto; <br/>
                - Caso escolha <b>NÃO</b> apenas os usuários logados no site poderão comentar e votar nos comentários do texto.';
    }

    /**
     * Print the custom templates section text
     */
    public function print_section_custom_templates_info()
    {
        //TODO: recuperar texto de outro lugar
        print 'Personalize a exibição do bloco de comentários laterais.';
    }

    /**
     * Prints the value of allow guest interaction
     */
    public function print_guests_interaction_field_callback()
    {
        //TODO: recuperar HTML de outro local
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

    /**
     * prints the value of the section's template
     */
    public function print_section_custom_templates_field_section()
    {
        //TODO: recuperar HTML de outro local
        //TODO: implementar editor de texto com highlight para html
        printf(
            '<textarea class="section" id="%s" name="%s[%s]">%s</textarea>',
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID,
            self::SETTINGS_OPTION_NAME,
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID,
            $this->getCurrentSectionTemplate()
        );
    }

    /**
     * prints the value of comment's template
     */
    public function print_section_custom_templates_field_comment()
    {
        //TODO: recuperar HTML de outro local
        //TODO: implementar editor de texto com highlight para html
        printf(
            '<textarea class="section" id="%s" name="%s[%s]">%s</textarea>',
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID,
            self::SETTINGS_OPTION_NAME,
            self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID,
            $this->getCurrentCommentTemplate()
        );
    }

    /**
     * Find the current template for comment's section
     *
     * @return string the template
     */
    public function getCurrentSectionTemplate()
    {
        //TODO: considerar opçao de usar o template default mesmo com um template diferente cadastrado no banco de dados
        if (isset($this->options[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID])) {
            return $this->options[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_SECTION_ID];
        } else {
            return file_get_contents(CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'templates/section.html');
        }
    }

    /**
     * Find the current template for comment
     *
     * @return string the template
     */
    public function getCurrentCommentTemplate()
    {
        //TODO: considerar opçao de usar o template default mesmo com um template diferente cadastrado no banco de dados
        if (isset($this->options[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID])) {
            return $this->options[self::SETTINGS_SECTION_CUSTOM_TEMPLATES_FIELD_COMMENT_ID];
        } else {
            return file_get_contents(CTLT_WP_SIDE_COMMENTS_PLUGIN_PATH . 'templates/comment.html');
        }
    }

    /**
     * Checks whether a guest user is able to interact or not
     *
     * @return bool returns TRUE if the user is able to interact, FALSE otherwise
     */
    public function isGuestInteractionAllowed()
    {
        return isset($this->options[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID])
        && $this->options[self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_ID] == self::SETTINGS_SECTION_GUESTS_INTERACTION_FIELD_VALUE_ALLOW;
    }
}

global $WPSideCommentsAdmin;
$WPSideCommentsAdmin = new WP_Side_Comments_Admin();