<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 4.x
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2022 André Elvan
 */

namespace aelvan\mailchimpsubscribe\models;

use craft\base\Model;

class SubscribeResponse extends Model
{
    public $action = 'subscribe';
    public $success = '';
    public $errorCode = '';
    public $message = '';
    public $values = null;
    public $response = null;
}
