# Story Card

A highly customisable interactive Story Card dashboard. Uses the [Flight](http://flightphp.com/) PHP micro-framework and [jQuery](http://jquery.com/) for all the heavy(ish) lifting. Authenticates over LDAP and currently works with Sharepoint as a data source - more data sources and authentication methods soon!

## Installation

1. Copy `xhr/config/config.sample.php` to `xhr/config/config.php` and edit following the prompts.

2. For Sharepoint drop WDSL file for the Sharepoint lists to poll for cards. This location should be reflected in the `xhr/config/config.php` file in the `sharepoint.wsdl` field.

3. Point a web server to the directory and visit this with your browser - you should now be good to go!