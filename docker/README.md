riprunner is a docker image that include the Docker-Lamp baseimage (Ubuntu 22.04), along with a LAMP stack ([Apache][apache], [MySQL][mysql] and [PHP][php]) all in one handy package.

1. With Ubuntu **22.04** image on the `latest-2204`, riprunner is ready to test the Rip Runner communication suite.  

# To build a new docker image  
sudo docker build -t=softcoder/riprunner:latest -f ./docker/2204/Dockerfile .  

# To run the docker image  
sudo docker run -p "80:80" -v ${PWD}/mysql:/var/lib/mysql softcoder/riprunner:latest  

# To connect to the running the docker image  
sudo docker ps (this will show you the name of the running container)  
sudo docker exec -it <container name from previous step> /bin/bash  

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

