<?php

namespace PurpleSpider\DiscourseSSO;

use PageController;
use SilverStripe\Security\Security;
use Cviebrock\DiscoursePHP\SSOHelper;

class DiscourseSSOEndpoint extends PageController
{   
    private static $allowed_actions = [
        'sso'
    ];

    public function sso()
    {
        if(!isset($_GET['sso']) || !isset($_GET['sig'])) {
            return $this->owner->httpError(404);
        }

        $sso = new SSOHelper();

        // this should be the same in your yml config and in your Discourse settings:
        $sso->setSecret($this->config()->get('secret'));

        // load the payload passed in by Discourse
        $payload = $_GET['sso'];
        $signature = $_GET['sig'];

        // validate the payload
        if (!($sso->validatePayload($payload,$signature))) {
            // invaild, deny
            header("HTTP/1.1 403 Forbidden");
            echo("Bad SSO request");
            die();
        }

        $nonce = $sso->getNonce($payload);

        // Authenticate with Silverstripe
        if(!Security::getCurrentUser()) {
            return Security::permissionFailure(null, _t("LOGINMESSAGE", "Please log in:"));
        }
        $member = Security::getCurrentUser();

        // Enables an extension hook to add additional authentication checks, e.g. email validation
        $authenticated = true;
        $this->extend('updateAuthentication', $authenticated, $member, $action);
        if(!$authenticated) {
            return $action;
        }

        // Details for Discourse
        $userId = $member->ID;
        $userEmail = $member->Email;
        $extraParameters = array(
            'name'     => $member->getName(),
        );
        $this->extend('updateExtraParameters', $extraParameters, $member);

        // build query string and redirect back to the Discourse site
        $query = $sso->getSignInString($nonce, $userId, $userEmail, $extraParameters);
        header('Location: '.$this->config()->get('discourse-sso-url').'?' . $query);
        exit(0);
    }

}