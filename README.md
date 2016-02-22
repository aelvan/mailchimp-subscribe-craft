Introduction
---
MailChimp Subscribe for [Craft](http://craftcms.com/) is a very simple plugin for subscribing to a MailChimp newsletter list.  It can also check if a user is subscribed to a list.


Installation
---
1. Download and extract the contents of the zip. Put the `mailchimpsubscribe` folder to your Craft plugin folder.   
2. Enable the MailChimp Subscribe plugin in Craft (Settings > Plugins).  
3. Configure the plugin either in the plugin settings page in the control panel, or configure it via the general config file (see "Configuration" below).  
4. Add a form for signing up to your templates (see "Example Usage" below).  
5. Eat a banana.


Configuration
---
To use the plugin you need to create an API key from the MailChimp control panel, and create a list (or use one that you already have). 

You can configure MailChimp Subscribe either through the plugins settings in the control panel, or 
by adding the settings to the general config file (usually found in /craft/config/general.php).
Configuring it in the settings file is more flexible, since you can set up the config file to have
different settings depending on the environment.


### Example

    'mcsubApikey' => 'xxxxxxxxxxxxxxxxxxxxx-us2',
    'mcsubListId' => '2fd6ec09cf',
    'mcsubDoubleOptIn => false

If you have multiple lists you want users to subscribe to, each form can have a hidden field with a name of "lid" and the "value" as your list id. The plugin will use this list id on form submit. 

### Example
	<input type="hidden" name="lid" value="2fd6ec09cf">

If you want to subscribe to several lists from the same form, you can send in several list id's as a piped list.

### Example
	<input type="hidden" name="lid" value="2fd6ec09cf|5fe66521c0">


Example Usage
---
The following example shows the plugin in use:

      <form class="newsletter-form" action="" method="POST">
        <input type="hidden" name="action" value="mailchimpSubscribe/list/Subscribe">
        <input type="hidden" name="redirect" value="newsletter/receipt">
        
        {% if mailchimpSubscribe is defined %}
          {% if (not mailchimpSubscribe.success) and (mailchimpSubscribe.errorCode!='1000') %}
            <p>An error occured. Please try again.</p>
          {% endif %}
        {% endif %}
        
        <div class="field-line">
          <label>First name:</label>
          <input type="text" name="mcvars[FNAME]" value="{% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) %}{{ mailchimpSubscribe.values.vars.FNAME }}{% endif %}"/>
        </div>

        <div class="field-line">
          <label>Last name:</label>
          <input type="text" name="mcvars[LNAME]" value="{% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) %}{{ mailchimpSubscribe.values.vars.LNAME }}{% endif %}"/>
        </div>

        <div class="field-line">
          <label{% if (mailchimpSubscribe is defined) and (mailchimpSubscribe.errorCode=='1000') %} class="error"{% endif %}>Email:</label>
          <input type="text" name="email" value="{% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) %}{{ mailchimpSubscribe.values.email }}{% endif %}"/>
        </div>
        
        <input type="submit" name="" value="Subscribe"/>
      </form>

This code assumes that you have a template path newsletter/receipt which the user is redirected to upon 
successfully signing up to MailChimp. If you want to display the receipt message inside the same template, 
you just obmit the redirect parameter: 

      <form class="newsletter-form" action="" method="POST">
        <input type="hidden" name="action" value="mailchimpSubscribe/list/Subscribe">
        
        {% if mailchimpSubscribe is defined %}
          {% if (not mailchimpSubscribe.success) and (mailchimpSubscribe.errorCode!='1000') %}
            <p>An error occured. Please try again.</p>
          {% endif %}
          
          {% if mailchimpSubscribe.success %}
            <p>Thank you for signing up!</p>
          {% endif %}
        {% endif %}
        
        <div class="field-line">
          <label>First name:</label>
          <input type="text" name="mcvars[FNAME]" value="{% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) %}{{ mailchimpSubscribe.values.vars.FNAME }}{% endif %}"/>
        </div>

        <div class="field-line">
          <label>Last name:</label>
          <input type="text" name="mcvars[LNAME]" value="{% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) %}{{ mailchimpSubscribe.values.vars.LNAME }}{% endif %}"/>
        </div>

        <div class="field-line">
          <label{% if (mailchimpSubscribe is defined) and (mailchimpSubscribe.errorCode=='1000') %} class="error"{% endif %}>Email:</label>
          <input type="text" name="email" value="{% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) %}{{ mailchimpSubscribe.values.email }}{% endif %}"/>
        </div>
        
        <input type="submit" name="" value="Subscribe"/>
      </form>

Any other list variables you have configured in MailChimp can be added with formfields with name values like mcvars[YOURMCVAR].

Groups can be added by giving inputs a `name` attribute with the format `mcvars[group][X][Y]`, where `X` is the group's ID and `Y` is the name of a group's option. The easiest way to find your group's ID is via "Signup Forms > Embedded forms", and looking at the generated source code. [This](http://kb.mailchimp.com/lists/signup-forms/manage-groups-in-advanced-forms) page explains this process a little further. 

For example, if we have the group "Interests" with the values "Branding" and "Social Media", our form would like this:

     <label for="checkbox-1"><input type="checkbox" id="checkbox-1" name="mcvars[group][4329]['Branding']" value="Branding" {% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) and (mailchimpSubscribe.values.vars.group[4329]['Branding'] is defined) %}checked{% endif %}>Branding</label>
     <label for="checkbox-2"><input type="checkbox" id="checkbox-2" name="mcvars[group][4329]['Social Media']" value="Social Media" {% if (mailchimpSubscribe is defined) and (not mailchimpSubscribe.success) and (mailchimpSubscribe.values.vars.group[4329]['Social Media'] is defined) %}checked{% endif %}>Social Media</label>

