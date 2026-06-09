<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Admin;

use Itgalaxy\Cf7\AmoCRM\Integration\Includes\AssetsHelper;
use Itgalaxy\Cf7\AmoCRM\Integration\Includes\Bootstrap;
use Itgalaxy\Cf7\AmoCRM\Integration\Includes\CRM;
use Itgalaxy\Cf7\AmoCRM\Integration\Includes\CrmFields;
use Itgalaxy\Cf7\AmoCRM\Integration\Includes\Helper;
use Itgalaxy\Cf7\AmoCRM\Integration\Includes\PluginRequest;

class CF7 extends \WPCF7_Service
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
        add_action('wpcf7_init', [$this, 'registerService']);

        // https://developer.wordpress.org/reference/hooks/admin_notices/
        add_action('admin_notices', [$this, 'notice']);

        if ($this->is_active()) {
            add_filter('wpcf7_editor_panels', [$this, 'settingsPanels']);
            add_action('save_post_' . \WPCF7_ContactForm::post_type, [$this, 'saveSettings']);

            if (isset($_GET['page']) && $_GET['page'] === 'wpcf7' && !empty($_GET['post'])) {
                add_action('admin_enqueue_scripts', function () {
                    wp_enqueue_style('cf7-amocrm-admin', AssetsHelper::getPathAssetFile('/admin/css/app.css'), false, false);
                    wp_enqueue_script('cf7-amocrm-admin', AssetsHelper::getPathAssetFile('/admin/js/app.js'), false, false);
                });
            }
        }
    }

    public function notice()
    {
        if (\get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY)) {
            return;
        }

        echo sprintf(
            '<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s <a href="%3$s">%4$s</a></p></div>',
            esc_html__('Contact Form 7 - amoCRM - Integration', 'cf7-amocrm-integration'),
            esc_html__(
                'Please verify the purchase code on the plugin settings page - ',
                'cf7-amocrm-integration'
            ),
            esc_url(admin_url() . 'admin.php?page=wpcf7-integration&service=cf7-amocrm-integration&action=setup'),
            esc_html__('open', 'cf7-amocrm-integration')
        );
    }

    public function registerService()
    {
        $integration = \WPCF7_Integration::get_instance();
        $categories = ['crm' => $this->get_title()];

        foreach ($categories as $name => $category) {
            $integration->add_category($name, $category);
        }

        $services = ['cf7-amocrm-integration' => self::getInstance()];

        foreach ($services as $name => $service) {
            $integration->add_service($name, $service);
        }
    }

    // @codingStandardsIgnoreStart
    public function is_active()
    {
        // @codingStandardsIgnoreEnd
       return Helper::hasToken();
    }

    // @codingStandardsIgnoreStart
    public function get_title()
    {
        // @codingStandardsIgnoreEnd
        return esc_html__('Integration with amoCRM', 'cf7-amocrm-integration');
    }

    // @codingStandardsIgnoreStart
    public function get_categories()
    {
        // @codingStandardsIgnoreEnd
        return ['crm'];
    }

    public function icon()
    {
    }

    public function link()
    {
        echo '<a href="https://codecanyon.net/user/itgalaxycompany">itgalaxycompany</a>';
    }

    public function load($action = '')
    {
        if ('setup' == $action) {
            if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
                if (isset($_POST['purchase-code'])) {
                    check_admin_referer('wpcf7-amocrm-integration-setup-license');
                    $code = trim(wp_unslash($_POST['purchase-code']));

                    $response = PluginRequest::call(
                        isset($_POST['verify']) ? 'code_activate' : 'code_deactivate',
                        $code
                    );

                    if (is_wp_error($response)) {
                        // fix network connection problems
                        if ($response->get_error_code() === 'http_request_failed') {
                            if (isset($_POST['verify'])) {
                                $messageContent = 'Success verify.';
                                update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, $code);
                            } else {
                                $messageContent = 'Success unverify.';
                                update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, '');
                            }

                            $message = 'successCheck';
                        } else {
                            $messageContent = '(Code - '
                                . $response->get_error_code()
                                . ') '
                                . $response->get_error_message();

                            $message = 'failedCheck';
                        }
                    } else {
                        if ($response->status === 'successCheck' && isset($_POST['verify'])) {
                            update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, $code);
                        } else {
                            update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, '');
                        }

                        $messageContent = $response->message;
                        $message = $response->status;
                    }

                    wp_safe_redirect(
                        $this->menuPageUrl(
                            [
                                'action' => 'setup',
                                'message' => $message,
                                'messageContent' => rawurlencode($messageContent)
                            ]
                        )
                    );

                    exit();
                } elseif (isset($_POST['cf7AmoCrmReloadFieldsCache'])) {
                    CRM::updateInformation();

                    $redirect = $this->menuPageUrl(
                        [
                            'message' => 'success-update-cache',
                            'action' => 'setup'
                        ]
                    );

                    wp_safe_redirect($redirect);
                    exit();
                } else {
                    check_admin_referer('wpcf7-amocrm-integration-setup');
                    $redirectUrl = isset($_POST['redirect-url']) ? trim(wp_unslash($_POST['redirect-url'])) : '';
                    $domain = isset($_POST['domain']) ? trim(wp_unslash($_POST['domain']), '/') : '';
                    $clientID = isset($_POST['client-id']) ? trim(wp_unslash($_POST['client-id'])) : '';
                    $clientSecret = isset($_POST['client-secret']) ? trim(wp_unslash($_POST['client-secret'])) : '';
                    $authCode = isset($_POST['authorization-code']) ? trim(wp_unslash($_POST['authorization-code'])) : '';
                    $trackingId = isset($_POST['trackingId']) ? trim(wp_unslash($_POST['trackingId'])) : '';

                    if (!empty($clientID) && !empty($clientSecret) && !empty($domain) && empty($authCode)) {
                        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);
                        $settings['enabled_logging'] = isset($_POST['enabled_logging']) ? wp_unslash($_POST['enabled_logging']) : '';
                        $settings['send_type'] = isset($_POST['send_type']) ? trim(wp_unslash($_POST['send_type'])) : '';
                        $settings['trackingId'] = $trackingId;

                        \WPCF7::update_option(
                            Bootstrap::OPTIONS_KEY,
                            $settings
                        );

                        $redirect = $this->menuPageUrl(['message' => 'success', 'action' => 'setup']);

                        wp_safe_redirect($redirect);

                        exit();
                    }

                    if (empty($clientID) || empty($clientSecret) || empty($domain) || empty($authCode)) {
                        $redirect = $this->menuPageUrl(['message' => 'invalid', 'action' => 'setup']);
                    } else {
                        $redirect = $this->menuPageUrl(['message' => 'success', 'action' => 'setup']);
                    }

                    \WPCF7::update_option(
                        Bootstrap::OPTIONS_KEY,
                        [
                            'redirect-url' => $redirectUrl,
                            'domain' => $domain,
                            'client-id' => $clientID,
                            'client-secret' => $clientSecret,
                            'authorization-code' => $authCode,
                            'trackingId' => $trackingId,
                            'send_type' => isset($_POST['send_type']) ? trim(wp_unslash($_POST['send_type'])) : '',
                            'enabled_logging' => isset($_POST['enabled_logging']) ? wp_unslash($_POST['enabled_logging']) : ''
                        ]
                    );

                    // Check connection and update CRM information of lead statuses and custom fields
                    CRM::checkConnection();
                    CRM::updateInformation();

                    wp_safe_redirect($redirect);

                    exit();
                }
            }
        }
    }

    // @codingStandardsIgnoreStart
    public function admin_notice($message = '')
    {
        // @codingStandardsIgnoreEnd
        if ('invalid' === $message) {
            echo sprintf(
                '<div class="error notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
                esc_html__('ERROR', 'cf7-amocrm-integration'),
                esc_html__('To integrate with amoCRM, you must fill in all fields.', 'cf7-amocrm-integration')
            );
        } elseif ('success' === $message) {
            echo sprintf(
                '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html__('Settings successfully updated.', 'cf7-amocrm-integration')
            );
        } elseif ($message == 'successCheck') {
            echo sprintf(
                '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(isset($_GET['messageContent']) ? $_GET['messageContent'] : '')
            );
        } elseif ($message == 'success-update-cache') {
            echo sprintf(
                '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html__('Fields cache updated successfully.', 'cf7-amocrm-integration')
            );
        } elseif (isset($_GET['messageContent'])) {
            echo sprintf(
                '<div class="error notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html(isset($_GET['messageContent']) ? $_GET['messageContent'] : '')
            );
        }
    }

    public function display($action = '')
    {
        $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);
        ?>
        <p>
            <?php
            esc_html_e(
                'Formation of leads in amoCRM from the hits that users leave on your site, using '
                    . 'the Contact Form 7 plugin.',
                'cf7-amocrm-integration'
            );
            ?>
        </p>
        <?php
        if ('setup' == $action) {
            ?>
            <form method="post" action="<?php echo esc_url($this->menuPageUrl('action=setup')); ?>">
                <?php wp_nonce_field('wpcf7-amocrm-integration-setup'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="domain">
                                    <?php esc_html_e('Redirect URL', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="url"
                                    aria-required="true"
                                    value="<?php
                                    echo esc_url(Helper::getRedirectUrl());
                                    ?>"
                                    id="redirect-url"
                                    name="redirect-url"
                                    required
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="domain">
                                    <?php esc_html_e('Domain Name', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                    aria-required="true"
                                    value="<?php
                                    echo isset($settings['domain'])
                                        ? esc_attr($settings['domain'])
                                        : '';
                                    ?>"
                                    id="domain"
                                    placeholder="example.amocrm.ru"
                                    name="domain"
                                    class="regular-text">
                                <small>
                                    <?php
                                    esc_html_e(
                                        'domain of your amoCRM account (without http:// or https://).',
                                        'cf7-amocrm-integration'
                                    );
                                    ?>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="client-secret">
                                    <?php esc_html_e('Secret key', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                    aria-required="true"
                                    value="<?php
                                    echo isset($settings['client-secret'])
                                        ? esc_attr($settings['client-secret'])
                                        : '';
                                    ?>"
                                    id="client-secret"
                                    name="client-secret"
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="client-id">
                                    <?php esc_html_e('Integration ID', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                    aria-required="true"
                                    value="<?php
                                    echo isset($settings['client-id'])
                                        ? esc_attr($settings['client-id'])
                                        : '';
                                    ?>"
                                    id="client-id"
                                    name="client-id"
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="authorization-code">
                                    <?php esc_html_e('Authorization code', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                    aria-required="true"
                                    value="<?php
                                    echo isset($settings['authorization-code'])
                                        ? esc_attr($settings['authorization-code'])
                                        : '';
                                    ?>"
                                    id="authorization-code"
                                    name="authorization-code"
                                    class="regular-text">
                                <small><?php esc_html_e('Authorization code - will remain empty after saving the '
                                    . 'settings, as it is used once to get the first token', 'cf7-amocrm-integration');
                                ?></small>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="trackingId">
                                    <?php esc_html_e('Google Analytics Tracking ID', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                    aria-required="true"
                                    value="<?php
                                    echo isset($settings['trackingId'])
                                        ? esc_attr($settings['trackingId'])
                                        : '';
                                    ?>"
                                    id="trackingId"
                                    name="trackingId"
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="enabled_logging">
                                    <?php esc_html_e('Enable logging', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="hidden" value="0" name="enabled_logging">
                                <input type="checkbox"
                                    value="1"
                                    <?php echo isset($settings['enabled_logging']) && $settings['enabled_logging'] == '1' ? 'checked' : ''; ?>
                                    id="enabled_logging"
                                    name="enabled_logging">
                                <br>
                                <small><?php echo esc_html(CF7_AMOCRM_PLUGIN_LOG_FILE); ?></small>
                                <hr>
                                <a href="<?php echo esc_url(admin_url() . '?' . Bootstrap::OPTIONS_KEY . '-logs-get'); ?>"
                                    class="button"
                                    target="_blank">
                                    <?php echo esc_html__('Download log', 'cf7-amocrm-integration'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url()); ?>admin.php?page=wpcf7-integration&service=cf7-amocrm-integration&action=setup&<?php echo Bootstrap::OPTIONS_KEY; ?>-logs-clear"
                                    class="button">
                                    <?php echo esc_html__('Clear log', 'cf7-amocrm-integration'); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="send_type">
                                    <?php esc_html_e('Send type', 'cf7-amocrm-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <select name="send_type" id="send_type">
                                    <option value="wp_cron" <?php
                                    echo empty($settings['send_type']) || $settings['send_type'] == 'wp_cron' ? 'selected' : '';
                                    ?>>
                                        <?php esc_html_e('WP Cron', 'cf7-amocrm-integration'); ?>
                                    </option>
                                    <option value="immediately" <?php
                                    echo isset($settings['send_type']) && $settings['send_type'] == 'immediately' ? 'selected' : '';
                                    ?>>
                                        <?php esc_html_e('Immediately upon submitting the form', 'cf7-amocrm-integration'); ?>
                                    </option>
                                </select>
                                <?php if ($this->is_active() && !empty($settings['send_type']) && $settings['send_type'] == 'wp_cron') { ?>
                                    <p class="descripton">
                                        <?php esc_html_e('The number of registered form submit events pending', 'cf7-amocrm-integration'); ?>:
                                        <strong>
                                            <?php echo (int) $this->getCountEvents(); ?>
                                        </strong>
                                    </p>
                                <?php } ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php
                echo sprintf(
                    '%1$s <a href="%2$s" target="_blank">%3$s</a>. %4$s.',
                    esc_html__('Plugin documentation: ', 'cf7-amocrm-integration'),
                    esc_url(CF7_AMOCRM_PLUGIN_URL . 'documentation/index.html#step-1'),
                    esc_html__('open', 'cf7-amocrm-integration'),
                    esc_html__('Or open the folder `documentation` in the plugin and open index.html', 'cf7-amocrm-integration')
                );
                ?>
                <p class="submit">
                    <input type="submit"
                        class="button button-primary"
                        value="<?php esc_attr_e('Save settings', 'cf7-amocrm-integration'); ?>"
                        name="submit">
                </p>
            </form>
            <?php if ($this->is_active()) { ?>
                <hr>
                <form action="" method="post">
                    <input
                        type="submit"
                        class="button button-primary"
                        name="cf7AmoCrmReloadFieldsCache"
                        value="<?php esc_html_e('Reload fields data from CRM', 'cf7-amocrm-integration'); ?>">
                </form>
            <?php } ?>
            <hr>
            <?php $code = get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY); ?>
            <h1>
                <?php esc_html_e('License verification', 'cf7-amocrm-integration'); ?>
                <?php if ($code) { ?>
                    - <small style="color: green;">
                        <?php esc_html_e('verified', 'cf7-amocrm-integration'); ?>
                    </small>
                <?php } else { ?>
                    - <small style="color: red;">
                        <?php esc_html_e('please verify your purchase code', 'cf7-amocrm-integration'); ?>
                    </small>
                <?php } ?>
            </h1>
            <form method="post" action="<?php echo esc_url($this->menuPageUrl('action=setup')); ?>">
                <?php wp_nonce_field('wpcf7-amocrm-integration-setup-license'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="purchase-code">
                                <?php esc_html_e('Purchase code', 'cf7-amocrm-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                aria-required="true"
                                required
                                value="<?php
                                echo !empty($code)
                                    ? esc_attr($code)
                                    : '';
                                ?>"
                                id="purchase-code"
                                name="purchase-code"
                                class="large-text">
                            <small>
                                <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-"
                                    target="_blank">
                                    <?php esc_html_e('Where Is My Purchase Code?', 'cf7-amocrm-integration'); ?>
                                </a>
                            </small>
                        </td>
                    </tr>
                </table>
                <p>
                    <input type="submit"
                        class="button button-primary"
                        value="<?php esc_attr_e('Verify', 'cf7-amocrm-integration'); ?>"
                        name="verify">
                    <?php if ($code) { ?>
                        <input type="submit"
                            class="button button-primary"
                            value="<?php esc_attr_e('Unverify', 'cf7-amocrm-integration'); ?>"
                            name="unverify">
                    <?php } ?>
                </p>
            </form>
            <?php
        } else {
            if ($this->is_active()) {
                ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Domain Name', 'cf7-amocrm-integration'); ?></th>
                            <td class="code"><?php echo esc_html($settings['domain']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Integration ID', 'cf7-amocrm-integration'); ?></th>
                            <td class="code"><?php echo esc_html($settings['client-id']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Google Analytics Tracking ID', 'cf7-amocrm-integration'); ?></th>
                            <td class="code"><?php echo esc_html($settings['trackingId']); ?></td>
                        </tr>
                    </tbody>
                </table>
                <p>
                    <a href="<?php echo esc_url($this->menuPageUrl('action=setup')); ?>" class="button">
                        <?php esc_html_e('Change settings', 'cf7-amocrm-integration'); ?>
                    </a>
                </p>
                <?php
            } else {
                ?>
                <p>
                    <?php
                    esc_html_e(
                        'To work with the plugin, you must configure integration with amoCRM.',
                        'cf7-amocrm-integration'
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($this->menuPageUrl('action=setup')); ?>" class="button">
                        <?php esc_html_e('Go to setup', 'cf7-amocrm-integration'); ?>
                    </a>
                </p>
                <p class="description">
                    <?php
                    esc_html_e(
                        'The fields sent to the CRM are configured on the form editing page, on the "amoCRM" tab.',
                        'cf7-amocrm-integration'
                    );
                    ?>
                </p>
                <?php
            }
        }
    }

    public function settingsPanels($panels)
    {
        $panels['amocrm-panel'] = [
            'title' => esc_html__('amoCRM', 'cf7-amocrm-integration'),
            'callback' => [$this, 'panel']
        ];

        return $panels;
    }

    public function panel(\WPCF7_ContactForm $post)
    {
        $additionalFields = get_option(Bootstrap::OPTIONS_CUSTOM_FIELDS);
        $crmFields = new CrmFields();
        ?>
        <input type="hidden" name="cf7amoCRM[ENABLED]" value="0">
        <input
            type="checkbox"
            name="cf7amoCRM[ENABLED]" value="1"
            <?php checked(get_post_meta($post->id(), Bootstrap::META_PREFIX . 'ENABLED', true), true); ?>
            title="<?php esc_attr_e('Send the lead to amoCRM', 'cf7-amocrm-integration'); ?>">
        <strong><?php esc_html_e('Send the lead to amoCRM', 'cf7-amocrm-integration'); ?></strong>
        <br><br>
        <?php echo esc_html_e( 'In the following fields, you can use these mail-tags:', 'contact-form-7'); ?>
        <br>
        <?php
        $post->suggest_mail_tags();
        $currentType = get_post_meta($post->id(), Bootstrap::META_PREFIX . 'TYPE', true);
        $currentType = in_array($currentType, Bootstrap::$sendTypes) ? $currentType : 'leads';
        ?>
        <br><br>
        Utm-fields:<br>
        <span class="mailtag code">[utm_source]</span>
        <span class="mailtag code">[utm_medium]</span>
        <span class="mailtag code">[utm_campaign]</span>
        <span class="mailtag code">[utm_term]</span>
        <span class="mailtag code">[utm_content]</span>
        <span class="mailtag code">and etc.</span>
        <br><br>
        Roistat-fields:<br>
        <span class="mailtag code">[roistat_visit]</span>
        <br><br>
        GA fields:<br>
        <span class="mailtag code">[gaClientID]</span>
        <br><br>
        Yandex fields:<br>
        <span class="mailtag code">[yandexClientID]</span>
        <br><br>
        <strong><?php esc_html_e('Choose the type of lead that will be generated in CRM:', 'cf7-amocrm-integration'); ?></strong>
        <br>
        <input type="radio"
            value="leads"
            name="cf7amoCRM[TYPE]"
            title="<?php esc_attr_e('Lead', 'cf7-amocrm-integration'); ?>"
            <?php checked($currentType, 'leads'); ?>>
        <?php esc_html_e('Deal (use fields contact, company, lead and task)', 'cf7-amocrm-integration'); ?>
        <br>
        <input type="radio"
            value="contacts"
            name="cf7amoCRM[TYPE]"
            title="<?php esc_html_e('Contact', 'cf7-amocrm-integration'); ?>"
            <?php checked($currentType, 'contacts'); ?>>
        <?php esc_html_e('Contact', 'cf7-amocrm-integration'); ?>
        <br>
        <input type="radio"
            value="unsorted"
            name="cf7amoCRM[TYPE]"
            title="<?php esc_html_e('Incoming Leads', 'cf7-amocrm-integration'); ?>"
            <?php checked($currentType, 'unsorted'); ?>>
        <?php esc_html_e('Incoming Leads (use fields contact and lead)', 'cf7-amocrm-integration'); ?>

        <hr>
        <div id="amocrm-tabs">
            <ul>
                <li>
                    <a href="#cf7-deal-fields">
                        <?php esc_html_e('Lead fields', 'cf7-amocrm-integration'); ?>
                    </a>
                </li>
                <li>
                    <a href="#cf7-contact-fields">
                        <?php esc_html_e('Contact fields', 'cf7-amocrm-integration'); ?>
                    </a>
                </li>
                <li>
                    <a href="#cf7-company-fields">
                        <?php esc_html_e('Company fields', 'cf7-amocrm-integration'); ?>
                    </a>
                </li>
                <li>
                    <a href="#cf7-note-fields">
                        <?php esc_html_e('Note fields', 'cf7-amocrm-integration'); ?>
                    </a>
                </li>
                <li>
                    <a href="#cf7-task-fields">
                        <?php esc_html_e('Task fields', 'cf7-amocrm-integration'); ?>
                    </a>
                </li>
            </ul>
            <div id="cf7-deal-fields">
                <table class="form-table">
                    <tbody>
                        <?php
                        foreach ($crmFields->leads as $key => $field) {
                            ?>
                            <tr>
                                <th scope="row" style="word-break: break-all;">
                                    <label for="__<?php echo esc_attr($key); ?>">
                                        <?php
                                        echo wp_kses_post($field['name']);
                                        echo isset($field['required']) && $field['required'] === true
                                            ? '<span style="color:red;"> * </span>'
                                            : '';
                                        ?>
                                    </label>
                                </th>
                                <td>
                                    <?php
                                    if ($key === 'status_id') {
                                        $currentStatus = get_post_meta(
                                            $post->id(),
                                            Bootstrap::META_PREFIX . '-leads-' . $key,
                                            true
                                        );
                                        ?>
                                        <select id="__<?php echo esc_attr($key); ?>"
                                            title="<?php echo esc_attr($field['name']); ?>"
                                            name="cf7amoCRM[leads][<?php echo esc_attr($key); ?>]">
                                            <?php
                                            $pipelines = get_option(Bootstrap::OPTIONS_PIPELINES);

                                            foreach ($pipelines as $pipelineID => $pipeline) {
                                                if (empty($pipeline['statuses'])) {
                                                    continue;
                                                }

                                                echo '<optgroup label="' . esc_attr($pipeline['label']) . '">';

                                                foreach ($pipeline['statuses'] as $statusID => $status) {
                                                    // show only deal stages
                                                    if ($status['type'] !== 0) {
                                                        continue;
                                                    }

                                                    $statusValue = $pipelineID . '.' . $statusID;
                                                    ?>
                                                    <option value="<?php echo esc_attr($statusValue); ?>"
                                                        <?php selected($currentStatus, $statusValue); ?>>
                                                        <?php echo esc_attr($status['name']); ?>
                                                    </option>
                                                    <?php
                                                }

                                                echo '</optgroup>';
                                            }
                                            ?>
                                        </select>
                                        <?php
                                    } else {
                                        ?>
                                        <input id="__<?php echo esc_attr($key); ?>"
                                            type="text"
                                            class="large-text code"
                                            title="<?php echo esc_attr($field['name']); ?>"
                                            name="cf7amoCRM[leads][<?php echo esc_attr($key); ?>]"
                                            value="<?php
                                            echo esc_attr(get_post_meta(
                                                $post->id(),
                                                Bootstrap::META_PREFIX . '-leads-' . $key,
                                                true
                                            ));
                                            ?>">
                                        <?php
                                    }

                                    if (isset($field['description'])) { ?>
                                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                                        <?php
                                    }

                                    if (isset($field['defaultValues'])) {
                                        foreach ($field['defaultValues'] as $fieldKey => $fieldValue) {
                                            ?>
                                            <p><?php echo wp_kses_post($fieldKey . ' - ' . $fieldValue);?></p>
                                            <?php
                                        }
                                    }

                                    if ($key === 'responsible_user_id') {
                                        $users = get_option(Bootstrap::OPTIONS_USERS);
                                        $showUsers = [];

                                        foreach ($users as $user) {
                                            $showUsers[] = $user['id'] . ' - ' . $user['login'];
                                        }

                                        echo '<p class="description">'
                                            . implode(', ', $showUsers)
                                            . '</p>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo wp_kses_post($field['type']); ?></td>
                            </tr>
                            <?php
                        }

                        if (!empty($additionalFields['leads']) && is_array($additionalFields['leads'])) {
                            foreach ($additionalFields['leads'] as $field) {
                                // Not show plugin created analytics fields
                                if (isset($field['name']) && in_array($field['name'], ['GA UTM', 'UTM CONTENT'])) {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <th scope="row" style="word-break: break-all;">
                                        <label for="__<?php echo esc_attr($field['id']); ?>">
                                            <?php
                                            echo wp_kses_post($field['name']);
                                            echo isset($field['required']) && $field['required'] === true
                                                ? '<span style="color:red;"> * </span>'
                                                : '';
                                            ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php if (!empty($field['enums'])) { ?>
                                            <table width="100%">
                                                <tr>
                                                    <td style="width: 50%;">
                                                        <label><?php esc_html_e('Default value', 'cf7-amocrm-integration'); ?></label>
                                                        <br>
                                                        <select id="__<?php echo esc_attr($field['id']); ?>"
                                                            title="<?php echo esc_attr($field['name']); ?>"
                                                            name="cf7amoCRM[leads][<?php echo esc_attr($field['id']); ?>]">
                                                            <option value="">Not chosen</option>
                                                            <?php
                                                            $currentValue = get_post_meta(
                                                                $post->id(),
                                                                Bootstrap::META_PREFIX . '-leads-' . $field['id'],
                                                                true
                                                            );
                                                            foreach ($field['enums'] as $value => $label) {
                                                                ?>
                                                                <option value="<?php echo esc_attr($value); ?>"
                                                                    <?php selected($currentValue, $value); ?>>
                                                                    <?php echo esc_attr($label); ?>
                                                                </option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $currentValuePopulate = get_post_meta(
                                                            $post->id(),
                                                            Bootstrap::META_PREFIX . '-leads-' . $field['id'] . '-populate',
                                                            true
                                                        );
                                                        ?>
                                                        <label><?php esc_html_e('Form value (optional)', 'cf7-amocrm-integration'); ?></label>
                                                        <br>
                                                        <input id="__<?php echo esc_attr($field['id']); ?>-populate"
                                                            type="text"
                                                            class="large-text code"
                                                            title="<?php echo esc_attr($field['name']); ?>"
                                                            name="cf7amoCRM[leads][<?php echo esc_attr($field['id']); ?>-populate]"
                                                            value="<?php echo esc_attr($currentValuePopulate); ?>">
                                                    </td>
                                                </tr>
                                            </table>
                                        <?php } else { ?>
                                            <input id="__<?php echo esc_attr($field['id']); ?>"
                                                type="text"
                                                class="large-text code"
                                                title="<?php echo esc_attr($field['name']); ?>"
                                                name="cf7amoCRM[leads][<?php echo esc_attr($field['id']); ?>]"
                                                value="<?php
                                                echo esc_attr(get_post_meta(
                                                    $post->id(),
                                                    Bootstrap::META_PREFIX . '-leads-' . $field['id'],
                                                    true
                                                ));
                                                ?>">
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo $field['code'];
                                        // escape ok
                                        ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div id="cf7-contact-fields">
                <input type="hidden" name="cf7amoCRM[update_contact]" value="0">
                <input type="checkbox"
                    value="1"
                    name="cf7amoCRM[update_contact]"
                    <?php checked(get_post_meta($post->id(), Bootstrap::META_PREFIX . '-update_contact', true), true); ?>
                    title="<?php esc_html_e('Update contact', 'cf7-amocrm-integration'); ?>">
                <?php
                esc_html_e(
                    'Update data in an existing contact (be careful, as this can change the data you specified through CRM)',
                    'cf7-amocrm-integration'
                );
                ?>
                <br>
                <table class="form-table">
                    <tbody>
                        <?php
                        foreach ($crmFields->contacts as $key => $field) {
                            ?>
                            <tr>
                                <th scope="row" style="word-break: break-all;">
                                    <label for="__<?php echo esc_attr($key); ?>">
                                        <?php
                                        echo wp_kses_post($field['name']);
                                        echo isset($field['required']) && $field['required'] === true
                                            ? '<span style="color:red;"> * </span>'
                                            : '';
                                        ?>
                                    </label>
                                </th>
                                <td>
                                    <input id="__<?php echo esc_attr($key); ?>"
                                        type="text"
                                        class="large-text code"
                                        title="<?php echo esc_attr($field['name']); ?>"
                                        name="cf7amoCRM[contacts][<?php echo esc_attr($key); ?>]"
                                        value="<?php
                                        echo esc_attr(get_post_meta(
                                            $post->id(),
                                            Bootstrap::META_PREFIX . '-contacts-' . $key,
                                            true
                                        ));
                                        ?>">
                                    <?php
                                    if (isset($field['description'])) {
                                        ?>
                                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                                        <?php
                                    }

                                    if (isset($field['defaultValues'])) {
                                        foreach ($field['defaultValues'] as $fieldKey => $fieldValue) {
                                            ?>
                                            <p><?php echo wp_kses_post($fieldKey . ' - ' . $fieldValue); ?></p>
                                            <?php
                                        }
                                    }

                                    if ($key === 'responsible_user_id') {
                                        $users = get_option(Bootstrap::OPTIONS_USERS);
                                        $showUsers = [];

                                        foreach ($users as $user) {
                                            $showUsers[] = $user['id'] . ' - ' . $user['login'];
                                        }

                                        echo '<p class="description">'
                                            . implode(', ', $showUsers)
                                            . '</p>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo wp_kses_post($field['type']); ?></td>
                            </tr>
                            <?php
                        }

                        if (!empty($additionalFields['contacts']) && is_array($additionalFields['contacts'])) {
                            foreach ($additionalFields['contacts'] as $field) {
                                ?>
                                <tr>
                                    <th scope="row" style="word-break: break-all;">
                                        <label for="__<?php echo esc_attr($field['id']); ?>">
                                            <?php
                                            echo wp_kses_post($field['name']);
                                            echo isset($field['required']) && $field['required'] === true
                                                ? '<span style="color:red;"> * </span>'
                                                : '';
                                            ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php if (!in_array($field['code'], ['PHONE', 'EMAIL', 'IM']) && !empty($field['enums'])) { ?>
                                            <table width="100%">
                                                <tr>
                                                    <td style="width: 50%;">
                                                        <label><?php esc_html_e('Default value', 'cf7-amocrm-integration'); ?></label>
                                                        <br>
                                                        <select id="__<?php echo esc_attr($field['id']); ?>"
                                                            title="<?php echo esc_attr($field['name']); ?>"
                                                            name="cf7amoCRM[contacts][<?php echo esc_attr($field['id']); ?>]">
                                                            <option value="">Not chosen</option>
                                                            <?php
                                                            $currentValue = get_post_meta(
                                                                $post->id(),
                                                                Bootstrap::META_PREFIX . '-contacts-' . $field['id'],
                                                                true
                                                            );
                                                            foreach ($field['enums'] as $value => $label) {
                                                                ?>
                                                                <option value="<?php echo esc_attr($value); ?>"
                                                                    <?php selected($currentValue, $value); ?>>
                                                                    <?php echo esc_attr($label); ?>
                                                                </option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $currentValuePopulate = get_post_meta(
                                                            $post->id(),
                                                            Bootstrap::META_PREFIX . '-contacts-' . $field['id'] . '-populate',
                                                            true
                                                        );
                                                        ?>
                                                        <label><?php esc_html_e('Form value (optional)', 'cf7-amocrm-integration'); ?></label>
                                                        <br>
                                                        <input id="__<?php echo esc_attr($field['id']); ?>-populate"
                                                            type="text"
                                                            class="large-text code"
                                                            title="<?php echo esc_attr($field['name']); ?>"
                                                            name="cf7amoCRM[contacts][<?php echo esc_attr($field['id']); ?>-populate]"
                                                            value="<?php echo esc_attr($currentValuePopulate); ?>">
                                                    </td>
                                                </tr>
                                            </table>
                                        <?php } else { ?>
                                            <input id="__<?php echo esc_attr($field['id']); ?>"
                                                type="text"
                                                class="large-text code"
                                                title="<?php echo esc_attr($field['name']); ?>"
                                                name="cf7amoCRM[contacts][<?php echo esc_attr($field['id']); ?>]"
                                                value="<?php
                                                echo esc_attr(get_post_meta(
                                                    $post->id(),
                                                    Bootstrap::META_PREFIX . '-contacts-' . $field['id'],
                                                    true
                                                ));
                                                ?>">
                                            <?php if ($field['code'] === 'PHONE') { ?>
                                                <label for="__contact_phone_is_mobile">
                                                    <input type="hidden" name="cf7amoCRM[contact_phone_is_mobile]" value="0">
                                                    <input
                                                        type="checkbox"
                                                        id="__contact_phone_is_mobile"
                                                        name="cf7amoCRM[contact_phone_is_mobile]" value="1"
                                                        <?php checked(get_post_meta($post->id(), Bootstrap::META_PREFIX . '-contact_phone_is_mobile', true), true); ?>
                                                        title="<?php esc_attr_e('Write phone as mobile (work by default)'); ?>">
                                                    <?php
                                                    echo esc_html_e('Write phone as mobile (work by default)', 'cf7-amocrm-integration');
                                                    ?>
                                                </label>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo wp_kses_post($field['code']); ?></td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div id="cf7-company-fields">
                <table class="form-table">
                    <tbody>
                        <?php
                        foreach ($crmFields->companies as $key => $field) {
                            ?>
                            <tr>
                                <th scope="row" style="word-break: break-all;">
                                    <label for="__<?php echo esc_attr($key); ?>">
                                        <?php
                                        echo wp_kses_post($field['name']);
                                        echo isset($field['required']) && $field['required'] === true
                                            ? '<span style="color:red;"> * </span>'
                                            : '';
                                        ?>
                                    </label>
                                </th>
                                <td>
                                    <input id="__<?php echo esc_attr($key); ?>"
                                        type="text"
                                        class="large-text code"
                                        title="<?php echo esc_attr($field['name']); ?>"
                                        name="cf7amoCRM[companies][<?php echo esc_attr($key); ?>]"
                                        value="<?php
                                        echo esc_attr(get_post_meta(
                                            $post->id(),
                                            Bootstrap::META_PREFIX . '-companies-' . $key,
                                            true
                                        ));
                                        ?>">
                                    <?php
                                    if (isset($field['description'])) {
                                        ?>
                                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                                        <?php
                                    }

                                    if (isset($field['defaultValues'])) {
                                        foreach ($field['defaultValues'] as $fieldKey => $fieldValue) {
                                            ?>
                                            <p><?php echo wp_kses_post($fieldKey . ' - ' . $fieldValue); ?></p>
                                            <?php
                                        }
                                    }
                                    ?>
                                    <?php
                                    if ($key === 'responsible_user_id') {
                                        $users = get_option(Bootstrap::OPTIONS_USERS);
                                        $showUsers = [];

                                        foreach ($users as $user) {
                                            $showUsers[] = $user['id'] . ' - ' . $user['login'];
                                        }

                                        echo '<p class="description">'
                                            . implode(', ', $showUsers)
                                            . '</p>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo wp_kses_post($field['type']); ?></td>
                            </tr>
                            <?php
                        }

                        if (!empty($additionalFields['companies']) && is_array($additionalFields['companies'])) {
                            foreach ($additionalFields['companies'] as $field) {
                                ?>
                                <tr>
                                    <th scope="row" style="word-break: break-all;">
                                        <label for="__<?php echo esc_attr($field['id']); ?>">
                                            <?php
                                            echo wp_kses_post($field['name']);
                                            echo isset($field['required']) && $field['required'] === true
                                                ? '<span style="color:red;"> * </span>'
                                                : '';
                                            ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php if (!in_array($field['code'], ['PHONE', 'EMAIL', 'IM']) && !empty($field['enums'])) { ?>
                                            <table width="100%">
                                                <tr>
                                                    <td style="width: 50%;">
                                                        <label><?php esc_html_e('Default value', 'cf7-amocrm-integration'); ?></label>
                                                        <br>
                                                        <select id="__<?php echo esc_attr($field['id']); ?>"
                                                            title="<?php echo esc_attr($field['name']); ?>"
                                                            name="cf7amoCRM[companies][<?php echo esc_attr($field['id']); ?>]">
                                                            <option value="">Not chosen</option>
                                                            <?php
                                                            $currentValue = get_post_meta(
                                                                $post->id(),
                                                                Bootstrap::META_PREFIX . '-companies-' . $field['id'],
                                                                true
                                                            );
                                                            foreach ($field['enums'] as $value => $label) {
                                                                ?>
                                                                <option value="<?php echo esc_attr($value); ?>"
                                                                    <?php selected($currentValue, $value); ?>>
                                                                    <?php echo esc_attr($label); ?>
                                                                </option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $currentValuePopulate = get_post_meta(
                                                            $post->id(),
                                                            Bootstrap::META_PREFIX . '-companies-' . $field['id'] . '-populate',
                                                            true
                                                        );
                                                        ?>
                                                        <label><?php esc_html_e('Form value (optional)', 'cf7-amocrm-integration'); ?></label>
                                                        <br>
                                                        <input id="__<?php echo esc_attr($field['id']); ?>-populate"
                                                            type="text"
                                                            class="large-text code"
                                                            title="<?php echo esc_attr($field['name']); ?>"
                                                            name="cf7amoCRM[companies][<?php echo esc_attr($field['id']); ?>-populate]"
                                                            value="<?php echo esc_attr($currentValuePopulate); ?>">
                                                    </td>
                                                </tr>
                                            </table>
                                        <?php } else { ?>
                                            <input id="__<?php echo esc_attr($field['id']); ?>"
                                                type="text"
                                                class="large-text code"
                                                title="<?php echo esc_attr($field['name']); ?>"
                                                name="cf7amoCRM[companies][<?php echo esc_attr($field['id']); ?>]"
                                                value="<?php
                                                echo esc_attr(get_post_meta(
                                                    $post->id(),
                                                    Bootstrap::META_PREFIX . '-companies-' . $field['id'],
                                                    true
                                                ));
                                                ?>">
                                        <?php } ?>
                                    </td>
                                    <td><?php echo wp_kses_post($field['code']); ?></td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div id="cf7-note-fields">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row" style="word-break: break-all;">
                                <label for="__note_contact">
                                    <?php
                                    echo esc_html_e('Note text', 'cf7-amocrm-integration');
                                    ?>
                                </label>
                            </th>
                            <td>
                                <?php $note = get_post_meta($post->id(), Bootstrap::META_PREFIX . '-note', true); ?>
                                <textarea
                                    id="__note_contact"
                                    class="large-text code"
                                    name="cf7amoCRM[note]"><?php echo esc_attr($note); ?></textarea>
                                <p class="description">
                                    <?php
                                    esc_html_e(
                                        'ip, user agent, date and time, referrer - added auto',
                                        'cf7-amocrm-integration');
                                    ?>
                                </p>

                                <label for="__note_disable">
                                    <input type="hidden" name="cf7amoCRM[disable_note_additional_meta]" value="0">
                                    <input
                                        type="checkbox"
                                        id="__note_disable"
                                        name="cf7amoCRM[disable_note_additional_meta]" value="1"
                                        <?php checked(get_post_meta($post->id(), Bootstrap::META_PREFIX . '-disable_note_additional_meta', true), true); ?>
                                        title="<?php esc_attr_e('Disable auto add - ip, user agent, date and time, referrer'); ?>">
                                    <?php
                                    echo esc_html_e('Disable auto add - ip, user agent, date and time, referrer', 'cf7-amocrm-integration');
                                    ?>
                                </label>
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="cf7-task-fields">
                <table class="form-table">
                    <tbody>
                        <?php
                        foreach ($crmFields->task as $key => $field) {
                            ?>
                            <tr>
                                <th scope="row" style="word-break: break-all;">
                                    <label for="__<?php echo esc_attr($key); ?>">
                                        <?php
                                        echo wp_kses_post($field['name']);
                                        echo isset($field['required']) && $field['required'] === true
                                            ? '<span style="color:red;"> * </span>'
                                            : '';
                                        ?>
                                    </label>
                                </th>
                                <td>
                                    <?php if (!empty($field['items'])) { ?>
                                        <?php
                                        $currentValue = get_post_meta(
                                            $post->id(),
                                            Bootstrap::META_PREFIX . '-task-' . $key,
                                            true
                                        );
                                        ?>
                                        <select id="__<?php echo esc_attr($key); ?>"
                                            title="<?php echo esc_attr($field['name']); ?>"
                                            name="cf7amoCRM[task][<?php echo esc_attr($key); ?>]">
                                            <?php
                                            foreach ((array) $field['items'] as $value => $name) {
                                                echo '<option value="'
                                                    . esc_attr($value)
                                                    . '"'
                                                    . ($currentValue == $value ? ' selected' : '')
                                                    . '>'
                                                    . esc_html($value . ' - ' . $name)
                                                    . '</option>';
                                            }
                                            ?>
                                        </select>
                                    <?php } else { ?>
                                        <input id="__<?php echo esc_attr($key); ?>"
                                            type="text"
                                            class="large-text code"
                                            title="<?php echo esc_attr($field['name']); ?>"
                                            name="cf7amoCRM[task][<?php echo esc_attr($key); ?>]"
                                            value="<?php
                                            echo esc_attr(get_post_meta(
                                                $post->id(),
                                                Bootstrap::META_PREFIX . '-task-' . $key,
                                                true
                                            ));
                                            ?>">
                                    <?php } ?>
                                    <?php
                                    if (isset($field['description'])) {
                                        ?>
                                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                                        <?php
                                    }

                                    if (isset($field['defaultValues'])) {
                                        foreach ($field['defaultValues'] as $fieldKey => $fieldValue) {
                                            ?>
                                            <p><?php echo wp_kses_post($fieldKey . ' - ' . $fieldValue); ?></p>
                                            <?php
                                        }
                                    }

                                    if ($key === 'responsible_user_id') {
                                        $users = get_option(Bootstrap::OPTIONS_USERS);
                                        $showUsers = [];

                                        foreach ($users as $user) {
                                            $showUsers[] = $user['id'] . ' - ' . $user['login'];
                                        }

                                        echo '<p class="description">'
                                            . implode(', ', $showUsers)
                                            . '</p>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo wp_kses_post($field['type']); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function saveSettings($postID)
    {
        if (isset($_POST['cf7amoCRM']['ENABLED'])) {
            update_post_meta($postID, Bootstrap::META_PREFIX . 'ENABLED', wp_unslash($_POST['cf7amoCRM']['ENABLED']));
        }

        if (isset($_POST['cf7amoCRM']['TYPE'])) {
            update_post_meta($postID, Bootstrap::META_PREFIX . 'TYPE', wp_unslash($_POST['cf7amoCRM']['TYPE']));
        }

        $crmFields = new CrmFields();

        $types = ['leads', 'contacts', 'companies', 'task'];

        foreach ($types as $type) {
            foreach ($crmFields->$type as $key => $_) {
                if (isset($_POST['cf7amoCRM'][$type][$key])) {
                    update_post_meta(
                        $postID,
                        Bootstrap::META_PREFIX . '-' . $type . '-' . $key,
                        wp_unslash($_POST['cf7amoCRM'][$type][$key])
                    );
                }
            }
        }

        $additionalFields = get_option(Bootstrap::OPTIONS_CUSTOM_FIELDS);

        if (!empty($additionalFields['leads']) && is_array($additionalFields['leads'])) {
            foreach ($additionalFields['leads'] as $field) {
                if (isset($_POST['cf7amoCRM']['leads'][$field['id']])) {
                    update_post_meta(
                        $postID,
                        Bootstrap::META_PREFIX . '-leads-' . $field['id'],
                        wp_unslash($_POST['cf7amoCRM']['leads'][$field['id']])
                    );
                }

                if (isset($_POST['cf7amoCRM']['leads'][$field['id'] . '-populate'])) {
                    update_post_meta(
                        $postID,
                        Bootstrap::META_PREFIX . '-leads-' . $field['id'] . '-populate',
                        wp_unslash($_POST['cf7amoCRM']['leads'][$field['id'] . '-populate'])
                    );
                }
            }
        }

        if (!empty($additionalFields['contacts']) && is_array($additionalFields['contacts'])) {
            foreach ($additionalFields['contacts'] as $field) {
                if (isset($_POST['cf7amoCRM']['contacts'][$field['id']])) {
                    update_post_meta(
                        $postID,
                        Bootstrap::META_PREFIX . '-contacts-' . $field['id'],
                        wp_unslash($_POST['cf7amoCRM']['contacts'][$field['id']])
                    );
                }

                if (isset($_POST['cf7amoCRM']['contacts'][$field['id'] . '-populate'])) {
                    update_post_meta(
                        $postID,
                        Bootstrap::META_PREFIX . '-contacts-' . $field['id'] . '-populate',
                        wp_unslash($_POST['cf7amoCRM']['contacts'][$field['id'] . '-populate'])
                    );
                }
            }
        }

        if (!empty($additionalFields['companies']) && is_array($additionalFields['companies'])) {
            foreach ($additionalFields['companies'] as $field) {
                if (isset($_POST['cf7amoCRM']['companies'][$field['id']])) {
                    update_post_meta(
                        $postID,
                        Bootstrap::META_PREFIX . '-companies-' . $field['id'],
                        wp_unslash($_POST['cf7amoCRM']['companies'][$field['id']])
                    );
                }

                if (isset($_POST['cf7amoCRM']['companies'][$field['id'] . '-populate'])) {
                    update_post_meta(
                        $postID,
                        Bootstrap::META_PREFIX . '-companies-' . $field['id'] . '-populate',
                        wp_unslash($_POST['cf7amoCRM']['companies'][$field['id'] . '-populate'])
                    );
                }
            }
        }

        if (isset($_POST['cf7amoCRM']['disable_note_additional_meta'])) {
            update_post_meta(
                $postID,
                Bootstrap::META_PREFIX . '-disable_note_additional_meta',
                wp_unslash($_POST['cf7amoCRM']['disable_note_additional_meta'])
            );
        }

        if (isset($_POST['cf7amoCRM']['contact_phone_is_mobile'])) {
            update_post_meta(
                $postID,
                Bootstrap::META_PREFIX . '-contact_phone_is_mobile',
                wp_unslash($_POST['cf7amoCRM']['contact_phone_is_mobile'])
            );
        }

        if (isset($_POST['cf7amoCRM']['note'])) {
            update_post_meta(
                $postID,
                Bootstrap::META_PREFIX . '-note',
                wp_unslash($_POST['cf7amoCRM']['note'])
            );
        }

        if (isset($_POST['cf7amoCRM']['update_contact'])) {
            update_post_meta(
                $postID,
                Bootstrap::META_PREFIX . '-update_contact',
                wp_unslash($_POST['cf7amoCRM']['update_contact'])
            );
        }
    }

    protected function __clone()
    {
    }

    private function menuPageUrl($args = '')
    {
        $args = wp_parse_args($args, []);
        $url = menu_page_url('wpcf7-integration', false);
        $url = add_query_arg(['service' => 'cf7-amocrm-integration'], $url);

        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    private function getCountEvents()
    {
        $cronJobs = get_option('cron', []);
        $count = 0;

        foreach ($cronJobs as $time => $cron) {
            if (empty($cron[Bootstrap::SEND_CRON_TASK])) {
                continue;
            }

            $count += count($cron[Bootstrap::SEND_CRON_TASK]);
        }

        return $count;
    }
}
