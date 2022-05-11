<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 4.x
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2022 André Elvan
 */

namespace aelvan\mailchimpsubscribe\variables;

use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;
use craft\errors\DeprecationException;


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
     * Get member in audience by email
     *
     * @param string $email
     * @param string $id
     *
     * @return mixed
     * @throws DeprecationException
     */
    public function getMemberByEmail($email, $id = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->getMemberByEmail($email, $id);
    }

    /**
     * Get member tags by email
     *
     * @param string $email
     * @param string $id
     *
     * @return mixed
     * @throws DeprecationException
     */
    public function getMemberTagsByEmail($email, $id = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->getMemberTagsByEmail($email, $id);
    }
    
    
    /**
     * Get audience by id
     *
     * @param string $id
     *
     * @return mixed
     * @throws DeprecationException
     */
    public function getAudienceById($id = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->getAudienceById($id);
    }
    
    /**
     * Get interest groups for audience
     *
     * @param string $id
     *
     * @return mixed
     * @throws DeprecationException
     */
    public function getInterestGroups($id = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->getInterestGroups($id);
    }
    
    /**
     * Get marketingpermissions by email and audience id
     *
     * @param string $email
     * @param string $id
     *
     * @return mixed
     * @throws DeprecationException
     */
    public function getMarketingPermissionsByEmail($email, $id = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->getMarketingPermissionsByEmail($email, $id);
    }

    /**
     * --- Deprecated -----------------------------------------------------------------------------
     */
    
    /**
     * Get interest groups for list
     *
     * @param string $listId
     *
     * @return mixed
     * @throws DeprecationException
     * @deprecated Deprecated since version 3.0
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
     * @throws DeprecationException
     * @deprecated Deprecated since version 3.0
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
     * @throws DeprecationException
     * @deprecated Deprecated since version 3.0
     */
    public function checkIfInList($email, $listId = '')
    {
        return Plugin::$plugin->mailchimpSubscribe->checkIfInList($email, $listId);
    }

}
