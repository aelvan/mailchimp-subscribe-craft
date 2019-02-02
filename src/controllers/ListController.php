<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * Simple Craft plugin for subscribing to a MailChimp list.
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\controllers;

use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;

use Craft;
use craft\web\Controller;

/**
 * @author    André Elvan
 * @package   MailchimpSubscribe
 * @since     2.0.0
 */
class ListController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Controller action for subscribing an email to a list
     * 
     * @return null|\yii\web\Response
     */
    public function actionSubscribe()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        
        // get post variables
        $email = $request->getParam('email', '');
        $formListId = $request->getParam('lid', '');
        $emailType = $request->getParam('emailtype', 'html');
        $vars = $request->getParam('mcvars', null);
        $redirect = $request->getParam('redirect', '');
        $language = $request->getParam('language', null);

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->subscribe($email, $formListId, $emailType, $vars, $language);
        
        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }
        
        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl(array('mailchimpSubscribe' => $result));
        }
        
        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $result]
        ]);
        
        return null;
    }

    /**
     * Controller action for unsubscribing an email to a list
     * 
     * @return null|\yii\web\Response
     */
    public function actionUnsubscribe()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        
        // get post variables
        $email = $request->getParam('email', '');
        $formListId = $request->getParam('lid', '');
        $redirect = $request->getParam('redirect', '');

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->unsubscribe($email, $formListId);
        
        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }
        
        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl(array('mailchimpSubscribe' => $result));
        }
        
        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $result]
        ]);
        
        return null;
    }

    /**
     * Controller action for checking if a user is subscribed to list
     * 
     * @return null|\yii\web\Response
     */
    public function actionCheckIfSubscribed()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        
        // get post variables
        $email = $request->getParam('email', '');
        $formListId = $request->getParam('lid', '');
        $redirect = $request->getParam('redirect', '');
        
        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->checkIfSubscribed($email, $formListId);
        
        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }
        
        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl(array('mailchimpSubscribe' => $result));
        }
        
        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $result]
        ]);
        
        return null;
    }

    /**
     * Controller action for checking if a user is on a list
     * 
     * @return null|\yii\web\Response
     */
    public function actionCheckIfInList()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        
        // get post variables
        $email = $request->getParam('email', '');
        $formListId = $request->getParam('lid', '');
        $redirect = $request->getParam('redirect', '');
        
        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->checkIfInList($email, $formListId);
        
        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }
        
        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl(array('mailchimpSubscribe' => $result));
        }
        
        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $result]
        ]);
        
        return null;
    }
}
