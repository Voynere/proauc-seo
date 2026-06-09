<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Admin;

use Itgalaxy\Cf7\AmoCRM\Integration\Includes\Bootstrap;

class LogHelper
{
    private static $instance = false;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function __construct()
    {
        if (!current_user_can('manage_' . Bootstrap::OPTIONS_KEY)) {
            return;
        }

        if (isset($_GET[Bootstrap::OPTIONS_KEY . '-logs-get'])) {
            add_action('admin_init', [$this, 'logsGet']);
        }

        if (isset($_GET[Bootstrap::OPTIONS_KEY . '-logs-clear'])) {
            add_action('admin_init', [$this, 'logsClear']);
        }
    }

    public function logsGet()
    {
        if (!file_exists(CF7_AMOCRM_PLUGIN_LOG_FILE)) {
            header('Content-Type: plain/text');
            header('Content-Disposition: attachment; filename="' . basename(CF7_AMOCRM_PLUGIN_LOG_FILE) . '"');

            echo 'Empty logs';

            exit();
        }
        // check exists php-zip extension
        if (function_exists('zip_open')) {
            $file = dirname(CF7_AMOCRM_PLUGIN_LOG_FILE) . '/' . uniqid() . '.zip';

            // create empty file
            file_put_contents($file, '');

            $zip = new \ZipArchive();
            $zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFile(CF7_AMOCRM_PLUGIN_LOG_FILE, basename(CF7_AMOCRM_PLUGIN_LOG_FILE));

            // zip archive will be created only after closing object
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . 'logs_' . date('Y-m-d_H:i:s') . '.zip' . '"');
            header('Content-Length: ' . filesize($file));

            readfile($file);
            unlink($file);

            exit();
        }

        header('Content-Type: plain/text');
        header('Content-Disposition: attachment; filename="' . basename(CF7_AMOCRM_PLUGIN_LOG_FILE) . '"');
        header('Content-Length: ' . filesize(CF7_AMOCRM_PLUGIN_LOG_FILE));

        readfile(CF7_AMOCRM_PLUGIN_LOG_FILE);

        exit();
    }

    public function logsClear()
    {
        if (!file_exists(CF7_AMOCRM_PLUGIN_LOG_FILE) || !is_writable(CF7_AMOCRM_PLUGIN_LOG_FILE)) {
            return;
        }

        unlink(CF7_AMOCRM_PLUGIN_LOG_FILE);

        add_action('admin_notices', function () {
            echo sprintf(
                '<div class="updated notice notice-success is-dismissible"><p>%1$s</p></div>',
                esc_html__('Log file has been cleared successfully.', 'cf7-amocrm-integration')
            );
        });
    }
}
