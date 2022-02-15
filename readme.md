# Silverstripe SSO for Discourse (DiscourseConnect)

Allows users to sign in to Discourse with user accounts on your Silverstripe based website.

Note: This becomes the **only** way to log in to Discourse. To add a "Log in as..." button instead, look into OAuth, instead of SSO ([DiscourseConnect](https://meta.discourse.org/t/discourseconnect-official-single-sign-on-for-discourse-sso/13045)).

## Installation

1. Install into your Silverstripe site via composer:
````
composer require purplespider/silverstripe-discourse-sso "1.*"
````
2. Add config:
````yml
PurpleSpider\DiscourseSSO\DiscourseSSOEndpoint:
  extensions:
    - PurpleSpider\MySite\DiscourseSSOEndpointExtension
  secret: REPLACE-WITH-RANDOM-STRING
  discourse-sso-url: "https://community.example.com/session/sso_login"
````
  * Set `secret` to a random string (min 10 characters)
  * Change `https://community.example.com` to your Discourse install's URL.

2. Perform a dev\build: 
````
dev/build?flush=1
````
3. Configure [DiscourseConnect](https://meta.discourse.org/t/discourseconnect-official-single-sign-on-for-discourse-sso/13045) in your Discourse Admin.
  * Settings > Login > `enable discourse connect`: `Enabled`
  * Settings > Login > `discourse connect url`: `https://example.com/discourse/sso` (Replace `example.com` with your Silverstripe site's domain.)
  * Settings > Login > `discourse_connect_secret`: Set to the SAME random string from your Silverstripe config above.

## Optional Customisation

* Implement user email validation on the Silverstripe side (essential for Discourse).
* Redirect to Silverstripe logout page after a Discourse log out: Discourse > Settings > Users > `logout redirect`: `https://example.com/Security/logout?BackURL=/home`
* Log out user on Discourse after logging out from Silverstripe site.
    * Using [Discouse API](https://meta.discourse.org/t/discourseconnect-official-single-sign-on-for-discourse-sso/13045#heading--logoff).
    * Or use something like https://github.com/johnmap/discourse-sso-logout
* Customise Silverstripe log in message:
**app/lang/en.yml**
````yml
en:
  PurpleSpider\DiscourseSSO\DiscourseSSOEndpoint:
    LOGINMESSAGE: "To access our forum, please log in or register:"
````
* Use an Extension to pass through extra member data to Discourse, and/or customise log in authentication, e.g.
````php
<?php

namespace YOURNAMESPACE;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;

class DiscourseSSOEndpointExtension extends DataExtension
{


    public function updateExtraParameters(&$extraParameters, $member)
    {
            $extraParameters['add_groups'] = 'my-members';
            $extraParameters['remove_groups'] = 'my-other-members';
            $extraParameters['username'] = $member->ForumUsername;
            $extraParameters['custom.user_field_1'] = "Scotland";
    }

    public function updateAuthentication(&$authenticated, $member, &$action)
    {
        if(!$member->EmailIsValidated) {
            $authenticated = false;
            $action =  $this->owner->render(array(
                'Title' => 'Please Verify Your Email Address',
                'Content' => DBField::create_field(
                        'HTMLFragment', 
                        "<p>Your email address has not yet been verified.</p>"
                    ),
            ));
        }
    }

}
````

**_config/discoursesso.yml**
````yml
PurpleSpider\DiscourseSSO\DiscourseSSOEndpoint:
  extensions:
    - YOURNAMESPACE\DiscourseSSOEndpointExtension
````

# Thanks
Many thanks to [Colin Viebrock](https://github.com/cviebrock) for their [Discourse Single-Sign-On Helper for PHP](https://github.com/cviebrock/discourse-php) module which does all the hard work.
