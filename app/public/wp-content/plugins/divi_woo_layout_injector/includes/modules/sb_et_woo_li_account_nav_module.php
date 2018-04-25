<?php

class sb_et_woo_li_account_nav_module extends ET_Builder_Module
{
    function init()
    {
        $this->name = __('Woo Account Navigation', 'et_builder');
        $this->slug = 'et_pb_woo_account_nav';

        $this->whitelisted_fields = array(
            'title',
            'admin_label',
            'module_id',
            'module_class',
        );

        $this->options_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_settings' => esc_html__('Main Settings', 'et_builder'),
                ),
            ),
        );

        $this->fields_defaults = array();
        $this->main_css_element = '%%order_class%%';

        $this->advanced_options = array(
            'fonts' => array(
                'headings' => array(
                    'label' => esc_html__('Title', 'et_builder'),
                    'css' => array(
                        'main' => "{$this->main_css_element} h2.module_title",
                    ),
                    'font_size' => array('default' => '30px'),
                    'line_height' => array('default' => '1.5em'),
                ),
                'links' => array(
                    'label' => esc_html__('Links', 'et_builder'),
                    'css' => array(
                        'main' => "{$this->main_css_element} a",
                    ),
                    'font_size' => array('default' => '14px'),
                    'line_height' => array('default' => '1.4em'),
                ),
            ),
            'background' => array(
                'settings' => array(
                    'color' => 'alpha',
                ),
            ),
            'border' => array(),
            'custom_margin_padding' => array(
                'css' => array(
                    'important' => 'all',
                ),
            ),
        );

        $this->custom_css_options = array();
    }

    function get_fields()
    {
        $fields = array(
            'title' => array(
                'label' => __('Title', 'et_builder'),
                'type' => 'text',
                'toggle_slug' => 'main_settings',
                'description' => __('If you want a title on the module then use this box and an H3 will be added above the module content.', 'et_builder'),
            ),

        );

        return $fields;
    }

    function shortcode_callback($atts, $content = null, $function_name)
    {

        if (is_admin()) {
            return;
        }

        $module_id = $this->shortcode_atts['module_id'];
        $module_class = $this->shortcode_atts['module_class'];
        $title = $this->shortcode_atts['title'];

        $output = '';

        $module_class = ET_Builder_Element::add_module_order_class($module_class, $function_name);

        //////////////////////////////////////////////////////////////////////

        ob_start();

        if ($title) {
            echo '<h3 class="module_title">' . $title . '</h3>';
        }

        do_action( 'woocommerce_before_account_navigation' );

        echo '<nav class="woocommerce-MyAccount-navigation">
            <ul>';

        foreach ( wc_get_account_menu_items() as $endpoint => $label ) {
            echo '<li class="' . wc_get_account_menu_item_classes( $endpoint ) . '">
                        <a href="' . esc_url( wc_get_account_endpoint_url( $endpoint ) ) . '">' . esc_html( $label ) . '</a>
                    </li>';
        }

        echo '</ul>
        </nav>';

        do_action( 'woocommerce_after_account_navigation' );

        $content = ob_get_clean();

        //////////////////////////////////////////////////////////////////////

        if ($content) {
            $output = '<div ' . ($module_id ? 'id="' . esc_attr($module_id) . '"' : '') . ' class="' . $module_class . ' clearfix ' . ($title ? 'has_title':'') . ' et_pb_module et_pb_woo_account_nav">' . $content . '</div>';
        }

        return $output;
    }
}

new sb_et_woo_li_account_nav_module();

?>