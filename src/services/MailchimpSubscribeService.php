<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * Simple Craft plugin for subscribing to a MailChimp list.
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\services;

use Craft;
use craft\base\Component;
use craft\errors\DeprecationException;

use Mailchimp\Mailchimp;

use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;

/**
 * @author    André Elvan
 * @package   MailchimpSubscribe
 * @since     2.0.0
 */
class MailchimpSubscribeService extends Component
{
    /**
     * Subscribe a member to a Mailchimp lists
     *
     * @param string $email
     * @param string $listId
     * @param string $emailType
     * @param array $vars
     * @param string $language
     *
     * @return array
     * @throws DeprecationException
     */
    public function subscribe($email, $listId, $emailType = 'html', $vars = null, $language = null): array
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, $vars, Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        // get list id string
        $listId = !empty($listId) ? $listId : $settings->listId;

        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, $vars, Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // create a new api instance, and subscribe
        $mc = new Mailchimp($settings->apiKey);

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listId);

        if (count($listIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__,
                'Mailchimp Subscribe no longer supports subscribing to multiple lists by adding multiple list ids as a 
                pipe-seperated string. The user will only be subscribed to the first list id.');

            $listId = $listIdArr[0];
        }

        // convert interest groups if present
        $interests = [];
        if (isset($vars['interests']) && \count($vars['interests'])) {
            foreach ($vars['interests'] as $interest) {
                $interests[$interest] = true;
            }
            unset($vars['interests']);
        }

        $member = $this->getMemberByEmail($email, $listId);

        if ($member && !empty($interests) && isset($member['interests'])) {
            $interests = $this->prepInterests($listId, $member, $interests);
        }

        // subscribe
        $postVars = [
            'status_if_new' => $settings->doubleOptIn ? 'pending' : 'subscribed',
            'status' => $settings->doubleOptIn ? 'pending' : 'subscribed',
            'email_type' => $emailType,
            'email_address' => $email
        ];

        if (isset($vars) && count($vars) > 0) {
            $postVars['merge_fields'] = $vars;
        }

        if (!empty($interests)) {
            $postVars['interests'] = $interests;
            $vars['interests'] = $interests;
        }

        if (null !== $language) {
            $postVars['language'] = $language;
        }

        try {
            $result = $mc->request('lists/' . $listId . '/members/' . md5(strtolower($email)), $postVars, 'PUT');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $errorObj = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to subscribe email `' . $email . '`: ' . $message, __METHOD__);
                return $this->getMessage($errorObj->status ?? '9999', $email, [], Craft::t('mailchimp-subscribe', $message));
            }

            Craft::error('An error occured when trying to subscribe email `' . $email . '`: ' . $errorObj->title . ' (' . $errorObj->status . ')', __METHOD__);
            return $this->getMessage($errorObj->status, $email, [], Craft::t('mailchimp-subscribe', $errorObj->title));
        }

        return $this->getMessage(200, $email, $vars, Craft::t('mailchimp-subscribe', 'Subscribed successfully'), true);
    }

    /**
     * Unsubscribe a member from a Mailchimp lists
     *
     * @param string $email
     * @param string $listId
     *
     * @return array
     * @throws DeprecationException
     */
    public function unsubscribe($email, $listId): array
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        // get list id string
        $listId = !empty($listId) ? $listId : $settings->listId;

        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // create a new api instance, and subscribe
        $mc = new Mailchimp($settings->apiKey);

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listId);

        if (count($listIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__,
                'Mailchimp Subscribe no longer supports unsubscribing from multiple lists by adding multiple list ids as a 
                pipe-seperated string. The user will only be unsubscribed from the first list id.');

            $listId = $listIdArr[0];
        }

        try {
            $result = $mc->request('lists/' . $listId . '/members/' . md5(strtolower($email)), ['status' => 'unsubscribed'], 'PATCH');
        } catch (\Exception $e) { // an error occured
            $message = $e->getMessage();
            $errorObj = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to unsubscribe email `' . $email . '`: ' . $message, __METHOD__);
                return $this->getMessage($errorObj->status ?? '9999', $email, [], Craft::t('mailchimp-subscribe', $message));
            }

            Craft::error('An error occured when trying to subscribe email `' . $email . '`: ' . $errorObj->title . ' (' . $errorObj->status . ')', __METHOD__);
            return $this->getMessage($errorObj->status, $email, [], Craft::t('mailchimp-subscribe', $errorObj->title));
        }

        return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'Unsubscribed successfully'), true);
    }

    /**
     * Delete a member from a Mailchimp lists
     *
     * @param string $email
     * @param string $listId
     * @param bool $permanent
     * @return array
     * @throws DeprecationException
     */
    public function delete($email, $listId, $permanent = false): array
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        // get list id string
        $listId = !empty($listId) ? $listId : $settings->listId;

        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // create a new api instance, and subscribe
        $mc = new Mailchimp($settings->apiKey);

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listId);

        if (count($listIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__,
                'Mailchimp Subscribe no longer supports unsubscribing from multiple lists by adding multiple list ids as a 
                pipe-seperated string. The user will only be unsubscribed from the first list id.');

            $listId = $listIdArr[0];
        }

        try {
            if ($permanent) {
                $result = $mc->request('lists/' . $listId . '/members/' . md5(strtolower($email)) . '/actions/delete-permanent', [], 'POST');
            } else {
                $result = $mc->request('lists/' . $listId . '/members/' . md5(strtolower($email)), [], 'DELETE');
            }
        } catch (\Exception $e) { // an error occured
            $message = $e->getMessage();
            $errorObj = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to unsubscribe email `' . $email . '`: ' . $message, __METHOD__);
                return $this->getMessage($errorObj->status ?? '9999', $email, [], Craft::t('mailchimp-subscribe', $message));
            }

            Craft::error('An error occured when trying to subscribe email `' . $email . '`: ' . $errorObj->title . ' (' . $errorObj->status . ')', __METHOD__);
            return $this->getMessage($errorObj->status, $email, [], Craft::t('mailchimp-subscribe', $errorObj->title));
        }

        return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'Deleted successfully'), true);
    }

    /**
     * Check if email is subscribed to one or more lists.
     *
     * @param string $email
     * @param string $listId
     *
     * @return array|mixed
     * @throws DeprecationException
     */
    public function checkIfSubscribed($email, $listId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        $listId = !empty($listId) ? $listId : $settings->listId;

        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listId);

        if (count($listIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__, 'Mailchimp Subscribe no longer supports using multiple lists by adding multiple list ids as a pipe-seperated string.');
            $listId = $listIdArr[0];
        }

        $member = $this->getMemberByEmail($email, $listId);

        if ($member) {
            if ($member['status'] === 'subscribed') {
                return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'The email address exists on this list'), true);
            } else {
                return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'The email address was unsubscribed from this list'), false);
            }
        }

        return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'The email address does not exist on this list'), false);
    }

    /**
     * Check if email exists in one or more lists.
     *
     * @param string $email
     * @param string $listId
     *
     * @return array|mixed
     * @throws DeprecationException
     */
    public function checkIfInList($email, $listId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        $listId = !empty($listId) ? $listId : $settings->listId;

        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listId);

        if (count($listIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__, 'Mailchimp Subscribe no longer supports using multiple lists by adding multiple list ids as a pipe-seperated string.');
            $listId = $listIdArr[0];
        }

        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        if ($this->getMemberByEmail($email, $listId)) {
            return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'The email address exists on this list'), true);
        }

        return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'The email address does not exist on this list'), false);
    }

    /**
     * Return member object by email
     *
     * @param string $email
     * @param string $listId
     *
     * @return mixed
     * @throws DeprecationException
     */
    public function getMemberByEmail($email, $listId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();
        $listId = $this->prepAudienceId($listId);

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'Invalid email'));
        }
        
        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // create a new api instance
        $mc = new Mailchimp($settings->apiKey);

        try {
            $member = $mc->request('lists/' . $listId . '/members/' . md5(strtolower($email)));
        } catch (\Exception $e) { // subscriber didn't exist
            $member = null;
        }

        return $member;
    }

    /**
     * Return list object by list id
     *
     * @param string $listId
     *
     * @return mixed
     * @throws DeprecationException
     */
    public function getListById($listId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();
        $listId = $this->prepAudienceId($listId);

        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }
        
        // create a new api instance
        $mc = new Mailchimp($settings->apiKey);

        try {
            $list = $mc->request('lists/' . $listId);
        } catch (\Exception $e) { // subscriber didn't exist
            $list = null;
        }

        return $list;
    }

    /**
     * Returns interest groups in list by list id
     *
     * @param string $listId
     *
     * @return array
     * @throws DeprecationException
     */
    public function getListInterestGroups($listId = '')
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        $listId = !empty($listId) ? $listId : $settings->listId;

        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, '', [], Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listId);

        if (count($listIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__, 'Mailchimp Subscribe no longer supports using multiple lists by adding multiple list ids as a pipe-seperated string.');
            $listId = $listIdArr[0];
        }

        // create a new api instance
        $mc = new Mailchimp($settings->apiKey);

        try {
            $result = $mc->request('lists/' . $listId . '/interest-categories');

            $return = [];

            foreach ($result['categories'] as $category) {
                $categoryData = [];
                $categoryData['title'] = $category->title;
                $categoryData['type'] = $category->type;
                $categoryData['interests'] = [];

                $interestsResult = $mc->request('lists/' . $listId . '/interest-categories/' . $category->id . '/interests');

                foreach ($interestsResult['interests'] as $interest) {
                    $interestData = [];
                    $interestData['id'] = $interest->id;
                    $interestData['name'] = $interest->name;

                    $categoryData['interests'][] = $interestData;
                }

                $return[] = $categoryData;
            }


            return [
                'success' => true,
                'groups' => $return
            ];
        } catch (\Exception $e) { // subscriber didn't exist
            $msg = json_decode($e->getMessage());

            return [
                'success' => false,
                'message' => $msg->detail
            ];
        }
    }


    /**
     * --- Private methods --------------------------------------------------------------------------------------
     */

    /**
     * Gets the correct list ID to use. Throws deprecation errors if something is wrong. 
     *
     * @param $audienceId
     * @return string
     * @throws DeprecationException
     */
    private function prepAudienceId($audienceId): string
    {
        $settings = Plugin::$plugin->getSettings();
        $audienceId = !empty($audienceId) ? $audienceId : $settings->listId;

        // split id string on | in case more than one list id is supplied
        $audienceIdArr = explode('|', $audienceId);

        if (count($audienceIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__, 'Mailchimp Subscribe no longer supports using multiple lists by adding multiple list ids as a pipe-seperated string.');
            $audienceId = $audienceIdArr[0];
        }
        
        return $audienceId;
    }

    /**
     * Removes existing interests in groups of type radio or dropdown, and merges all other interests
     *
     * @param $listId
     * @param $member
     * @param $interests
     *
     * @return array
     * @throws DeprecationException
     */
    private function prepInterests($listId, $member, $interests): array
    {
        $interestGroupsResult = $this->getListInterestGroups($listId);
        $memberInterests = (array)$member['interests'];

        // reset any id's in member object that belong to a select or radio group, if there is an id in interests array in that group.
        foreach ($interestGroupsResult['groups'] as $group) {
            if ($group['type'] === 'radio' || $group['type'] === 'dropdown') {
                if ($this->interestsHasIdInGroup($interests, $group['interests'])) {

                    // reset all member interests for group interests
                    foreach ($group['interests'] as $groupInterest) {
                        $memberInterests[$groupInterest['id']] = false;
                    }
                }
            }
        }

        return array_merge($memberInterests, $interests);
    }

    /**
     * Check if there is an id in the posted interests, in a groups interests
     *
     * @param array $interests
     * @param array $groupInterests
     *
     * @return bool
     */
    private function interestsHasIdInGroup($interests, $groupInterests): bool
    {
        foreach ($groupInterests as $groupInterest) {
            foreach ($interests as $interestId => $interestVal) {
                if ($interestId === $groupInterest['id']) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Creates return message object
     *
     * @param string|int $errorcode
     * @param string $email
     * @param array $vars
     * @param string $message
     * @param bool $success
     *
     * @return array
     * @author Martin Blackburn
     */
    private function getMessage($errorcode, $email, $vars, $message = '', $success = false): array
    {
        return [
            'success' => $success,
            'errorCode' => $errorcode,
            'message' => $message,
            'values' => [
                'email' => $email,
                'vars' => $vars
            ]
        ];
    }

    /**
     * Validate an email address.
     * Provide email address (raw input)
     * Returns true if the email address has the email
     * address format and the domain exists.
     *
     * @param string $email Email to validate
     *
     * @return boolean
     * @author André Elvan
     */
    public function validateEmail($email): bool
    {
        $isValid = true;
        $atIndex = strrpos($email, '@');
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);

            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if (strpos($local, '.') === 0 || $local[$localLen - 1] === '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } else if (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
                    $isValid = false;
                }
            }

            if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                // domain not found in DNS
                $isValid = false;
            }
        }

        return $isValid;
    }
}
