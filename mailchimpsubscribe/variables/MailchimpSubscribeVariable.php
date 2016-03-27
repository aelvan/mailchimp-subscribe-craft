<?php
namespace Craft;

class MailchimpSubscribeVariable
{

    /**
     * @param $u - from Mailchimp Archive generator code
     * @param $fid - from Mailchimp Archive generator code
     * @param $show - from Mailchimp Archive generator code
     * @return string - archive contents, or false if there was an error retrieving the archive
     *
     * This variable effectively allows you to grab a Mailchimp archive on a page served by https, which is not currently possible by just embedding the script.
     *
     * Mailchimp docs about this at: http://kb.mailchimp.com/campaigns/archives/add-a-custom-campaign-archive-to-a-website#Use-the-Archive-Generator-Code
     *
     */
    public function mailchimpArchive($u, $fid, $show = 10){

        //$mcArchive will be false on failure for any reason, otherwise will contain the generated archive code
        $mcArchive = file_get_contents('http://us5.campaign-archive1.com/generate-js/?u='.$u.'&fid='.$fid.'&show='. $show);
        if(!$mcArchive) return "";

        return ('<script language="javascript">' . $mcArchive . '</script>');

    }

}

