# Business Association Management System (BAMS)

BAMS is purpose built to effectively and efficiently run business associations in the Taiwanese market.

- SaaS — Cloud based solution, no need for any local installation or management of software.
- Complete accounting — track association expenses, dues, A/P, A/R; complete reporting for both management and government requirements.
- Member management — track member information and association dues, including government health and labor insurance dues.
- Automated dues billing — all member association and insurance are automatically calculated for billing.
- PDF reporting & billing — reports and member billing are generated in PDF for maximum flexibility.
- Open & accessible data — backend data is contained in an accessible MySQL database enabling easy mail merge and other custom reports.



# BAMS App on Docker

BAMS Union Management System on Docker. This is set to run as a stack behind the main Sakura site stack with nginx-proxy on the front end.

## First Time Deploy
1. Set up MariaDB BAMS db
2. Build PHP image
3. Build birt

## Running
If running this stack as a stand alone, in the `docker-compose.yml` need to:

1. Comment out the `external: true` under the `sakura_webnet:`
2. Uncomment the port mapping in web-bams
3. Uncomment the port mapping in birt
4. Comment out the `s3backup` section
5. Connect to `http://bams.localhost:8088` - bams.localhost must be in `private/etc/hosts` file

Start stack: `$ docker stack deploy -c ~/docker/bams/docker-compose.yml bams`

* We are using Docker [stack](https://docs.docker.com/get-started/part3/) instead of compose
* Volumes `$ docker volume inspect [vol name]`

Alpine shell access to running container
`$ docker exec -it [container_name] /bin/sh`

Use [Attach](https://docs.docker.com/engine/reference/commandline/attach/#attach-to-and-detach-from-a-running-container) as a sort of `tail -f` on a container.

Stop: `$ docker stack rm bams`

Remove stopped containers `$ docker ps -aq --no-trunc | xargs docker rm`

# Containers
1. web-bams
2. db
3. php
4. birt
5. s3backup

## web-bams
Using the official nginx:alpine build

[Source on docker hub](https://store.docker.com/images/nginx) 

Putting conf files for each site into `/etc/nginx/conf.d` to keep this as normal as possible. Key changes in the `bams.sakuratechnology.com.conf` file to enable PHP and BIRT reports.


## db
MariaDB is a community-developed fork of MySQL

[Source on Docker hub](https://store.docker.com/images/mariadb) 

### First Run
1. Create volume: `docker volume create bamsdb`
2. Migrate over the volume with 
[loomchild/volume-backup](https://store.docker.com/community/images/loomchild/volume-backup) 

OR

1. Begin by running the below to get the bamsdb volume created and setup with the initial DB

	```
	$ docker run \
	  --name mariadb \
	  --mount source=bamsdb,target=/var/lib/mysql \
	  -e MYSQL_ROOT_PASSWORD='ee[N}DCn9ubgRv]77#8s' \
	  -p 3306:3306 \
	  -d mariadb
	```
	
2. Initiate the table structure with  BAMSdb-create.sql. 

3. Setup your security settings so you don’t need to use root

4. Stop and remove the container.


## php
Using php:fpm-alpine-mysqli

[Source on Docker hub](https://store.docker.com/images/php)

### First Build
Due to needing to add in the mysqli PHP extention, need to build this image first.

`$ docker build -t ums/php:fpm-alpine-mysqli .`


## birt
* Base image is [Tomcat](https://store.docker.com/images/tomcat) on Docker hub
* [BIRT Viewer](http://www.eclipse.org/birt/documentation/integrating/viewer-setup.php)
* [MariaDB Connector J](https://mariadb.com/kb/en/library/about-mariadb-connector-j/)

### First Build
1. Download Birt + MariaDB or MySQL JDBC driver to have it copied in when building.
2. `$ docker build -t ums/birt .`

Reference: See [mybirt](https://github.com/cdorde/mybirt) or [wr1241/docker-birt-runtime](https://github.com/wr1241/docker-birt-runtime/blob/master/Dockerfile)


## s3backup
schickling/mysql-backup-s3

* [Source on Docker hub](https://hub.docker.com/r/schickling/mysql-backup-s3/)
* [Source on Github](https://github.com/schickling/dockerfiles/tree/master/mysql-backup-s3)

#### Interaction with the Container or Code
Access the running Container bash shell to interact live with data and running processes
`$ docker exec -it mysql-backup /bin/bash`

To run a manual backup enter the container and run: `$ ./backup.sh`
