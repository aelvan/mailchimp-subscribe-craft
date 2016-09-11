<?php
namespace Craft;

class MailchimpSubscribeVariable
{

    public function getListInterestGroups($listId)
    {
        return craft()->mailchimpSubscribe->getListInterestGroups($listId);
    }

    
}
