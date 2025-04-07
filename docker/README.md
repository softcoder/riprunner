riprunner is a docker image that include the Docker-Lamp baseimage (Ubuntu 24.04), along with a LAMP stack ([Apache][apache], [MySQL][mysql] and [PHP][php]) all in one handy package.

1. With Ubuntu **24.04** image on the `latest-2404`, riprunner is ready to test the Rip Runner communication suite.  

# To build a new docker image  
sudo docker build -t=softcoder/riprunner:latest -f ./docker/2204/Dockerfile .  

# To run the docker image (set environment variables to match your values) 
sudo docker run -p "80:80" -v ${PWD}/mysql:/var/lib/mysql softcoder/riprunner:latest

APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL "X:X"
APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_TWILIO_FROM "+xxxxxxxxxx"

APP_RIPRUNNER_CONFIG_DEFAULT_GCM_API_KEY "X"
APP_RIPRUNNER_CONFIG_DEFAULT_GCM_PROJECTID "X"
APP_RIPRUNNER_CONFIG_DEFAULT_GCM_APPLICATIONID "X"
APP_RIPRUNNER_CONFIG_DEFAULT_GCM_SAM "applicationid@appspot.gserviceaccount.com"

APP_RIPRUNNER_CONFIG_DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY "X"

APP_RIPRUNNER_CONFIG_DEFAULT_ALLOW_CALLOUT_UPDATES_AFTER_FINISHED true
APP_RIPRUNNER_CONFIG_MAP_REFRESH_TIMER "60"
APP_RIPRUNNER_CONFIG_DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD 48

APP_EMAIL_HostEnabled true
APP_EMAIL_FromTrigger "someemail@yourhost.com"
APP_EMAIL_ConnectionString ""
APP_EMAIL_UserName ""
APP_EMAIL_Password ""
APP_EMAIL_DeleteOnProcessed false
APP_EMAIL_EnableOutboundSMTP true
APP_EMAIL_OutboundHost "smtp.gmail.com"
APP_EMAIL_OutboundPort 587
APP_EMAIL_OutboundEncrypt "tls"
APP_EMAIL_OutboundAuth true
APP_EMAIL_OutboundUsername "X@gmail.com"
APP_EMAIL_OutboundPassword "XX"
APP_EMAIL_OutboundFromAddress "X@gmail.com"
APP_EMAIL_OutboundFromName "Rip Runner Mailer"

APP_DSN ""
APP_DB_USERNAME "riprunner"
APP_DB_PASSWORD "riprunner"
APP_DB "riprunner"

APP_SMS_GATEWAY_TYPE "TEXTBELT-LOCAL"
APP_SMS_CALLOUT_PROVIDER_TYPE "DEFAULT"
APP_SMS_TEXTBELT_LOCAL_FROM "2505551212"
APP_SMS_TEXTBELT_LOCAL_REGION "canada"
APP_SMS_SPECIAL_CONTACTS ""
APP_SMS_PROVIDER_PLIVO_BASE_URL ""
APP_SMS_PROVIDER_PLIVO_AUTH_ID ""
APP_SMS_PROVIDER_PLIVO_AUTH_TOKEN ""
APP_SMS_PROVIDER_PLIVO_FROM ""
APP_SMS_PROVIDER_TWILIO_BASE_URL ""
APP_SMS_PROVIDER_TWILIO_AUTH_TOKEN ""
APP_SMS_PROVIDER_TWILIO_FROM ""

APP_MOBILE_SIGNAL_ENABLED false
APP_MOBILE_TRACKING_ENABLED false
APP_MOBILE_GCM_ENABLED false
APP_MOBILE_GCM_API_KEY ""
APP_MOBILE_GCM_PROJECTID ""
APP_MOBILE_GCM_APPLICATIONID ""
APP_MOBILE_GCM_EMAIL_APPLICATIONID ""
APP_MOBILE_GCM_SAM ""
APP_MOBILE_FCM_SERVICES_JSON ""

APP_WEBSITE_FIREHALL_NAME "My Volunteer Fire Department"
APP_WEBSITE_FIREHALL_ADDRESS "5155 Fire Fighter Road, Prince George, BC"
APP_WEBSITE_FIREHALL_TIMEZONE "America/Vancouver"
APP_WEBSITE_FIREHALL_GEO_LAT 54.0918642
APP_WEBSITE_FIREHALL_GEO_LONG -122.6544671
APP_GOOGLE_MAP_API_KEY ""
APP_WEBSITE_ROOT "/"

# To run the docker image with some customized views that you have located on the local host folder named views-custom  
sudo docker run -p "80:80" -v ${PWD}/mysql:/var/lib/mysql -v ${PWD}/views-custom:/app/views-custom softcoder/riprunner:latest    

# To connect to the running the docker image  
sudo docker ps (this will show you the name of the running container)  
sudo docker exec -it {container name from previous step} /bin/bash  

# To login to your hub.docker.com account  
sudo docker login  

# To push the docker image to dockerhub  
sudo docker push softcoder/riprunner:latest  

To login to the docker image:  

web login username: admin  
web login password: riprunner  
db name:            riprunner  
db username:        riprunner  
db password:        riprunner  
  
---  

2. Google Cloud Run:  

There is a separate Dockerfile for deploying to Google's Serverless Cloud Run platform.  
The Dockerfile is located in the app folder. The docker image produced does not include a db engine  
it is assumed you have setup Google Cloud SQL (MySQL). In your cloud run service, ensure the live   
revision has the following environment variables set to connect to your environment:  

Environment variables  
Name: APP_DSN  
Value: mysql:unix_socket=/cloudsql/<your instance connection name>;dbname=riprunner  

Name: APP_GOOGLE_MAP_API_KEY  
Value: <your api key>  

To see more variables look in the config.php file in the docker/app folder  

Open a terminal and navigate into the 'docker/app' folder (make sure NOT to run from the docker folder)  

# Authentication your google cloud platform account
gcloud auth login  

# Build the docker Image in google cloud run  
gcloud builds submit --tag gcr.io/pgtg-container-demo/riprunner  

On Success you will see something like:  
...  
ID                CREATE_TIME               DURATION SOURCE                                               IMAGES                                          STATUS  
e53b2c57-697b-... 2019-08-22T07:10:32+00:00 2M60S    gs://pgtg-container-demo_cloudbuild/source/1566..tgz gcr.io/pgtg-container-demo/riprunner (+1 more)  SUCCESS  

# Deploy the image to make it live (notice the last parameter shows how you can pass the env var via commandline)  
gcloud beta run deploy --image gcr.io/pgtg-container-demo/riprunner --platform managed \
       --update-env-vars APP_DSN='mysql:unix_socket=/cloudsql/<your instance connection name>;dbname=riprunner' APP_GOOGLE_MAP_API_KEY=<your api key>  

To login to the image visit this link to install the database tables and user accounts for Riprunner:  

https://riprunner-v23k7quonq-uc.a.run.app/install.php  

FYI important links regarding Google Cloud run:  

https://cloud.google.com/sql/docs/mysql/quickstart  
https://stackoverflow.com/questions/56342904/enter-a-docker-container-running-with-google-cloud-run  
https://cloud.google.com/php/getting-started/using-cloud-sql-with-mysql  

