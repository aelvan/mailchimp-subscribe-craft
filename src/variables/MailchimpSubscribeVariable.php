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
     * @param string $listId
     *
     * @return mixed
     */
    public function getListInterestGroups($listId = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->getListInterestGroups($listId);
    }

    /**
     * Check if email is subscribed to list
     * 
     * @param string $email
     * @param string $listId
     *
     * @return array|mixed
     */
    public function checkIfSubscribed($email, $listId = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->checkIfSubscribed($email, $listId);
    }

    /**
     * Check if email exists in one or more lists.
     * 
     * @param string $email
     * @param string $listId
     *
     * @return array|mixed
     */
    public function checkIfInList($email, $listId = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->checkIfInList($email, $listId);
    }

}
