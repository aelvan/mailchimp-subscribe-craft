Mailchimp Subscribe plugin for Craft CMS 3.x
===

Mailchimp Subscribe for [Craft](http://craftcms.com/) is a plugin for working with Mailchimp audiences.  

## Requirements

This plugin requires Craft CMS 3.0.0 or later.

## Update notes

### Updating from version 2.x to 3.x

Too much, [see the changelog for information](https://raw.githubusercontent.com/aelvan/mailchimp-subscribe-craft/craft3/CHANGELOG.md).

### Updating from version 1.x (Craft 2) to 2.x (Craft 3)

- The plugin no longer is configurable from the control panel, you need to use a config file.
- The plugin handle used in the action input has changed from `mailchimpSubscribe` to `mailchimp-subscribe`. 
- The redirect url now has to be hashed in the redirect input. 

## Installation

To install the plugin, either install it from the plugin store, or follow these instructions:

1. Install with composer via `composer require aelvan/mailchimp-subscribe` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings → Plugins, or from the command line via `./craft install/plugin mailchimp-subscribe`.
3. Eat a banana and continue reading!

---

## Configuration

Mailchimp Subscribe must be configured by creating a file named `mailchimp-subscribe.php` in your Craft config 
folder (usually `/config`), and configure settings as needed.  

### apiKey [string]
*Default: `''`*  
The API key for your Mailchimp account. *This setting is required.*

### audienceId [string]
*Default: `''`*  
The default audience ID you want users to subscribe to. You can also configure the audienceID by posting this
through your forms (see below). 

### doubleOptIn [bool]
*Default: `true`*  
Indicates if double opt-in should be used when subscribing. If enabled, users will receive an email to 
confirm their subscription before being added to the audience. 

### Example config file

    <?php
    return [
        'apiKey' => 'xxxxxxxxxxxxxxxxxxx-us2',
        'audienceId' => '7fw6eq98ca',
        'doubleOptIn' => true,
    ];

---

The plugin config also supports using different settings on a per-site basis. This works like Craft's general config does:

    <?php
    return [
        'apiKey' => [
            'siteHandleA' => 'xxxxxxxxxxxxxxxxxxx-us2',
            'siteHandleB' => 'xxxxxxxxxxxxxxxxxxx-us2',
        ],
        'audienceId' => [
            'siteHandleA' => '7fw6eq98ca',
            'siteHandleB' => '3fa8ew9fce',
        ],
        'doubleOptIn' => [
            'siteHandleA' => true,
            'siteHandleB' => false,
        ],
    ];

## Usage

Mailchimp Subscribe let's you subscribe, unsubscribe and delete members to/from Mailchimp audiences.   

### Subscribing a member to an audience

In it's simplest form, you can subscribe a user with a simple form with just an email input field:

```
<form class="newsletter-form" action="" method="post">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="mailchimp-subscribe/audience/subscribe">
    
    {% set subscribeResponse = mailchimpSubscribe is defined and mailchimpSubscribe.action == 'subscribe' ? mailchimpSubscribe : null %}
    
    {% if subscribeResponse %}
        {% if subscribeResponse.success %}
            <p>Subscribed successfully!</p>
        {% else %}
            <p>An error occured: {{ subscribeResponse.message }}</p>
        {% endif %}
    {% endif %}

    <div>
        <label for="emailInput"{% if subscribeResponse and subscribeResponse.errorCode=='1000' %} class="error"{% endif %}>
            Email:
        </label>
        <input id="emailInput" type="text" name="email" 
            {% if subscribeResponse and not subscribeResponse.success %}value="{{ subscribeResponse.values.email ?? '' }}"{% endif %}/>
    </div>

    <input type="submit" name="" value="Subscribe"/>
</form>
```

If you want to *redirect the user upon success*, you can supply a redirect parameter (like you would in any 
craft front-end form). You can also *set or override the `audienceId` directly from the form*:  

```
<form class="newsletter-form" action="" method="post">
    {{ csrfInput() }}
    {{ redirectInput('thankyou') }}
    <input type="hidden" name="action" value="mailchimp-subscribe/audience/subscribe">
    <input type="hidden" name="audienceId" value="1fr4ew09qv">
    
    {% set subscribeResponse = mailchimpSubscribe is defined and mailchimpSubscribe.action == 'subscribe' ? mailchimpSubscribe : null %}
    
    {% if subscribeResponse %}
        {% if not subscribeResponse.success %}
            <p>An error occured: {{ subscribeResponse.message }}</p>
        {% endif %}
    {% endif %}

    <div>
        <label for="emailInput"{% if subscribeResponse and subscribeResponse.errorCode=='1000' %} class="error"{% endif %}>
            Email:
        </label>
        <input id="emailInput" type="text" name="email" 
            {% if subscribeResponse and not subscribeResponse.success %}value="{{ subscribeResponse.values.email ?? '' }}"{% endif %}/>
    </div>

    <input type="submit" name="" value="Subscribe"/>
</form>
```

You can also set `email_type`, `merge_fields`, `interests`, `language`, `vip`, `marketing_permissions` and `tags` 
as per [the Mailchimp API documentation](https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/).

The following example shows all the options in play - obviously you should adapt this to your own needs:

```
<form class="newsletter-form" action="" method="post">
    {{ csrfInput() }}
    {{ redirectInput('thankyou') }}
    <input type="hidden" name="action" value="mailchimp-subscribe/audience/subscribe">
    <input type="hidden" name="audienceId" value="2a34d0978q">

    <input type="hidden" name="email_type" value="text">
    <input type="hidden" name="language" value="no">
    <input type="hidden" name="vip" value="yes">
    
    {% set subscribeResponse = mailchimpSubscribe is defined and mailchimpSubscribe.action == 'subscribe' ? mailchimpSubscribe : null %}
    
    {% if subscribeResponse %}
        {% if not subscribeResponse.success %}
            <p>An error occured: {{ subscribeResponse.message }}</p>
        {% endif %}
    {% endif %}

    <div>
        <label for="emailInput"{% if subscribeResponse and subscribeResponse.errorCode=='1000' %} class="error"{% endif %}>
            Email:
        </label>
        <input id="emailInput" type="text" name="email" 
            {% if subscribeResponse and not subscribeResponse.success %}value="{{ subscribeResponse.values.email ?? '' }}"{% endif %}/>
    </div>
    
    <div>
        <label for="firstNameInput">First name:</label>
        <input id="firstNameInput" type="text" name="merge_fields[FNAME]" 
            {% if subscribeResponse and not subscribeResponse.success %}value="{{ subscribeResponse.values.FNAME ?? '' }}"{% endif %}/>
    </div>

    <div>
        <label for="lastNameInput">Last name:</label>
        <input id="lastNameInput" type="text" name="merge_fields[LNAME]" 
            {% if subscribeResponse and not subscribeResponse.success %}value="{{ subscribeResponse.values.LNAME ?? '' }}"{% endif %}/>
    </div>

    <div>
        <h4>Marketing permissions</h4>
        <input type="hidden" name="marketing_permissions" value="">
        <input type="checkbox" value="1c862d81f1" name="marketing_permissions[]">Weekly newsletter<br>
        <input type="checkbox" value="46712a192a" name="marketing_permissions[]">Special offers<br>
        <input type="checkbox" value="46acfd7a2b" name="marketing_permissions[]">Special offers from partners<br>
	</div>
    
    <div>
        <h4>Tags</h4>
        <input type="hidden" value="" name="tags">
        <input type="checkbox" value="One tag" name="tags[]">One tag<br>
        <input type="checkbox" value="Two tag" name="tags[]">Two tag<br>
        <input type="checkbox" value="Three tag" name="tags[]">Three tag<br>
	</div>
				
    {% set interestGroups = craft.mailchimpSubscribe.getInterestGroups('2a34d0978q') %}

	{% if interestGroups and (interestGroups | length > 0) %}
		{% for group in interestGroups %}
		    <div>
                <h4>{{ group.title }}</h4>
                <input type="hidden" value="" name="interests[{{ group.title }}]">
                
                {% if group.type=='checkboxes' %}
                    {% for interest in group.interests %}
                        <input type="checkbox" value="{{ interest.id }}" name="interests[{{ group.title }}][]">{{ interest.name }}<br>
                    {% endfor %}
                {% endif %}
    
                {% if group.type=='radio' %}
                    {% for interest in group.interests %}
                        <input type="radio" value="{{ interest.id }}" name="interests[{{ group.title }}][]">{{ interest.name }}<br>
                    {% endfor %}
                {% endif %}
    
                {% if group.type=='dropdown' %}
                    <select name="interests[{{ group.title }}][]">
                        {% for interest in group.interests %}
                            <option value="{{ interest.id }}">{{ interest.name }}</option>
                        {% endfor %}
                    </select>
                {% endif %}
            </div>
		{% endfor %}
	{% endif %}
	
    <br>
    <input type="submit" name="" value="Subscribe"/>
</form>
```   

**Please note:**

- You can get the interests connected to a list with the template variable `getInterestGroups`, as shown in 
the example. Mailchimp lets you create different types of groups, checkboxes, radio buttons, dropdown, etc, 
but doesn't actually limit the add functionality to the groups depending on the type. You have to do this 
based on the group type.

- If you want to override/reset any existing tags and marketing permissions, make sure you add the hidden 
inputs as shown above. Omit it if you want tags to be append only.  

- For more information about marketing permissions, see the GDPR section below.

- You will not get an error message if you submit an email address that's already subscribed to your 
audience – _this is intended behavior_. If you want to check if an email is already in your audience, 
you can use the template variable `craft.mailchimpSubscribe.getMemberByEmail` or the controller action
`mailchimp-subscribe/audience/get-member-by-email`, and check if the response is `null`, or what the status 
of the member is.     


### Unsubscribing a member from an audience

You can subscribe a user by submitting the email address to the unsubscribe controller action:

```
<form class="newsletter-form" action="" method="post">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="mailchimp-subscribe/audience/unsubscribe">

    {% set unsubscribeResponse = mailchimpSubscribe is defined and mailchimpSubscribe.action == 'unsubscribe' ? mailchimpSubscribe : null %}

    {% if unsubscribeResponse %}
        {% if unsubscribeResponse.success %}
            <p>Unsubscribed successfully!</p>
        {% else %}
            <p>An error occured: {{ unsubscribeResponse.message }}</p>
        {% endif %}
    {% endif %}

    <div class="field-line">
        <label for="emailInput"{% if unsubscribeResponse and unsubscribeResponse.errorCode=='1000' %} class="error"{% endif %}>
            Email:
        </label>
        <input id="emailInput" type="text" name="email" 
            {% if unsubscribeResponse and not unsubscribeResponse.success %}value="{{ unsubscribeResponse.values.email ?? '' }}"{% endif %}/>
    </div>

    <input type="submit" name="" value="Unsubscribe"/>
</form>
```

### Deleting a member from an audience

You can delete a user by submitting the email address to the unsubscribe controller action:

```
<form class="newsletter-form" action="" method="post">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="mailchimp-subscribe/audience/delete">

    {% set deleteResponse = mailchimpSubscribe is defined and mailchimpSubscribe.action == 'delete' ? mailchimpSubscribe : null %}

    {% if deleteResponse %}
        {% if deleteResponse.success %}
            <p>Deleted successfully!</p>
        {% else %}
            <p>An error occured: {{ deleteResponse.message }}</p>
        {% endif %}
    {% endif %}

    <div>
        <label for="emailInput"{% if deleteResponse and deleteResponse.errorCode=='1000' %} class="error"{% endif %}>
            Email:
        </label>
        <input id="emailInput" type="text" name="email" 
            {% if deleteResponse and not deleteResponse.success %}value="{{ deleteResponse.values.email ?? '' }}"{% endif %}/>
    </div>
    
    <div>
        <label><input type="checkbox" name="permanent"/> Permanent</label>
    </div>
    
    <input type="submit" name="" value="Delete"/>
</form>
```

The `permanent` parameter is optional, when enabled it creates a permanent/hard delete.


### Differences between unsubscribe, delete and permanent/hard delete

When unsubscribing a user from an audience, the status of that member is set to `unsubscribed`. All information 
about the member will be kept in Mailchimp, the data can be queried, you can see it in the Mailchimp 
control panel, and the status can be changed back to `subscribed` at a later point. 

When (soft) deleting a user, it's mostly the same as an unsubscribe, except the member will have an empty 
status and will not be visible in the Mailchimp control panel.

When permanently (hard) deleting a user, all personally identifiable information related to the member
is deleted, and the member is removed from the audience. This is the GDPR-compliant way of removing a member 
if requested. **This will make it impossible to re-import or re-subscribe the member at a later point, the 
member can only resubscribe through a Mailchimp hosted, GDPR-compliant subscribe form.**  


### Get information about a member or an audience

If you need to get information about a member or an audience, you can use the `craft.mailchimpSubscribe.getMemberByEmail`
and `craft.mailchimpSubscribe.getAudienceById` template variables, or the corresponding controller actions 
`mailchimp-subscribe/audience/get-member-by-email` and `mailchimp-subscribe/audience/get-audience-by-id` (see documentation
below).

The returned data contains all the information directly from the Mailchimp API, as documented in the API documentation 
for [members](https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/) and 
[audiences](https://developer.mailchimp.com/documentation/mailchimp/reference/lists/). 

In the previous versions of Mailchimp Subscribe, there were template variables for `checkIfInList` and `checkIfSubscribed`.
These are now deprecated, you should instead get member information and check the `status` of the member.  

### GDPR and marketing permissions

At the time of writing this (june 2019) it's still unclear if subscribing through the API is considered GDPR
compliant by Mailchimp, so it's probably safest to assume not, and use their hosted forms, if you're 
serving EU customers.

The API supports updating marketing permissions, though, so feel free to experiment. The only way to create 
and edit marketing permissions at the moment, is to create a new signup form from your GDPR enabled audience,
and edit the attached "Marketing permissions" block. To create the input checkboxes in your own form, you need 
to get the ID's of each permission. The only way to do this at the moment, is to get information about a member
that's already subscribed to the list. So subscribe a user, then do (`dump` is only available when `devMode`
is enabled):

```     
{{ dump(craft.mailchimpSubscribe.getMarketingPermissionsByEmail('myemail@domain.com')) }}
```

This will dump out an array of marketing permissions, where the important part is the `marketing_permission_id`
that you'll need to add to you form like this:

``` 
...
<input type="hidden" name="marketing_permissions" value="">
<input type="checkbox" value="1c862d81f1" name="marketing_permissions[]">Weekly newsletter<br>
<input type="checkbox" value="46712a192a" name="marketing_permissions[]">Special offers<br>
<input type="checkbox" value="46acfd7a2b" name="marketing_permissions[]">Special offers from partners<br>
...
```

Again, make sure your form actually is GDPR compliant, and don't blame me.

---

## Controller actions

All controller actions: 

- Requires `POST`, so make sure to add a CSRF token to the request.
- Returns JSON if the request was an ajax request/has the right headers.
- A variable named `mailchimpSubscribe` will be available in your templates if an error occurs, or
if you return to the same template after success, without redirecting (see examples above). 

### mailchimp-subscribe/audience/subscribe

Subscribes a member to an audience. The following variables can be submitted:

**email** (required): Email of member to subscribe.  
**redirect**: Route to redirect to on success. If not set, the same route will be loaded with `mailchimpSubscribe` variable set.  
**audienceId**: ID of audience to subscribe the member to. If not set, the configured `audienceId` will be used.  
**email_type**: Email type the member prefers. Can be `html` (default) or `text`  
**language**: Preferred language for member ([see documentation for possible language codes](https://mailchimp.com/help/view-and-edit-contact-languages)).  
**vip**: Sets VIP status for member, accepted values are `true`, `yes`, `1` for `true`, anything else is considered `false` (default).  
**merge_fields**: An array of additional fields defined in Mailchimp (Currently called "Audience fields and *|MERGE|* tags" in Mailchimp).  
**marketing_permissions**: An array of marketing permissions that should be enabled for member.  
**interests**: An array of group interests that should be enabled for member (you find these under "Manage Contacts" > "Groups" in Mailchimp).  
**tags**: An array of tags that should be enabled for member (you find these under "Manage Contacts" > "Tags" in Mailchimp). New tags are automatically created in Mailchimp.  

The action will return a [SubscribeResponse](#subscriberesponse) with the `action` property set to `subscribe`.
    
### mailchimp-subscribe/audience/unsubscribe

Unsubscribes a member from an audience. The following variables can be submitted:

**email** (required): Email of member to unsubscribe.  
**redirect**: Route to redirect to on success. If not set, the same route will be loaded with `mailchimpSubscribe` variable set.   
**audienceId**: ID of audience to unsubscribe the member from. If not set, the configured `audienceId` will be used.  

The action will return a [SubscribeResponse](#subscriberesponse) with the `action` property set to `unsubscribe`.

### mailchimp-subscribe/audience/delete

Delete a member from an audience. The following variables can be submitted:

**email** (required): Email of member to delete.  
**redirect**: Route to redirect to on success. If not set, the same route will be loaded with `mailchimpSubscribe` variable set.  
**audienceId**: ID of audience to delete the member from. If not set, the configured `audienceId` will be used.  
**permanent**: Set to `true` if the member should be permanently deleted. See the "Differences between unsubscribe, delete and permanent delete" section above for more information on what this means.    

The action will return a [SubscribeResponse](#subscriberesponse) with the `action` property set to `delete`.


### mailchimp-subscribe/audience/get-member-by-email

Gets information about a member in an audience by email. The following variables can be submitted:

**email** (required): Email to query.  
**redirect**: Route to redirect to on success. If not set, the same route will be loaded with `mailchimpSubscribe` variable set.  
**audienceId**: ID of audience to unsubscribe the member to. If not set, the configured `audienceId` will be used.  

The action will return a [MemberResponse](#memberresponse) with the `action` property set to `get-member`.


### mailchimp-subscribe/audience/get-audience-by-id

Gets information about an audience by its ID. The following variables can be submitted:

**audienceId**: ID of audience to get information on. If not set, the configured `audienceId` will be used.  
**redirect**: Route to redirect to on success. If not set, the same route will be loaded with `mailchimpSubscribe` variable set.  

The action will return a [AudienceResponse](#audienceresponse) with the `action` property set to `get-audience`.


---

## Template variables

_In the definitions below, square brackets indicate parameters that are optional. If audience ID is 
not submitted, the id from the config file will be used._

All methods will return `null` if an error occured, please refer to you logs/debug toolbar for the
error message.

### craft.mailchimpSubscribe.getInterestGroups([$id=''])

Returns interest groups for an audience in the following structure:

```   
[
    0 => [
        'title' => 'Job position'
        'type' => 'radio'
        'interests' => [
            0 => [
                'id' => '3ba93a1818'
                'name' => 'Developer'
            ]
            1 => [
                'id' => '98a725d885'
                'name' => 'Designer'
            ]
            2 => [
                'id' => 'e54cdea2b8'
                'name' => 'Project Manager'
            ]
        ]
    ]
    1 => [
        'title' => 'Interests'
        'type' => 'checkboxes'
        'interests' => [
            0 => [
                'id' => '517e7129x5'
                'name' => 'Donating'
            ]
            1 => [
                'id' => '15128d2daf'
                'name' => 'Volunteering'
            ]
            2 => [
                'id' => 'f254817294'
                'name' => 'Events'
            ]
        ]
    ]
]
```

### craft.mailchimpSubscribe.getAudienceById([$id=''])

Queries the Mailchimp API for an audience, and returns the response object in its entirety. 
Please refer to the [API documentation for details](https://developer.mailchimp.com/documentation/mailchimp/reference/lists/). 

### craft.mailchimpSubscribe.getMemberByEmail($email [, $id = ''])

Queries the Mailchimp API for a member, and returns the response object in its entirety. 
Please refer to the [API documentation for details](https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/). 

### craft.mailchimpSubscribe.getMarketingPermissionsByEmail($email [, $id = ''])

Queries the Mailchimp API for a member, and returns the marketing permissions for that member in the
following structure:

```
[
    0 => stdClass#1
    (
        [marketing_permission_id] => '0e963d96x1'
        [text] => 'Weekly newsletter'
        [enabled] => true
    )
    1 => stdClass#2
    (
        [marketing_permission_id] => '42723a122x'
        [text] => 'Special offers'
        [enabled] => false
    )
    2 => stdClass#3
    (
        [marketing_permission_id] => '36cxbd1a8b'
        [text] => 'Special offers from partners'
        [enabled] => false
    )
]
```

You can also get this directly from the member, this method is just for convenience.

### craft.mailchimpSubscribe.getMemberTagsByEmail($email [, $id = ''])

Queries the Mailchimp API for a member's tags, and returns them in the
following structure:

```
[
    0 => stdClass#1
    (
        [id] => 32608
        [name] => 'One tag'
        [date_added] => '2019-06-24T18:35:07+00:00'
    )
    1 => stdClass#2
    (
        [id] => 32611
        [name] => 'Three tag'
        [date_added] => '2019-06-24T18:35:07+00:00'
    )
]
```

You can also get this directly from the member, this method is just for convenience.


---

## Service methods

You can access Mailchimp Subscribe's service methods from your own plugin or module, by doing something like this:

``` 
$msPlugin = Craft::$app->plugins->getPlugin('mailchimp-subscribe');

if ($msPlugin && $msPlugin instanceof \aelvan\mailchimpsubscribe\MailchimpSubscribe) {
    $msPlugin->mailchimpSubscribe->subscribe('my_email@domain.com', '2a34d0978q');
}
```

The available public methods are (_again, square brackets indicate parameters that are optional_):

### subscribe($email, $audienceId[, $opts=null]): SubscribeResponse 

Subscribes a member to an audience. The third, optional parameter, is an array of all the  possible 
parameters documented in the subscribe controller action above. The response is always an SubscribeResponse, 
as documented below. Example:

```
$response = $msPlugin->mailchimpSubscribe->subscribe('myemail@domain.com', '2a34d0978q', [
    'email_type' => 'html', 
    'language' => 'no',
    'merge_fields' => [
        'FNAME' => 'Lorem',
        'LNAME' => 'Ipsum'
    ]
]);
```

### unsubscribe($email, $audienceId): SubscribeResponse 

Unsubscribes a member from an audience. The response is always an SubscribeResponse, as documented below. Example:

``` 
$response = $msPlugin->mailchimpSubscribe->unsubscribe('myemail@domain.com', '2a34d0978q');
```

### delete($email, $audienceId[, $permanent = false]): SubscribeResponse 

Deletes a member from an audience. If the third parameter is `true`, a hard delete is performed, see explanation
above for the differences between unsubscribe, delete, and hard delete. The response is always an SubscribeResponse, 
as documented below. Example:

``` 
$response = $msPlugin->mailchimpSubscribe->delete('myemail@domain.com', '2a34d0978q', true);
```

### getMemberByEmail($email, $audienceId): Collection|null 

Gets information about a member from an audience. The response is `null` if an error occured (see logs/debug toolbar 
for error message), and a Collection object if the request was successful. The collection will have all the 
information about the member, as 
[documented in the Mailchimp API](https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/). 
Example:

``` 
$member = $msPlugin->mailchimpSubscribe->getMemberByEmail('myemail@domain.com', '2a34d0978q');
```

### getAudienceById($audienceId): Collection|null 

Gets information about an audience. The response is `null` if an error occured (see logs/debug toolbar 
for error message), and a Collection object if the request was successful. The collection will have all the 
information about the audience, as 
[documented in the Mailchimp API](https://developer.mailchimp.com/documentation/mailchimp/reference/lists/). 
Example:

``` 
$member = $msPlugin->mailchimpSubscribe->getAudienceById('2a34d0978q');
```

### getMarketingPermissionsByEmail($email, $audienceId): array|null 

Gets marketing permissions from a member. The response is `null` if an error occured (see logs/debug toolbar 
for error message), and an array if the request was successful. See the documentation for the corresponding 
template variable for an example of the returned data.

### getInterestGroups($audienceId): array|null 

Gets interests groups from an audience. The response is `null` if an error occured (see logs/debug toolbar 
for error message), and an array if the request was successful. See the documentation for the corresponding 
template variable for an example of the returned data.

### getMemberTagsByEmail($email, $audienceId): array|null 

Gets tags from a member. The response is `null` if an error occured (see logs/debug toolbar 
for error message), and an array if the request was successful. See the documentation for the corresponding 
template variable for an example of the returned data.


---

## Models

### SubscribeResponse

Returned by `subscribe`, `unsubscribe` and `delete` controller actions. Has the following properties:

**action**: Indicates which of the actions where used to trigger the response. If you have more than one type of 
form on a single template, this is how you differenciate which form was submitted.  
**success**: Boolean indicating if the request was successful or not.  
**errorCode**: If an error occured, the error code will be set. `1000` indicates an invalid email and `2000` an invalid API key or audienceId. For all other error codes, [refer to the Mailchimp API documentation](https://developer.mailchimp.com/documentation/mailchimp/guides/error-glossary/).  
**message**: If an error occured, an error message will be returned.  
**values**: The values that were submitted from the form.  
**response**: The complete Mailchimp response that the request resulted in.  

### MemberResponse

Returned by the `get-member-by-email` controller actions. Has the following properties:

**action**: Indicates which of the actions where used to trigger the response, will always be `'get-member'` for this response.  
**success**: Boolean indicating if the request was successful or not.  
**response**: The complete Mailchimp response that the request resulted in.  

### AudienceResponse

Returned by the `get-audience-by-id` controller actions. Has the following properties:

**action**: Indicates which of the actions where used to trigger the response, will always be `'get-audience'` for this response.  
**success**: Boolean indicating if the request was successful or not.  
**response**: The complete Mailchimp response that the request resulted in.  

---

Price, license and support
---
The plugin is released under the MIT license, meaning you can do what ever you want with it as long as you don't  blame me. **It's free**, which means there is absolutely no support included, but you might get it anyway. Just post an issue here on github if you have one, and I'll see what I can do. 

---

Changelog
---
See [CHANGELOG.md](https://raw.githubusercontent.com/aelvan/mailchimp-subscribe-craft/craft3/CHANGELOG.md).
