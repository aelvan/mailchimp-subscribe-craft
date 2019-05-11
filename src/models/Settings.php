<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\models;

use craft\base\Model;

class Settings extends Model
{
    public $apiKey = '';
    public $listId = '';
    public $audienceId = '';
    public $doubleOptIn = true;
}
