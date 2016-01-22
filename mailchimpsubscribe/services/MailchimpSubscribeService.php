<?php
namespace Craft;

/**
 * Mailchimp Subscribe by André Elvan
 *
 * @author      André Elvan <http://vaersaagod.no>
 * @package     Mailchimp Subscribe
 * @copyright   Copyright (c) 2016, André Elvan
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 * @link        https://github.com/aelvan/mailchimp-subscribe-craft
 */

use Mailchimp\Mailchimp;

class MailchimpSubscribeService extends BaseApplicationComponent
{
    var $settings = null;
    
    /**
     * Subscribe to one or more Mailchimp lists
     *
     * @param $email
     * @param $formListId
     * @param string $emailType
     * @param string $vars
     * @return array
     */
    public function subscribe($email, $formListId, $emailType = 'html', $vars = '')
    {
        if ($email != '' && $this->validateEmail($email)) { // validate email

            $listIdStr = $formListId != '' ? $formListId : $this->getSetting('mcsubListId');

            // check if we got an api key and a list id
            if ($this->getSetting('mcsubApikey') != '' && $listIdStr != '') {

                // create a new api instance, and subscribe
                $mc = new Mailchimp($this->getSetting('mcsubApikey'));

                // split id string on | in case more than one list id is supplied
                $listIdArr = explode("|", $listIdStr);

                // convert groups to input format if present
                if (isset($vars['group']) && count($vars['group'])) {
                    $vars['GROUPINGS'] = array();
                    foreach ($vars['group'] as $key => $vals) {
                        $vars['GROUPINGS'][] = array('id' => $key, 'groups' => implode(',', $vals));
                    }
                }

                // loop over list id's and subscribe
                $results = array();
                foreach ($listIdArr as $listId) {
                    // check if email is subscribed
                    try {
                        $existsCheck = $mc->request('lists/' . $listId . '/members/' . md5(strtolower($email)));
                        array_push($results, $this->_getMessage(200, $email, $vars, Craft::t("Already subscribed"), true));

                    } catch (\Exception $e) { // subscriber didn't exist, add him
                        
                        try {
                            $result = $mc->request('lists/' . $listId . '/members', [
                              'status' => $this->getSetting('mcsubDoubleOptIn') ? 'pending' : 'subscribed',
                              'email_type' => $emailType,
                              'email_address' => $email,
                              'merge_fields' => $vars
                            ], 'POST');

                            array_push($results, $this->_getMessage(200, $email, $vars, Craft::t("Subscribed successfully"), true));
                        } catch (\Exception $e) { // an error occured
                            array_push($results, $this->_getMessage($e->status, $email, $vars, Craft::t($e->title)));
                        }

                    }
                }

                if (count($results) > 1) {
                    return $this->_parseMultipleListsResult($results);
                } else {
                    return $results[0];
                }

            } else {
                // error, no API key or list id
                return $this->_getMessage(2000, $email, $vars, Craft::t("API Key or List ID not supplied. Check your settings."));
            }

        } else {
            // error, invalid email
            return $this->_getMessage(1000, $email, $vars, Craft::t("Invalid email"));
        }
    }

    /**
     * Check if email exists in one or more lists.
     * 
     * @param $email
     * @param $formListId
     * @return array|mixed
     */
    public function checkIfSubscribed($email, $formListId)
    {
        if ($email != '' && $this->validateEmail($email)) { // validate email
            
            $listIdStr = $formListId != '' ? $formListId : $this->getSetting('mcsubListId');

            // check if we got an api key and a list id
            if ($this->getSetting('mcsubApikey') != '' && $listIdStr != '') {

                // create a new api instance, and subscribe
                $mc = new Mailchimp($this->getSetting('mcsubApikey'));

                // split id string on | in case more than one list id is supplied
                $listIdArr = explode("|", $listIdStr);

                // loop over list id's and subscribe
                $results = array();
                foreach ($listIdArr as $listId) {
                    // check if email is subscribed
                    try {
                        $existsCheck = $mc->request('lists/' . $listId . '/members/' . md5(strtolower($email)));
                        array_push($results, $this->_getMessage(200, $email, array(), Craft::t("The email address passed exists on this list"), true));
                    } catch (\Exception $e) { // subscriber didn't exist
                        array_push($results, $this->_getMessage(1000, $email, array(), Craft::t("The email address passed does not exist on this list"), false));
                    }
                }

                if (count($results) > 1) {
                    return $this->_parseMultipleListsResult($results);
                } else {
                    return $results[0];
                }

            } else {
                // error, no API key or list id
                return $this->_getMessage(2000, $email, $vars, Craft::t("API Key or List ID not supplied. Check your settings."));
            }

        } else {
            // error, invalid email
            return $this->_getMessage(1000, $email, $vars, Craft::t("Invalid email"));
        }        
    }

    /**
     * Creates returned message object
     *
     * @param $errorcode
     * @param $email
     * @param $vars
     * @param string $message
     * @param bool $success
     * @return array
     * @author Martin Blackburn
     */
    private function _getMessage($errorcode, $email, $vars, $message = '', $success = false)
    {
        return array(
          'success' => $success,
          'errorCode' => $errorcode,
          'message' => $message,
          'values' => array(
            'email' => $email,
            'vars' => $vars
          )
        );
    }

    /**
     * Parses array with multiple list results and creates a backward compatible
     * return value that will have success => false if one of the lists failed.
     * listResults contains all results.
     *
     * @param $results
     * @return mixed
     */
    private function _parseMultipleListsResult($results)
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
     * Validate an email address.
     * Provide email address (raw input)
     * Returns true if the email address has the email
     * address format and the domain exists.
     *
     * @param string Email to validate
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


    /**
     * Gets a plugin setting
     *
     * @param $name String Setting name
     * @return mixed Setting value
     * @author André Elvan
     */
    public function getSetting($name)
    {
        if ($this->settings == null) {
            $this->settings = $this->_init_settings();
        }

        return $this->settings[$name];
    }

    /**
     * Gets Stamp settings from config
     *
     * @return array Array containing all settings
     * @author André Elvan
     */
    private function _init_settings()
    {
        $plugin = craft()->plugins->getPlugin('mailchimpsubscribe');
        $plugin_settings = $plugin->getSettings();

        $settings = array();
        $settings['mcsubApikey'] = craft()->config->get('mcsubApikey') !== null ? craft()->config->get('mcsubApikey') : $plugin_settings['mcsubApikey'];
        $settings['mcsubListId'] = craft()->config->get('mcsubListId') !== null ? craft()->config->get('mcsubListId') : $plugin_settings['mcsubListId'];
        $settings['mcsubDoubleOptIn'] = craft()->config->get('mcsubDoubleOptIn') !== null ? craft()->config->get('mcsubDoubleOptIn') : $plugin_settings['mcsubDoubleOptIn'];

        return $settings;
    }
}