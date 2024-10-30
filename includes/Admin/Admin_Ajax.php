<?php

namespace BitPress\BIT_WC_ZOHO_CRM\Admin;

use BitPress\BIT_WC_ZOHO_CRM\Plugin;
use BitPress\BIT_WC_ZOHO_CRM\Core\Util\HttpHelper;
use WC_Checkout;

class Admin_Ajax
{
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function register()
    {
        add_action('wp_ajax_bit_wc_zoho_crm_generate_token', [$this, 'generateTokens']);
        add_action('wp_ajax_bit_wc_zoho_crm_get_log_data', [$this, 'getLogData']);
        add_action('wp_ajax_bit_wc_zoho_crm_get_integration_data', [$this, 'getIntegData']);
        add_action('wp_ajax_bit_wc_zoho_crm_add_integration_data', [$this, 'addIntegData']);
        add_action('wp_ajax_bit_wc_zoho_crm_save_integration_data', [$this, 'saveIntegData']);
        add_action('wp_ajax_bit_wc_zoho_crm_import_order_data', [$this, 'importOrderData']);
        add_action('wp_ajax_bit_wc_zoho_crm_refresh_modules', [$this, 'refreshModulesAjaxHelper']);
        add_action('wp_ajax_bit_wc_zoho_crm_refresh_layouts', [$this, 'refreshLayoutsAjaxHelper']);
        add_action('wp_ajax_bit_wc_zoho_crm_refresh_related_lists', [$this, 'getRelatedListsAjaxHelper']);
        add_action('wp_ajax_bit_wc_zoho_crm_refresh_tags', [$this, 'refreshTagListAjaxHelper']);
        add_action('wp_ajax_bit_wc_zoho_crm_get_users', [$this, 'refreshUsersAjaxHelper']);
        add_action('wp_ajax_bit_wc_zoho_crm_get_assignment_rules', [$this, 'getAssignmentRulesAjaxHelper']);
    }

    public function getLogData()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $query = "SELECT * FROM {$this->wpdb->prefix}bit_wc_zoho_crm_log ORDER BY {$requestsParams->sortBy->sortField} ";
            if (!empty($requestsParams->sortBy->orderType)) {
                $query .= $requestsParams->sortBy->orderType;
            } else {
                $query .= 'DESC';
            }
            $query .= " LIMIT {$requestsParams->offset},10";
            $response['logs'] = $this->wpdb->get_results($query);

