# My3 (Ireland) Usage App

This is a small (mobile friendly) web app for checking your post-pay allowance and usage.
Its only goal is to provide a fast and convenient way to check you're not going over your allowance for the month.

Three Ireland doesn't offer an app or mobile-friendly version of My3. This is an attempt at one.

This app is made up of two parts:

- A client-side JavaScript app for small screens;
- A PHP API that uses cURL to scrape the current usage information from three.ie.

## Getting started

First, clone the repository's files:

    git clone git://github.com/daviddoran/mythree.git

Install [composer](http://getcomposer.org/) (if you don't have it installed globally):

    curl -sS https://getcomposer.org/installer | php

Download the app's PHP dependencies using composer:

    php composer.phar install

Create a new database (for example):

    mysqladmin -u root create my3

Import the database tables:

    mysql -u root my3 < schema.sql

Create the config file:

    cp config.sample.php config.php

Edit config.php with your database name and connection settings.

Now you just need to serve the webroot/ folder using Apache, nginx, etc.

If you have PHP 5.4 installed then you can use the built-in web server for testing:

    cd webroot
    php -S localhost:8000

And then go to http://localhost:8000/ in your browser.

## Screenshots

![My3 Screenshots](http://daviddoran.github.com/mythree/screenshots.png "My3 Screenshots")

## Requirements

Developed with PHP 5.3.15 (Apache mod_php). Should work with any PHP 5.3+ and on most servers.

## License

This project is released under the MIT License - see the LICENSE file for details.