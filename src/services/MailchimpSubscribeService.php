<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\services;

use Craft;
use craft\base\Component;
use craft\errors\DeprecationException;

use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;
use aelvan\mailchimpsubscribe\models\SubscribeResponse;
use aelvan\mailchimpsubscribe\models\Settings;

use Illuminate\Support\Collection;
use Mailchimp\Mailchimp;


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
     * @param string $audienceId
     * @param array|null $opts
     *
     * @return SubscribeResponse
     * @throws DeprecationException
     */
    public function subscribe($email, $audienceId, $opts = null): SubscribeResponse
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return new SubscribeResponse([
                'success' => false,
                'errorCode' => '1000',
                'message' => Craft::t('mailchimp-subscribe', 'Invalid email'),
                'values' => array_merge(['email' => $email], $opts ?? [])
            ]);
        }

        // get list id string
        $audienceId = $this->prepAudienceId($audienceId);

        if ($settings->getApiKey() === '' || $audienceId === '') { // error, no API key or list id
            return new SubscribeResponse([
                'success' => false,
                'errorCode' => '2000',
                'message' => Craft::t('mailchimp-subscribe', 'API Key or Audience ID not supplied. Check your settings.'),
                'values' => array_merge(['email' => $email], $opts ?? [])
            ]);
        }

        // create a new api instance, and subscribe
        $mc = $this->getClient();
        $member = $this->getMemberByEmail($email, $audienceId);

        // convert interest groups if present
        $interests = [];
        if (isset($opts['interests']) && $opts['interests'] !== null) {
            $interests = $this->prepInterests($audienceId, $opts['interests']);
        }
        
        // marketing permissions
        $marketingPermissions = [];
        
        if (isset($opts['marketing_permissions']) && $opts['marketing_permissions'] !== null) {
            $marketingPermissions = $this->prepMarketingPermissions($member, $opts['marketing_permissions']);
        }
        
        // Build the post variables
        $postVars = [
            'status_if_new' => $settings->getDoubleOptIn() ? 'pending' : 'subscribed',
            'status' => $settings->getDoubleOptIn() ? 'pending' : 'subscribed',
            'email_type' => $opts['email_type'],
            'email_address' => $email,
        ];

        if ($opts['language'] !== null) {
            $postVars['language'] = $opts['language'];
        }

        if ($opts['vip'] !== null) {
            $postVars['vip'] = $opts['vip'];
        }

        if (isset($opts['merge_fields']) && is_array($opts['merge_fields']) && count($opts['merge_fields']) > 0) {
            $postVars['merge_fields'] = $opts['merge_fields'];
        }

        if (!empty($interests)) {
            $postVars['interests'] = $interests;
            $opts['interests'] = $interests;
        }

        if (!empty($marketingPermissions)) {
            $marketingPostObject = [];

            foreach ($marketingPermissions as $marketingPermissionId => $marketingPermissionValue) {
                $marketingPostObject[] = ['marketing_permission_id' => $marketingPermissionId, 'enabled' => $marketingPermissionValue];
            }

            $postVars['marketing_permissions'] = $marketingPostObject;
            $opts['marketing_permissions'] = $marketingPermissions;
        }

        // Subscribe
        try {
            $result = $mc->request('lists/' . $audienceId . '/members/' . md5(strtolower($email)), $postVars, 'PUT');

            if (isset($result['_links'])) {
                unset($result['_links']);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $errorObj = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to subscribe email `' . $email . '`: ' . $message, __METHOD__);

                return new SubscribeResponse([
                    'success' => false,
                    'errorCode' => $errorObj->status ?? '9999',
                    'message' => Craft::t('mailchimp-subscribe', $message),
                    'values' => array_merge(['email' => $email], $opts ?? [])
                ]);
            }

            Craft::error('An error occured when trying to subscribe email `' . $email . '`: ' . $errorObj->title . ' (' . $errorObj->status . ')', __METHOD__);

            return new SubscribeResponse([
                'response' => $errorObj,
                'success' => false,
                'errorCode' => $errorObj->status,
                'message' => Craft::t('mailchimp-subscribe', $errorObj->title),
                'values' => array_merge(['email' => $email], $opts ?? [])
            ]);
        }

        // Add tags to member if they were submitted.
        if ($opts['tags'] !== null) {
            try {
                $tags = $this->prepMemberTags($opts['tags'], $result['tags']);
                $tagsResult = $mc->request('lists/' . $audienceId . '/members/' . md5(strtolower($email)) . '/tags', ['tags' => $tags], 'POST');
            } catch (\Exception $e) {
                Craft::error('An error occured when trying to add tags to email `' . $email . '`: ' . print_r($e->getMessage(), true), __METHOD__);
            }
        }

        return new SubscribeResponse([
            'response' => $result,
            'success' => true,
            'errorCode' => 200,
            'message' => Craft::t('mailchimp-subscribe', 'Subscribed successfully'),
            'values' => array_merge(['email' => $email], $opts ?? [])
        ]);
    }

    /**
     * Unsubscribe a member from a Mailchimp lists
     *
     * @param string $email
     * @param string $audienceId
     *
     * @return SubscribeResponse
     * @throws DeprecationException
     */
    public function unsubscribe($email, $audienceId): SubscribeResponse
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return new SubscribeResponse([
                'action' => 'unsubscribe',
                'success' => false,
                'errorCode' => '1000',
                'message' => Craft::t('mailchimp-subscribe', 'Invalid email'),
                'values' => ['email' => $email]
            ]);
        }

        // get list id string
        $audienceId = $this->prepAudienceId($audienceId);

        if ($settings->getApiKey() === '' || $audienceId === '') { // error, no API key or list id
            return new SubscribeResponse([
                'action' => 'unsubscribe',
                'success' => false,
                'errorCode' => '2000',
                'message' => Craft::t('mailchimp-subscribe', 'API Key or Audience ID not supplied. Check your settings.'),
                'values' => ['email' => $email]
            ]);
        }

        // create a new api instance, and subscribe
        $mc = $this->getClient();;

        try {
            $result = $mc->request('lists/' . $audienceId . '/members/' . md5(strtolower($email)), ['status' => 'unsubscribed'], 'PATCH');

            if (isset($result['_links'])) {
                unset($result['_links']);
            }
        } catch (\Exception $e) { // an error occured
            $message = $e->getMessage();
            $errorObj = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to unsubscribe email `' . $email . '`: ' . $message, __METHOD__);

                return new SubscribeResponse([
                    'action' => 'unsubscribe',
                    'success' => false,
                    'errorCode' => $errorObj->status ?? '9999',
                    'message' => Craft::t('mailchimp-subscribe', $message),
                    'values' => ['email' => $email]
                ]);
            }

            Craft::error('An error occured when trying to unsubscribe email `' . $email . '`: ' . $errorObj->title . ' (' . $errorObj->status . ')', __METHOD__);

            return new SubscribeResponse([
                'action' => 'unsubscribe',
                'response' => $errorObj,
                'success' => false,
                'errorCode' => $errorObj->status,
                'message' => Craft::t('mailchimp-subscribe', $errorObj->title),
                'values' => ['email' => $email]
            ]);
        }

        return new SubscribeResponse([
            'action' => 'unsubscribe',
            'response' => $result,
            'success' => true,
            'errorCode' => 200,
            'message' => Craft::t('mailchimp-subscribe', 'Unsubscribed successfully'),
            'values' => ['email' => $email]
        ]);
    }

    /**
     * Delete a member from a Mailchimp lists
     *
     * @param string $email
     * @param string $audienceId
     * @param bool $permanent
     * @return SubscribeResponse
     * @throws DeprecationException
     */
    public function delete($email, $audienceId, $permanent = false): SubscribeResponse
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return new SubscribeResponse([
                'action' => 'delete',
                'success' => false,
                'errorCode' => '1000',
                'message' => Craft::t('mailchimp-subscribe', 'Invalid email'),
                'values' => ['email' => $email]
            ]);
        }

        // get list id string
        $audienceId = $this->prepAudienceId($audienceId);

        if ($settings->getApiKey() === '' || $audienceId === '') { // error, no API key or list id
            return new SubscribeResponse([
                'action' => 'delete',
                'success' => false,
                'errorCode' => '2000',
                'message' => Craft::t('mailchimp-subscribe', 'API Key or Audience ID not supplied. Check your settings.'),
                'values' => ['email' => $email]
            ]);
        }

        // create a new api instance, and subscribe
        $mc = $this->getClient();;

        try {
            if ($permanent) {
                $result = $mc->request('lists/' . $audienceId . '/members/' . md5(strtolower($email)) . '/actions/delete-permanent', [], 'POST');
            } else {
                $result = $mc->request('lists/' . $audienceId . '/members/' . md5(strtolower($email)), [], 'DELETE');
            }

            if (isset($result['_links'])) {
                unset($result['_links']);
            }
        } catch (\Exception $e) { // an error occured
            $message = $e->getMessage();
            $errorObj = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to delete email `' . $email . '`: ' . $message, __METHOD__);

                return new SubscribeResponse([
                    'action' => 'delete',
                    'success' => false,
                    'errorCode' => $errorObj->status ?? '9999',
                    'message' => Craft::t('mailchimp-subscribe', $message),
                    'values' => ['email' => $email]
                ]);
            }

            Craft::error('An error occured when trying to delete email `' . $email . '`: ' . $errorObj->title . ' (' . $errorObj->status . ')', __METHOD__);

            return new SubscribeResponse([
                'action' => 'delete',
                'response' => $errorObj,
                'success' => false,
                'errorCode' => $errorObj->status,
                'message' => Craft::t('mailchimp-subscribe', $errorObj->title),
                'values' => ['email' => $email]
            ]);
        }

        return new SubscribeResponse([
            'action' => 'delete',
            'response' => $result,
            'success' => true,
            'errorCode' => 200,
            'message' => Craft::t('mailchimp-subscribe', 'Deleted successfully'),
            'values' => ['email' => $email]
        ]);
    }
    
    /**
     * Return member object by email
     *
     * @param string $email
     * @param string $audienceId
     *
     * @return Collection|null
     * @throws DeprecationException
     */
    public function getMemberByEmail($email, $audienceId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();
        $audienceId = $this->prepAudienceId($audienceId);

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            Craft::error('Invalid email', __METHOD__);
            return null;
        }

        // check if we got an api key and a audience id
        if ($settings->getApiKey() === '' || $audienceId === '') {
            Craft::error('API Key or Audience ID not supplied. Check your settings.', __METHOD__);
            return null;
        }

        // create a new api instance
        $mc = $this->getClient();;

        try {
            /** @var Collection $member */
            $member = $mc->request('lists/' . $audienceId . '/members/' . md5(strtolower($email)));

            if (isset($member['_links'])) {
                unset($member['_links']);
            }
        } catch (\Exception $e) { // subscriber didn't exist
            $member = null;
        }

        return $member;
    }

    /**
     * Return audience object by id
     *
     * @param string $audienceId
     *
     * @return Collection|null
     * @throws DeprecationException
     */
    public function getAudienceById($audienceId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();
        $audienceId = $this->prepAudienceId($audienceId);

        // check if we got an api key and  id
        if ($settings->getApiKey() === '' || $audienceId === '') {
            Craft::error('API Key or Audience ID not supplied. Check your settings.', __METHOD__);
            return null;
        }

        // create a new api instance
        $mc = $this->getClient();;

        try {
            /** @var Collection $list */
            $list = $mc->request('lists/' . $audienceId);

            if (isset($list['_links'])) {
                unset($list['_links']);
            }
        } catch (\Exception $e) { // audience didn't exist
            $list = null;
        }

        return $list;
    }

    /**
     * Gets marketing permissions from member by email
     *
     * @param $email
     * @param $audienceId
     * @return array|null
     * @throws DeprecationException
     */
    public function getMarketingPermissionsByEmail($email, $audienceId)
    {
        // get settings
        $settings = Plugin::$plugin->getSettings();
        $audienceId = $this->prepAudienceId($audienceId);

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            Craft::error('Invalid email', __METHOD__);
            return null;
        }

        // check if we got an api key and id
        if ($settings->getApiKey() === '' || $audienceId === '') {
            Craft::error('API Key or Audience ID not supplied. Check your settings.', __METHOD__);
            return null;
        }

        $member = $this->getMemberByEmail($email, $audienceId);

        if ($member === null) {
            return null;
        }

        return $member['marketing_permissions'] ?? null;
    }

    /**
     * Returns interest groups in audience by id
     *
     * @param string $audienceId
     *
     * @return array|null
     * @throws DeprecationException
     */
    public function getInterestGroups($audienceId = '')
    {
        $settings = Plugin::$plugin->getSettings();
        $audienceId = $this->prepAudienceId($audienceId);

        // check if we got an api key and a list id
        if ($settings->getApiKey() === '' || $audienceId === '') { // error, no API key or list id
            Craft::error('API Key or Audience ID not supplied. Check your settings.', __METHOD__);
            return null;
        }

        // create a new api instance
        $mc = $this->getClient();;

        try {
            /** @var Collection $result */
            $result = $mc->request('lists/' . $audienceId . '/interest-categories');

            $return = [];

            foreach ($result['categories'] as $category) {
                $categoryData = [];
                $categoryData['title'] = $category->title;
                $categoryData['type'] = $category->type;
                $categoryData['interests'] = [];

                /** @var Collection $interestsResult */
                $interestsResult = $mc->request('lists/' . $audienceId . '/interest-categories/' . $category->id . '/interests');

                foreach ($interestsResult['interests'] as $interest) {
                    $interestData = [];
                    $interestData['id'] = $interest->id;
                    $interestData['name'] = $interest->name;

                    $categoryData['interests'][] = $interestData;
                }

                $return[] = $categoryData;
            }

            return $return;

        } catch (\Exception $e) { // subscriber didn't exist
            $message = $e->getMessage();
            $msg = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to get list interests: ' . $message, __METHOD__);
                return null;
            }

            Craft::error('An error occured when trying to get list interests: ' . $msg->detail, __METHOD__);
            return null;
        }
    }

    /**
     * Returns member tags from member by email
     * 
     * @param string $email
     * @param string $audienceId
     * @return array|null
     * @throws DeprecationException
     */
    public function getMemberTagsByEmail($email, $audienceId = '')
    {
        $settings = Plugin::$plugin->getSettings();
        $audienceId = $this->prepAudienceId($audienceId);

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            Craft::error('Invalid email', __METHOD__);
            return null;

        }

        // check if we got an api key and a list id
        if ($settings->getApiKey() === '' || $audienceId === '') { // error, no API key or list id
            Craft::error('API Key or Audience ID not supplied. Check your settings.', __METHOD__);
            return null;
        }

        // create a new api instance
        $mc = $this->getClient();

        try {
            /** @var Collection $result */
            $result = $mc->request('lists/' . $audienceId . '/members/' . md5(strtolower($email)) . '/tags');
        } catch (\Exception $e) { // subscriber didn't exist
            $message = $e->getMessage();
            $msg = json_decode($message, false);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Craft::error('An error occured when trying to get list interests: ' . $message, __METHOD__);
                return null;
            }

            Craft::error('An error occured when trying to get list interests: ' . $msg->detail, __METHOD__);
            return null;
        }

        return $result['tags'];
    }
    

    /**
     * --- Deprecated methods --------------------------------------------------------------------------------------
     */

    /**
     * @param string $listId
     * @return array|mixed
     * @throws DeprecationException
     * @deprecated Deprecated since version 3.0
     */
    public function getListInterestGroups($listId = '')
    {
        Craft::$app->deprecator->log(__METHOD__, 'The `getListInterestGroups` template variable and service method is deprecated. Use `getInterestGroups` instead.');
        return $this->getInterestGroups($listId);
    }
    
    /**
     * Check if email is subscribed to one or more lists.
     *
     * @param string $email
     * @param string $audienceId
     *
     * @return array|mixed
     * @throws DeprecationException
     * @deprecated Deprecated since version 3.0
     */
    public function checkIfSubscribed($email, $audienceId)
    {
        Craft::$app->deprecator->log(__METHOD__, 'The `checkIfSubscribed` service method and controller action has been deprecated. Use `getMemberByEmail` and check `status` instead.');

        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        $audienceId = $this->prepAudienceId($audienceId);

        // check if we got an api key and a list id
        if ($settings->getApiKey() === '' || $audienceId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or Audience ID not supplied. Check your settings.'));
        }

        $member = $this->getMemberByEmail($email, $audienceId);

        if ($member) {
            if ($member['status'] === 'subscribed') {
                return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'The email address exists on this list'), true);
            }

            return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'The email address was unsubscribed from this list'), false);
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
     * @deprecated Deprecated since version 3.0
     */
    public function checkIfInList($email, $listId)
    {
        Craft::$app->deprecator->log(__METHOD__, 'The `checkIfInList` service method and controller action has been deprecated. Use `getMemberByEmail` and check `status` instead.');

        // get settings
        $settings = Plugin::$plugin->getSettings();

        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'Invalid email'));
        }

        $listId = $this->prepAudienceId($listId);

        // check if we got an api key and a list id
        if ($settings->getApiKey() === '' || $listId === '') { // error, no API key or list id
            return $this->getMessage(2000, $email, [], Craft::t('mailchimp-subscribe', 'API Key or Audience ID not supplied. Check your settings.'));
        }

        if ($this->getMemberByEmail($email, $listId)) {
            return $this->getMessage(200, $email, [], Craft::t('mailchimp-subscribe', 'The email address exists on this list'), true);
        }

        return $this->getMessage(1000, $email, [], Craft::t('mailchimp-subscribe', 'The email address does not exist on this list'), false);
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
     * @deprecated Deprecated since version 3.0
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
     * --- Private methods --------------------------------------------------------------------------------------
     */

    /**
     * Creates Mailchimp client
     * 
     * @return Mailchimp
     */
    private function getClient(): Mailchimp
    {
        $settings = Plugin::$plugin->getSettings();
        return new Mailchimp(Craft::parseEnv($settings->getApiKey()));
    }

    /**
     * Gets the correct list ID to use. Throws deprecation errors if something is wrong.
     *
     * @param $audienceId
     * @return string
     * @throws DeprecationException
     */
    private function prepAudienceId($audienceId): string
    {
        /** @var Settings $settings */
        $settings = Plugin::$plugin->getSettings();

        if ($settings->listId !== '') {
            Craft::$app->deprecator->log(__METHOD__ . '_listId_setting', 'The Mailchimp Subscribe config setting `listId` has been deprecated and will be removed in the next major version. Use `audienceId` instead.');

            if ($settings->getAudienceId() === '') {
                $settings->audienceId = $settings->listId;
            }
        }

        $audienceId = !empty($audienceId) ? $audienceId : $settings->getAudienceId();

        // split id string on | in case more than one list id is supplied
        $audienceIdArr = explode('|', $audienceId);

        if (count($audienceIdArr) > 1) {
            Craft::$app->deprecator->log(__METHOD__, 'Mailchimp Subscribe no longer supports using multiple lists by adding multiple list ids as a pipe-seperated string.');
            $audienceId = $audienceIdArr[0];
        }

        return Craft::parseEnv($audienceId);
    }

    /**
     * Preps interests. For groups that have been set in front-end form, existing interests are reset.
     *
     * @param string $audienceId
     * @param array|string $interests
     *
     * @return array
     * @throws DeprecationException
     */
    private function prepInterests($audienceId, $interests): array
    {
        $interestGroupsResult = $this->getInterestGroups($audienceId);
        $r = [];
        
        // Reset all interests 
        foreach ($interestGroupsResult as $group) {
            if (isset($interests[$group['title']])) {
                foreach ($group['interests'] as $groupInterest) {
                    $r[$groupInterest['id']] = false;
                }
            }
        }
        
        // add configures interests
        if (is_array($interests)) {
            foreach ($interests as $interestGroup) {
                if (is_array($interestGroup)) {
                    foreach ($interestGroup as $interest) {
                        $r[$interest] = true;
                    }
                }
            }
        }
        
        return $r;
    }

    /**
     * Preps marketing permissions array by adding all missing permissions to array and assuming that they are disabled.
     *
     * @param Collection|null $member
     * @param array $marketingPermissions
     * @return array
     */
    private function prepMarketingPermissions($member, $marketingPermissions): array
    {
        $memberMarketingPermissions = $member ? $member['marketing_permissions'] : [];
        $r = [];

        foreach ($memberMarketingPermissions as $memberMarketingPermission) {
            $r[$memberMarketingPermission->marketing_permission_id] = false;
        }
        
        if (is_array($marketingPermissions)) {
            foreach ($marketingPermissions as $marketingPermission) {
                $r[$marketingPermission] = true;
            }
        }
        
        return $r;
    }

    /**
     * Preps submitted array of tags for sending to member tags endpoint. 
     *
     * @param array $tags
     * @param array $memberTags
     * @return array
     */
    private function prepMemberTags($tags, $memberTags): array
    {
        $r = [];
        $tagsMap = [];
        
        foreach ($memberTags as $tag) {
            $tagsMap[$tag->name] = 'inactive';
        }
        
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tagsMap[$tag] = 'active';
            }
        }
        
        foreach ($tagsMap as $tag=>$status) {
            $r[] = ['name' => $tag, 'status' => $status];
        }

        return $r;
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
