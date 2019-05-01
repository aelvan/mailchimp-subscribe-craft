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

use Craft;
use craft\web\Controller;
use craft\errors\DeprecationException;

use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;

/**
 * @author    André Elvan
 * @package   MailchimpSubscribe
 * @since     3.0.0
 */
class AudienceController extends Controller
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
     * @throws \yii\web\BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionSubscribe()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $email = $request->getParam('email', '');
        $audienceId = $request->getParam('audienceId', '');
        $listId = $request->getParam('lid', '');
        $emailType = $request->getParam('emailtype', 'html');
        $vars = $request->getParam('mcvars', null);
        $redirect = $request->getParam('redirect', '');
        $language = $request->getParam('language', null);

        if ($audienceId === '' && $listId !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'Passing the `lid` parameter to Mailchimp Subscribe\'s subscribe action is deprecated. Use `audienceId` instead.');
            $audienceId = $listId;
        }

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->subscribe($email, $audienceId, $emailType, $vars, $language);

        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }

        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl();
        }

        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $result]
        ]);

        return null;
    }

    /**
     * Controller action for unsubscribing an email from a list
     *
     * @return null|\yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionUnsubscribe()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $email = $request->getParam('email', '');
        $audienceId = $request->getParam('audienceId', '');
        $listId = $request->getParam('lid', '');
        $redirect = $request->getParam('redirect', '');
        
        if ($audienceId === '' && $listId !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'Passing the `lid` parameter to Mailchimp Subscribe\'s unsubscribe action is deprecated. Use `audienceId` instead.');
            $audienceId = $listId;
        }

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->unsubscribe($email, $audienceId);

        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }

        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl();
        }

        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $result]
        ]);

        return null;
    }
    
    /**
     * Controller action for deleting an email from a list
     *
     * @return null|\yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionDelete()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $email = $request->getParam('email', '');
        $audienceId = $request->getParam('audienceId', '');
        $listId = $request->getParam('lid', '');
        $permanent = $request->getParam('permanent', '');
        $redirect = $request->getParam('redirect', '');
        
        if ($audienceId === '' && $listId !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'Passing the `lid` parameter to Mailchimp Subscribe\'s unsubscribe action is deprecated. Use `audienceId` instead.');
            $audienceId = $listId;
        }

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->delete($email, $audienceId, $permanent==='on' || $permanent==='yes' || $permanent==='1' || $permanent==='true');

        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }

        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl();
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
     * @throws \yii\web\BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionCheckIfSubscribed()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $email = $request->getParam('email', '');
        $audienceId = $request->getParam('audienceId', '');
        $listId = $request->getParam('lid', '');
        $redirect = $request->getParam('redirect', '');

        if ($audienceId === '' && $listId !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'Passing the `lid` parameter to Mailchimp Subscribe\'s checkIfSubscribed action is deprecated. Use `audienceId` instead.');
            $audienceId = $listId;
        }
        
        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->checkIfSubscribed($email, $audienceId);

        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }

        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl();
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
     * @throws \yii\web\BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionCheckIfInList()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $email = $request->getParam('email', '');
        $audienceId = $request->getParam('audienceId', '');
        $listId = $request->getParam('lid', '');
        $redirect = $request->getParam('redirect', '');

        if ($audienceId === '' && $listId !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'Passing the `lid` parameter to Mailchimp Subscribe\'s checkIfInList action is deprecated. Use `audienceId` instead.');
            $audienceId = $listId;
        }
        
        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->checkIfInList($email, $audienceId);

        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }

        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result['success']) {
            return $this->redirectToPostedUrl();
        }

        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $result]
        ]);

        return null;
    }
}
