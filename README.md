# Blogs at blogs.lib.umich.edu

University of Michigan Library Blogs site is a drupal application (https://www.drupal.org/).

## Prerequisites

1. If you haven't already, you will need to install Docker or Docker Desktop (https://docs.docker.com/get-docker/) and Git (https://github.com/git-guides/install-git)

2. To start working on it in docker please contact eliotwsc@umich.edu to obtain appropriate credentials for files and database.

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
docker run --rm -it -v $(pwd)/.aws:/root/.aws -v $(pwd):/aws amazon/aws-cli s3 cp s3://blogs-lib-umich-edu/ ./ --recursive
```

4.  **Set up files and database settings.**

```sh
tar -zxf files.tar.gz -C sites/default && cp sites/default/docker.settings.php sites/default/settings.php && sudo chown -R www-data sites/default/files
```

5.  **Start the server.**

```sh
docker-compose build && docker-compose up -d
```

6.  **Import the openid config for development.**

NOTE: you may get errors about other config but should be fine.
If you get the error "[ERROR] Command "config:import:single", is not a valid command name." wait a minute. Container is not yet functional.

```sh
docker exec -it blogslib_drupal_1 drupal config:import:single --file=openid_connect.settings.generic.yml
```

7.  **Open the site. (NOTE: Takes a minute for mariadb to load up.)**

Site should load at http://localhost:25647

## 🚀 Completely Rebuild environment
1.  **Pull the latest from git.**

```sh
git pull
```

2.  **Get fresh data from the s3 bucket (NOTE: credentials must be supplied from initial build.)

```sh
docker run --rm -it -v $(pwd)/.aws:/root/.aws -v $(pwd):/aws amazon/aws-cli s3 cp s3://blogs-lib-umich-edu/ ./ --recursive
```

3.  **Set up refreshed files.**

```sh
tar -zxvf files.tar.gz -C sites/default && sudo chown -R www-data sites/default/files
```

4.  **Rebuild docker with latest stuff.**

Remove all associated volumes, images and containers. Download the latest.

```sh
docker rm -f blogslib_database_1 && docker rm -f blogslib_drupal_1 && docker volume rm -f blogslib_database && docker image rm -f mariadb:latest && docker image rm -f blogslib_drupal:latest && docker-compose build --no-cache && docker-compose up -d --force-recreate
```

5.  **Import the openid config for development. (NOTE: you may get errors about other config but should be fine.)**

```sh
docker exec -it blogslib_drupal_1 drupal config:import:single --file=openid_connect.settings.generic.yml
```

## Other handy commands

**Update composer**

```sh
docker exec -it blogslib_drupal_1 composer update
```