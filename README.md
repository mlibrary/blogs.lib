# Blogs at blogs.lib.umich.edu

University of Michigan Library Blogs site is a drupal application (https://www.drupal.org/).

## Prerequisites

1. If you haven't already, you will need to install Docker or Docker Desktop (https://docs.docker.com/get-docker/) and Git (https://github.com/git-guides/install-git)

2. To start working on it in docker please contact eliotwsc@umich.edu to obtain appropriate credentials for files and database.

3. IMPORTANT! If you run Docker with a new non-Intel Apple chip, you may have issues! If you do and find a solution please post it in issues.

## 🚀 Quick start

1.  **Clone `blogs.lib`.**

```sh
git clone https://github.com/mlibrary/blogs.lib.git && cd blogs.lib
```

2.  **Set up s3 credentials.**

Add credentials for s3 (NOTE: credentials must be supplied.)

```sh
tar -zxf aws-stub && vi .aws/credentials
```

Add the key and secret supplied and save the credentials file

3.  **Get the data from an s3 bucket (NOTE: credentials must be supplied from prior step.)**

```sh
sudo docker run --rm -it -v $(pwd)/.aws:/root/.aws -v $(pwd):/aws amazon/aws-cli s3 cp s3://blogs-lib-umich-edu/ ./ --recursive
```

4.  **Set up files and database settings.**

```sh
tar -zxf files.tar.gz -C sites/default && cp sites/default/docker.settings.php sites/default/settings.php
```

5.  **Start the server.**

```sh
sudo docker-compose build && sudo docker-compose up -d
```

6. **Set files permissions to apache**

```sh
sudo docker exec -it blogslib_drupal_1 chown -R www-data sites/default/files
```

7.  **Import the openid config for development.**

NOTE: you may get errors about other config but should be fine.
If you get the error "[ERROR] Command "config:import:single", is not a valid command name." wait a minute. Container is not yet functional.

```sh
sudo docker exec -it blogslib_drupal_1 drupal config:import:single --file=openid_connect.settings.generic.yml
```

8.  **Open the site. (NOTE: Takes a minute for mariadb to load up.)**

Site should load at http://localhost:25647

## 🚀 Completely Rebuild environment
1.  **Pull the latest from git.**

```sh
git pull
```

2.  **Get fresh data from the s3 bucket (NOTE: credentials must be supplied from initial build.)

```sh
sudo docker run --rm -it -v $(pwd)/.aws:/root/.aws -v $(pwd):/aws amazon/aws-cli s3 cp s3://blogs-lib-umich-edu/ ./ --recursive
```

3.  **Set up refreshed files. (NOTE: you may want to mv sites/default/files sites/default/files- or rm -r sites/default/files)**

```sh
sudo tar -zxvf files.tar.gz -C sites/default && sudo chown -R www-data sites/default/files
```

4.  **Rebuild docker with latest stuff.**

Remove all associated volumes, images and containers. Download the latest. Note that you may have names other than blogslib_drupal_1 and blogslib_database_1.

```sh
sudo docker rm -f blogslib_database_1 && sudo docker rm -f blogslib_drupal_1 && sudo docker volume rm -f blogslib_database && sudo docker image rm -f mariadb:latest && sudo docker image rm -f blogslib_drupal:latest && sudo docker-compose build --no-cache && sudo docker-compose up -d --force-recreate
```

5.  **Import the openid config for development. (NOTE: you need to wait for database to start up. Should go green.)**

```sh
sudo docker exec -it blogslib_drupal_1 drupal config:import:single --file=openid_connect.settings.generic.yml
```

## Other handy commands

**Update composer**

```sh
sudo docker exec -it blogslib_drupal_1 composer update
```
