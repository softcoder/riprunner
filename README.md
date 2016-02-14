Rip Runner - Shell Glen Fire / Rescue
=========

A Firehall dispatching communication suite modifed and customized from softcoder/github
https://github.com/softcoder/riprunner
Special thanks to Mark Vejvoda, whom without, this fork would not exist.

I have made many additional customizations to this project.


Rip Runner
=========

A Firehall dispatching communication suite.

#####Current Version: 1.0.0

Description:

This application suite was designed by volunteer fire fighters to enhance the experience of First Responders during an emergency 911 callout. The main goal of this application is to provide a completely free suite of applications which help fire fighters receive timely information and communicate activities with one another as incidents progress. This software is currently in use by some firehalls in the Prince George, BC Canada, Regional District. For contact information see the contact section at the bottom of this page.

Key Features:
-------------
- Real-time Email trigger (using Google App Engine) or polling to check for an emergency 911 callout (or page) received from your FOCC (Fire Operations Command Center). Easily adaptable to other callout trigger mechanisms.
- Pluggable support for SMS gateway providers to send SMS information to fire fighters. 
  Current SMS providers implemented include (all offer free acounts with limited SMS / month):
  - Twilio (twilio.com - paid account charges approx $0.0075 per SMS -> https://www.twilio.com/sms/pricing)
  - Plivo (plivo.com - paid account charges approx $0.0035 per SMS -> https://www.plivo.com/pricing/CA/#!sms)
  - Sendhub (sendhub.com)
  - EzTexting (eztexting.com)
  - TextBelt (textbelt.com -> a free service but not as reliable and not available everywhere)
- Self Installation
- User Account management (LDAP support optional)
- Callout history with responding members
- 'SMS command mode' allows SMS only users to respond to call
- Google Maps showing Distance from Firehall to Incident
- Google charts shows statistical view of data.
- Ability for members to indicate a response to callouts allowing other members to know who is responding
- Customizable user interface using twig templates (http://twig.sensiolabs.org/)
- Experimental Android App which interfaces to the web application (does not require SMS Gateway, uses free GCM)
- Makes a great backup system in case of power outages at your firehall, still get callouts via cell phone when radio backup battery system fails.

Key Android App Features:
-------------------------
- Integrates with the website user accounts for authentication.
- Uses the free GCM API (Google Cloud Messaging) for notifications saving you SMS charges.
- Plays a pager tone during a callout
- Acquires your GPS co-ordinates to display a map from your location to the firehall which other members can view during a callout.
- Displays a map from the Firehall to the Incident scene
- Allows responders to indicate that they are responding to the call with the click of 1 button. (other responders are notified of each responder)
- Allows responders to indicate that the call is coempleted or cancelled with the click of 1 button. (other responders are notified)
- If you login during a live callout, you will receive the pager tones and live call information.

Overview:
-------------------------

![Overview](/screenshots/riprunner-diagram.png?raw=true "Overview")

The diagram above shows all of the possible features enabled and the communication paths involved. This may be simplified depending on your needs and how you configure Rip Runner. You may or may not require the following:
- An archaic FOCC that uses a CAD system that only notifies via email requires one of two methods to trigger the callout process. If your FOCC can directly call the rip runner website calling a rip runner URL containing callout details then the trigger system can be greatly simplified.
- A Cron or polling mechaism to check for archaic email notifications from FOCC (we reccomend using the google app engine email gateway solution).
- An SMS provider to integrate text messages via cell carriers (we recommend using twilio as the best service provider tested).
- An LDAP server to manage user accounts.
- Map services via google maps.
- Android communication using google app engine.

Why is Rip Runner a good choice for me?:
--------------------
- It's written by firefighters for firefighters.
- It's free!
- It's Open Source meaning anyone with programming skills in PHP can easily make changes to suit your needs
- It's very flexible. You are able to change many aspects of the behaviour and display of this application. You can select from various backend database engines (Mysql, MS SQL, Postgres, Oracle, etc). You can customize the user interface by making small overrides via the 'twig' framework or completely repalce the user interface with your own. You can easily add on trigger mechanisms, support additional SMS providers all with relative ease.
- It's secure. We have adopted industry 'best practises' to ensure the security of this application. Security experts have evaluated the software to look for weaknesses and this continual process is ongoing.
- Its (mostly) clean code. The source code comes with many unit tests to ensure that ongoing changes to the source code do not break existing functionalty. (uses PHPUnit and dbUnit)
- Lastly its fast. We cache information where it makes sense to produce a scalable user experience, and conform to PHP 'best practises' for optimimum performance.

System Requirements:
--------------------
- An email account that recieves Callout information during a 911 page (other trigger mechanisms can be easily supported, please contact using the details at the bottom of this page)
- A Google App Engine account with one of our GAE apps published OR a service that periodically triggers the email polling (like cron) if your dispatch system is based off of emails. One free option (included in the source tree) is to use a google app engine (GAE) account to do the polling for you (see the googleae folder contents)
- A webserver that can run PHP 5.x (such as Apache, IIS or NGinx)
- A MySQL (or other PDO compatible) database to install the Rip Runner Schema and store the data (see the list supported here: http://php.net/manual/en/pdo.drivers.php)
- A Registered Account on an SMS Gateway Provider (Twilio (recommended),Sendhub,EzTexting,TextBelt)
- A Google Maps API key: https://developers.google.com/maps/documentation/javascript/tutorial#api_key (one key for server applications and one for android apps)
- Optional: If using the experimental Android app, you need a Google Apps Engine (GAE) Project Number (see http://developer.android.com/google/gcm/gs.html) and Browser API Key.

Screenshots:
------------

Call out example (SMS sent to responders with a link to this page):

![Callout Example](/screenshots/riprunner-callou1.png?raw=true "Callout Example")

System administration:

![Login](/screenshots/riprunner-admin1.png?raw=true "Login")

![Main Menu](/screenshots/riprunner-admin2.png?raw=true "Main Menu")

![Charts Menu](/screenshots/riprunner-charts1.png?raw=true "Charts Menu")

Android App:

Login Screen:

![Login](/screenshots/android-login.png?raw=true "Login")

Options:

![Options](/screenshots/android-settings.png?raw=true "Options")

Main Screen:

![Main](/screenshots/android-main.png?raw=true "Main")

Live Callout Screen:

![Callout](/screenshots/android-callout.png?raw=true "Callout")


Installation:
-------------
- Download the application either using git commands (for those who know how to use git) or download the master archive here: https://github.com/softcoder/riprunner/archive/master.zip and extract to a folder on your local PC.
- Edit the values in [config-default.php](php/config-default.php) to suit your environment. (see Configuration section below)
- Rename the file config-default.php to config.php
- Upload the files in the php folder to a location on your webserver (this will be the root folder for riprunner).
- If using IIS (Apache users skip to 'Open the url') you should import the file [IIS_Import.htaccess](php/IIS_Import.htaccess) following these steps:
-  1. Start IIS Manager. 
-  2. On the left, in the Connections pane, select Default Web Site.
-  3. On the right, in Features View, click URL Rewrite.
-  4. On the right, in the Actions pane, click Import Rules. 
-  5. Select the file IIS_import.htaccess using the ... elipses and import, then click apply.
- Open the url: http://www.yourwebserver.com/uploadlocation/install.php (substitute your root riprunner host://uploadpath/install.php)
- If everything was done correctly you should see an install page offering to install one the firehall's 
  you configured in config.php (we support more than 1 firehall if desired). Select the firehall and click install.
- If successful the installation will display the admin user's password. Click the link to login using it.
- Add firehall members to the user accounts page. Users given admin access can modify user accounts. You may also choose to use an LDAP server to manage accounts in which case you should specify LDAP values in config.php.
- You will need something that will trigger the email trigger checker. Please check the Readme in the googleae folder for details. If your server offers a 'cron' or scheduler process, configure it to visit http://www.yourwebserver.com/uploadlocation/email_trigger_check.php
  every minute. If your server does not have cron or limits the frequency, you can use Google App Engine's 
  cron service to call your email trigger every minute. (see files in [php/googleae](php/googleae) folder as a reference)
- Send a test email to the trigger email address in order to see if you get notified of a callout (if using a 'from' filter make sure you send the e from the 'from' address that you specified).
- To allow use of the Android app, either copy the prebuilt apk located in 
  android/RipRunnerApp/bin/RipRunnerApp.apk to apk/ or compile the Android app in Eclipse and copy to apk/
  This will allow users to select the Android app from the Mobile menu item for download and installation 
  on their mobile device.

Configuration:
--------------
The most important information that you require to configure is located in config.php. 
You must create this file (or rename [config-default.php](php/config-default.php) to config.php) and supply configuration values.
The following explains the main sections in config.php. The structures used in coinfig.php are
defined in [config_interfaces.php](php/config_interfaces.php) if you are interested to see their definitions.

 Config.php:
 -----------

	// ----------------------------------------------------------------------
	// Email Settings
	
	// Below is the email address that we expect to receive callouts from.
	// This can be a full email address as shown below  or just the domain
	// example: focc.mycity.ca (this would allow all email addresses from this domain)
  	define( 'DEFAULT_EMAIL_FROM_TRIGGER', 'donotreply@focc.mycity.ca');
	
	// Below we create an email account structure for our firehall.
	// See the class FireHallEmailAccount in config_interfaces.php
	// for details
	$LOCAL_DEBUG_EMAIL = new FireHallEmailAccount(
	    true, 
	    DEFAULT_EMAIL_FROM_TRIGGER,
	    '{pop.secureserver.net:995/pop3/ssl/novalidate-cert}INBOX',
	    'my-email-trigger@my-email-host.com',
	    'my-email-password',
	    true);
				
	// ----------------------------------------------------------------------
	// MySQL Database Settings
	
	// Below we create a MySQL structure for our firehall.
	// See the class FireHallMySQL in config_interfaces.php
	// for details
	$LOCAL_DEBUG_MYSQL = new FireHallMySQL(
	    'localhost',
	    'riprunner', 
	    'riprunner', 
	    'riprunner');

	// -----------------------------------------------------------------------
	// SMS Settings
	
	// Below is the URL if you are using SendHub to send SMS messages.
	// username=X - replace X with your sendhub Username
	// api_key=X  - replace X with your sendhub API Key
	define( 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL', 	'https://api.sendhub.com/v1/messages/?username=X&api_key=X');
	
	// Below is the URL if you are using TextBelt to send SMS messages.
	// Ensure that you use the correct url for your country
	define( 'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 	'http://textbelt.com/canada');
	
	// Below is the URL if you are using EzTexting to send SMS messages.
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL', 	'https://app.eztexting.com/sending/messages?format=xml');
	// Below is the EzTexting account username
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 	'X');
	// Below is the EzTexting account password
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD', 	'X');
	
	// Below is the URL if you are using Twilio to send SMS messages.
	// https://api.twilio.com/2010-04-01/Accounts/X/Messages.xml - replace X with your Twilio account name
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 	'https://api.twilio.com/2010-04-01/Accounts/X/Messages.xml');
	// Below is the Twilio account authentication token
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN', 	'X:X');
	// Below is the Twilio account From mobile phone #
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 		'+12505551212');

	// Below we create an SMS structure for our firehall.
	// See the class FireHallSMS in config_interfaces.php
	// for details
	$LOCAL_DEBUG_SMS = new FireHallSMS(
		true,
		SMS_GATEWAY_TWILIO, 
		'', 
		false, 
		true,
		DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL, 
		DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL,
		DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL,
		DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME,
		DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD, 
		DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL,
		DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN,
		DEFAULT_SMS_PROVIDER_TWILIO_FROM);

	// ----------------------------------------------------------------------
	// Mobile App Settings
	
	// Below is the Google Cloud Messaging API Key
	// This is the Google 'Key for browser applications' API key from your google project:
	// https://console.developers.google.com/project/<your proj name>/apiui/credential
	define( 'DEFAULT_GCM_API_KEY', 	'X');
	// Below is the Google Cloud Messaging Project Number (aka sender id)
	define( 'DEFAULT_GCM_PROJECTID','X');
	// The Google Project Id
	define( 'DEFAULT_GCM_APPLICATIONID','X');
	// The Google Service Account Name
	define( 'DEFAULT_GCM_SAM','applicationid@appspot.gserviceaccount.com');

	// Below we create a Mobile structure for our firehall.
	// See the class FireHallMobile in config_interfaces.php
	// for details
	$LOCAL_DEBUG_MOBILE = new FireHallMobile(
	    true, 
	    true,
	    true,
	    DEFAULT_GCM_SEND_URL,
	    DEFAULT_GCM_API_KEY,
	    DEFAULT_GCM_PROJECTID,
	    DEFAULT_GCM_APPLICATIONID,
	    DEFAULT_GCM_SAM);
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	
	// Below is the Google Maps API Key
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY', 						'X' );
	// A ; delimited list of original_city_name|new_city_name city names to swap for google maps 
	// This list changes city names fro mthe item on the left to that on the right and is only
	// used when drawing google maps. In the example below all callouts with the city name
	// SALMON VALLEY, will be changed into PRINCE GEORGE, when google maps are used
	define( 'DEFAULT_WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION', 	'SALMON VALLEY,|PRINCE GEORGE,;' );

	// Below we create a Website structure for our firehall.
	// See the class FireHallWebsite in config_interfaces.php
	// for details
	$LOCAL_DEBUG_WEBSITE = new FireHallWebsite(
	    'Local Test Fire Department',
	    '5155 Salmon Valley Road, Prince George, BC',
 	    54.0916667,
	    -122.6537361,
	    'http://yourwebsite.com/riprunner/',
	    DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY, 
	    $GOOGLE_MAP_CITY_LOOKUP);
	
	// ----------------------------------------------------------------------
	// LDAP Settings
	// These are the LDAP settings for sites that wish to have an LDAP server manage user accounts
	// See config_interfaces.php for more info about these fields
	$LOCAL_DEBUG_LDAP = new FireHall_LDAP(
			false,
			'ldap://myhost.example.com',
			null, null,
			'dc=example,dc=com',
			'ou=users,dc=example,dc=com',
			'(|(uid=${login})(cn=${login})(mail=${login}@\*))',
			'dn',
			'sn',
			'(&(objectClass=posixGroup)(cn=riprunner-users))',
			'(&(objectClass=posixGroup)(cn=riprunner-admin))',
			'(&(objectClass=posixGroup)(cn=riprunner-sms))',
			'memberuid',
			'mobile',
			'uidnumber',
			'uid');
	
	// ----------------------------------------------------------------------
	// Main Firehall Configuration Container Settings
	
	// Below we create a Firehall config structure for our firehall.
	// See the class FireHallConfig in config_interfaces.php
	// for details
	$LOCAL_DEBUG_FIREHALL = new FireHallConfig(	
	        true, 
		0,
		$LOCAL_DEBUG_MYSQL,
		$LOCAL_DEBUG_EMAIL,
		$LOCAL_DEBUG_SMS,
		$LOCAL_DEBUG_WEBSITE,
		$LOCAL_DEBUG_MOBILE,
		$LOCAL_DEBUG_LDAP);
	
	// Add as many firehalls to the array as you desire to support
	// This array is used through Rip Runner and lookups are done using the firehall id
	// to find the firehall configuration to use for a given request
	$FIREHALLS = array($LOCAL_DEBUG_FIREHALL);


SMS command mode:
--------------
Currently users of the Twilio and Plivo providers are able to offer users the ability to communicate using SMS (no data connection needed). To configure this option you must edit the following in your provider:

Twilio Account configuration:
- Under Numbers -> Twilio Numbers -> SMS and MMS: Select Configure with URL as follows:
       Request URL: http://www.yourwebserver.com/riprunner/plugins/sms-provider-hook/twilio-webhook.php
       using HTTP Post.

Plivo Application configuration:
- Under Edit Application: Select Message URL as follows:
       Request URL: http://www.yourwebserver.com/riprunner/plugins/sms-provider-hook/plivo-webhook.php
       using Message Method: POST.


To test your provider, send the following text message to your provider configured account phone # to get a list of available commands: ?

Special Notes:
--------------

Compiling the Android application in the Eclipse IDE requires you to install the ADT plugin (http://developer.android.com/tools/sdk/eclipse-adt.html) as well as setup the Google Play Services SDK (https://developer.android.com/google/play-services/setup.html#Setup) as this is a dependency in the riprunner android app.

Contributions:
--------------
Special thanks to all who have contributed to the success of this project. We accept patches and ideas from others and priortize based on time constraints and compatibility with our future direction.

Contributors currently include:
- The Salmon Valley Fire Hall for all the great testing and feedback
- Dennis Lloyd (Officer at the Shell Glen Fire Hall) for peer review, and many contributions, without which
  we would have many more defecs and have a much less pleasing user interface.

Contact Info:
--------------
- Email: mark_vejvoda@hotmail.com
- Join our IRC channel `#softhaus` on FreeNode.
- Webchat IRC channel: http://webchat.freenode.net/?channels=softhaus
