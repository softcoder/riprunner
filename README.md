riprunner
=========

A Firehall dispatching communication suite.

Description:

This application suite was designed by a volunteer fire fighter (whose full time job is software development) to enhance the experience of First Responders during an emergency 911 callout. The main goal of this application is to provide a completely free suite of applications which help fire fighters receive timely information and communicate activities with one another as incidents progress.

Key Features:
-------------
- Email polling to check for an emergency 911 callout (or page) received from your FOCC (Fire Operations Command Center)
- Pluggable support for SMS gateway providers to send SMS information to fire fighters. 
  Currently providers implemented include (all offer free acounts with limited SMS / month):
  - Twilio
  - Sendhub
  - EzTexting
  - TextBelt
- Self Installation
- User Account management
- Callout history with responding memebers
- Google Maps showing Distance from Firehall to Incident
- Ability for members to respond to callout, thus letting other members know who is responding
- Experimental Native Android App which interfaces to the web application (does not require SMS Gateway)

System Requirements:
--------------------
- An email account that recieves Callout information during a 911 page
- A webserver that can run PHP 5.x
- A MySQL database to install the Rip Runnder Schema
- A Registered Account on an SMS Gateway Provider (Twilio,Sendhub,EzTexting,TextBelt)
- A Google Maps API key
- Optional: If using the experimental Android app, you need a Google Apps Engine (GAE) Project # (see http://developer.android.com/google/gcm/gs.html)

Installation:
-------------
- Edit values in config-default.php to suite your environment.
- Rename config-default.php to config.php
- Upload the files in the php folder to a location on your webserver.
- Open the url: http://www.yourwebserver.com/uploadlocation/install.php
- If everything was done correctly you should see an install page offering to install one the firehall's 
  you configured in config.php (we support more than 1 firehall if desired). Select the firehall and click install.
- If successful the installation will display the admin user's password. Click the link to login using it.
- Add firehall members to the user accounts page. Users given admin access can modify user accounts.
- You will need something that will trigger the email trigger checker. If your server offers a 'cron' or 
  scheduler process, configure it to visit http://www.yourwebserver.com/uploadlocation/email_trigger_check.php
  every minute. If your server does not have cron or limits the frequency, you can use Google App Engine's 
  cron service to call your email trigger every minute. (see files in php/googleae folder as a reference)
- Send a test email to the trigger email address in order to see if you get notified of a callout.
