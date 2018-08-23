<?php
namespace Craft;

class MailchimpSubscribeVariable
{

    public function getListInterestGroups($listId, $count = 10)
    {
        return craft()->mailchimpSubscribe->getListInterestGroups($listId, $count);
    }

    public function checkIfSubscribed($email, $formListId)
    {
        return craft()->mailchimpSubscribe->checkIfSubscribed($email, $formListId);
    }

}
