<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Includes;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Helper
{
    public static $log;

    public static function log($message, $data = [], $type = 'info')
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);
        $enableLogging = isset($settings['enabled_logging']) && (int) $settings['enabled_logging'] === 1;

        if ($enableLogging) {
            try {
                if (empty(self::$log)) {
                    self::$log = new Logger('cf7amo');
                    self::$log->pushHandler(
                        new StreamHandler(CF7_AMOCRM_PLUGIN_LOG_FILE, Logger::INFO)
                    );
                }

                self::$log->$type($message, (array) $data);
            } catch (\Exception $exception) {
                if (is_super_admin()) {
                    wp_die(
                        sprintf(
                            esc_html__(
                                'Error code (%s): %s.',
                                'cf7-amocrm-integration'
                            ),
                            $exception->getCode(),
                            $exception->getMessage()
                        ),
                        esc_html__(
                            'An error occurred while writing the log file.',
                            'cf7-amocrm-integration'
                        ),
                        [
                            'back_link' => true
                        ]
                    );
                    // escape ok
                }
            }
        }
    }

    public static function getRedirectUrl()
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);

        if (!empty($settings['redirect-url'])) {
            return $settings['redirect-url'];
        }

        return admin_url() . 'admin.php?page=wpcf7-integration&service=cf7-amocrm-integration&action=setup';
    }

    public static function addCustomField($fields, $id, $value, $enum = false, $subtype = false)
    {
        if (empty($fields)) {
            $fields = [];
        }

        if (empty($fields['custom_fields'])) {
            $fields['custom_fields'] = [];
        }

        $field = [
            'id' => $id,
            'values' => [],
        ];

        if (is_array($value)) {
            $field['values'] = $value;

            $fields['custom_fields'][] = $field;

            return $fields;
        }

        if (!is_array($value)) {
            $values = [[$value, $enum]];
        } else {
            $values = $value;
        }

        foreach ($values as $val) {
            list($value, $enum) = $val;

            $fieldValue = [
                'value' => $value,
            ];

            if ($enum !== false) {
                $fieldValue['enum'] = $enum;
            }

            if ($subtype !== false) {
                $fieldValue['subtype'] = $subtype;
            }

            $field['values'][] = $fieldValue;
        }

        $fields['custom_fields'][] = $field;

        return $fields;
    }

    public static function hasToken()
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);
        $tokenData = get_option(Bootstrap::TOKEN_DATA_KEY, []);

        if (
            !empty($settings['domain']) &&
            !empty($settings['client-id']) &&
            !empty($settings['client-secret']) &&
            !empty($tokenData['refresh_token'])
        ) {
            return true;
        }

        return false;
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
