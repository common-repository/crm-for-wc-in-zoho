<?php

namespace BitPress\BIT_WC_ZOHO_CRM;

use WP_Error;
use BitPress\BIT_WC_ZOHO_CRM\Core\Util\HttpHelper;
use BitPress\BIT_WC_ZOHO_CRM\Core\Util\DateTimeHelper;

final class IntegrationHandler
{
  public static function searchRecord($module, $searchCriteria, $defaultHeader)
  {
    $searchRecordEndpoint = "https://www.zohoapis.com/crm/v2/{$module}/search";
    return HttpHelper::get($searchRecordEndpoint, ["criteria" => "({$searchCriteria})"], $defaultHeader);
  }

  public static function upsertRecord($module, $data, $defaultHeader)
  {
    $insertRecordEndpoint = "https://www.zohoapis.com/crm/v2/{$module}/upsert";
    $data = \is_string($data) ? $data : \json_encode($data);
    return HttpHelper::post($insertRecordEndpoint, $data, $defaultHeader);
  }

  public static function insertRecord($module, $data, $defaultHeader)
  {
    $insertRecordEndpoint = "https://www.zohoapis.com/crm/v2/{$module}";
    $data = \is_string($data) ? $data : \json_encode($data);
    return HttpHelper::post($insertRecordEndpoint, $data, $defaultHeader);
  }

  public static function addTagsSingleRecord($recordID, $module, $tagNames, $defaultHeader)
  {
    $addTagsEndpoint = "https://www.zohoapis.com/crm/v2/{$module}/{$recordID}/actions/add_tags";

    $addTagsResponse = HttpHelper::post($addTagsEndpoint, ['tag_names' => $tagNames], $defaultHeader);

    return $addTagsResponse;
  }

  public static function formatFieldValue($value, $formatSpecs)
  {
    if (empty($value)) {
      return '';
    }

    switch ($formatSpecs->json_type) {
      case 'jsonarray':
        $apiFormat = 'array';
        break;
      case 'jsonobject':
        $apiFormat = 'object';
        break;

      default:
        $apiFormat = $formatSpecs->json_type;
        break;
    }

    $formatedValue = '';
    $fieldFormat = gettype($value);
    if ($fieldFormat === $apiFormat && $formatSpecs->data_type !== 'datetime') {
      $formatedValue = $value;
    } else {
      if ($apiFormat === 'array' || $apiFormat === 'object') {
        if ($fieldFormat === 'string') {
          if (strpos($value, ',') === -1) {
            $formatedValue = json_decode($value);
          } else {
            $formatedValue = explode(',', $value);
          }
          $formatedValue = is_null($formatedValue) && !is_null($value) ? [$value] : $formatedValue;
        } else {
          $formatedValue = $value;
        }

        if ($apiFormat === 'object') {
          $formatedValue = (object) $formatedValue;
        }
      } elseif ($apiFormat === 'string' && $formatSpecs->data_type !== 'datetime') {
        $formatedValue = !is_string($value) ? json_encode($value) : $value;
      } elseif ($formatSpecs->data_type === 'datetime') {
        $dateTimeHelper = new DateTimeHelper();
        $formatedValue = $dateTimeHelper->getFormated($value, 'Y-m-d\TH:i', wp_timezone(), 'Y-m-d\TH:i:sP', null);
      } else {
        $stringyfiedValue = !is_string($value) ? json_encode($value) : $value;

        switch ($apiFormat) {
          case 'double':
            $formatedValue = (float) $stringyfiedValue;
            break;

          case 'boolean':
            $formatedValue = (bool) $stringyfiedValue;
            break;

          case 'integer':
            $formatedValue = (int) $stringyfiedValue;
            break;

          default:
            $formatedValue = $stringyfiedValue;
            break;
        }
      }
    }
    $formatedValueLenght = $apiFormat === 'array' || $apiFormat === 'object' ? (is_countable($formatedValue) ? \count($formatedValue) : @count($formatedValue)) : \strlen($formatedValue);
    if ($formatedValueLenght > $formatSpecs->length) {
      $formatedValue = $apiFormat === 'array' || $apiFormat === 'object' ? array_slice($formatedValue, 0, $formatSpecs->length) : substr($formatedValue, 0, $formatSpecs->length);
    }

    return $formatedValue;
  }

  public static function saveToLogDB($order_id, $apiType, $respType, $respObj, $generated_at)
  {
    global $wpdb;
    $respObj = addslashes(wp_json_encode($respObj));
    $wpdb->query("INSERT INTO {$wpdb->prefix}bit_wc_zoho_crm_log(order_id, api_type, response_type, response_obj, generated_at) VALUE($order_id, '{$apiType}', '{$respType}', '{$respObj}', '$generated_at')");
  }

