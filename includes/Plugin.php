<?php

namespace BitPress\BIT_WC_ZOHO_CRM;

use WP_Error;
use BitPress\BIT_WC_ZOHO_CRM\Admin\Admin_Bar;
use BitPress\BIT_WC_ZOHO_CRM\Admin\Admin_Ajax;
use BitPress\BIT_WC_ZOHO_CRM\Core\Util\Activation;
use BitPress\BIT_WC_ZOHO_CRM\IntegrationHandler;
use BitPress\BIT_WC_ZOHO_CRM\API\Routes\Routes;

/**
 * Main class for the plugin.
 *
 * @since 1.0.0-alpha
 */
final class Plugin
{

	/**
	 * Main instance of the plugin.
	 *
	 * @since 1.0.0-alpha
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Holds various class instances
	 *
	 * @var array
	 */
	private $container = array();

	/**
	 * Registers the plugin with WordPress.
	 *
	 * @since 1.0.0-alpha
	 */
	public function register()
	{
		(new Activation())->activate();

		$display_bit_form_meta = function () {
			printf('<meta name="generator" content="Plugin by BitPress %s" />', esc_attr(BIT_WC_ZOHO_CRM_VERSION));
		};
		add_action('wp_head', $display_bit_form_meta);
		add_action('login_head', $display_bit_form_meta);
		add_action('plugins_loaded', array($this, 'init_plugin'));
		add_action( 'rest_api_init', [$this, 'register_bf_api_routes'], 10 );
		// Initiate the plugin on 'init' 
		$this->init_plugin();
	}

	public function register_bf_api_routes()
	{
		$routes = new Routes();
		$routes->register_routes();
	}

	/*****************************frm***************************************************************** */
	/**
	 * Do plugin upgrades
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	function plugin_upgrades()
	{

		if (!current_user_can('manage_options')) {
			return;
		}
	}

	/**
	 * Initialize the hooks
	 *
	 * @return void
	 */

	public function init_hooks()
	{

		// Localize our plugin
		add_action('init', array($this, 'localization_setup'));

		// initialize the classes
		add_action('init', array($this, 'init_classes'));
		add_action('init', array($this, 'wpdb_table_shortcuts'), 0);

		add_action('woocommerce_loaded', function () {
			add_action('woocommerce_checkout_order_processed', array($this, 'executeBitWcZohoCRMIntegration'), 100, 2);
		});

		add_filter('plugin_action_links_' . plugin_basename(BIT_WC_ZOHO_CRM_PLUGIN_MAIN_FILE), array($this, 'plugin_action_links'));
	}

