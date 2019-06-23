<?php
/**
 * Mailchimp Subscribe plugin for Craft CMS 3.x
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\mailchimpsubscribe\controllers;

use aelvan\mailchimpsubscribe\models\AudienceResponse;
use aelvan\mailchimpsubscribe\models\MemberResponse;
use Craft;
use craft\web\Controller;
use craft\errors\DeprecationException;

use aelvan\mailchimpsubscribe\MailchimpSubscribe as Plugin;
use yii\web\BadRequestHttpException;
use yii\web\Response;

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
     * @return null|Response
     * @throws BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionSubscribe()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $email = $request->getParam('email', '');
        $audienceId = $request->getParam('audienceId', '');
        $emailType = $request->getParam('email_type', 'html');
        $language = $request->getParam('language', null);
        $mergeFields = $request->getParam('merge_fields', null);
        $marketingPermissions = $request->getParam('marketing_permissions', null);
        $interests = $request->getParam('interests', null);
        $tags = $request->getParam('tags', null);
        $vip = $request->getParam('vip', false);
        $redirect = $request->getParam('redirect', '');
        
        if ($vip !== null) {
            $vip = $this->parseBoolParam($vip);
        }
        
        // deprecated parameters
        $listId = $request->getParam('lid', '');
        $emailTypeOld = $request->getParam('emailtype', '');
        $mcvars = $request->getParam('mcvars', null);

        if ($audienceId === '' && $listId !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'Passing the `lid` parameter to Mailchimp Subscribe\'s subscribe action is deprecated. Use `audienceId` instead.');
            $audienceId = $listId;
        }

        if ($emailTypeOld !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'The `emailtype` parameter passed to Mailchimp Subscribe\'s subscribe action is deprecated. Use `email_type` instead.');
            $emailType = $emailTypeOld;
        }

        if ($mcvars !== null) {
            Craft::$app->deprecator->log(__METHOD__, 'The `mcvars` parameter passed to Mailchimp Subscribe\'s subscribe action is deprecated. Use `merge_fields` instead.');

            if ($mergeFields === null) {
                $mergeFields = $mcvars;
            }
        }

        if ($mcvars !== null && isset($mcvars['interests'])) {
            Craft::$app->deprecator->log(__METHOD__, 'Passing `interests` through the `mcvars` parameter to Mailchimp Subscribe\'s subscribe action is deprecated. Use `interests` directly instead.');

            if ($interests === null) {
                $interests = $mcvars['interests'];
            }
        }

        $opts = [
            'email_type' => $emailType,
            'language' => $language,
            'merge_fields' => $mergeFields,
            'marketing_permissions' => $marketingPermissions,
            'interests' => $interests,
            'tags' => $tags,
            'vip' => $vip,
        ];
        
        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->subscribe($email, $audienceId, $opts);

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
     * @return null|Response
     * @throws BadRequestHttpException
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
     * @return null|Response
     * @throws BadRequestHttpException
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
        $permanent = $this->parseBoolParam($request->getParam('permanent', ''));
        $redirect = $request->getParam('redirect', '');

        if ($audienceId === '' && $listId !== '') {
            Craft::$app->deprecator->log(__METHOD__, 'Passing the `lid` parameter to Mailchimp Subscribe\'s unsubscribe action is deprecated. Use `audienceId` instead.');
            $audienceId = $listId;
        }

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->delete($email, $audienceId, $permanent);

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
     * Controller action for getting a member by email
     *
     * @return null|Response
     * @throws BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionGetMemberByEmail()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $email = $request->getParam('email', '');
        $audienceId = $request->getParam('audienceId', '');
        $redirect = $request->getParam('redirect', '');

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->getMemberByEmail($email, $audienceId);

        $memberResponse = new MemberResponse([
            'success' => $result !== null,
            'response' => $result
        ]);

        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($memberResponse);
        }

        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result !== null) {
            return $this->redirectToPostedUrl();
        }

        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $memberResponse]
        ]);
        
        return null;
    }    

    /**
     * Controller action for getting an audience by id
     *
     * @return null|Response
     * @throws BadRequestHttpException
     * @throws DeprecationException
     */
    public function actionGetAudienceById()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // get post variables
        $audienceId = $request->getParam('audienceId', '');
        $redirect = $request->getParam('redirect', '');

        // call service method
        $result = Plugin::$plugin->mailchimpSubscribe->getAudienceById($audienceId);

        $audienceResponse = new AudienceResponse([
            'success' => $result !== null,
            'response' => $result
        ]);
        
        // if this was an ajax request, return json
        if ($request->getAcceptsJson()) {
            return $this->asJson($audienceResponse);
        }

        // if a redirect variable was passed, do redirect
        if ($redirect !== '' && $result !== null) {
            return $this->redirectToPostedUrl();
        }

        // set route variables and return
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['mailchimpSubscribe' => $audienceResponse]
        ]);
        
        return null;
    }    

    /**
     * --- Deprecated -----------------------------------------------------------------------------
     */
    
    /**
     * Controller action for checking if a user is subscribed to list
     *
     * @return null|Response
     * @throws BadRequestHttpException
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
     * @return null|Response
     * @throws BadRequestHttpException
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

    /**
     * --- Private methods --------------------------------------------------------------------------------------
     */
    
    /**
     * Check's if a param is bool'ish.
     * 
     * @param $param
     * @return bool
     */
    private function parseBoolParam($param): bool
    {
        return $param === 'on' || $param === 'yes' || $param === '1' || $param === 'true';
    }
}
