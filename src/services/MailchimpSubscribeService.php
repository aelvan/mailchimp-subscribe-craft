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
use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;
use Mailchimp\Mailchimp;

/**
 * @author    André Elvan
 * @package   MailchimpSubscribe
 * @since     2.0.0
 */
class MailchimpSubscribeService extends Component
{
    /**
     * Subscribe to one or more Mailchimp lists
     *
     * @param string $email
     * @param string $formListId
     * @param string $emailType
     * @param array  $vars
     * @param string $language
     *
     * @return array
     */
    public function subscribe($email, $formListId, $emailType = 'html', $vars = null, $language = null)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, $vars, Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        // get list id string
        $listIdStr = $formListId !== '' ? $formListId : $settings->listId;

        if ($settings->apiKey === '' || $listIdStr === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, $vars, Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // create a new api instance, and subscribe
        $mc = new Mailchimp($settings->apiKey);

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listIdStr);

        // convert interest groups if present
        $interests = [];
        if (isset($vars['interests']) && \count($vars['interests'])) {
            foreach ($vars['interests'] as $interest) {
                $interests[$interest] = true;
            }
            unset($vars['interests']);
        }

        // loop over list id's and subscribe
        $results = [];

        foreach ($listIdArr as $listId) {
            $member = $this->getMemberByEmail($email, $listId);

            if ($member && !empty($interests) && isset($member['interests'])) {
                $interests = $this->prepInterests($listId, $member, $interests);
            }

            // subscribe
            $postVars = [
                'status_if_new' => $settings->doubleOptIn ? 'pending' : 'subscribed',
                'email_type' => $emailType,
                'email_address' => $email
            ];

            if (isset($vars) && \count($vars) > 0) {
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
                $result = $mc->request('lists/'.$listId.'/members/'.md5(strtolower($email)), $postVars, 'PUT');
                $results[] = $this->getMessage(200, $email, $vars, Craft::t('mailchimp-subscribe', 'Subscribed successfully'), true);
            } catch (\Exception $e) { // an error occured
                $msg = json_decode($e->getMessage());
                $results[] = $this->getMessage($msg->status, $email, $vars, Craft::t('mailchimp-subscribe', $msg->title));
            }
        }

        if (\count($results) > 1) {
            return $this->parseMultipleListsResult($results);
        }

