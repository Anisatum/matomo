<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DisableUrlDecoding;

use Piwik\Piwik;
use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;

/**
 * Defines Settings for DisableUrlDecoding.
 *
 * Usage like this:
 * $settings = new SystemSettings();
 * $settings->metric->getValue();
 * $settings->description->getValue();
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $disableUrlDecoding;

    protected function init()
    {
        $this->disableUrlDecoding = $this->makeDisableUrlDecoding();
    }
    private function makeDisableUrlDecoding()
    {
        $defaultValue = false;
        $type = FieldConfig::TYPE_BOOL;

        return $this->makeSetting('disable_decoding', $defaultValue, $type, function (FieldConfig $field) {
            $field->title = Piwik::translate('DisableUrlDecoding_DisableTitle');
            $field->inlineHelp = Piwik::translate('DisableUrlDecoding_DisableInlineHelp');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }
}
