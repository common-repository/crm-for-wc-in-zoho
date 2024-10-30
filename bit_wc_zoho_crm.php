<?php

use BitPress\BIT_WC_ZOHO_CRM\Plugin;

/**
 * Plugin Name: Integration of WooCommerce and Zoho CRM
 * Requires Plugins: woocommerce
 * Plugin URI:  https://formsintegrations.com/woocommerce-integration-with-zoho-crm
 * Description: A connector of WooCommerce to Zoho CRM by Bit Apps
 * Version:     2.4
 * Author:      Forms Integrations
 * Author URI:  https://formsintegrations.com
 * Text Domain: bit_wc_zoho_crm
 * Domain Path: /languages
 * License: GPLv2 or later
 */

/***
 *
 *If try to direct access  plugin folder it will Exit
 *
 **/
if (!defined('ABSPATH')) {
    exit;
}

// Define most essential constants.
define('BIT_WC_ZOHO_CRM_VERSION', '2.4');
define('BIT_WC_ZOHO_CRM_PLUGIN_MAIN_FILE', __FILE__);
define('BIT_WC_ZOHO_CRM_PLUGIN_BASENAME', plugin_basename(BIT_WC_ZOHO_CRM_PLUGIN_MAIN_FILE));
define('BIT_WC_ZOHO_CRM_PLUGIN_DIR_PATH', plugin_dir_path(BIT_WC_ZOHO_CRM_PLUGIN_MAIN_FILE));
define('BIT_WC_ZOHO_CRM_ROOT_URI', plugins_url('', BIT_WC_ZOHO_CRM_PLUGIN_MAIN_FILE));
define('BIT_WC_ZOHO_CRM_ASSET_URI', BIT_WC_ZOHO_CRM_ROOT_URI . '/assets');

/**
 * Handles plugin activation.
 *
 * Throws an error if the plugin is activated on an older version than PHP 5.4.
 *
 * @access private
 *
 * @param bool $network_wide Whether to activate network-wide.
 */
function bit_wc_zoho_crm_activate_plugin($network_wide)
{
    if (version_compare(PHP_VERSION, '5.6', '<')) {
        wp_die(
            esc_html__('WC-2-ZCRM requires PHP version 5.6', 'bit_wc_zoho_crm'),
            esc_html__('Error Activating', 'bit_wc_zoho_crm')
        );
    }

    if ($network_wide) {
        return;
    }

    do_action('bit_wc_zoho_crm_activation', $network_wide);
}

register_activation_hook(__FILE__, 'bit_wc_zoho_crm_activate_plugin');

/**
 * Handles plugin deactivation.
 *
 * @access private
 *
 * @param bool $network_wide Whether to deactivate network-wide.
 */
function bit_wc_zoho_crm_deactivate_plugin($network_wide)
{
    if (version_compare(PHP_VERSION, '5.6', '<')) {
        return;
    }

    if ($network_wide) {
        return;
    }

    do_action('bit_wc_zoho_crm_deactivation', $network_wide);
}

register_deactivation_hook(__FILE__, 'bit_wc_zoho_crm_deactivate_plugin');

/**
 * Handles plugin uninstall.
 *
 * @access private
 */
function bit_wc_zoho_crm_uninstall_plugin()
{
    if (version_compare(PHP_VERSION, '5.6', '<')) {
        return;
    }

    do_action('bit_wc_zoho_crm_uninstall');
}
register_uninstall_hook(__FILE__, 'bit_wc_zoho_crm_uninstall_plugin');

if (version_compare(PHP_VERSION, '5.6', '>=')) {
    // Autoload vendor files.
    require_once BIT_WC_ZOHO_CRM_PLUGIN_DIR_PATH . 'vendor/autoload.php';

    // Initialize the plugin.
    Plugin::load(BIT_WC_ZOHO_CRM_PLUGIN_MAIN_FILE);
}
