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

class MailchimpSubscribe_ListController extends BaseController
{

    protected $allowAnonymous = true;

    /**
     * Subscribe controller action
     * 
     * @return null
     */
    public function actionSubscribe()
    {
        // get post variables
        $email = craft()->request->getParam('email', '');
        $formListId = craft()->request->getParam('lid', '');
        $emailType = craft()->request->getParam('emailtype', 'html');
        $vars = craft()->request->getParam('mcvars', null);
        $redirect = craft()->request->getParam('redirect', '');
        $language = craft()->request->getParam('language', null);

        $result = craft()->mailchimpSubscribe->subscribe($email, $formListId, $emailType, $vars, $language);
        
        if (craft()->request->isAjaxRequest()) {
            return $this->returnJson($result);
        }
        
        if ($redirect != '' && $result['success']) {
            // if a redirect url was set in template form, redirect to this
            $this->redirectToPostedUrl();
        } else {
            craft()->urlManager->setRouteVariables(array('mailchimpSubscribe' => $result));
        }
    }

    /**
     * Check if subscribed controller action
     * 
     * @return null
     */
    public function actionCheckIfSubscribed()
    {
        // get post variables
        $email = craft()->request->getParam('email', '');
        $formListId = craft()->request->getParam('lid', '');
        $redirect = craft()->request->getParam('redirect', '');
        
        $result = craft()->mailchimpSubscribe->checkIfSubscribed($email, $formListId);
        
        if (craft()->request->isAjaxRequest()) {
            return $this->returnJson($result);
        }
        
        if ($redirect != '' && $result->success) {
            // if a redirect url was set in template form, redirect to this
            $this->redirectToPostedUrl();
        } else {
            craft()->urlManager->setRouteVariables(array('mailchimpSubscribe' => $result));
        }
    }
   
}
