Summary: 

Basic LTI provider plugin for Vanilla2  (http://vanillaforums.org/)

Version Compatibility: 

The plugin has developed and tested with version 2.0.18.1


Subversion url:

https://learningapps.svn.sourceforge.net/svnroot/learningapps/vanilla2/branches/simple_level_forum


Full Description:

This plugin adds to Vanilla the capability of doing a Single Sign On from another platform/tool which is BLTI compliant.
That means that it has a BLTI consumer to prepare the redirection to the Vanilla forums. 

The plugin is, therefore, a BLTI provider, that permits to extract the necessary information from the signed BLTI request to do the SSO .

The validation process of the signature is done with the php Oauth library.

The plugin is based on the UOC code.

Requirements

In order to make it work it is necessary to activate the "pretty URLs" issue based on the apache url rewriting
In Apache it requires .htaccess to be working on your server. 
In Vanilla, edit your config.php file and set $Configuration['Garden']['RewriteUrls'] = TRUE;


Installation and Configuration

a) plugin installation 

   Just copy the BLTIConnect folder to the plugins folder of Vanilla
   Enter the Vanilla plugin section and enable it.

b) Oauth configuration file
 
   So that the BLTI provider succeeds in the signature validation is necessary to configure the key 
   and the secret properties used by Oauth in the IMSBasicLTI/configuration/authorizedConsumersKey.cfg
   
   p.e: to define the consumer key "external" yo have to define 
        consumer_key.external.enabled=1 
        consumer_key.external.secret=pwd_12345


c) Provider url configuration
   Once the plugin is enabled, the endpoint url to access to the provider will be:
   
   http://<server>/<vanilla app context>/index.php?p=/entry/signin
   
   changing <server> and <vanilla app context> to your environment
 
   Its behaviour can be customized thanks to the custom_gotocategory flag.
   If you add custom_gotocategory=1 to the blti request, you will go only to the category and not to the discussion
