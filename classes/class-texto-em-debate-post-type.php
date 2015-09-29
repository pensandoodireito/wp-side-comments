<?php

/**
 * Created by PhpStorm.
 * User: josafa
 * Date: 21/09/15
 * Time: 17:24
 */

/**
 * Class Texto_Em_Debate_Post_Type
 * Custom Post Type
 */
class Texto_Em_Debate_Post_Type
{

    // cores pré definidas para o texto em debate
    public static $cores_texto_debate_editor = '"FF1212", "Vermelho A", "984444", "Vermelho B", "FF7112", "Laranja A", "FFA012",  "Laranja A", "986644", "Laranja B", "FFFF12", "Amarelo A", "987644", "Amarelo B", "988744", "Amarelo C", "B2E710", "Verde A", "0FD00F", "Verde B", "989844", "Verde C", "7C9040", "Verde D", "3C873C", "Verde E", "0F9FD0", "Azul A", "0F5FD0",  "Azul B", "2F0FD0", "Azul C", "3C7587", "Azul D", "3C5C87", "Azul E", "8F0FD0", "Violeta A", "493C87", "Violeta B", "6E3C87", "Violeta C", "DB0F86", "Rosa A", "8B3E6B", "Rosa B"';

    public function __construct()
    {
        //register the custom post type
        $this->wp_side_comments_post_types();

        //register post type actions and filters
        add_action('the_content', array($this, 'wp_side_comments_parse_headers'));
        add_filter('texto_em_debate_menu_items', array($this, 'wp_side_comments_create_menu_items'));
        add_filter('tiny_mce_before_init', array($this, 'editor_visual_novas_cores'));
    }

    /**
     * Registro do Custom Post Type "Texto em Debate"
     */
    function wp_side_comments_post_types()
    {
        $domain = 'wp_side_comments';

        $labels_texto = array(
            'name' => _x('Textos em debate', 'Textos em debate', $domain),
            'singular_name' => _x('Texto em debate', 'Texto em debate', $domain),
            'menu_name' => __('Textos em debate', $domain),
            'parent_item_colon' => __('Texto pai:', $domain),
            'all_items' => __('Todos os textos', $domain),
            'view_item' => __('Ver texto', $domain),
            'add_new_item' => __('Adicionar texto', $domain),
            'add_new' => __('Novo', $domain),
            'edit_item' => __('Editar texto', $domain),
            'update_item' => __('Atualizar texto', $domain),
            'search_items' => __('Procurar texto', $domain),
            'not_found' => __('Não encontrado', $domain),
            'not_found_in_trash' => __('Não encontrado na lixeira', $domain),
        );
        $args_texto = array(
            'label' => __('Textos em debate', $domain),
            'description' => __('Texto a ser posto em debate por parágrafo', $domain),
            'labels' => $labels_texto,
            'supports' => array('title', 'editor', 'author', 'excerpt', 'trackbacks', 'comments', 'revisions', 'page-attributes'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 5,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'page',
            'rewrite' => true,
            'permalink_epmask' => 'EP_PERMALINK ',
            'query_var' => true
        );
        register_post_type('texto-em-debate', $args_texto);
    }

    /**
     * Filtra o conteúdo HTML e gera os itens de menu
     * @param $content Conteúdo HTML do texto em debate
     * @return string HTML com os itens de menu
     */
    public function wp_side_comments_create_menu_items($content)
    {
        return $this->wp_side_comments_get_toc($content);
    }

    /**
     * Incluí novas cores no editor visual
     *
     * @param $init
     * @return mixed
     */
    public function editor_visual_novas_cores($init)
    {

        if (get_post_type() == "texto-em-debate") {

            $init['textcolor_map'] = '[' . self::$cores_texto_debate_editor . ']'; // build colour grid default+custom colors
            $init['textcolor_rows'] = 3; // enable 6th row for custom colours in grid
            return $init;
        } else {
            return $init;
        }
    }

    /**
     * Gera slug de um texto aleatório
     *
     * @param $text
     * @return mixed|string
     */
    public function slugfy($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Obtem o índice (table of contents) de um conteúdo html passado
     *
     * @param $content
     * @return string
     */
    public function wp_side_comments_get_toc($content)
    {
        $output = "";
        $matches = array();

        if (preg_match_all('/(<h([1-6]{1})[^>]*)>(.*)<\/h\2>/msuU', $content, $matches, PREG_SET_ORDER)) {
            $output .= '<select class="form-control">';
            $level = 1;

            foreach ($matches as $match) {

                $item_toc = '<option value="' . $this->slugfy($match[3]) . '">' . $match[3] . '</option>';

                if ($match[2] > $level) {

                    for (; $level < $match[2]; $level++) {
                        $output .= "<optgroup>";
                        $output .= $item_toc;
                    }
                } elseif ($match[2] < $level) {
                    for (; $level > $match[2]; $level--) {
                        $output .= "</optgroup>";
                    }

                    $output .= $item_toc;

                } else {
                    $output .= $item_toc;
                }
            }

            $output .= "</select>";

        }

        return $output;
    }

    /**
     * Filtra os cabeçalhos do texto (tags H) e gera os ids pra cada cabeçalho
     * @param $content Conteúdo HTML do texto em debate
     * @return mixed Conteúdo HTML do texto em debate, após filtro
     */
    public function wp_side_comments_parse_headers($content)
    {
        if (get_post_type() == "texto-em-debate") {
            return preg_replace_callback('/(<h([1-6]{1})([^>]*)>)(.*)<\/h\2>/msuU', array($this, 'wp_side_comments_parse_head'), $content);
        } else {
            return $content;
        }
    }

    /**
     * Função de callback para gerar IDs para cada cabeçalho de acordo com o conteúdo (usa método slugfy)
     * @param $matches gerados pelo preg_replace_callback
     * @return string novo conteúdo de cada cabeçalho
     */
    public function wp_side_comments_parse_head($matches)
    {
        return "<h{$matches[2]} {$matches[3]} id='" . str_replace('--', '-', str_replace('8211', '', $this->slugfy($matches[4]))) . "'>{$matches[4]}</h{$matches[2]}>";
    }
}