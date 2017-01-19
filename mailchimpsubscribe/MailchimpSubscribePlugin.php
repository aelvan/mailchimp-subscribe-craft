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

class MailchimpSubscribePlugin extends BasePlugin
{
    protected $_version = '1.1.3',
      $_schemaVersion = '1.0.0',
      $_name = 'Mailchimp Subscribe',
      $_url = 'https://github.com/aelvan/mailchimp-subscribe-craft',
      $_releaseFeedUrl = 'https://raw.githubusercontent.com/aelvan/mailchimp-subscribe-craft/master/releases.json',
      $_documentationUrl = 'https://github.com/aelvan/mailchimp-subscribe-craft/blob/master/README.md',
      $_description = '',
      $_developer = 'André Elvan',
      $_developerUrl = 'http://vaersaagod.no/',
      $_minVersion = '2.5';


    public function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    public function getName()
    {
        return Craft::t($this->_name);
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getDeveloper()
    {
        return $this->_developer;
    }

    public function getDeveloperUrl()
    {
        return $this->_developerUrl;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getDocumentationUrl()
    {
        return $this->_documentationUrl;
    }

    public function getSchemaVersion()
    {
        return $this->_schemaVersion;
    }

    public function getReleaseFeedUrl()
    {
        return $this->_releaseFeedUrl;
    }

    public function getCraftRequiredVersion()
    {
        return $this->_minVersion;
    }

    public function hasCpSection()
    {
        return false;
    }

    protected function defineSettings()
    {
        return array(
          'mcsubApikey' => array(AttributeType::String, 'default' => ''),
          'mcsubListId' => array(AttributeType::String, 'default' => ''),
          'mcsubDoubleOptIn' => array(AttributeType::Bool, 'default' => true)
        );
    }

    public function getSettingsHtml()
    {
        $config_settings = array();
        $config_settings['mcsubApikey'] = craft()->config->get('mcsubApikey');
        $config_settings['mcsubListId'] = craft()->config->get('mcsubListId');
        $config_settings['mcsubDoubleOptIn'] = craft()->config->get('mcsubDoubleOptIn');

        return craft()->templates->render('mailchimpsubscribe/settings', array(
          'settings' => $this->getSettings(),
          'config_settings' => $config_settings
        ));
    }
}
