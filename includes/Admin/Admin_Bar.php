<?php

namespace BitPress\BIT_WC_ZOHO_CRM\Admin;

/**
 * The admin menu and page handler class
 */
class Admin_Bar
{
    public function register()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        /*  */
    }

    /**
     * Register the admin menu
     *
     * @return void
     */
    public function register_admin_menu()
    {
        global $submenu;

        $capability = apply_filters('bit_wc_zoho_crm_form_access_capability', 'manage_options');

        $hook = add_menu_page(__('WC-2-ZCRM | A connector of WooCommerce to Zoho CRM by BitPress', 'bit_wc_zoho_crm'), 'WC-2-ZCRM', $capability, 'bit_wc_zoho_crm', [$this, 'table_home_page'], 'data:image/svg+xml;base64,' . base64_encode('<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256"><defs><style>.cls-1{fill:#fff;}</style></defs><rect class="cls-1" width="256" height="256" rx="40"/><path d="M87.39,180.06h0ZM77.88,93.21V81.47a4.28,4.28,0,0,0-4.28-4.28H18.23a3.93,3.93,0,0,0-3.92,3.92V91.78a3.93,3.93,0,0,0,3.92,3.93H55L13.66,161a3.92,3.92,0,0,0-.61,2.1v13A3.93,3.93,0,0,0,17,180.06h63a31.36,31.36,0,0,1-4-1.83c-5.51-3-11.43-7.29-14.34-13-.35-.69-.68-1.41-1-2.14v0a4,4,0,0,0-3.63-2.41h-21l14.56-23L77.22,95.5A4.32,4.32,0,0,0,77.88,93.21Z"/><path d="M179.43,78h9a4.28,4.28,0,0,1,4.11,3.1l18.62,65,12.45-64.59a4.28,4.28,0,0,1,4.2-3.47h10.88a4.28,4.28,0,0,1,4.17,5.24l-21.53,93.46a4.29,4.29,0,0,1-4.18,3.32H205.77a4.28,4.28,0,0,1-4.15-3.23l-17.28-68.06-18.1,68.11a4.29,4.29,0,0,1-4.14,3.18H150.7a4.27,4.27,0,0,1-4.17-3.32L125,83.28A4.29,4.29,0,0,1,129.17,78H140a4.28,4.28,0,0,1,4.2,3.47l12.45,64.59,18.62-65A4.3,4.3,0,0,1,179.43,78Z"/><path d="M135.89,149.36a4.19,4.19,0,0,1,0,2.11,47.86,47.86,0,0,1-5.38,12.42,34,34,0,0,1-12.5,12,35,35,0,0,1-17.14,4.14A49.92,49.92,0,0,1,90.29,179a36.39,36.39,0,0,1-9.41-3.41,29.7,29.7,0,0,1-12.62-13c-.36-.69-.69-1.41-1-2.14A46.86,46.86,0,0,1,63.91,142v-5.88l26.54-41.9V77h-.07a49.73,49.73,0,0,1,10.44-1A35.24,35.24,0,0,1,118,80.08a6.65,6.65,0,0,1,.58.33,4.31,4.31,0,0,1,2.06,2.76l5.24,22.74a4.28,4.28,0,0,1-4.17,5.24h-1a4.27,4.27,0,0,1-4.06-3,24.41,24.41,0,0,0-2.67-5.68,16.74,16.74,0,0,0-5.69-5.47,14.85,14.85,0,0,0-7.46-1.89,18,18,0,0,0-9.2,2.21,14.32,14.32,0,0,0-5.79,6.46,23.82,23.82,0,0,0-2,10.2v28a23.55,23.55,0,0,0,2,10.17,14.32,14.32,0,0,0,5.79,6.42,18,18,0,0,0,9.2,2.21,15.41,15.41,0,0,0,7.57-1.86,15.81,15.81,0,0,0,5.68-5.43,22.87,22.87,0,0,0,2.6-5.69,4.28,4.28,0,0,1,4.08-3h10.69a4.26,4.26,0,0,1,4.17,3.32Z"/></svg>'), 56);

        add_action('load-' . $hook, [$this, 'load_assets']);
    }

    /**
     * Load the asset libraries
     *
     * @return void
     */
    public function load_assets()
    {
        /*  require_once dirname( __FILE__ ) . '/class-form-builder-assets.php';
        new BIT_WC_ZOHO_CRM_Form_Builder_Assets(); */
    }

    /**
     * The contact form page handler
     *
     * @return void
     */
    public function table_home_page()
    {
        require_once BIT_WC_ZOHO_CRM_PLUGIN_DIR_PATH . '/views/view-root.php';

        /* echo plugin_basename( BIT_WC_ZOHO_CRM_PLUGIN_MAIN_FILE );
      $query = new WP_Query();
      $forms = $query->get_posts();
      var_dump($forms); */
        global $wp_rewrite;
        $api = [
            'base'      => get_rest_url() . 'bitwczoho/v1',
            'separator' => $wp_rewrite->permalink_structure ? '?' : '&'
        ];
        $parsed_url = parse_url(get_admin_url());
        //   echo get_admin_url();
        $base_apth_admin = str_replace($parsed_url['scheme'] . '://' . $parsed_url['host'], null, get_admin_url());
        wp_enqueue_script('bit_wc_zoho_crm-admin-script', BIT_WC_ZOHO_CRM_ASSET_URI . '/js/index.js');
        $bit_wc_zoho_crm = apply_filters('bit_wc_zoho_crm_localized_script', [
            'nonce'           => wp_create_nonce('bit_wc_zoho_crm'),
            'confirm'         => __('Are you sure?', 'bit_wc_zoho_crm'),
            'isPro'           => false,
            'routeComponents' => ['default' => null],
            'mixins'          => ['default' => null],
            'assetsURL'       => BIT_WC_ZOHO_CRM_ASSET_URI . '/js/',
            'baseURL'         => $base_apth_admin . 'admin.php?page=bit_wc_zoho_crm#/',
            'ajaxURL'         => admin_url('admin-ajax.php'),
            'api'             => $api,
        ]);

        wp_localize_script('bit_wc_zoho_crm-admin-script', 'bit_wc_zoho_crm', $bit_wc_zoho_crm);
    }

    /**
     * Admin footer text.
     *
     * Fired by `admin_footer_text` filter.
     *
     * @since 1.3.5
     *
     * @param string $footer_text The content that will be printed.
     *
     * @return string The content that will be printed.
     **/
    public function admin_footer_text($footer_text)
    {
        $current_screen = get_current_screen();
        $is_bit_wc_zoho_crms_screen = ($current_screen && false !== strpos($current_screen->id, 'bit_wc_zoho_crm'));

        if ($is_bit_wc_zoho_crms_screen) {
            $footer_text = sprintf(
                __('If you like %1$s please leave us a %2$s rating. A huge thank you from %3$s in advance!', 'bit_wc_zoho_crm'),
                '<strong>' . __('bit_wc_zoho_crm', 'bit_wc_zoho_crm') . '</strong>',
                '<a href="https://wordpress.org/support/plugin/bit_wc_zoho_crm/reviews/" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
                '<strong>Bit Press</strong>'
            );
        }

        return $footer_text;
    }
}
