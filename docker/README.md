riprunner is a docker image that include the Docker-Lamp baseimage (Ubuntu 18.04), along with a LAMP stack ([Apache][apache], [MySQL][mysql] and [PHP][php]) all in one handy package.

With Ubuntu **18.04** image on the `latest-1804`, riprunner is ready to test the Rip Runner communication suite.

# To build a new docker image
sudo docker build -t=softcoder/riprunner:latest -f ./1804/Dockerfile .

# To run the docker image
sudo docker run -p "80:80" -v ${PWD}/app:/app -v ${PWD}/mysql:/var/lib/mysql softcoder/riprunner:latest

# To push the docker image to dockerhub
sudo docker push softcoder/riprunner:latest
