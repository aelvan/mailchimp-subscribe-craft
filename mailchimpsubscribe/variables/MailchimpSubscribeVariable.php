<?php
namespace Craft;

class MailchimpSubscribeVariable
{

    public function getListInterestGroups($listId)
    {
        return craft()->mailchimpSubscribe->getListInterestGroups($listId);
    }

    public function checkIfSubscribed($email, $formListId)
    {
        return craft()->mailchimpSubscribe->checkIfSubscribed($email, $formListId);
    }

}
