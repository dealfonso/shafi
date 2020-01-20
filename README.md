# ShaFi: Share your Files
ShaFi is a platform to share files using self expiring tokens. It offers features similar to WeTransfer, but hosted in your servers thus enabling you to control the limitations. It is similar to [youtransfer](https://github.com/YouTransfer/YouTransfer), but ShaFi implements a backend to allow registered users to have control over their files, to have stats of usage of the sharing links and to create multiple sharing tokens.

ShaFI implements two main workflows: 
- Unregistered sharing (similar to WeTransfer): an unregistered user uploads a file to ShaFi, and ShaFi creates an unique token that will automatically expire in 1 week.
- Registered sharing: the registered users can upload files, but they are allowed to manage tokens for these files. It is possible to create multiple tokens for the same file, with different features: expiration dates, expiration hits, using password, etc.

![A file created by a registered user](img/create-file.png)

## Installation

### Test using docker

You need a working docker installation

1. Get ShaFi and build the docker container

```
$ git clone https://github.com/dealfonso/shafi
$ cd shafi
$ docker build . -t shafidemo
```

2. Start a container with the generated image

```
$ docker run -id -p 10080:80 --name shafidemo shafidemo
```

3. Navigate to the URL of the new installation: `http://localhost:10080/shafi`

    - user: *shafi*
    - password: *123*

**WARNING:** This image should be used with caution, because the passwords are known. 

**WARNING 2:** The files, users and configuration changed in the container will be lost upon stopping the container.

### Install using apache

You need a working installation of Apache and PHP 7.2 or higher, and make sure that `mod_rewrite` is enabled.

1. Prepare a user for the database

```
$ mysql -u root -p
mysql> create database shafi;
mysql> create user shafi@'localhost' identified by 'a-secret-password';
mysql> grant all privileges on shafi.* to 'shafi'@'localhost';
mysql> flush privileges;
```

2. Get ShaFi and copy it to the apache root folder

```
$ git clone https://github.com/dealfonso/shafi
$ cp -r shafi /var/www/html
$ chown -R www-data:www-data /var/www/html/shafi
```

3. Prepare the configuration for ShaFi in file `/etc/apache2/conf-available/shafi.conf` (please adapt it to your installation):

```
<Directory /var/www/html/shafi>
   AllowOverride All
</Directory>
```

4. Enable the configuration and restart apache

```
$ a2enconf shafi
$ a2enmod rewrite
$ service apache2 restart
```

5. Use your browser and navigate to the installation URL and customize your installation:

```
http://localhost/shafi/install/install.php
```

In this guide we'll use the following settings:

- Database name: shafi
- Database user: shafi
- Password: a-secret-password
- Web server URL: http://localhost
- URL path for ShaFi: /shafi

Prepare the ShaFi configuration file `/var/www/html/shafi/config.php` and the htaccess file `/var/www/html/shafi/.htaccess` with the result of the configuration.

## More on ShaFi

### Some technical features
- The upload of files is made of chunks, by using [Resumable.js](https://github.com/23/resumable.js). So the files are also resumable if they fail to upload.
- The tokens are unique, in the form of http://shafi.com/507564f4-70d2-46ce-ac51-7af9d25dade7
- Diffrent tokens for the same file may have different features (e.g. different expiration dates, different expiration hits, passwords or combinations of them).
- Files are expired but not automatically deleted (so that you can have a track of the files uploaded to your servers).
- Most options are configurable (e.g. default token expiration for both anonymous or registered, allowing passwords for users, grace period for files that are not being shared, etc.)
- It is translated into spanish and english (translations are welcomed).
- It is developed in PHP so it works in most of servers.
- ShaFi is easy to integrate with different authorization mechanisms (e.g. Google)
- ShaFi is designed to use different storage backends. At this time it only implements filesystem backend, but S3 is easy to implement.

### Future features
- Integrate with Google auth.
- Use Amazon S3 as a backend for storing files.
- Allowing multiple files to be uploaded (or downloaded) at once.
- Add quotas of files or storage space.
- Send e-mails when expiration dates are near.
- Hits stats (i.e. integrate with GeoIP and others).