            // get count
            $response['total_log'] = $this->wpdb->get_row("SELECT COUNT(id) as count FROM {$this->wpdb->prefix}bit_wc_zoho_crm_log");
            wp_send_json_success($response, 200);
        }
    }

    private function woocommerce_get_order_statuses()
    {
        if (!function_exists('wc_get_order_statuses')) {
            require_once dirname(WC_PLUGIN_FILE) . '/includes/wc-order-functions.php';
        }

        if (function_exists('wc_get_order_statuses')) {
            return wc_get_order_statuses();
        }

        return (object) [];
    }

    public function getIntegData()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $response['integ'] = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}bit_wc_zoho_crm_integration LIMIT 1");
            $response['wc_status'] = $this->woocommerce_get_order_statuses();
            wp_send_json_success($response, 200);
        }
    }

    public function addIntegData()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            unset($requestsParams->wcCRMConf->newInteg);
            $integration_details = wp_json_encode($requestsParams->wcCRMConf);
            $result = $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->wpdb->prefix}bit_wc_zoho_crm_integration(integration_details) VALUE(%s)", $integration_details));
            if ($result) {
                wp_send_json_success('Integration Saved Successfully', 200);
            } else {
                wp_send_json_error('Integration Create failed!', 400);
            }
        }
    }

    public function saveIntegData()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            // $integration_details = wp_json_encode($requestsParams->wcCRMConf);
            $integration_details = json_encode($requestsParams->wcCRMConf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

            $idSql = "SELECT id FROM {$this->wpdb->prefix}bit_wc_zoho_crm_integration ORDER BY id DESC LIMIT 1";
            $id = $this->wpdb->get_results($idSql)[0]->id;

            $result = $this->wpdb->query("UPDATE {$this->wpdb->prefix}bit_wc_zoho_crm_integration SET integration_details = '$integration_details' WHERE id = $id");
            // var_dump("UPDATE {$this->wpdb->prefix}bit_wc_zoho_crm_integration SET integration_details = '$integration_details' WHERE id = $id");
            // var_dump($result);
            // die;

            if (!is_wp_error($result)) {
                wp_send_json_success('Integration Updated Successfully', 200);
            } else {
                wp_send_json_error('Integration Update failed!', 400);
            }
        }
    }

    private function woocommerce_get_orders($args)
    {
        if (!function_exists('wc_get_orders')) {
            require_once dirname(WC_PLUGIN_FILE) . '/includes/wc-order-functions.php';
        }

        if (function_exists('wc_get_orders')) {
            return wc_get_orders($args);
        }

        return [];
    }

    public function importOrderData()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON)->importDataOptions;
            $args = [
                'return'          => 'ids',
                'order'           => 'ASC',
                'limit'           => 9999
            ];
            if (isset($requestsParams->start_date) && !empty($requestsParams->start_date)) {
                $date_created = $requestsParams->start_date;
                if (isset($requestsParams->end_date) && !empty($requestsParams->end_date)) {
                    $date_created .= '...' . $requestsParams->end_date;
                }
                $args['date_created'] = $date_created;
            }
            if (isset($requestsParams->status) && !empty($requestsParams->status)) {
                $args['status'] = explode(',', $requestsParams->status);
            }
            $orders = $this->woocommerce_get_orders($args);
            $pluginInstance = new Plugin();
            foreach ($orders as $order_id) {
                $pluginInstance->executeBitWcZohoCRMIntegration($order_id, $requestsParams->importType);
            }
            wp_send_json_success('Orders Imported to Zoho CRM Successfully', 200);
        }
    }

    public static function generateTokens()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            if (
                empty($requestsParams->{'accounts-server'})
                || empty($requestsParams->dataCenter)
                || empty($requestsParams->clientId)
                || empty($requestsParams->clientSecret)
                || empty($requestsParams->redirectURI)
                || empty($requestsParams->code)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bit_wc_zoho_crm'
                    ),
                    400
                );
            }
            $apiEndpoint = \urldecode($requestsParams->{'accounts-server'}) . '/oauth/v2/token';
            $requestParams = [
                'grant_type'    => 'authorization_code',
                'client_id'     => $requestsParams->clientId,
                'client_secret' => $requestsParams->clientSecret,
                'redirect_uri'  => \urldecode($requestsParams->redirectURI),
                'code'          => $requestsParams->code
            ];
            $apiResponse = HttpHelper::post($apiEndpoint, $requestParams);
            if (is_wp_error($apiResponse) || !empty($apiResponse->error)) {
                wp_send_json_error(
                    empty($apiResponse->error) ? 'Unknown' : $apiResponse->error,
                    400
                );
            }
            $apiResponse->generates_on = \time();
            wp_send_json_success($apiResponse, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bit_wc_zoho_crm'
                ),
                401
            );
        }
    }

    public static function refreshAccessToken($apiData)
    {
        if (
            empty($apiData->dataCenter)
            || empty($apiData->clientId)
            || empty($apiData->clientSecret)
            || empty($apiData->tokenDetails)
        ) {
            return false;
        }
        $tokenDetails = $apiData->tokenDetails;

        $dataCenter = $apiData->dataCenter;
        $apiEndpoint = "https://accounts.zoho.{$dataCenter}/oauth/v2/token";
        $requestParams = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $apiData->clientId,
            'client_secret' => $apiData->clientSecret,
            'refresh_token' => $tokenDetails->refresh_token,
        ];

        $apiResponse = HttpHelper::post($apiEndpoint, $requestParams);
        if (is_wp_error($apiResponse) || !empty($apiResponse->error)) {
            return false;
        }
        $tokenDetails->generates_on = \time();
        $tokenDetails->access_token = $apiResponse->access_token;
        return $tokenDetails;
    }

    public static function refreshModulesAjaxHelper()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (
                empty($queryParams->tokenDetails)
                || empty($queryParams->dataCenter)
                || empty($queryParams->clientId)
                || empty($queryParams->clientSecret)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bit_wc_zoho_crm'
                    ),
                    400
                );
            }
            $response = [];
            if ((intval($queryParams->tokenDetails->generates_on) + (55 * 60)) < time()) {
                $response['tokenDetails'] = Admin_Ajax::refreshAccessToken($queryParams);
            }

            $modulesMetaApiEndpoint = "{$queryParams->tokenDetails->api_domain}/crm/v2/settings/modules";
            $authorizationHeader['Authorization'] = "Zoho-oauthtoken {$queryParams->tokenDetails->access_token}";
            $modulesMetaResponse = HttpHelper::get($modulesMetaApiEndpoint, null, $authorizationHeader);
            if (!is_wp_error($modulesMetaResponse) && (empty($modulesMetaResponse->status) || (!empty($modulesMetaResponse->status) && $modulesMetaResponse->status !== 'error'))) {
                $retriveModuleData = $modulesMetaResponse->modules;

                $allModules = [];
                foreach ($retriveModuleData as $module) {
                    if ($module->generated_type === 'custom' || in_array($module->api_name, ['Leads', 'Contacts', 'Accounts'])) {
                        $allModules[$module->api_name] = (object) [
                            'moduleId'   => $module->id,
                            'moduleName' => $module->module_name,
                            'apiName'    => $module->api_name
                        ];
                    }
                }

                uksort($allModules, 'strnatcasecmp');
                $response['modules'] = $allModules;
            } else {
                wp_send_json_error(
                    empty($modulesMetaResponse->error) ? 'Unknown' : $modulesMetaResponse->error,
                    400
                );
            }
            wp_send_json_success($response, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bit_wc_zoho_crm'
                ),
                401
            );
        }
    }

    public static function refreshLayoutsAjaxHelper()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (
                empty($queryParams->module)
                || empty($queryParams->tokenDetails)
                || empty($queryParams->dataCenter)
                || empty($queryParams->clientId)
                || empty($queryParams->clientSecret)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bit_wc_zoho_crm'
                    ),
                    400
                );
            }
            $response = [];
            if ((intval($queryParams->tokenDetails->generates_on) + (55 * 60)) < time()) {
                $response['tokenDetails'] = Admin_Ajax::refreshAccessToken($queryParams);
            }
            $layoutsMetaApiEndpoint = "{$queryParams->tokenDetails->api_domain}/crm/v2/settings/layouts";
            $authorizationHeader['Authorization'] = "Zoho-oauthtoken {$queryParams->tokenDetails->access_token}";
            $requiredParams['module'] = $queryParams->module;
            $layoutsMetaResponse = HttpHelper::get($layoutsMetaApiEndpoint, $requiredParams, $authorizationHeader);
            if (!is_wp_error($layoutsMetaResponse) && (empty($layoutsMetaResponse->status) || (!empty($layoutsMetaResponse->status) && $layoutsMetaResponse->status !== 'error'))) {
                $retriveLayoutsData = $layoutsMetaResponse->layouts;
                $layouts = [];
                foreach ($retriveLayoutsData as $layoutKey => $layoutValue) {
                    $fields = [];
                    $fileUploadFields = [];
                    $requiredFields = [];
                    $requiredFileUploadFiles = [];
                    $uniqueFields = [];
                    foreach ($layoutValue->sections as $sectionKey => $sectionValue) {
                        foreach ($sectionValue->fields as $fieldKey => $fieldDetails) {
                            if (empty($fieldDetails->subform) && !empty($fieldDetails->api_name) && !empty($fieldDetails->view_type->create) && $fieldDetails->view_type->create && $fieldDetails->data_type !== 'ownerlookup') {
                                if ($fieldDetails->data_type === 'fileupload') {
                                    $fileUploadFields[$fieldDetails->api_name] = (object) [
                                        'display_label' => $fieldDetails->display_label,
                                        'length'        => $fieldDetails->length,
                                        'visible'       => $fieldDetails->visible,
                                        'json_type'     => $fieldDetails->json_type,
                                        'data_type'     => $fieldDetails->data_type,
                                        'required'      => $fieldDetails->required
                                    ];
                                } elseif ($fieldDetails->api_name !== 'Product_Details') {
                                    $fields[$fieldDetails->api_name] = (object) [
                                        'display_label' => $fieldDetails->display_label,
                                        'length'        => $fieldDetails->length,
                                        'visible'       => $fieldDetails->visible,
                                        'json_type'     => $fieldDetails->json_type,
                                        'data_type'     => $fieldDetails->data_type,
                                        'required'      => $fieldDetails->required
                                    ];
                                }

                                if (!empty($fieldDetails->required) && $fieldDetails->required) {
                                    if ($fieldDetails->data_type === 'fileupload') {
                                        $requiredFileUploadFiles[] = $fieldDetails->api_name;
                                    } elseif (!in_array($fieldDetails->api_name, ['Parent_Id', 'Product_Details'])) {
                                        $requiredFields[] = $fieldDetails->api_name;
                                    }
                                }
                                if (!empty($fieldDetails->unique) && count((array)$fieldDetails->unique)) {
                                    $uniqueFields[] = $fieldDetails->api_name;
                                }
                            }
                        }
                    }
                    uksort($fields, 'strnatcasecmp');
                    uksort($fileUploadFields, 'strnatcasecmp');
                    usort($requiredFields, 'strnatcasecmp');
                    usort($requiredFileUploadFiles, 'strnatcasecmp');

                    $layouts[$layoutValue->name] = (object) [
                        'visible'                  => $layoutValue->visible,
                        'fields'                   => $fields,
                        'required'                 => $requiredFields,
                        'unique'                   => $uniqueFields,
                        'id'                       => $layoutValue->id,
                        'fileUploadFields'         => $fileUploadFields,
                        'requiredFileUploadFields' => $requiredFileUploadFiles
                    ];
                }
                uksort($layouts, 'strnatcasecmp');
                $response['layouts'] = $layouts;
                $wcCheckoutFields = (new WC_Checkout)->get_checkout_fields();

                if ($wcCheckoutFields) {
                    $wcCheckoutFields['shipping']['shipping_total'] = (object) [
                        'label' => 'Shipping Total'
                    ];
                    $wcCheckoutFields['order']['order_id'] = (object) [
                        'label' => 'Order ID'
                    ];
                    $wcCheckoutFields['order']['paid_on_date'] = (object) [
                        'label' => 'Paid On Date'
                    ];
                    $wcCheckoutFields['order']['payment_method'] = (object) [
                        'label' => 'Payment Method'
                    ];
                    $wcCheckoutFields['order']['total'] = (object) [
                        'label' => 'Total'
                    ];
                    $wcCheckoutFields['order']['channel'] = (object) [
                        'label' => 'Channel'
                    ];
                    $wcCheckoutFields['order']['order_created_date'] = (object) [
                        'label' => 'Created Date'
                    ];
                    $wcCheckoutFields['order']['order_status'] = (object) [
                        'label' => 'Status'
                    ];
                    $wcCheckoutFields['order']['dispaly_name'] = (object) [
                        'label' => 'Display Name'
                    ];
                    $wcCheckoutFields['order']['payment_status'] = (object) [
                        'label' => 'Payment Status'
                    ];
                    if ($queryParams->module === 'Products') {
                        $productFld = [];
                        $productFld['product_name'] = (object) [
                            'label' => 'Name'
                        ];
                        $productFld['product_type'] = (object) [
                            'label' => 'Type'
                        ];
                        $productFld['product_slug'] = (object) [
                            'label' => 'Slug'
                        ];
                        $productFld['product_date_created'] = (object) [
                            'label' => 'Date Created'
                        ];
                        $productFld['product_date_modified'] = (object) [
                            'label' => 'Date Modified'
                        ];
                        $productFld['product_status'] = (object) [
                            'label' => 'Status'
                        ];
                        $productFld['product_description'] = (object) [
                            'label' => 'Description'
                        ];
                        $productFld['product_short_description'] = (object) [
                            'label' => 'Short Description'
                        ];
                        $productFld['product_sku'] = (object) [
                            'label' => 'SKU'
                        ];
                        $productFld['product_price'] = (object) [
                            'label' => 'Price'
                        ];
                        $productFld['product_regular_price'] = (object) [
                            'label' => 'Regular Price'
                        ];
                        $productFld['product_sale_price'] = (object) [
                            'label' => 'Sale Price'
                        ];
                        $productFld['product_total_sales'] = (object) [
                            'label' => 'Total Sales'
                        ];
                        $productFld['product_quantity'] = (object) [
                            'label' => 'Quantity'
                        ];
                        $productFld['product_purchase_note'] = (object) [
                            'label' => 'Purchase Note'
                        ];
                        $productFld['product_weight'] = (object) [
                            'label' => 'Weight'
                        ];
                        $productFld['product_length'] = (object) [
                            'label' => 'Length'
                        ];
                        $productFld['product_width'] = (object) [
                            'label' => 'Width'
                        ];
                        $productFld['product_height'] = (object) [
                            'label' => 'Height'
                        ];
                        $productFld['product_dimensions'] = (object) [
                            'label' => 'Dimensions'
                        ];
                        $productFld['product_average_rating'] = (object) [
                            'label' => 'Average Rating'
                        ];
                        $productFld['product_rating_count'] = (object) [
                            'label' => 'Rating Count'
                        ];
                        $productFld['product_review_count'] = (object) [
                            'label' => 'Review Count'
                        ];
                        $wcProductFields = (object) $productFld;
                        uksort($wcProductFields, 'strnatcasecmp');
                        $response['wcProductFields'] = $wcProductFields;
                    }
                    uksort($wcCheckoutFields, 'strnatcasecmp');
                    $response['wcCheckoutFields'] = $wcCheckoutFields;
                }
            } else {
                wp_send_json_error(
                    $layoutsMetaResponse->status === 'error' ? $layoutsMetaResponse->message : 'Unknown',
                    400
                );
            }
            wp_send_json_success($response, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bit_wc_zoho_crm'
                ),
                401
            );
        }
    }

    public static function getAssignmentRulesAjaxHelper()
    {
        if (true || isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (
                empty($queryParams->module)
                || empty($queryParams->tokenDetails)
                || empty($queryParams->dataCenter)
                || empty($queryParams->clientId)
                || empty($queryParams->clientSecret)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bit_wc_zoho_crm'
                    ),
                    400
                );
            }
            $response = [];
            if ((intval($queryParams->tokenDetails->generates_on) + (55 * 60)) < time()) {
                $response['tokenDetails'] = Admin_Ajax::refreshAccessToken($queryParams);
            }
            $assignmentRulesMetaApiEndpoint = "{$queryParams->tokenDetails->api_domain}/crm/v2.1/settings/automation/assignment_rules";
            $authorizationHeader['Authorization'] = "Zoho-oauthtoken {$queryParams->tokenDetails->access_token}";
            $requiredParams['module'] = $queryParams->module;
            $assignmentRulesResponse = HttpHelper::get($assignmentRulesMetaApiEndpoint, $requiredParams, $authorizationHeader);
            $assignment_rules = [];
            if (
                !is_wp_error($assignmentRulesResponse)
                && empty($assignmentRulesResponse->status)
                && !empty($assignmentRulesResponse)
            ) {
                if (!empty($assignmentRulesResponse->assignment_rules)) {
                    foreach ($assignmentRulesResponse->assignment_rules as $rulesDetails) {
                        $assignment_rules[$rulesDetails->name] = $rulesDetails->id;
                    }
                }
                uksort($assignment_rules, 'strnatcasecmp');
                $response['assignmentRules'] = $assignment_rules;
                wp_send_json_success($response, 200);
            } elseif (!count($assignment_rules)) {
                wp_send_json_success(__('Assignment Rules is Empty', 'bit_wc_zoho_crm'), 200);
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bit_wc_zoho_crm'
                ),
                401
            );
        }
    }

    /**
     * Process ajax request to get realted lists of a Zoho CRM module
     *
     * @return JSON crm layout data
     */
    public static function getRelatedListsAjaxHelper()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (
                empty($queryParams->module)
                || empty($queryParams->tokenDetails)
                || empty($queryParams->dataCenter)
                || empty($queryParams->clientId)
                || empty($queryParams->clientSecret)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bit_wc_zoho_crm'
                    ),
                    400
                );
            }
            $response = [];
            if ((intval($queryParams->tokenDetails->generates_on) + (55 * 60)) < time()) {
                $response['tokenDetails'] = Admin_Ajax::refreshAccessToken($queryParams);
            }
            $relatedListResponse = Admin_Ajax::getRelatedLists($queryParams);
            if (
                !is_wp_error($relatedListResponse)
                && !empty($relatedListResponse)
                && empty($relatedListResponse->status)
            ) {
                uksort($relatedListResponse, 'strnatcasecmp');
                $response['relatedLists'] = $relatedListResponse;
            } else {
                wp_send_json_error(
                    !empty($relatedListResponse->status)
                        && $relatedListResponse->status === 'error' ?
                        $relatedListResponse->message : (empty($relatedListResponse) ? __('RelatedList is empty', 'bit_wc_zoho_crm') : 'Unknown'),
                    empty($relatedListResponse) ? 204 : 400
                );
            }
            wp_send_json_success($response, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bit_wc_zoho_crm'
                ),
                401
            );
        }
    }

    public static function getRelatedLists($queryParams)
    {
        $module = $queryParams->module;
        $getRelatedListsEndpoint = "{$queryParams->tokenDetails->api_domain}/crm/v2.1/settings/related_lists";
        $authorizationHeader['Authorization'] = "Zoho-oauthtoken {$queryParams->tokenDetails->access_token}";
        $getRelatedListsResponse = HttpHelper::get($getRelatedListsEndpoint, ['module' => $module], $authorizationHeader);
        if (is_wp_error($getRelatedListsResponse)) {
            return $getRelatedListsResponse;
        }

        if ($module !== 'Tasks' || $module !== 'Events' || $module !== 'Calls') {
            $related_lists = [
                'Tasks' => (object) [
                    'name'     => 'Tasks',
                    'api_name' => 'Tasks',
                    'href'     => null,
                    'module'   => 'Tasks',
                ],
                'Events' => (object) [
                    'name'     => 'Events',
                    'api_name' => 'Events',
                    'href'     => null,
                    'module'   => 'Events',
                ],
                'Calls' => (object) [
                    'name'     => 'Calls',
                    'api_name' => 'Calls',
                    'href'     => null,
                    'module'   => 'Calls',
                ],
            ];
        }

        $relatedModuleToRemove = ['Attachments', 'Products', 'Activities', 'Activities_History', 'Emails', 'Invited_Events', 'Campaigns', 'Social', 'CheckLists', 'Zoho_Survey', 'Visits_Zoho_Livedesk', 'ZohoSign_Documents', 'Lead_Quote', 'Zoho_ShowTime'];
        if (!empty($getRelatedListsResponse->related_lists)) {
            foreach ($getRelatedListsResponse->related_lists as $relatedListsDetails) {
                if (!in_array($relatedListsDetails->api_name, $relatedModuleToRemove)) {
                    $related_lists[$relatedListsDetails->api_name] = (object) [
                        'name'     => $relatedListsDetails->name,
                        'api_name' => $relatedListsDetails->api_name,
                        'href'     => $relatedListsDetails->href,
                        'module'   => $relatedListsDetails->module,
                    ];
                }
            }
        } else {
            return $getRelatedListsResponse;
        }
        return $related_lists;
    }

    /**
     * Process ajax request for refresh crm users
     *
     * @return JSON crm users data
     */
    public static function refreshUsersAjaxHelper()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (
                empty($queryParams->tokenDetails)
                || empty($queryParams->dataCenter)
                || empty($queryParams->clientId)
                || empty($queryParams->clientSecret)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bit_wc_zoho_crm'
                    ),
                    400
                );
            }
            $response = [];
            if ((intval($queryParams->tokenDetails->generates_on) + (55 * 60)) < time()) {
                $response['tokenDetails'] = Admin_Ajax::refreshAccessToken($queryParams);
            }
            $usersApiEndpoint = "{$queryParams->tokenDetails->api_domain}/crm/v2/users?type=ActiveConfirmedUsers";
            $authorizationHeader['Authorization'] = "Zoho-oauthtoken {$queryParams->tokenDetails->access_token}";
            $retrivedUsersData = [];
            $usersResponse = null;
            do {
                $requiredParams = [];
                if (!empty($usersResponse->users)) {
                    if (!empty($retrivedUsersData)) {
                        $retrivedUsersData = array_merge($retrivedUsersData, $usersResponse->users);
                    } else {
                        $retrivedUsersData = $usersResponse->users;
                    }
                }
                if (!empty($usersResponse->info->more_records) && $usersResponse->info->more_records) {
                    $requiredParams['page'] = intval($usersResponse->info->page) + 1;
                }
                $usersResponse = HttpHelper::get($usersApiEndpoint, $requiredParams, $authorizationHeader);
            } while ($usersResponse == null || (!empty($usersResponse->info->more_records) && $usersResponse->info->more_records));
            if (empty($requiredParams) && !is_wp_error($usersResponse)) {
                $retrivedUsersData = $usersResponse->users;
            }
            if (!is_wp_error($usersResponse) && !empty($retrivedUsersData)) {
                $users = [];
                foreach ($retrivedUsersData as $userKey => $userValue) {
                    $users[$userValue->full_name] = (object) [
                        'full_name' => $userValue->full_name,
                        'id'        => $userValue->id,
                    ];
                }
                uksort($users, strnatcasecmp);
                $response['users'] = $users;
            } else {
                wp_send_json_error(
                    $usersResponse->status === 'error' ? $usersResponse->message : 'Unknown',
                    400
                );
            }
            if (!empty($response['tokenDetails']) && $response['tokenDetails'] && !empty($queryParams->id)) {
                Admin_Ajax::_saveRefreshedToken($queryParams->formID, $queryParams->id, $response['tokenDetails'], $response);
            }
            wp_send_json_success($response, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bit_wc_zoho_crm'
                ),
                401
            );
        }
    }

    /**
     * Process ajax request for refresh tags of a module
     *
     * @return JSON crm Tags  for a module
     */
    public static function refreshTagListAjaxHelper()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bit_wc_zoho_crm')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (
                empty($queryParams->module)
                || empty($queryParams->tokenDetails)
                || empty($queryParams->dataCenter)
                || empty($queryParams->clientId)
                || empty($queryParams->clientSecret)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bit_wc_zoho_crm'
                    ),
                    400
                );
            }
            $response = [];
            if ((intval($queryParams->tokenDetails->generates_on) + (55 * 60)) < time()) {
                $response['tokenDetails'] = Admin_Ajax::refreshAccessToken($queryParams);
            }
            $tagsMetaApiEndpoint = "{$queryParams->tokenDetails->api_domain}/crm/v2/settings/tags";
            $authorizationHeader['Authorization'] = "Zoho-oauthtoken {$queryParams->tokenDetails->access_token}";
            $requiredParams['module'] = $queryParams->module;
            $tagListApiResponse = HttpHelper::get($tagsMetaApiEndpoint, $requiredParams, $authorizationHeader);
            if (!is_wp_error($tagListApiResponse)) {
                if (!empty($tagListApiResponse->tags)) {
                    foreach ($tagListApiResponse->tags as $tagDetails) {
                        $tags[] = $tagDetails->name;
                    }
                }
                usort($tags, 'strnatcasecmp');
                $response['tags'] = $tags;
            } else {
                wp_send_json_error(
                    is_wp_error($tagListApiResponse) ? $tagListApiResponse->get_error_message() : (empty($tagListApiResponse) ? __('Tag is empty', 'bit_wc_zoho_crm') : 'Unknown'),
                    empty($tagListApiResponse) ? 204 : 400
                );
            }
            wp_send_json_success($response, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bit_wc_zoho_crm'
                ),
                401
            );
        }
    }
}
