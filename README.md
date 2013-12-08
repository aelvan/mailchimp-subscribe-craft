Introduction
---
MailChimp Subscribe for [Craft](http://buildwithcraft.com/) is a very simple plugin for subscribing to a MailChimp newsletter list. 
The only thing it does is use the API to sign up, nothing more, nothing less. :) 


Installation
---
1. Download and extract the contents of the zip. Copy the /mailchimpsubscribe folder to your Craft plugin folder. 
2. Enable the MailChimp Subscribe plugin in Craft (Settings > Plugins).
3. Click on the plugin name to configure the plugin settings, or configure it via the general config file (see "Configuration" below).
4. Add a form for signing up to your templates (see "Example Usage" below). 
5. Eat a banana.


Configuration
---
To use the plugin you need to create an API key from the MailChimp control panel, and create a list (or use one you already have). 

You can configure MailChimp Subscribe either through the plugins settings in the control panel, or 
by adding the settings to the general config file (usually found in /craft/config/general.php). Configuring it in
the settings file is more flexible, since you can set up the config file to have different settings depending on the 
environment.


####Example

    'mcsubApikey' => 'xxxxxxxxxxxxxxxxxxxxx-us2',
    'mcsubListId' => '2fd6ec09cf',


Example Usage
---
The following example shows the plugin in use:

      <form class="newsletter-form" action="" method="POST">
        <input type="hidden" name="action" value="mailchimpsubscribe/list/Subscribe">
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


The mailchimpSubscribe object
---
When the plugin returns to the origin template, either if an error occured or successfully posting without a redirect, it will return 
a mailchimpSubscribe object to the template. It contains the following variables:
 
**mailchimpSubscribe.success (Boolean):** True or false, depending on if the Subscribe was completed successfully or not. 

**mailchimpSubscribe.errorCode (Number):** If an error occured, an error code will also be supplied. See below for a list. 

**mailchimpSubscribe.message (String):** A message describing the error. This probably shouldn't be displayed to end users, you should display your own depending on error code. 

**mailchimpSubscribe.values (Object):** A structure containing the values that were submitted. For instance mailchimpSubscribe.values.email and mailchimpSubscribe.values.vars.FNAME.


Error codes
---
**1000:** Missing or invalid email.   
**2000:** Missing API key or List ID. 

Any other codes are API errors, and the same code that the MailChimp API returned. [Refer to MailChimp's documentation](http://apidocs.mailchimp.com/api/1.3/exceptions.field.php).


Changelog
---
### Version 0.2
 - Fixed noob error, forgot to allow anonymous access to controller.
 
### Version 0.1
 - Initial Public Release