	public function executeBitWcZohoCRMIntegration($order_id, $importType)
	{
		include_once dirname(WC_PLUGIN_FILE) . '/includes/class-wc-order.php';
		global $wpdb;

		$if_already_imported_result = $wpdb->get_results("SELECT COUNT(response_type) as success_count FROM {$wpdb->prefix}bit_wc_zoho_crm_log WHERE order_id = {$order_id} AND response_type = 'success' GROUP BY generated_at");
		foreach ($if_already_imported_result as $res) {
			if (($importType === 'contact' && intval($res->success_count) >= 1) || ($importType === 'contactsales' && intval($res->success_count) >= 2)) {
				return;
			}
		}
		$integ_result = $wpdb->get_results("SELECT integration_details FROM {$wpdb->prefix}bit_wc_zoho_crm_integration ORDER BY id DESC LIMIT 1");
		if (!$integ_result) return;
		$integ_details = json_decode($integ_result[0]->integration_details);

		if (isset($integ_details->enabled) && !$integ_details->enabled) return;

		$generated_at = uniqid();
		$order = wc_get_order($order_id);
	
		if ((intval($integ_details->tokenDetails->generates_on) + (55 * 60)) < time()) {
			$requiredParams['clientId'] = $integ_details->clientId;
			$requiredParams['clientSecret'] = $integ_details->clientSecret;
			$requiredParams['dataCenter'] = $integ_details->dataCenter;
			$requiredParams['tokenDetails'] = $integ_details->tokenDetails;
			$newTokenDetails = Admin_Ajax::refreshAccessToken((object)$requiredParams);
			if ($newTokenDetails) {
				$integ_details->tokenDetails = $newTokenDetails;
				$new_integ_details = wp_json_encode($integ_details);
				$idSql = "SELECT id FROM {$wpdb->prefix}bit_wc_zoho_crm_integration ORDER BY id DESC LIMIT 1" ;
				$id =  $wpdb->get_results($idSql)[0]->id;
				$wpdb->query("UPDATE {$wpdb->prefix}bit_wc_zoho_crm_integration SET integration_details = '{$new_integ_details}' WHERE id = $id");
			}
		}

		$tokenDetails = $integ_details->tokenDetails;
		$integrationDetails = $integ_details->integInfo->customer;
		$module = $integrationDetails->module;
		$layout = $integrationDetails->layout;
		$fieldMap = $integrationDetails->field_map;
		$actions = $integrationDetails->actions;
		$defaultDataConf = $integ_details->default;

		if (
			empty($tokenDetails)
			|| empty($module)
			|| empty($layout)
			|| empty($fieldMap)
		) {
			$error = new WP_Error('REQ_FIELD_EMPTY', __('module, layout, fields are required for zoho crm api', 'bit_wc_zoho_crm'));
			IntegrationHandler::saveToLogDB($order_id, 'customer', 'error', $error, $generated_at);
			return $error;
		}

		if (empty($defaultDataConf->layouts->{$module}->{$layout}->fields) || empty($defaultDataConf->modules->{$module})) {
			$error = new WP_Error('REQ_FIELD_EMPTY', __('module, layout, fields are required for zoho crm api', 'bit_wc_zoho_crm'));
			IntegrationHandler::saveToLogDB($order_id, 'customer', 'error', $error, $generated_at);
			return $error;
		}

		$required = !empty($defaultDataConf->layouts->{$module}->{$layout}->required) ?
			$defaultDataConf->layouts->{$module}->{$layout}->required : [];

		$woocommerceFieldValuesMap = [
			'bit_wc_order_id' => $order_id,
			'bit_wc_billing_address_1' => $order->get_billing_address_1(),
			'bit_wc_billing_address_2' => $order->get_billing_address_2(),
			'bit_wc_billing_city' => $order->get_billing_city(),
			'bit_wc_billing_company' => $order->get_billing_company(),
			'bit_wc_billing_country' => $order->get_billing_country(),
			'bit_wc_billing_email' => $order->get_billing_email(),
			'bit_wc_billing_first_name' => $order->get_billing_first_name(),
			'bit_wc_billing_last_name' => $order->get_billing_last_name(),
			'bit_wc_billing_phone' => $order->get_billing_phone(),
			'bit_wc_billing_postcode' => $order->get_billing_postcode(),
			'bit_wc_billing_state' => $order->get_billing_state(),
			'bit_wc_order_comments' => $order->get_customer_note(),
			'bit_wc_shipping_address_1' => $order->get_shipping_address_1(),
			'bit_wc_shipping_address_2' => $order->get_shipping_address_2(),
			'bit_wc_shipping_city' => $order->get_shipping_city(),
			'bit_wc_shipping_company' => $order->get_shipping_company(),
			'bit_wc_shipping_country' => $order->get_shipping_country(),
			'bit_wc_shipping_first_name' => $order->get_shipping_first_name(),
			'bit_wc_shipping_last_name' => $order->get_shipping_last_name(),
			'bit_wc_shipping_postcode' => $order->get_shipping_postcode(),
			'bit_wc_shipping_state' => $order->get_shipping_state(),
			'bit_wc_shipping_total' => $order->get_shipping_total(),
			'bit_wc_paid_on_date' => wc_format_datetime($order->get_date_paid(),"Y-m-d\TH:i:s\+05:30"),
			// zoho create date format Y-m-d\TH:i:s\+05:30
			'bit_wc_payment_method' => $order->get_payment_method(),
			'bit_wc_total' => $order->get_total(),
			'bit_wc_channel' => $order->get_payment_method_title(),
			'bit_wc_order_created_date' =>  wc_format_datetime($order->get_date_created(),"Y-m-d"),
			'bit_wc_order_status' => $order->get_status(),
			'bit_wc_dispaly_name' => get_userdata(get_current_user_id())->display_name,
			'bit_wc_payment_status' => $order->is_paid() ,
		];

		$zcrmCustomerApiResponse = IntegrationHandler::executeRecordApi(
			'customer',
			$order_id,
			$generated_at,
			$tokenDetails,
			$defaultDataConf,
			$module,
			$layout,
			$woocommerceFieldValuesMap,
			$fieldMap,
			$actions,
			$required
		);

		if (
			!empty($zcrmCustomerApiResponse->data)
			&& !empty($zcrmCustomerApiResponse->data[0]->code)
			&& $zcrmCustomerApiResponse->data[0]->code === 'SUCCESS'
		) {
			IntegrationHandler::saveToLogDB($order_id, 'customer', 'success', $zcrmCustomerApiResponse, $generated_at);
			if (count($integrationDetails->relatedlists)) {
				$zcrmCustomerRelatedApiResponse = IntegrationHandler::addRelatedList(
					'customer',
					$order_id,
					$generated_at,
					$zcrmCustomerApiResponse,
					$tokenDetails,
					$defaultDataConf,
					$integrationDetails,
					$woocommerceFieldValuesMap
				);
			}
			$defaultHeader = [
				'Authorization' => "Zoho-oauthtoken {$tokenDetails->access_token}"
			];
			$woocommerceFieldValuesMap['customer_id'] = $zcrmCustomerApiResponse->data[0]->details->id;
			apply_filters('bit_wc_zoho_crm_addSalesOrder', $order_id, $integ_details, $defaultHeader, $woocommerceFieldValuesMap,  $generated_at);
		} else {
			IntegrationHandler::saveToLogDB($order_id, 'customer', 'error', $zcrmCustomerApiResponse, $generated_at);
		}
	}