        return $results[0];
    }

    /**
     * Unsubscribe from one or more Mailchimp lists
     *
     * @param string $email
     * @param string $formListId
     *
     * @return array
     */
    public function unsubscribe($email, $formListId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, null, Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        // get list id string
        $listIdStr = $formListId !== '' ? $formListId : $settings->listId;

        if ($settings->apiKey === '' || $listIdStr === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, null, Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        // create a new api instance, and subscribe
        $mc = new Mailchimp($settings->apiKey);

        // split id string on | in case more than one list id is supplied
        $listIdArr = explode('|', $listIdStr);

        // loop over list id's and subscribe
        $results = [];

        foreach ($listIdArr as $listId) {
            try {
                $result = $mc->request('lists/'.$listId.'/members/'.md5(strtolower($email)), null, 'DELETE');
                $results[] = $this->getMessage(200, $email, null, Craft::t('mailchimp-subscribe', 'Unsubscribed successfully'), true);
            } catch (\Exception $e) { // an error occured
                $msg = json_decode($e->getMessage());
                $results[] = $this->getMessage($msg->status, $email, null, Craft::t('mailchimp-subscribe', $msg->title));
            }
        }

        if (\count($results) > 1) {
            return $this->parseMultipleListsResult($results);
        }

        return $results[0];
    }

    /**
     * Check if email is subscribed to one or more lists.
     *
     * @param string $email
     * @param string $formListId
     *
     * @return array|mixed
     */
    public function checkIfSubscribed($email, $formListId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, false, Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        $listIdStr = $formListId !== '' ? $formListId : $settings->listId;

        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listIdStr === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, false, Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        $member = $this->getMemberByEmail($email, $listIdStr);

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
     * @param string $formListId
     *
     * @return array|mixed
     */
    public function checkIfInList($email, $formListId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, false, Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        $listIdStr = $formListId !== '' ? $formListId : $settings->listId;

        // check if we got an api key and a list id
        if ($settings->apiKey === '' || $listIdStr === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, false, Craft::t('mailchimp-subscribe', 'API Key or List ID not supplied. Check your settings.'));
        }

        if ($this->getMemberByEmail($email, $listIdStr)) {
            return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'The email address exists on this list'), true);
        }

        return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'The email address does not exist on this list'), false);
    }


    /**
     * Returns interest groups in list by list id
     *
     * @param string $listId
     *
     * @return array
     */
    public function getListInterestGroups($listId = null)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($listId === null) {
            $listId = $settings->listId;
        }

        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($listId === '') {
            // todo : should use our new model
            return [
                'success' => false,
                'message' => Craft::t('mailchimp-subscribe', 'No list ID given')
            ];
        }

        // check if we got an api key and a list id
        if ($settings->apiKey === '') {
            return [
                'success' => false,
                'message' => 'API Key not supplied. Check your settings.'
            ];
        }

        // create a new api instance
        $mc = new Mailchimp($settings->apiKey);

        try {
            $result = $mc->request('lists/'.$listId.'/interest-categories');

            $return = [];

            foreach ($result['categories'] as $category) {
                $categoryData = [];
                $categoryData['title'] = $category->title;
                $categoryData['type'] = $category->type;
                $categoryData['interests'] = [];

                $interestsResult = $mc->request('lists/'.$listId.'/interest-categories/'.$category->id.'/interests');

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
     * Removes existing interests in groups of type radio or dropdown, and merges all other interests
     *
     * @param $listId
     * @param $member
     * @param $interests
     *
     * @return array
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
     * Parses array with multiple list results and creates a backward compatible
     * return value that will have success => false if one of the lists failed.
     * listResults contains all results.
     *
     * @param array $results
     *
     * @return mixed
     */
    private function parseMultipleListsResult($results)
    {
        $base = $results[0];

        foreach ($results as $result) {
            if ($result['success'] == false) {
                $base = $result;
                break;
            }
        }

        $base['listResults'] = $results;

        return $base;
    }

    /**
     * Return user object by email if it is present in one or more lists.
     *
     * @param string $email
     * @param string $listId
     *
     * @return array|mixed
     */
    private function getMemberByEmail($email, $listId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        // create a new api instance
        $mc = new Mailchimp($settings->apiKey);

        try {
            $member = $mc->request('lists/'.$listId.'/members/'.md5(strtolower($email)));
        } catch (\Exception $e) { // subscriber didn't exist
            $member = false;
        }

        return $member;
    }

    /**
     * Creates return message object
     *
     * @param        $errorcode
     * @param        $email
     * @param        $vars
     * @param string $message
     * @param bool   $success
     *
     * @return array
     * @author Martin Blackburn
     */
    private function getMessage($errorcode, $email, $vars, $message = '', $success = false)
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
    public function validateEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
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
            } else {
                if ($domainLen < 1 || $domainLen > 255) {
                    // domain part length exceeded
                    $isValid = false;
                } else {
                    if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                        // local part starts or ends with '.'
                        $isValid = false;
                    } else {
                        if (preg_match('/\\.\\./', $local)) {
                            // local part has two consecutive dots
                            $isValid = false;
                        } else {
                            if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                                // character not valid in domain part
                                $isValid = false;
                            } else {
                                if (preg_match('/\\.\\./', $domain)) {
                                    // domain part has two consecutive dots
                                    $isValid = false;
                                } else {
                                    if
                                    (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                                        str_replace("\\\\", "", $local))
                                    ) {
                                        // character not valid in local part unless
                                        // local part is quoted
                                        if (!preg_match('/^"(\\\\"|[^"])+"$/',
                                            str_replace("\\\\", "", $local))
                                        ) {
                                            $isValid = false;
                                        }
                                    }
                                }
                            }
                        }
                    }
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