  public static function addRelatedList($type, $order_id, $generated_at, $zcrmApiResponse, $tokenDetails, $defaultDataConf, $integrationDetails, $fieldValues)
  {
    foreach ($integrationDetails->relatedlists as $relatedlist) {
      // Related List apis..
      $relatedListModule =  !empty($relatedlist->module) ? $relatedlist->module : '';
      $relatedListLayout =  !empty($relatedlist->layout) ? $relatedlist->layout : '';
      if (empty($relatedListModule) || empty($relatedListLayout)) {
        $error = new WP_Error('REQ_FIELD_EMPTY', __('module, layout are required for zoho crm relatedlist', 'bit_wc_zoho_crm'));
        IntegrationHandler::saveToLogDB($order_id, $type, 'error', $error, $generated_at);
        return $error;
      }
      $module = $integrationDetails->module;
      $moduleSingular = \substr($module, 0, \strlen($module) - 1);
      if (isset($defaultDataConf->layouts->{$relatedListModule}->{$relatedListLayout}->fields->{$module})) {
        $moduleSingular = $module;
      } elseif (!isset($defaultDataConf->layouts->{$relatedListModule}->{$relatedListLayout}->fields->{$moduleSingular})) {
        $moduleSingular = '';
      }
      $relatedListRequired = !empty($defaultDataConf->layouts->{$relatedListModule}->{$relatedListLayout}->required) ?
        $defaultDataConf->layouts->{$relatedListModule}->{$relatedListLayout}->required : [];
      $recordID = $zcrmApiResponse->data[0]->details->id;
      $defaultDataConf->layouts->{$relatedListModule}->{$relatedListLayout}->fields->{'$se_module'} = (object) array(
        'length' => 200,
        'visible' => true,
        'json_type' => 'string',
        'data_type' => 'string',
      );
      $fieldValues['$se_module'] = $module;
      $relatedlist->field_map[] = (object)
      array(
        'formField' => '$se_module',
        'zohoFormField' => '$se_module'
      );
      if (isset($defaultDataConf->layouts->{$relatedListModule}->{$relatedListLayout}->fields->Parent_Id)) {
        $fieldValues['Parent_Id'] = (object) ['id' => $recordID];
        $relatedlist->field_map[] = (object)
        array(
          'formField' => "Parent_Id",
          'zohoFormField' => "Parent_Id"
        );
      } elseif (!empty($moduleSingular)) {
        $fieldValues[$moduleSingular] = ['id' => $recordID];
        $relatedlist->field_map[] = (object)
        array(
          'formField' => $moduleSingular,
          'zohoFormField' => $moduleSingular
        );
      } elseif ($module === 'Contacts') {
        $fieldValues['Who_Id'] = (object) ['id' => $recordID];
        $relatedlist->field_map[] = (object)
        array(
          'formField' => 'Who_Id',
          'zohoFormField' => 'Who_Id'
        );
      } else {
        $fieldValues['What_Id'] = (object) ['id' => $recordID];
        $relatedlist->field_map[] = (object)
        array(
          'formField' => 'What_Id',
          'zohoFormField' => 'What_Id'
        );
      }

      $zcrmRelatedlistApiResponse = IntegrationHandler::executeRecordApi(
        $type,
        $order_id,
        $generated_at,
        $tokenDetails,
        $defaultDataConf,
        $relatedListModule,
        $relatedListLayout,
        $fieldValues,
        $relatedlist->field_map,
        $relatedlist->actions,
        $relatedListRequired,
        true
      );
      if (
        !empty($zcrmRelatedlistApiResponse->data)
        && !empty($zcrmRelatedlistApiResponse->data[0]->code)
        && $zcrmRelatedlistApiResponse->data[0]->code === 'SUCCESS'
      ) {
        IntegrationHandler::saveToLogDB($order_id, "$type | $relatedListModule", 'success', $zcrmRelatedlistApiResponse, $generated_at);
      } else {
        IntegrationHandler::saveToLogDB($order_id, "$type | $relatedListModule", 'error', $zcrmRelatedlistApiResponse, $generated_at);
      }
    }
  }