	/**
	 * Set WPDB table shortcut names
	 *
	 * @return void
	 */
	public function wpdb_table_shortcuts()
	{
		global $wpdb;

		$wpdb->bit_wc_zoho_crm_schema   = $wpdb->prefix . 'bit_wc_zoho_crm_schema';
		$wpdb->bit_wc_zoho_crm_schema_meta = $wpdb->prefix . 'bit_wc_zoho_crm_schema_meta';
	}

	/**
	 * Initialize plugin for localization
	 *
	 * @uses load_plugin_textdomain()
	 */
	public function localization_setup()
	{
		load_plugin_textdomain('bit_wc_zoho_crm', false, BIT_WC_ZOHO_CRM_PLUGIN_DIR_PATH . '/lang/');
	}

	/**
	 * Instantiate the required classes
	 *
	 * @return void
	 */
	public function init_classes()
	{
		if ($this->is_request('admin')) {
			$this->container['admin']        = (new Admin_Bar())->register();
			$this->container['admin_ajax']   = (new Admin_Ajax())->register();
		}
	}

	/**
	 * Plugin action links
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	function plugin_action_links($links)
	{
		$links[] = '<a href="https://formsintegrations.com" target="_blank">' . __('Forms Integration', 'bit_wc_zoho_crm') . '</a>';

		return $links;
	}


	/**
	 * What type of request is this?
	 *
	 * @since 1.0.0-alpha
	 *
	 * @param  string $type admin, ajax, cron, api or frontend.
	 *
	 * @return bool
	 */
	private function is_request($type)
	{

		switch ($type) {
			case 'admin':
				return is_admin();

			case 'ajax':
				return defined('DOING_AJAX');

			case 'cron':
				return defined('DOING_CRON');

			case 'api':
				return defined('REST_REQUEST');

			case 'frontend':
				return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
		}
	}

	public function init_plugin()
	{
		$this->init_hooks();

		do_action('bit_wc_zoho_crm_loaded');
	}
	/********************************************************************************************** */

	/**
	 * Retrieves the main instance of the plugin.
	 *
	 * @since 1.0.0-alpha
	 *
	 * @return BIT_WC_ZOHO_CRM Plugin main instance.
	 */
	public static function instance()
	{
		return static::$instance;
	}

	/**
	 * Loads the plugin main instance and initializes it.
	 *
	 * @since 1.0.0-alpha
	 *
	 * @param string $main_file Absolute path to the plugin main file.
	 * @return bool True if the plugin main instance could be loaded, false otherwise.
	 */
	public static function load($main_file)
	{
		if (null !== static::$instance) {
			return false;
		}

		static::$instance = new static($main_file);
		static::$instance->register();

		return true;
	}
}
