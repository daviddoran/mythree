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

## Security

Unfortunately, the user's My3 username and password must be passed to the server to get the My3 account details.
The username and password are currently stored in the database and a pseudorandom token is stored on the client.

There's no way to get around passing the credentials to the server but there are alternatives to how they're currently stored:

- The password (and username) could be stored encrypted in the database;
- The username and password could be stored on the client and only passed to the server each time the balance is checked;
- The client could require the username and password to be entered every time the balance is checked.

This app should always be served over SSL/TLS.

## Requirements

Developed with PHP 5.3.15 (Apache mod_php). Should work with any PHP 5.3+ and on most servers.
The app was tested with MySQL 5.5 but it may work with other SQL databases as it uses [PDO](http://php.net/manual/en/book.pdo.php).

## License

This project is released under the MIT License - see the LICENSE file for details.
