<?php
/**
 * MailChimp Signup plugin
 *
 * https://github.com/aelvan/mailchimp-subscribe-craft
 *
 * @author André Elvan
 */

namespace Craft;

class MailchimpSubscribePlugin extends BasePlugin
{
  public function getName()
  {
      return Craft::t('MailChimp Subscribe');
  }

  public function getVersion()
  {
      return '0.5';
  }

  public function getDeveloper()
  {
      return 'André Elvan';
  }

  public function getDeveloperUrl()
  {
      return 'http://vaersaagod.no';
  }

  public function hasCpSection()
  {
      return false;
  }

  protected function defineSettings()
  {
    return array(
         'mcsubApikey'      => array(AttributeType::String, 'default' => ''),
         'mcsubListId'      => array(AttributeType::String, 'default' => ''),
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
