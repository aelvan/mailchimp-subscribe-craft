<?php
namespace Craft;

class MailchimpSubscribe_ListController extends BaseController {

  /**
   * Main subscribe action
   *
   * @author André Elvan
   */

  protected $allowAnonymous = true;
  
  public function actionSubscribe() {
    
    // get post variables
    $email = craft()->request->getParam('email', '');
    $vars = craft()->request->getParam('mcvars', array());
    $redirect = craft()->request->getParam('redirect', '');
    
    if ($email!='' && $this->_validateEmail($email)) { // validate email
      
      // include mailchimp api class
      require_once(CRAFT_PLUGINS_PATH.'mailchimpsubscribe/vendor/mcapi/MCAPI.class.php');
      
      // get plugin settings
      $settings = $this->_init_settings();
      
      // check if we got an api key and a list id
      if ($settings['mcsubApikey']!='' && $settings['mcsubListId']!='') {
        
        // create a new api instance, and subscribe
        $api = new \MCAPI($settings['mcsubApikey']);
        $retval = $api->listSubscribe( $settings['mcsubListId'], $email, $vars );
        
        
        if ($api->errorCode) { // an api error occured 
          
          craft()->urlManager->setRouteVariables(array(
            'mailchimpSubscribe' => array(
              'success' => false,
              'errorCode' => $api->errorCode, // set errorCode to match actual mailchimp api error, see http://apidocs.mailchimp.com/api/1.3/exceptions.field.php
              'message' => 'An API error occured: ' . $api->errorCode . ' - ' . $api->errorMessage,
              'values' => array(
                'email' => $email,
                'vars' => $vars
              )
            )
          ));
          
        } else { // list subscribe was successful
          
          if ($redirect!='') { // if a redirect url was set in template form, redirect to this
            $this->redirectToPostedUrl();
          } else {
            craft()->urlManager->setRouteVariables(array(
              'mailchimpSubscribe' => array(
                'success' => true,
                'errorCode' => 1,
                'message' => '',
                'values' => array(
                  'email' => $email,
                  'vars' => $vars
                )
              )
            ));
          }
        }
        
      } else { // error, no api key or list id
        
        craft()->urlManager->setRouteVariables(array(
          'mailchimpSubscribe' => array(
            'success' => false,
            'errorCode' => 2000,
            'message' => 'API Key or List ID not supplied. Check your settings.',
            'values' => array(
              'email' => $email,
              'vars' => $vars
            )
          )
        ));
        
      }
      
    } else { // error, no email or invalid
      
      craft()->urlManager->setRouteVariables(array(
        'mailchimpSubscribe' => array(
          'success' => false,
          'errorCode' => 1000,
          'message' => 'Email invalid',
          'values' => array(
            'email' => $email,
            'vars' => $vars
          )
        )
      ));
    }
    
  }



  /**
   * Gets plugin settings, either from saved settings or from config
   *
   * @return array Array containing all settings
   * @author André Elvan
   */
  private function _init_settings() {
    $plugin = craft()->plugins->getPlugin('mailchimpsubscribe');
    $plugin_settings = $plugin->getSettings();

    $settings = array();
    $settings['mcsubApikey'] = craft()->config->get('mcsubApikey')!==null ? craft()->config->get('mcsubApikey') : $plugin_settings['mcsubApikey'];
    $settings['mcsubListId'] = craft()->config->get('mcsubListId')!==null ? craft()->config->get('mcsubListId') : $plugin_settings['mcsubListId'];

    return $settings;
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
  private function _validateEmail ($email) {
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex)
    {
      $isValid = false;
    }
    else
    {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
        // local part length exceeded
        $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
        // domain part length exceeded
        $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
        // local part starts or ends with '.'
        $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
        // local part has two consecutive dots
        $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
        // character not valid in domain part
        $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
        // domain part has two consecutive dots
        $isValid = false;
      }
      else if
      (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
        str_replace("\\\\","",$local)))
      {
        // character not valid in local part unless 
        // local part is quoted
        if (!preg_match('/^"(\\\\"|[^"])+"$/',
          str_replace("\\\\","",$local)))
        {
          $isValid = false;
        }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
        // domain not found in DNS
        $isValid = false;
      }
    }
    return $isValid;
  }
  
}