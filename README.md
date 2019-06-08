[![Build Status](https://travis-ci.org/softcoder/riprunner.svg?branch=master)](https://travis-ci.org/softcoder/riprunner)

To see detailed unit test and code coverage stats visit: https://travis-ci.org/softcoder/riprunner

Rip Runner
=========

<img src="/files/riprunner-small.png?raw=true" align="left" >

A Firehall dispatching communication suite. 
Current Version: 1.0.0

Description:

This application suite was designed by volunteer fire fighters to enhance the experience of First Responders during an emergency 911 callout. The main goal of this application is to provide a free suite of applications which can help fire fighters receive timely information and communicate activities with one another as incidents progress. This software is currently in use by some firehalls in the Prince George, BC Canada, Regional District. For contact information see the contact section at the bottom of this page.

Key Features:
-------------
- Real-time Email trigger (using Google App Engine) or email polling for 911 callouts received from your 
Fire Operations Command Center. Easily adaptable to other callout trigger mechanisms (suc has Web, Rest API, SMS, etc).
- Pluggable support for SMS gateway providers to send SMS information to fire fighters. 
  Current SMS providers implemented include:
  - Twilio (twilio.com - paid account charges approx $0.0075 per SMS -> https://www.twilio.com/sms/pricing)
  - Plivo (plivo.com - paid account charges approx $0.0035 per SMS -> https://www.plivo.com/pricing/CA/#!sms)
  - Sendhub (sendhub.com)
  - EzTexting (eztexting.com)
  - TextBelt (textbelt.com -> a free service but not as reliable and not available everywhere)
- Ability for members to indicate a response to callouts allowing other members to know who is responding
- 'SMS command mode' allows SMS users to respond to calls (useful if radios don't work or data is unreliable)
- Google Maps showing Distance from Firehall to Incident
- Support for Streaming audio of radio dispatch communications (using open source azuracast or any other standard streaming audio platform)
- Self Installation available (see videos below)
- User Account management (LDAP support optional)
- Callout history with responding members
- Google charts shows statistical view of data as well as monthly and annual member participation.
- Customizable user interface using twig templates (http://twig.sensiolabs.org/)
- Experimental Android App which integrates with the web application.
- Great backup system during power outages at your firehall (if installed offsite / cloud), still get callouts via cell phone when radio backup battery system fails or has poor coverage.

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

Technology:
-------------------------
Rip Runner was written using PHP for its backend server operations and html5 + javascript and angular 6+ for the frontend.
The design of this application allows for the use of any database backend supported by PHP's PDO layer but currently
MySQL is preferred (sql is abstracted into a file for each DB type). Currently most of the user interface is rendered using a server side framework called 'twig' but work has started to replace that using angular 6. Communications options include email, SMS (via a provider such as Twilio) and Google Cloud Messaging / Web Push for the Android client. Numerous google services are used like google maps which require a free API Key. The selection of PHP and javascript were made to allow this application to run on most free (or more offordable) hosting providers. This project makes use of test automation and Continuous Intregration via travis CI.

Why is Rip Runner a good choice for me?:
--------------------
- It's written by firefighters for firefighters.
- It's free!
- It's Open Source meaning anyone with programming skills in PHP can easily make changes to suit your needs
- It's very flexible. You are able to change many aspects of the behaviour and display of this application. You can select from various backend database engines (Mysql, MS SQL, Postgres, Oracle, etc). You can customize the user interface by making small overrides via the 'twig' framework or completely replace the user interface with your own. You can easily add on trigger mechanisms, support additional SMS providers all with relative ease.
- It's secure. We have adopted industry 'best practises' to ensure the security of this application. Security experts have evaluated the software to look for weaknesses and this continual process is ongoing.
- Its (mostly) clean code. The source code comes with many unit tests to ensure that ongoing changes to the source code do not break existing functionalty. (uses PHPUnit and dbUnit)
- Lastly its fast. We cache information where it makes sense to produce a scalable user experience, and conform to PHP 'best practises' for optimimum performance.

System Requirements:
--------------------
- If you want us to host for you on our servers, we take care of configuration, maintenance and support, contact us for more info.
- An email account that recieves Callout information during a 911 page (other trigger mechanisms can be easily supported, please contact us using the details at the bottom of this page)
- A Google App Engine account with one of our GAE apps published OR a service that periodically triggers the email polling (like cron) if your dispatch system is based off of emails. One free option (included in the source tree) is to use a google app engine (GAE) account to do the polling for you (see the googleae folder contents)
- A webserver that can run PHP 7.x (such as Apache, IIS or NGinx)
- A MySQL (or other PDO compatible) database to install the Rip Runner Schema and store the data (see the list supported here: http://php.net/manual/en/pdo.drivers.php)
- A Registered Account on an SMS Gateway Provider (Twilio (recommended),Sendhub,EzTexting,TextBelt)
- A Google Maps API key: https://developers.google.com/maps/documentation/javascript/tutorial#api_key (one key for server applications and one for android apps)
- Optional: If using the experimental Android app, you need a Google Apps Engine (GAE) Project Number (see http://developer.android.com/google/gcm/gs.html) and Browser API Key.

Screenshots:
------------

Call out example (SMS sent to responders with a link to this page):

![Callout Example](/screenshots/riprunner-callou1.png?raw=true "Callout Example")

System administration:

![Login](/screenshots/riprunner-admin1.png?raw=true "Login") ![Main Menu](/screenshots/riprunner-admin2.png?raw=true "Main Menu")

![Charts Menu](/screenshots/riprunner-charts1.png?raw=true "Charts Menu")

Android App:

<table>
<tr>
<td>
Login Screen:
<a href="/screenshots/android-login.png?raw=true"><img src="/screenshots/android-login.png?raw=true" align="left" height="713" width="401" ></a>
</td>
<td>
Options:
<a href="/screenshots/android-settings.png?raw=true"><img src="/screenshots/android-settings.png?raw=true" align="left" height="713" width="401" ></a>
</td>
</tr>
<tr>
<td>
Main Screen:
<a href="/screenshots/android-main.png?raw=true"><img src="/screenshots/android-main.png?raw=true" align="left" height="713" width="401" ></a>
</td>
<td>
Live Callout Screen:
<a href="/screenshots/android-callout.png?raw=true"><img src="/screenshots/android-callout.png?raw=true" align="left" height="713" width="401" ></a>
</td>
</tr>
</table>

Installation:
-------------
Getting started video - basic installation on Windows (click image below):

[![Getting Started install](http://img.youtube.com/vi/ZyUfvYsW39Q/0.jpg)](https://youtu.be/ZyUfvYsW39Q)

Getting started video - basic installation on Linux (click image below):

[![Ubuntu Host quick install](http://img.youtube.com/vi/PkREVKmyQzA/0.jpg)](https://youtu.be/PkREVKmyQzA)

[![Linux Host install](http://img.youtube.com/vi/ZDhPJ7qIXDc/0.jpg)](https://youtu.be/ZDhPJ7qIXDc)

General installation steps:
---------------------------
- Download the application either using git commands (for those who know how to use git) or download the master archive here: https://github.com/softcoder/riprunner/archive/master.zip and extract to a folder on your local PC.
- Edit the values in [config-default.php](php/config-default.php) to suit your environment. (see Configuration section below)
- Rename the file config-default.php to config.php
- Upload the files in the php folder to a location on your webserver (this will be the root folder for riprunner).
- If using IIS (Apache users skip to 'Open the url') you should import the file [IIS_Import.htaccess](php/IIS_Import.htaccess) following these steps:
-  1. Start IIS Manager. 
-  2. On the left, in the Connections pane, select 'Sites' then 'Default Web Site'.
-  3. Create a new virtual folder pointing to the root php folder of rip runner (example alias svvfd)
-  4. With the alias selected (example svvfd) click on the right, in Features View, IIS, click URL Rewrite.
-  5. On the right, in the Actions pane, click 'Open Feature'.
-  6. On the right, in the Actions pane, click 'Import Rules'.
-  7. Select the file IIS_import.htaccess using the ... elipses and import, then click apply.
- extract the contents of the appropriate third party archive into your rip runner root installation folder:
   ie: vendor-php-5.6.zip or vendor-php-X.X.zip (check for other supported versions where filename exists in repo)
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

Linux installation notes:
-------------------------
1. Install LAMP (Linux, Apache, MySQL and PHP) apps
2. Install these dependencies:
- sudo a2enmod rewrite
- sudo apt install php7.1-xml
- sudo apt install php7.1-mysql 
- sudo apt install php7.1-imap
- sudo apt install php7.1-mcrypt
- sudo apt install php7.1-curl
- sudo apt install php7.1-ldap
- sudo apt install php7.1-sqlite3
3. Restart Apache: sudo systemctl restart apache2
4. Configure web virtual host (if desired)
5. Create a MySql user for rip runner (with DBA access)
6. Copy Rip Runner (PHP folder) to the appropriate folder on the target host

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

FAQ:
----
- For apache virtual host support you need to enable allow override:
sudo gedit /etc/apache2/apache2.conf

and enable .htaccess by changing

AllowOverride None
to
AllowOverride All

sudo systemctl restart apache2

- If after apache restart if you get errord in error.log: 
cat /var/log/apache2/error.log

Showing:
Invalid command 'RewriteEngine', perhaps misspelled or defined by a module not included in the server configuration

fix it by:

sudo a2enmod rewrite && sudo systemctl restart apache2

- When calling install.php if you get:

Fatal error: Uncaught Error: Call to undefined function simplexml_load_file() in ...

then you must install extensions in your php config:

sudo apt install php7.1-xml
sudo systemctl restart apache2

- If after calling install.php you get: 

Warning: fopen(/home/softcoder/www/svvfd/public_html/rr/riprunner.log): failed to open stream: Permission denied in ...

then you must change access permission to riprunner.log to:

-rw-rw-rw-  1 softcoder softcoder   120 Apr 13 10:15 riprunner.log

- if after calling install.php you get:

Error detected, message : could not find driver, Code : 0

then you must check riprunner.log if you see:

2017-04-13T10:15:38-07:00 ERROR DB Connect for: dsn [mysql:host=localhost;] user [myvfd] error [could not find driver] 

then you must:

sudo apt-get install php7.1-mysql 
sudo systemctl restart apache2

- If after calling install.php you get:

Error detected, message : SQLSTATE[HY000] [1045] Access denied for user 'myvfd'@'localhost' (using password: YES), Code : 1045

You need to make sure the mysql user specified exists and has access to connect to the server.

- If after calling install.php you get:

Fatal error: Uncaught PDOException: SQLSTATE[42000]: Syntax error or access violation: 1044 Access denied for user 'myvfd'@'%' to database 'myvfd' in ...

ensure mysql user has dba access.

- If after calling install.php you get:

Fatal error: Uncaught PDOException: SQLSTATE[HY000] [1049] Unknown database 'myvfd' in

create the database first, or goto install page: install.php

- Make sure your rip runner folder has grant execution access to scripts:

sudo chmod 777 -R ~/www/svvfd/public_html/

- If you get the error:

HTTP Error 404.3 - Not Found
The page you are requesting cannot be served because of the extension configuration. If the page is a script, add a handler. If the file should be downloaded, add a MIME map.

- Make sure you have installed php (7.1 x64) for iis using web platform installer

- If you get the error:

Call to undefined function finfo_open()

- You need to add the following line your php.ini then to activate it: (C:\Program Files\IIS Express\PHP\v7.1\php.ini)

extension=php_fileinfo.dll

- If you get the error:

Fatal error: Uncaught Error: Call to undefined function riprunner\curl_init()

then you must:

sudo apt-get install php7.1-curl

- If you get the following error in the logs and no sms message is sent:

Curl error: SSL certificate problem: self signed certificate in certificate chain

- you must download: http://curl.haxx.se/ca/cacert.pem

edit php.ini

[curl]
curl.cainfo=c:/cert/cacert.pem

- If some of your users mobile devices do not show a clickable URL in the sms callout:

then your newer website domain name may not be recognized. For example some phones don't 
understand the following link because it uses a newer .solutions format:

https://vsoft.solutions/

- you must find a host that you have access to, which has a well known format example:

https://vejvoda.com/

create a folder on that host for example a folder named 'rr' and create a file name '.htaccess' 
in the 'rr' folder with the following content (notice rr matches the folder name you created, 
and the part to the right tells the webserver where to forward to, $1 copies url parameters):

RedirectMatch 301 /rr(.*) https://svvfd.vsoft.solutions$1

Next create a custom sms twig file in the root folder where config.php exists, inside a new folder 
you wil lcreated named:

views-custom

and name this file:

sms-callout-msg-custom.twig.html

with the following contents:

{% extends "sms-callout-msg.twig.html" %}

{% block sms_url_webroot %}
https://vejvoda.com/rr/
{% endblock %}

This will use the website: https://vejvoda.com/rr/ as a proxy to forward requests to: https://svvfd.vsoft.solutions
which all phones would recognize because it uses the well known .com format

Development:
--------------
Rip Runner uses composer for dependency management (the existing third-party folder is now deprecated and will eventually be deleted). Currently php 7.x is supported and our continuous integration system (travis) runs automated tests on those versions. If you want to contribute to rip runner as a developer checkout the repo from github and from the php folder of the repo on your local system run:

composer install

This will download all runtime and automated tests dependencies. If compser completed successfully you should be able to run the automated tests by running this command from the php folder:

phpunit

The travis CI automation results can be found here:

https://travis-ci.org/softcoder/riprunner

Experimental Work:
------------------
We have begun porting the user interface to Angular (v6+). Currently this UI is partially ported from the
legacy twig UI, in order to build and deploy to your server:

- Install Node.js® and npm (https://nodejs.org/en/download/) if they are not already on your machine.
- Install the Angular CLI globally, open a console prompt: 

npm install -g @angular/cli

- Install project dependencies (the angular folder below is the folder you get from the git source tree):

cd angular
npm install

- Compile and Build the angular project:

ng build --base-href=/~softcoder/svvfd1/php/ngui/ --output-path=../php/ngui/ --aot

Notice above the base-href which is the document root path on your webserver where rip runner is installed (same folder where config.php exists). Also notice the compiled javascript project will be placed into the rip runner php/ngui folder.

- Copy the ngui folder to your web server's Root Rip Runner folder (same folder as config.php)
- visit the SPA (single page application) login page and try it out:
  
  /~softcoder/svvfd1/php/ngui/index.html

If you installed rip runner in the root folder of s subdomain for example http://svvfd.yourhost.com, you would run the script as follows:

ng build --base-href=/ngui/ --output-path=../php/ngui/ --aot

then copy the ngui folder to the root folder on myhost.com.

Contributions:
--------------
Special thanks to all who have contributed to the success of this project. We accept patches and ideas from others and priortize based on time constraints and compatibility with our future direction.

Contributors currently include:
- The Salmon Valley Fire Hall for all the great testing and feedback
- Dennis Lloyd (Officer at the Shell Glen Fire Hall) for peer review, and many contributions, without which
  we would have many more defecs and have a much less pleasing user interface.

Contact Info:
--------------
- Email: mark@vsoft.solutions
- Slack Channel: https://vsoftsolutions.slack.com/messages/CJEQ6H5J6/
- Join our IRC channel `#softhaus` on FreeNode.
- Webchat IRC channel: http://webchat.freenode.net/?channels=softhaus

----
Free Firehall Software Volunteer SMS Android Google App Engine Communications
