<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\models;

use craft\base\Model;
use craft\helpers\ConfigHelper;

class Settings extends Model
{
    public $apiKey = '';
    public $listId = '';
    public $audienceId = '';
    public $doubleOptIn = true;

    public function getApiKey($siteHandle = null) {
        return ConfigHelper::localizedValue($this->apiKey, $siteHandle);
    }

    public function getAudienceId($siteHandle = null) {
        return ConfigHelper::localizedValue($this->audienceId, $siteHandle);
    }

    public function getDoubleOptIn($siteHandle = null) {
        return ConfigHelper::localizedValue($this->doubleOptIn, $siteHandle);
    }
}
