<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Includes;

class Cron
{
    private static $instance = false;

    protected function __construct()
    {
        add_action('init', [$this, 'createCron']);

        // not bind if run not cron mode
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }

        add_action(Bootstrap::SEND_CRON_TASK, [$this, 'sendCronAction'], 10, 4);
        add_action(Bootstrap::CRON, [$this, 'cronAction']);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function createCron()
    {
        if (wp_next_scheduled(Bootstrap::CRON)) {
            return;
        }

        wp_schedule_event(time(), 'weekly', Bootstrap::CRON);
    }

    public function sendCronAction($sendFields, $type, $contactForm, $postedData)
    {
        CRM::send($sendFields, $type, $contactForm, $postedData);
    }

    public function cronAction()
    {
        $response = PluginRequest::call('cron_code_check');

        if (is_wp_error($response)) {
            return;
        }

        if ($response->status === 'stop') {
            update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, '');
        }
    }

    private function __clone()
    {
        // Nothing
    }
}
