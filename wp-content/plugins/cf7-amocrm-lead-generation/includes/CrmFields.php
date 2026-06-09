<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Includes;

class CrmFields
{
    public $leads;
    public $contacts;

    public function __construct()
    {
        $this->leads = [
            'name' => [
                'required' => true,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Lead name', 'cf7-amocrm-integration')
            ],
            // 'date_create'
            // 'last_modified'
            'status_id' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__('Pipeline / Status', 'cf7-amocrm-integration')
            ],
            // 'pipeline_id'
            'price' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__('Deal budget', 'cf7-amocrm-integration')
            ],
            'responsible_user_id' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__('Responsible (ID)', 'cf7-amocrm-integration'),
                'description' => esc_html__('you can specify several, separated by commas, then the requests will be distributed sequentially', 'cf7-amocrm-integration')
            ],
            'contact_id' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__('Contact ID', 'cf7-amocrm-integration'),
                'description' => esc_html__(
                    'you can specify the contact id, which will always be assigned to the lead - only type deal',
                    'cf7-amocrm-integration'
                )
            ],
            // 'request_id'
            // 'linked_company_id'
            'tags' => [
                'required' => false,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Tags', 'cf7-amocrm-integration')
            ]
            // 'visitor_uid'
        ];

        $this->contacts = [
            'name' => [
                'required' => true,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Contact name', 'cf7-amocrm-integration')
            ],
            // 'request_id'
            // 'date_create'
            // 'last_modified'
            'responsible_user_id' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__('Responsible (ID)', 'cf7-amocrm-integration')
            ],
            // 'linked_leads_id'
            'company_name' => [
                'required' => false,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Company name', 'cf7-amocrm-integration')
            ],
            // 'linked_company_id'
            'tags' => [
                'required' => false,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Tags', 'cf7-amocrm-integration')
            ]
        ];

        $this->companies = [
            'name' => [
                'required' => true,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Company name', 'cf7-amocrm-integration')
            ],
            // 'request_id'
            // 'date_create'
            // 'last_modified'
            'responsible_user_id' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__('Responsible (ID)', 'cf7-amocrm-integration')
            ],
            // 'linked_leads_id'
            // 'linked_company_id'
            'tags' => [
                'required' => false,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Tags', 'cf7-amocrm-integration')
            ]
        ];

        $this->task = [
            'text' => [
                'required' => true,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Text', 'cf7-amocrm-integration')
            ],
            // 'request_id'
            // 'date_create'
            // 'last_modified'
            'responsible_user_id' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__('Responsible (ID)', 'cf7-amocrm-integration')
            ],
            // 'linked_leads_id'
            // 'linked_company_id'
            'type' => [
                'required' => false,
                'type' => esc_html__('String', 'cf7-amocrm-integration'),
                'name' => esc_html__('Type', 'cf7-amocrm-integration'),
                'items' => [
                    1 => esc_html__('Call', 'cf7-amocrm-integration'),
                    2 => esc_html__('Meeting', 'cf7-amocrm-integration')
                ]
            ],
            'complete_till_at' => [
                'required' => false,
                'type' => esc_html__('Numeric', 'cf7-amocrm-integration'),
                'name' => esc_html__(
                    'Number of minutes for deadline',
                    'cf7-amocrm-integration'
                ),
                'description' => esc_html__(
                    'after how many minutes after creation the task should be completed',
                    'cf7-amocrm-integration'
                )
            ]
        ];
    }

    private function __clone()
    {
    }
}
