<?php

namespace Itgalaxy\Cf7\AmoCRM\Integration\Includes;

class CF7
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
    add_action('wpcf7_mail_sent', [$this, 'onFormSubmit'], 10, 1);
  }

  public function onFormSubmit(\WPCF7_ContactForm $contactForm)
  {
    if (!class_exists('\\WPCF7_Submission')) {
      return;
    }

    if (!Helper::hasToken()) {
      return;
    }

    $submission = \WPCF7_Submission::get_instance();

    if (!$submission) {
      return;
    }

    $postedData = $submission->get_posted_data();

    if (!$postedData) {
      return;
    }

    if (!get_post_meta($contactForm->id(), Bootstrap::META_PREFIX . 'ENABLED', true)) {
      return;
    }

    $settings = \WPCF7::get_option(Bootstrap::OPTIONS_KEY);

    $postedData['roistat_visit'] = isset($_COOKIE['roistat_visit'])
      ? $_COOKIE['roistat_visit']
      : '';

    $postedData = $this->parseUtmCookie($postedData);

    // Set ga client id
    $postedData['gaClientID'] = '';

    if (!empty($_COOKIE['_ga'])) {
      $clientId = explode('.', wp_unslash($_COOKIE['_ga']));
      $postedData['gaClientID'] = $clientId[2] . '.' . $clientId[3];
    }

    // Set yandex client id
    $postedData['yandexClientID'] = '';

    if (!empty($_COOKIE['_ym_uid'])) {
      $postedData['yandexClientID'] = wp_unslash($_COOKIE['_ym_uid']);
    }

    // form title
    $postedData['formTitle'] = $contactForm->title();

    $uploadedFiles = $submission->uploaded_files();

    $sendFields = [];
    $keys = array_map(function ($key) {
      return '[' . $key . ']';
    }, array_keys($postedData));
    $values = array_values($postedData);
    array_walk($values, function (&$value) {
      if (is_array($value)) {
        $value = implode(', ', $value);
      }
    });

    $type = get_post_meta($contactForm->id(), Bootstrap::META_PREFIX . 'TYPE', true);
    $type = in_array($type, Bootstrap::$sendTypes) ? $type : 'unsorted';

    $crmFields = new CrmFields();
    $additionalFields = get_option(Bootstrap::OPTIONS_CUSTOM_FIELDS);

    Helper::log('send event form - ' . $contactForm->id());

    if ($type === 'contacts') {
      foreach ($crmFields->{$type} as $key => $_) {
        $value = get_post_meta($contactForm->id(), Bootstrap::META_PREFIX . '-' . $type . '-' . $key, true);

        if ($value) {
          $sendFields[$type][$key] = $this->replaceTagsToValue($keys, $values, $value);
        }
      }

      if (!empty($additionalFields[$type]) && is_array($additionalFields[$type])) {
        foreach ($additionalFields[$type] as $field) {
          $value = get_post_meta(
            $contactForm->id(),
            Bootstrap::META_PREFIX . '-' . $type . '-' . $field['id'],
            true
          );

          if ($value) {
            $sendFields[$type]['custom_fields'][$field['id']]
              = $this->replaceTagsToValue($keys, $values, $value);
          }
        }
      }
    } else {
      foreach (['contacts', 'companies', 'leads', 'task'] as $typeForeach) {
        foreach ($crmFields->$typeForeach as $key => $_) {
          $value = get_post_meta(
            $contactForm->id(),
            Bootstrap::META_PREFIX . '-' . $typeForeach . '-' . $key,
            true
          );

          if ($value) {
            $sendFields[$typeForeach][$key] = $this->replaceTagsToValue($keys, $values, $value);
          }
        }

        if (!empty($additionalFields[$typeForeach]) && is_array($additionalFields[$typeForeach])) {
          foreach ($additionalFields[$typeForeach] as $field) {
            $value = get_post_meta(
              $contactForm->id(),
              Bootstrap::META_PREFIX . '-' . $typeForeach . '-' . $field['id'],
              true
            );

            if ($value) {
              $value = $this->replaceTagsToValue($keys, $values, $value);
            }

            $populateValue = get_post_meta(
              $contactForm->id(),
              Bootstrap::META_PREFIX . '-' . $typeForeach . '-' . $field['id'] . '-populate',
              true
            );

            if ($populateValue) {
              $populateValue = $this->replaceTagsToValue($keys, $values, $populateValue);
            }

            if ($populateValue) {
              $value = $populateValue;
            }

            if ($value) {
              // is list
              if (!in_array($field['code'], ['PHONE', 'EMAIL', 'IM']) && !empty($field['enums'])) {
                $ids = array_keys($field['enums']);
                $labels = array_values($field['enums']);
                $labelsWithoutHtmlSpecialChars = array_values($field['enums']);

                foreach ($labelsWithoutHtmlSpecialChars as &$label) {
                  $label = html_entity_decode($label);
                }

                $explodedField = explode(', ', $value);
                $resolveValues = [];

                foreach ($explodedField as $explodeValue) {
                  if (array_search($explodeValue, $ids) !== false) {
                    $resolveValues[] = $explodeValue;
                  } elseif (array_search($explodeValue, $labels) !== false) {
                    $resolveValues[] = $ids[array_search($explodeValue, $labels)];
                  } elseif (array_search($explodeValue, $labelsWithoutHtmlSpecialChars) !== false) {
                    $resolveValues[] = $ids[array_search($explodeValue, $labelsWithoutHtmlSpecialChars)];
                  }
                }

                if ($resolveValues) {
                  // type_id = 5 - multiselect
                  $sendFields[$typeForeach]['custom_fields'][$field['id']]
                    = (int) $field['type_id'] === 5
                    ? $resolveValues
                    : $resolveValues[0];
                }
              } else {
                $sendFields[$typeForeach]['custom_fields'][$field['id']]
                  = $this->replaceTagsToValue($keys, $values, $value);
              }
            }
          }
        }
      }
    }

    $note = trim(get_post_meta($contactForm->id(), Bootstrap::META_PREFIX . '-note', true));

    if (!get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY)) {
      if ($note) {
        $note = $this->customText()
          . "\n"
          . $note;
      } else {
        $note = $this->customText();
      }
    }

    if ($note) {
      $sendFields['note'] = $this->replaceTagsToValue($keys, $values, $note);
    }

    $disableNoteMeta = get_post_meta(
      $contactForm->id(),
      Bootstrap::META_PREFIX . '-disable_note_additional_meta',
      true
    );

    if ((int) $disableNoteMeta === 1) {
      $sendFields['disable_note_additional_meta'] = true;
    }

    $contactPhoneIsMobile = get_post_meta(
      $contactForm->id(),
      Bootstrap::META_PREFIX . '-contact_phone_is_mobile',
      true
    );

    if ((int) $contactPhoneIsMobile === 1) {
      $sendFields['contact_phone_is_mobile'] = true;
    }

    if (!empty($uploadedFiles)) {
      $sendFields['files'] = $this->prepareUploads($uploadedFiles);
    }

    $updateContact = get_post_meta($contactForm->id(), Bootstrap::META_PREFIX . '-update_contact', true);

    if ((int) $updateContact) {
      $sendFields['update_contact'] = true;
    }

    Helper::log('send event form - ' . $contactForm->id() . ' - ' . $type, $sendFields);

    $contactFormData = [
      'id' => $contactForm->id(),
      'title' => $contactForm->title()
    ];

    //$sendFields['referrer'] = isset($_SERVER['HTTP_REFERER']) ? wp_unslash($_SERVER['HTTP_REFERER']) : '';
    $sendFields['referrer'] = isset($_COOKIE['nreferrer']) ? wp_unslash($_COOKIE['nreferrer']) : '';
    $sendFields['agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
    $sendFields['utm-cookies'] = isset($_COOKIE[Bootstrap::GOOGLE_ANALITICS_COOKIES])
      ? $_COOKIE[Bootstrap::GOOGLE_ANALITICS_COOKIES]
      : '';

    $sendFields['gaClientID'] = '';

    if (!empty($_COOKIE['_ga'])) {
      $clientId = explode('.', wp_unslash($_COOKIE['_ga']));
      $sendFields['gaClientID'] = $clientId[2] . '.' . $clientId[3];
    }

    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    $sendFields['ip'] = $ip;

    if (empty($settings['send_type']) || $settings['send_type'] === 'wp_cron') {
      Helper::log('register send form event - ' . $contactForm->id() . ' - ' . $type);
      wp_schedule_single_event(time() + 15, Bootstrap::SEND_CRON_TASK, [$sendFields, $type, $contactFormData, $postedData]);
    } else {
      CRM::send($sendFields, $type, $contactFormData, $postedData);
    }
  }

  public function parseUtmCookie($postedData)
  {
    $basedUtm = [
      'utm_source',
      'utm_medium',
      'utm_campaign',
      'utm_term',
      'utm_content'
    ];

    foreach ($basedUtm as $utm) {
      if (!isset($postedData[$utm])) {
        $postedData[$utm] = '';
      }
    }

    if (!empty($_COOKIE[Bootstrap::GOOGLE_ANALITICS_COOKIES])) {
      $utmParams = json_decode(wp_unslash($_COOKIE[Bootstrap::GOOGLE_ANALITICS_COOKIES]), true);

      foreach ($utmParams as $key => $value) {
        $postedData[$key] = rawurldecode(wp_unslash($value));
      }

      return $postedData;
    }

    return $postedData;
  }

  public function prepareUploads($files)
  {
    $uploadsDir = wp_upload_dir();
    $uploadedFilesLinks = [];

    if (!file_exists($uploadsDir['basedir'] . '/cf7-amocrm-integration')) {
      mkdir($uploadsDir['basedir'] . '/cf7-amocrm-integration', 0777);
    }

    $fileList = [];

    foreach ($files as $file) {
      if (is_array($file)) {
        foreach ($file as $subFile) {
          $fileList[] = $subFile;
        }
      } else {
        $fileList[] = $file;
      }
    }

    $fileList = array_unique($fileList);

    foreach ($fileList as $file) {
      if (!file_exists($file)) {
        continue;
      }

      $filePathInfo = pathinfo($file);
      $newFileName = uniqid()
        . '-'
        . $filePathInfo['basename'];

      $newFilePath = $uploadsDir['basedir']
        . '/cf7-amocrm-integration/'
        . $newFileName;

      copy($file, $newFilePath);

      $uploadedFilesLinks[] = $uploadsDir['baseurl']
        . '/cf7-amocrm-integration/'
        . $newFileName;
    }

    return $uploadedFilesLinks;
  }

  private function replaceTagsToValue($keys, $values, $value)
  {
    $value = trim(str_replace($keys, $values, $value));
    $value = $this->cookiesKeysProcess($value);

    if (function_exists('wpcf7_mail_replace_tags')) {
      $value = \wpcf7_mail_replace_tags($value);
    }

    return $value;
  }

  private function cookiesKeysProcess($value)
  {
    preg_match_all('/\[(cookies_value_.+?)\]/', $value, $matches);

    if (!empty($matches[1])) {
      foreach ($matches[1] as $metaKey) {
        $metaValue = isset($_COOKIE[str_replace('cookies_value_', '', $metaKey)])
          ? $_COOKIE[str_replace('cookies_value_', '', $metaKey)]
          : '';

        $value = trim(str_replace('[' . $metaKey . ']', $metaValue, $value));
      }
    }

    return $value;
  }

  private function customText()
  {
    return esc_html__(
      'Please verify the purchase code on the plugin integration settings page - ',
      'cf7-amocrm-integration'
    )
      . admin_url()
      . 'admin.php?page=wpcf7-integration&service=cf7-amocrm-integration&action=setup';
  }

  protected function __clone()
  {
    // Nothing
  }
}