The mailchimpSubscribe array
---
When the plugin returns to the origin template, either if an error occured or successfully posting without a redirect, it will return 
a mailchimpSubscribe array to the template. It contains the following variables:
 
**mailchimpSubscribe['success'] (Boolean):** True or false, depending on if the Subscribe was completed successfully or not. 

**mailchimpSubscribe['errorCode'] (Number):** If an error occured, an error code will also be supplied. See below for a list. 

**mailchimpSubscribe['message'] (String):** A message describing the error. This probably shouldn't be displayed to end users, you should display your own depending on error code. 

**mailchimpSubscribe['values'] (Array):** A structure containing the values that were submitted. For instance mailchimpSubscribe.values.email and mailchimpSubscribe.values.vars.FNAME.

**If you submit multiple list ids are submitted, the mailchimpSubscribe array will contain a listResults array containing the results for each list. If an error occured for one of the lists, the base array will contain the error.**

Checking if an email is already on a list
---
Sometimes you might want to know if a user is already on an email list - for example during a cart checkout.  It's nice not to bother your existing customers with repeated requests to subscribe to your mailing list, so if this check shows they're already subscribed, you can hide your subscribe form.

Here's an example that should get you started implementing such behaviour:

      <p>Check if a user is on our mailing list</p>

      <form method="POST" id="testMCOnList">
          {{ getCsrfInput() }}
          <input type="hidden" name="action" value="mailchimpSubscribe/list/CheckIfSubscribed">

          Enter email to check: <input type="text" id="email" size="40" name="email" >

          <input type="submit" class="btn" value="Check if subscribed">
      </form>

      <h3> Results: </h3>
      <span id="results"></span>

And some jquery to do the actual check:

    $('#testMCOnList').on('submit', function(e) {

        e.preventDefault();

        $.ajax({
              type: 'POST',
              url: '',
              data: $(this).serialize(),
              success: function( response ) {
                if(response.success){
                    $('#results').html("On List");
                     //hide your form here
                     //Also, response.vars.subscriberInfo will contain a bunch of info about the user should you want it
                }
                else{
                    $('#results').html("Not On List");
                    //display your subscribe form here
                }
              },
        });

    });


Error codes
---
**1000:** Missing or invalid email.   
**2000:** Missing API key or List ID. 

Any other codes are API errors, and the same code that the MailChimp API returned. [Refer to MailChimp's documentation](http://developer.mailchimp.com/documentation/mailchimp/guides/error-glossary/).


Ajax submitting
---
If the form is submitted with Ajax, the plugin will return a JSON object with the same keys as the template object described above. Big up to [Jake Chapman](https://github.com/imjakechapman) for implementing this. :)

Example:

      $('form').on("submit", function(event) {
        event.preventDefault();
      
        $.post('/', $(this).serialize())
        .done( function(data) {
      
          if (!data.success)
          {
            // there was an error, do something with data
            alert(data.message);
          }
          else
          {
            // Success
            alert("WEEEEEEEEEE");
          }
      
        });
      });



Changelog
---
### Version 1.0.1
 - Fixed missing vendor library.
 - Fixed bug where not supplying any merge vars would result in an error.
 - Fixed error message.

### Version 1.0.0
 - Refactored alot of code!
 - The plugin is now using the MailChimp API 3.0.
 - The plugin now has a service layer that other plugins can tap into.
 - Subscribe results from multiple lists are now returned in a separate listResults array in the mailchimpSubscribe variable.
 - Added Craft 2.5 plugin stuff.

### Version 0.6
 - Added new action CheckIsSubscribed to check if an email is already on list [Jeremy Daalder](https://github.com/bossanova808)

### Version 0.5
 - Added support for disabling double opt-in (particularly useful with Ajax submission).

### Version 0.4
 - Added support for multiple list id's.
 
### Version 0.3
 - Mailchimp Subscribe now returns JSON if form was submitted with Ajax (pull request from [Jake Chapman](https://github.com/imjakechapman)). 
 
### Version 0.2
 - Fixed noob error, forgot to allow anonymous access to controller.
 
### Version 0.1
 - Initial Public Release

