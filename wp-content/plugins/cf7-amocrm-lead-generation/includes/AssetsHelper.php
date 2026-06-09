<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Includes;

class AssetsHelper
{
    public static function getPathAssetFile($assetFile)
    {
        $manifestFile = CF7_AMOCRM_PLUGIN_DIR . 'resources/compiled/mix-manifest.json';

        if (!file_exists($manifestFile)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);

        if (!is_array($manifest) || !isset($manifest[$assetFile])) {
            return '';
        }

        return CF7_AMOCRM_PLUGIN_URL . 'resources/compiled' . $manifest[$assetFile];
    }
}
