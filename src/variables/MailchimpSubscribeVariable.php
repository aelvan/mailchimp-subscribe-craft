<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * Simple Craft plugin for subscribing to a MailChimp list.
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\variables;

use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;


/**
 * @author    André Elvan
 * @package   MailchimpSubscribe
 * @since     2.0.0
 */
class MailchimpSubscribeVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Get interest groups for list
     *
     * @param $listId
     *
     * @return mixed
     */
    public function getListInterestGroups($listId = null)
    {
        return Plugin::$plugin->mailchimpSubscribe->getListInterestGroups($listId);
    }

    /**
     * Check if email is subscribed to list
     * 
     * @param string $email
     * @param null $listId
     *
     * @return array|mixed
     */
    public function checkIfSubscribed($email, $listId = null)
    {
        return Plugin::$plugin->mailchimpSubscribe->checkIfSubscribed($email, $listId);
    }

}