  public static function executeRecordApi($type, $order_id, $generated_at, $tokenDetails, $defaultConf, $module, $layout, $fieldValues, $fieldMap, $actions, $required, $isRelated = false, $fieldData = [])
  {

    $defaultHeader = [
      'Authorization' => "Zoho-oauthtoken {$tokenDetails->access_token}"
    ];
    foreach ($fieldMap as $fieldKey => $fieldPair) {
      if (!empty($fieldPair->zohoFormField) && !empty($fieldPair->formField)) {
        if (empty($defaultConf->layouts->{$module}->{$layout}->fields->{$fieldPair->zohoFormField})) {
          continue;
        }
        if (!empty($fieldPair->isCustomer)) continue;
        if ($fieldPair->formField === 'custom' && isset($fieldPair->customValue)) {
          $fieldData[$fieldPair->zohoFormField] = IntegrationHandler::formatFieldValue($fieldPair->customValue, $defaultConf->layouts->{$module}->{$layout}->fields->{$fieldPair->zohoFormField});
        } else {
          $fieldData[$fieldPair->zohoFormField] = IntegrationHandler::formatFieldValue($fieldValues[$fieldPair->formField], $defaultConf->layouts->{$module}->{$layout}->fields->{$fieldPair->zohoFormField});
        }

        if (empty($fieldData[$fieldPair->zohoFormField]) && \in_array($fieldPair->zohoFormField, $required)) {
          $error = new WP_Error('REQ_FIELD_EMPTY', wp_sprintf(__('%s is required for zoho crm, %s module', 'bit_wc_zoho_crm'), $fieldPair->zohoFormField, $module));
          IntegrationHandler::saveToLogDB($order_id, $type, 'error', $error, $generated_at);
          return $error;
        }
        if (!empty($fieldData[$fieldPair->zohoFormField])) {
          $requiredLength = $defaultConf->layouts->{$module}->{$layout}->fields->{$fieldPair->zohoFormField}->length;
          $currentLength = is_array($fieldData[$fieldPair->zohoFormField]) || is_object($fieldData[$fieldPair->zohoFormField]) ?
            @count($fieldData[$fieldPair->zohoFormField])
            : strlen($fieldData[$fieldPair->zohoFormField]);
          if ($currentLength > $requiredLength) {
            $error = new WP_Error('REQ_FIELD_LENGTH_EXCEEDED', wp_sprintf(__('zoho crm field %s\'s maximum length is %s, Given %s', 'bit_wc_zoho_crm'), $fieldPair->zohoFormField, $module));
            IntegrationHandler::saveToLogDB($order_id, $type, 'error', $error, $generated_at);
            return $error;
          }
        }
      }
    }

    if (!empty($defaultConf->layouts->{$module}->{$layout}->id)) {
      $fieldData['Layout']['id'] = $defaultConf->layouts->{$module}->{$layout}->id;
    }
    if (!empty($actions->gclid) && isset($fieldValues['GCLID'])) {
      $fieldData['$gclid'] = $fieldValues['GCLID'];
    }
    if (!empty($actions->rec_owner)) {
      $fieldData['Owner']['id'] = $actions->rec_owner;
    }
    $requestParams['data'][] = (object) $fieldData;
    $requestParams['trigger'] = [];
    if (!empty($actions->workflow)) {
      $requestParams['trigger'][] = 'workflow';
    }
    if (!empty($actions->approval)) {
      $requestParams['trigger'][] = 'approval';
    }
    if (!empty($actions->blueprint)) {
      $requestParams['trigger'][] = 'blueprint';
    }
    if (!empty($actions->assignment_rules)) {
      $requestParams['lar_id'] = $actions->assignment_rules;
    }
    $recordApiResponse = '';
    if (!empty($actions->upsert) && !empty($actions->upsert->crmField)) {
      $requestParams['duplicate_check_fields'] = [];
      if (!empty($actions->upsert)) {
        $duplicateCheckFields = [];
        $searchCriteria = '';
        foreach ($actions->upsert->crmField as $fieldInfo) {
          if (!empty($fieldInfo->name) && $fieldData[$fieldInfo->name]) {
            $duplicateCheckFields[] = $fieldInfo->name;
            if (empty($searchCriteria)) {
              $searchCriteria .= "({$fieldInfo->name}:equals:{$fieldData[$fieldInfo->name]})";
            } else {
              $searchCriteria .= "and({$fieldInfo->name}:equals:{$fieldData[$fieldInfo->name]})";
            }
          }
        }
        if (isset($actions->upsert->overwrite) && !$actions->upsert->overwrite && !empty($searchCriteria)) {
          $searchRecordApiResponse = IntegrationHandler::searchRecord($module, $searchCriteria, $defaultHeader);
          if (!empty($searchRecordApiResponse) && !empty($searchRecordApiResponse->data)) {
            $previousData = $searchRecordApiResponse->data[0];
            foreach ($fieldData as $apiName => $currentValue) {
              if (!empty($previousData->{$apiName})) {
                $fieldData[$apiName] = $previousData->{$apiName};
              }
            }
            $requestParams['data'][] = (object) $fieldData;
          }
         
        }
        $requestParams['duplicate_check_fields'] = $duplicateCheckFields;
      }
      $recordApiResponse = IntegrationHandler::upsertRecord($module, (object) $requestParams, $defaultHeader);
    } elseif ($isRelated) {
      $recordApiResponse = IntegrationHandler::insertRecord($module, (object) $requestParams, $defaultHeader);
    } else {
      $recordApiResponse = IntegrationHandler::upsertRecord($module, (object) $requestParams, $defaultHeader);
    }

    if (
      !empty($recordApiResponse->data)
      && !empty($recordApiResponse->data[0]->code)
      && $recordApiResponse->data[0]->code === 'SUCCESS'
      && !empty($recordApiResponse->data[0]->details->id)
    ) {
      if (!empty($actions->tag_rec)) {
        $tags = '';
        $tag_rec = \explode(",", $actions->tag_rec);
        foreach ($tag_rec as $tag) {
          if (is_string($tag) && substr($tag, 0, 7) === 'bit_wc_') {
            $tags .= (!empty($tags) ? ',' : '') . $fieldValues[$tag];
          } else {
            $tags .= (!empty($tags) ? ',' : '') . $tag;
          }
        }
        $addTagResponse = IntegrationHandler::addTagsSingleRecord($recordApiResponse->data[0]->details->id, $module, $tags, $defaultHeader);
      }
    }

    return $recordApiResponse;
  }
}
