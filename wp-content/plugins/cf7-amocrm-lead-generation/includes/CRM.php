<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Includes;

class CRM
{
    private static $token = [];

    public static $analyticsFields = [
        'GA UTM'
    ];

    public static function parseGoogleAnaliticsCookie($fields)
    {
        if (!empty($fields['utm-cookies'])) {
            return json_decode(wp_unslash($fields['utm-cookies']), true);
        }

        return [];
    }

    public static function addedGoogleAnaliticsInfoLead($leadEntity, $fields)
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);

        if (empty($settings['trackingId']) || empty($fields['gaClientID'])) {
            return $leadEntity;
        }

        $additionalFields = get_option(Bootstrap::OPTIONS_CUSTOM_FIELDS);

        if (empty($additionalFields['leads']) || !is_array($additionalFields['leads'])) {
            return $leadEntity;
        }

        $cookieFields = self::parseGoogleAnaliticsCookie($fields);

        foreach ($additionalFields['leads'] as $field) {
            if ($field['name'] === 'GA UTM') {
                $leadEntity = Helper::addCustomField(
                    $leadEntity,
                    $field['id'],
                    wp_json_encode(
                        [
                            'ga' => [
                                'trackingId' => $settings['trackingId'],
                                'clientId' => wp_unslash($fields['gaClientID'])
                            ],
                            'utm' => [
                                'source' => !empty($cookieFields['utm_source'])
                                    ? $cookieFields['utm_source']
                                    : '',
                                'medium' => !empty($cookieFields['utm_medium'])
                                    ? $cookieFields['utm_medium']
                                    : '',
                                'content' => !empty($cookieFields['utm_content'])
                                    ? $cookieFields['utm_content']
                                    : '',
                                'campaign' => !empty($cookieFields['utm_campaign'])
                                    ? $cookieFields['utm_campaign']
                                    : '',
                                'term' => !empty($cookieFields['utm_term'])
                                    ? $cookieFields['utm_term']
                                    : ''
                            ],
                            'data_source' => 'form'
                        ]
                    )
                );
            }

            if (!empty($field['code'])) {
                switch ($field['code']) {
                    case 'UTM_SOURCE':
                        $leadEntity = Helper::addCustomField(
                            $leadEntity,
                            $field['id'],
                            !empty($cookieFields['utm_source']) ? $cookieFields['utm_source'] : ''
                        );
                        break;
                    case 'UTM_CONTENT':
                        $leadEntity = Helper::addCustomField(
                            $leadEntity,
                            $field['id'],
                            !empty($cookieFields['utm_content']) ? $cookieFields['utm_content'] : ''
                        );
                        break;
                    case 'UTM_MEDIUM':
                        $leadEntity = Helper::addCustomField(
                            $leadEntity,
                            $field['id'],
                            !empty($cookieFields['utm_medium']) ? $cookieFields['utm_medium'] : ''
                        );
                        break;
                    case 'UTM_CAMPAIGN':
                        $leadEntity = Helper::addCustomField(
                            $leadEntity,
                            $field['id'],
                            !empty($cookieFields['utm_campaign']) ? $cookieFields['utm_campaign'] : ''
                        );
                        break;
                    case 'UTM_TERM':
                        $leadEntity = Helper::addCustomField(
                            $leadEntity,
                            $field['id'],
                            !empty($cookieFields['utm_term']) ? $cookieFields['utm_term'] : ''
                        );
                        break;
                    default:
                        // Nothing
                        break;
                }
            }
        }

        return $leadEntity;
    }

    public static function send($fields, $type, $contactForm, $postedData = [])
    {
        $crmFields = new CrmFields();
        $additionalFields = get_option(Bootstrap::OPTIONS_CUSTOM_FIELDS);

        // prepare contact fields for search
        $prepareAdditionalContactFields = [];

        foreach ($additionalFields['contacts'] as $field) {
            $prepareAdditionalContactFields[$field['id']] = $field['code'];
        }

        // prepare company fields for search
        $prepareAdditionalCompanyFields = [];

        foreach ($additionalFields['companies'] as $field) {
            $prepareAdditionalCompanyFields[$field['id']] = $field['code'];
        }

        try {
            if ($type === 'contacts') {
                $contactEntity = [];

                $searchContactEmail = '';
                $searchContactPhone = '';

                foreach ($fields[$type] as $key => $value) {
                    if (in_array($key, array_keys($crmFields->{$type}))) {
                        if ($key === 'responsible_user_id') {
                            $value = self::resolveNextResponsible($value, 'contact', $contactForm);
                        }

                        $contactEntity[$key] = $value;
                    } elseif ($key === 'custom_fields' && !empty($value) && is_array($value)) {
                        foreach ($value as $id => $val) {
                            if ($prepareAdditionalContactFields[$id] === 'EMAIL') {
                                $searchContactEmail = $val;
                            } elseif ($prepareAdditionalContactFields[$id] === 'PHONE') {
                                $searchContactPhone = $val;
                            }

                            if (
                                isset($fields['contact_phone_is_mobile']) &&
                                $prepareAdditionalContactFields[$id] === 'PHONE'
                            ) {
                                $contactEntity = Helper::addCustomField($contactEntity, $id, $val, 'MOB');
                            } else {
                                $contactEntity = Helper::addCustomField(
                                    $contactEntity,
                                    $id,
                                    $val,
                                    in_array($prepareAdditionalContactFields[$id], ['EMAIL', 'PHONE'], true)
                                        ? 'WORK'
                                        : null
                                );
                            }
                        }
                    }
                }

                $existsContact = false;

                if ($searchContactEmail) {
                    $existsContact = self::sendApiGetRequest('contacts', ['query' => $searchContactEmail]);
                }

                if (!$existsContact && $searchContactPhone) {
                    $existsContact = self::sendApiGetRequest('contacts', ['query' => $searchContactPhone]);
                }

                $note = self::generateContactNote($fields);

                if ($note) {
                    $contactEntity['notes'] = [$note];
                }

                // Exists contact is found
                if ($existsContact) {
                    $existsContact = current($existsContact);

                    if (isset($fields['update_contact'])) {
                        $contactEntity['id'] = $existsContact['id'];
                        $contactEntity['last_modified'] = strtotime('now');
                        self::sendApiPostRequest('contacts', ['update' => [$contactEntity]]);
                    }
                } else {
                    self::sendApiPostRequest('contacts', ['add' => [$contactEntity]]);
                }
            } elseif ($type === 'leads') {
                $leadEntity = self::addedGoogleAnaliticsInfoLead([], $fields);

                // Set pipeline id
                if (isset($fields['leads']['status_id'])) {
                    $explodeCurrentStatus = explode('.', $fields['leads']['status_id']);

                    if (count($explodeCurrentStatus) > 1) {
                        $fields['leads']['pipeline_id'] = $explodeCurrentStatus[0];
                        $fields['leads']['status_id'] = $explodeCurrentStatus[1];
                    }
                }

                // allow modifying the set of lead fields before sending
                $fields['leads'] = apply_filters(
                    'itglx_cf7amo_lead_fields_before_send',
                    $fields['leads'],
                    $contactForm['id'],
                    $postedData
                );

                $leadResponsible = 0;

                foreach ($fields['leads'] as $key => $value) {
                    if (in_array($key, array_keys($crmFields->{$type}))) {
                        if ($key === 'responsible_user_id') {
                            $value = self::resolveNextResponsible($value, 'lead', $contactForm);
                            $leadResponsible = $value;
                        }

                        if ($key === 'price') {
                            $leadEntity['sale'] = $value;
                        }

                        $leadEntity[$key] = $value;
                    } elseif ($key === 'custom_fields' && !empty($value) && is_array($value)) {
                        foreach ($value as $id => $val) {
                            $leadEntity = Helper::addCustomField($leadEntity, $id, $val);
                        }
                    }
                }

                if (!get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY)) {
                    if (!empty($leadEntity['name'])) {
                        $leadEntity['name'] = esc_html__('Unverified purchase code', 'cf7-amocrm-integration')
                            . ': '
                            . $leadEntity['name'];
                    } else {
                        $leadEntity['name'] = esc_html__('Unverified purchase code', 'cf7-amocrm-integration');
                    }
                }

                $leadEntity = self::sendApiPostRequest('leads', ['add' => [$leadEntity]]);
                $leadID = false;

                if (!empty($leadEntity)) {
                    $leadID = $leadEntity[0]['id'];

                    $note = self::generateContactNote($fields);
                    $note['element_type'] = 2;
                    $note['element_id'] = $leadID;
                    self::sendApiPostRequest('notes', ['add' => [$note]]);
                }

                // create task to deal
                if (!empty($fields['task']) && !empty($fields['task']['text'])) {
                    $taskFields = $fields['task'];

                    $taskEntity = [];
                    $taskEntity['element_id'] = $leadID;
                    $taskEntity['element_type'] = 2;
                    $taskEntity['task_type'] = !empty($taskFields['type']) ? $taskFields['type'] : 1; //Звонок

                    if (!empty($leadResponsible)) {
                        $taskEntity['responsible_user_id'] = $leadResponsible;
                    } elseif (!empty($taskFields['responsible_user_id'])) {
                        $taskEntity['responsible_user_id'] = $taskFields['responsible_user_id'];
                    }

                    $completeCorrect = 3600;

                    if (!empty($taskFields['complete_till_at'])) {
                        $completeCorrect = (int) $taskFields['complete_till_at'] * 60;
                    }

                    $taskEntity['complete_till'] = strtotime(
                        date('Y-m-d H:i', strtotime('+' . $completeCorrect . ' seconds'))
                    );
                    $taskEntity['text'] = $taskFields['text'];
                    self::sendApiPostRequest('tasks', ['add' => [$taskEntity]]);
                }

                $contactEntity = [];

                // set specified contact id
                if (!empty($fields['leads']['contact_id'])) {
                    $existsContact = self::sendApiGetRequest('contacts', ['id' => $fields['leads']['contact_id']]);

                    if ($existsContact) {
                        $existsContact = current($existsContact);

                        $leadIds = [$leadID];

                        if (!empty($existsContact['leads']) && !empty($existsContact['leads']['id'])) {
                            $leadIds = array_merge($existsContact['leads']['id'], $leadIds);
                        }

                        Helper::log('update contact result lead list', $leadIds);

                        self::sendApiPostRequest(
                            'contacts',
                            [
                                'update' => [
                                    'id' => $existsContact['id'],
                                    'last_modified' => strtotime('now'),
                                    'linked_leads_id' => $leadIds
                                ]
                            ]
                        );
                    }

                    return true;
                }

                // create/find company
                if (!empty($fields['companies'])) {
                    $entityCompany = [];

                    $searchCompanyEmail = '';
                    $searchCompanyPhone = '';

                    foreach ($fields['companies'] as $key => $value) {
                        if (in_array($key, array_keys($crmFields->companies))) {
                            $entityCompany[$key] = $value;
                        } elseif ($key === 'custom_fields' && !empty($value) && is_array($value)) {
                            foreach ($value as $id => $val) {
                                if ($prepareAdditionalCompanyFields[$id] === 'EMAIL') {
                                    $searchCompanyEmail = $val;
                                } elseif ($prepareAdditionalCompanyFields[$id] === 'PHONE') {
                                    $searchCompanyPhone = $val;
                                }

                                $entityCompany = Helper::addCustomField(
                                    $entityCompany,
                                    $id,
                                    $val,
                                    in_array($prepareAdditionalCompanyFields[$id], ['EMAIL', 'PHONE'], true)
                                        ? 'WORK'
                                        : null
                                );
                            }
                        }
                    }

                    $existsCompany = false;

                    if ($searchCompanyEmail) {
                        $existsCompany = self::sendApiGetRequest('companies', ['query' => $searchCompanyEmail]);
                    }

                    if (!$existsCompany && $searchCompanyPhone) {
                        $existsCompany = self::sendApiGetRequest('companies', ['query' => $searchCompanyPhone]);
                    }

                    // Exists company is found
                    if ($existsCompany) {
                        $existsCompany = current($existsCompany);
                        $contactEntity['linked_company_id'] = $existsCompany['id'];
                    } elseif (!empty($entityCompany['name'])) {
                        $company = self::sendApiPostRequest('companies', ['add' => [$entityCompany]]);

                        if (!empty($company)) {
                            $contactEntity['linked_company_id'] = $company[0]['id'];
                        }
                    }
                }

                $searchContactEmail = '';
                $searchContactPhone = '';

                if (!empty($fields['contacts'])) {
                    foreach ($fields['contacts'] as $key => $value) {
                        if (in_array($key, array_keys($crmFields->contacts))) {
                            if ($key === 'responsible_user_id') {
                                $value = self::resolveNextResponsible($value, 'contact', $contactForm);
                            }

                            $contactEntity[$key] = $value;
                        } elseif ($key === 'custom_fields' && !empty($value) && is_array($value)) {
                            foreach ($value as $id => $val) {
                                if ($prepareAdditionalContactFields[$id] === 'EMAIL') {
                                    $searchContactEmail = $val;
                                } elseif ($prepareAdditionalContactFields[$id] === 'PHONE') {
                                    $searchContactPhone = $val;
                                }

                                if (
                                    isset($fields['contact_phone_is_mobile'])
                                    && $prepareAdditionalContactFields[$id] === 'PHONE'
                                ) {
                                    $contactEntity = Helper::addCustomField($contactEntity, $id, $val, 'MOB');
                                } else {
                                    $contactEntity = Helper::addCustomField(
                                        $contactEntity,
                                        $id,
                                        $val,
                                        in_array($prepareAdditionalContactFields[$id], ['EMAIL', 'PHONE'], true)
                                            ? 'WORK'
                                            : null
                                    );
                                }
                            }
                        }
                    }
                } else {
                    $contactEntity = [];
                }

                if (!empty($contactEntity) && !empty($leadResponsible)) {
                    $contactEntity['responsible_user_id']  = $leadResponsible;
                }

                $existsContact = false;

                if ($searchContactEmail) {
                    $existsContact = self::sendApiGetRequest('contacts', ['query' => $searchContactEmail]);
                }

                if (!$existsContact && $searchContactPhone) {
                    $existsContact = self::sendApiGetRequest('contacts', ['query' => $searchContactPhone]);
                }

                // Exists contact is found
                if ($existsContact) {
                    $existsContact = current($existsContact);
                    $leadIds = [$leadID];

                    if (!empty($existsContact['leads']) && !empty($existsContact['leads']['id'])) {
                        $leadIds = array_merge($existsContact['leads']['id'], $leadIds);
                    }

                    // if not enable update - just connect a new lead
                    if (!isset($fields['update_contact'])) {
                        $contactEntity = [];
                    }

                    Helper::log('update contact result lead list', $leadIds);

                    $contactEntity['id'] = $existsContact['id'];
                    $contactEntity['last_modified'] = strtotime('now');
                    $contactEntity['linked_leads_id'] = $leadIds;
                    self::sendApiPostRequest('contacts', ['update' => [$contactEntity]]);
                } elseif(!empty($contactEntity)) {
                    $contactEntity['linked_leads_id'] = [$leadID];
                    self::sendApiPostRequest('contacts', ['add' => [$contactEntity]]);
                }
            } else {
                $unsorted = [];
                $unsortedSourcedData = [];
                $lead = self::addedGoogleAnaliticsInfoLead([], $fields);

                // Set pipeline id
                if (isset($fields['leads']['status_id'])) {
                    $explodeCurrentStatus = explode('.', $fields['leads']['status_id']);

                    if (count($explodeCurrentStatus) > 1) {
                        $unsorted['pipeline_id'] = $explodeCurrentStatus[0];
                    }
                }

                if (isset($fields['leads']['status_id'])) {
                    // Incoming leads not have status
                    unset($fields['leads']['status_id']);
                }

                // allow modifying the set of lead fields before sending
                $unsorted = apply_filters(
                    'itglx_cf7amo_lead_fields_before_send',
                    $unsorted,
                    $contactForm['id'],
                    $postedData
                );

                foreach ($fields['leads'] as $key => $value) {
                    if (in_array($key, array_keys($crmFields->leads))) {
                        if ($key === 'responsible_user_id') {
                            $value = self::resolveNextResponsible($value, 'lead', $contactForm);
                        }

                        if ($key === 'price') {
                            $lead['sale'] = $value;
                        }

                        $lead[$key] = $value;
                        $unsortedSourcedData[$key . '_1'] = [
                            'type' => 'multitext',
                            'id' => $key,
                            'element_type' => '2',
                            'name' => $crmFields->leads[$key]['name'],
                            'value' => $value
                        ];
                    } elseif ($key === 'custom_fields' && !empty($value) && is_array($value)) {
                        foreach ($value as $id => $val) {
                            $lead = Helper::addCustomField($lead, $id, $val);
                            $name = '';

                            foreach ($additionalFields['leads'] as $field) {
                                if ($field['id'] == $id) {
                                    $name = $field['name'];
                                }
                            }

                            $unsortedSourcedData[$id . '_1'] = [
                                'type' => 'multitext',
                                'id' => $id,
                                'element_type' => '2',
                                'name' => $name,
                                'value' => $val
                            ];
                        }
                    }
                }

                $unsorted['data']['leads'] = [$lead];
                $contact = [];

                foreach ($fields['contacts'] as $key => $value) {
                    if (in_array($key, array_keys($crmFields->contacts))) {
                        $contact[$key] = $value;
                        $unsortedSourcedData[$key . '_1'] = [
                            'type' => 'multitext',
                            'id' => $key,
                            'element_type' => '2',
                            'name' => $crmFields->contacts[$key]['name'],
                            'value' => $value
                        ];
                    } elseif ($key === 'custom_fields' && !empty($value) && is_array($value)) {
                        foreach ($value as $id => $val) {
                            $contact = Helper::addCustomField(
                                $contact,
                                $id,
                                $val,
                                in_array($prepareAdditionalContactFields[$id], ['EMAIL', 'PHONE'], true)
                                    ? 'WORK'
                                    : null
                            );

                            $name = '';

                            foreach ($additionalFields['contacts'] as $field) {
                                if ($field['id'] == $id) {
                                    $name = $field['name'];
                                }
                            }

                            $unsortedSourcedData[$id . '_1'] = [
                                'type' => 'multitext',
                                'id' => $id,
                                'element_type' => '2',
                                'name' => $name,
                                'value' => $val
                            ];
                        }
                    }
                }

                $note = self::generateContactNote($fields);

                if ($note) {
                    $contact['notes'] = [$note];
                }

                $unsorted['data']['contacts'] = [$contact];
                $unsorted['source'] = \get_home_url();

                $unsorted['source_data'] = [
                    'data' => $unsortedSourcedData,
                    'form_id' => $contactForm['id'],
                    'form_type' => 1,
                    'origin' => [
                        'ip' => $fields['ip'],
                        'referer' => $fields['referrer']
                    ],
                    'date' => time(),
                    'from' => $contactForm['title'] . ' - ' . \get_home_url(),
                    'form_name' => $contactForm['title']
                ];

                self::sendApiPostRequest(
                    '',
                    [
                        'request' => [
                            'unsorted' => [
                                'category' => 'forms',
                                'add' => [
                                    $unsorted
                                ]
                            ]
                        ]
                    ],
                    '/api/unsorted/add/?'
                );
            }
        } catch (\Exception $e) {
            Helper::log('error when amo request', $e, 'error');

            if (defined('WP_DEBUG') && WP_DEBUG === true) {
                printf(
                    'Error (%d): %s' . "\n",
                    (int) $e->getCode(),
                    esc_html($e->getMessage())
                );
            }
        }
    }

    public static function resolveNextResponsible($list, $type, $contactForm)
    {
        $list = explode(',', $list);

        if ((int) count($list) === 1) {
            return $list[0];
        }

        if ($type === 'lead') {
            $last = get_post_meta($contactForm['id'], '_last_lead_responsible', true);
            $lastKey = array_search($last, $list);

            if (empty($last) || $lastKey === false || ($lastKey + 1) >= count($list)) {
                update_post_meta($contactForm['id'], '_last_lead_responsible', $list[0]);

                return $list[0];
            }

            update_post_meta($contactForm['id'], '_last_lead_responsible', $list[$lastKey + 1]);

            return $list[$lastKey + 1];
        }

        $last = get_post_meta($contactForm['id'], '_last_contact_responsible', true);
        $lastKey = array_search($last, $list);

        if (empty($last) || $lastKey === false || ($lastKey + 1) >= count($list)) {
            update_post_meta($contactForm['id'], '_last_contact_responsible', $list[0]);

            return $list[0];
        }

        update_post_meta($contactForm['id'], '_last_contact_responsible', $list[$lastKey + 1]);

        return $list[$lastKey + 1];
    }

    public static function updateInformation()
    {
        $gaFieldsIsAdded = get_option('amocrm-cf7-ga-fields-is-added');

        try {
            if (!$gaFieldsIsAdded) {
                foreach (self::$analyticsFields as $analyticsField) {
                    $field = [];
                    $field['name'] = $analyticsField;
                    $field['type'] = 1;
                    $field['element_type'] = 2;
                    $field['origin'] = uniqid() . '_cf7';
                    self::sendApiPostRequest('fields', ['add' => [$field]]);
                }

                update_option('amocrm-cf7-ga-fields-is-added', true);
            }

            $account = self::sendApiGetRequest('', [], '/private/api/v2/json/accounts/current?');

            update_option(Bootstrap::OPTIONS_CUSTOM_FIELDS, $account['custom_fields']);
            update_option(Bootstrap::OPTIONS_USERS, $account['users']);
            update_option(Bootstrap::OPTIONS_PIPELINES, $account['pipelines']);
        } catch (\Exception $e) {
            Helper::log('error when amo request', $e, 'error');

            if (defined('WP_DEBUG') && WP_DEBUG === true) {
                printf(
                    'Error (%d): %s' . "\n",
                    (int) $e->getCode(),
                    esc_html($e->getMessage())
                );
            }
        }
    }

    public static function checkConnection()
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);

        try {
            $response = wp_remote_post(
                'https://' . $settings['domain'] . '/oauth2/access_token',
                [
                    'body' => [
                        'grant_type' => 'authorization_code',
                        'client_id' => $settings['client-id'],
                        'client_secret' => $settings['client-secret'],
                        'redirect_uri' => Helper::getRedirectUrl(),
                        'code' => $settings['authorization-code']
                    ],
                    'timeout' => 20
                ]
            );

            if (is_wp_error($response)) {
                throw new \Exception(
                    $response->get_error_message(),
                    (int) $response->get_error_code()
                );
            }

            $body = $response['body'];
            $result = json_decode($body, true);

            if (isset($result['hint'])) {
                throw new \Exception(
                    $result['hint'] . ' | ' . $result['detail'],
                    (int) $result['status']
                );
            }

            if (empty($result['refresh_token']) && isset($result['title'])) {
                throw new \Exception(
                    $result['title'] . ' | ' . $result['detail'],
                    (int) $result['status']
                );
            }

            unset($settings['authorization-code']);

            \WPCF7::update_option(
                Bootstrap::OPTIONS_KEY,
                $settings
            );

            if (!empty($result['refresh_token'])) {
                update_option(
                    Bootstrap::TOKEN_DATA_KEY,
                    [
                        'access_token' => $result['access_token'],
                        'refresh_token' => $result['refresh_token'],
                        'expires_in' => time() + (int) $result['expires_in'],
                    ]
                );
            } else {
                update_option(Bootstrap::TOKEN_DATA_KEY, []);
            }

            Helper::log('check connection result - success', $result);
        } catch (\Exception $e) {
            Helper::log('error when amo request', $e, 'error');

            $settings['domain'] = '';
            $settings['client-id'] = '';
            $settings['client-secret'] = '';
            $settings['authorization-code'] = '';

            // Clean failed information
            \WPCF7::update_option(Bootstrap::OPTIONS_KEY, $settings);
            update_option(Bootstrap::TOKEN_DATA_KEY, []);

            wp_die(
                sprintf(
                    esc_html__(
                        'Response amoCRM: Error code (%d): %s. Check the settings.',
                        'cf7-amocrm-integration'
                    ),
                    (int) $e->getCode(),
                    esc_html($e->getMessage())
                ),
                esc_html__(
                    'An error occurred while verifying the connection to the amoCRM.',
                    'cf7-amocrm-integration'
                ),
                [
                    'back_link' => true
                ]
            );
            // Escape ok
        }
    }

    public static function generateContactNote($fields)
    {
        // Generate note from contact
        $note = [];

        $note['text'] = '';

        if (!empty($fields['files'])) {
            $note['text'] .= esc_html__('Uploaded files:', 'cf7-amocrm-integration')
                . "\n";

            foreach ($fields['files'] as $link) {
                $note['text'] .= $link
                    . "\n";
            }
        }

        if (isset($fields['disable_note_additional_meta'])) {
            if (!empty($fields['note'])) {
                $note['text'] .= $fields['note']
                    . "\n";
            }
        } else {
            if (!empty($fields['note'])) {
                $note['text'] .= $fields['note']
                    . "\n"
                    . esc_html__('Additional information about the sender:', 'cf7-amocrm-integration')
                    . "\n";
            } else {
                $note['text'] .= esc_html__('Additional information about the sender:', 'cf7-amocrm-integration')
                    . "\n";
            }

            if ($fields['ip']) {
                $note['text'] .= esc_html__('IP-address: ', 'cf7-amocrm-integration')
                    . wp_unslash($fields['ip'])
                    . "\n";
            }

            if ($fields['agent']) {
                $note['text'] .= 'User Agent: '
                    . wp_unslash($fields['agent'])
                    . "\n";
            }

            $note['text'] .= esc_html__('Date and time: ', 'cf7-amocrm-integration')
                . date_i18n('Y-m-d H:i:s')
                . "\n";

            if (!empty($fields['referrer'])) {
                $note['text'] .= esc_html__('Referrer: ', 'cf7-amocrm-integration')
                    . wp_unslash($fields['referrer'])
                    . "\n";
            }
        }

        if (empty($note['text'])) {
            return false;
        }

        // Set note type - is required
        $note['note_type'] = 4;

        return $note;
    }

    private static function sendApiPostRequest($method, $fields = [], $rawMethod = '')
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);

        Helper::log('POST - ' . $method, $fields);

        $response = \wp_remote_post(
            'https://' . $settings['domain'] . ($rawMethod ? $rawMethod : '/api/v2/' . $method),
            [
                'body' => json_encode($fields),
                'headers' => [
                    'Content-Type' =>  'application/json',
                    'Authorization' => 'Bearer ' . self::getToken()
                ],
                'timeout' => 20
            ]
        );


        if (is_wp_error($response)) {
            Helper::log('amo response error', $response, 'error');

            return [];
        }

        $body = $response['body'];

        if (!empty($body)) {
            $decodeResponse = json_decode($body, true);

            Helper::log('amo decode response', json_decode($body, true));

            if (!empty($decodeResponse['_embedded']) && !empty($decodeResponse['_embedded']['items'])) {
                return $decodeResponse['_embedded']['items'];
            }

            return [];
        }

        Helper::log('amo empty response', [], 'warning');

        return [];
    }

    private static function sendApiGetRequest($method, $fields = [], $rawMethod = '')
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);

        Helper::log('GET - ' . $method, $fields);

        $response = \wp_remote_get(
            'https://' . $settings['domain']
                . ($rawMethod ? $rawMethod : '/api/v2/' . $method)
                . ($fields ? '?' . http_build_query($fields) : ''),
            [
                'headers' => [
                    'Content-Type' =>  'application/json',
                    'Authorization' => 'Bearer ' . self::getToken()
                ],
                'timeout' => 20
            ]
        );

        if (is_wp_error($response)) {
            Helper::log('amo response error', $response, 'error');

            return [];
        }

        $body = $response['body'];

        if (!empty($body)) {
            $decodeResponse = json_decode($body, true);

            if (!empty($decodeResponse['response']) && !empty($decodeResponse['response']['account'])) {
                Helper::log('success get account data');

                return $decodeResponse['response']['account'];
            }

            Helper::log('amo decode response', json_decode($body, true));

            if (!empty($decodeResponse['_embedded']) && !empty($decodeResponse['_embedded']['items'])) {
                return $decodeResponse['_embedded']['items'];
            }

            return [];
        }

        Helper::log('amo empty response', [], 'warning');

        return [];
    }

    private static function getToken()
    {
        if (!empty(self::$token)) {
            return self::$token;
        }

        try {
            $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);
            $tokenData = get_option(Bootstrap::TOKEN_DATA_KEY, []);

            if ((int) $tokenData['expires_in'] < time() + 60) {
                Helper::log('get new token as expired');

                $response = \wp_remote_post(
                    'https://' . $settings['domain'] . '/oauth2/access_token',
                    [
                        'body' => [
                            'grant_type' => 'refresh_token',
                            'client_id' => $settings['client-id'],
                            'client_secret' => $settings['client-secret'],
                            'redirect_uri' => Helper::getRedirectUrl(),
                            'refresh_token' => $tokenData['refresh_token']
                        ],
                        'timeout' => 20
                    ]
                );

                if (is_wp_error($response)) {
                    throw new \Exception(
                        $response->get_error_message(),
                        (int) $response->get_error_code()
                    );
                }

                $body = $response['body'];
                $result = json_decode($body, true);

                if (isset($result['hint'])) {
                    throw new \Exception(
                        $result['hint'] . ' | ' . $result['detail'],
                        (int) $result['status']
                    );
                }

                if (!empty($result['refresh_token'])) {
                    update_option(
                        Bootstrap::TOKEN_DATA_KEY,
                        [
                            'access_token' => $result['access_token'],
                            'refresh_token' => $result['refresh_token'],
                            'expires_in' => time() + (int) $result['expires_in'],
                        ]
                    );
                }

                $accessToken = $result['access_token'];
            } else {
                $accessToken = $tokenData['access_token'];
            }

            self::$token = $accessToken;

            return self::$token;
        } catch (\Exception $e) {
            Helper::log('error when amo token request', $e, 'error');

            return '';
        }
    }

    private function __construct()
    {
        // Nothing
    }

    private function __clone()
    {
        // Nothing
    }
}
