<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * Simple Craft plugin for subscribing to a MailChimp list.
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\controllers;

use Craft;

/**
 * @author     André Elvan
 * @package    MailchimpSubscribe
 * @since      2.0.0
 * @deprecated 3.0.0
 */
class ListController extends AudienceController
{
    public function __construct($id, $module, $config = [])
    {
        Craft::$app->deprecator->log(__METHOD__, 'The Mailchimp Subscribe `list` controller has been deprecated and will be removed in the next major version. Use `audience` instead.');
        parent::__construct($id, $module, $config);
    }
}
