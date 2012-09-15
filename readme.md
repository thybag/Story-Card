# Story Card

A highly customisable interactive Story Card dashboard. Uses the [Flight](http://flightphp.com/) PHP micro-framework and [jQuery](http://jquery.com/) for all the heavy(ish) lifting. Authenticates over LDAP and currently works with Sharepoint and MySQL as a data sources - more data sources and authentication methods soon!

## SharePoint Installation

1. Copy `xhr/config/config.sample.php` to `xhr/config/config.php` and edit following the prompts.

2. Drop a WDSL file for the Sharepoint lists to poll for cards - this can normally be obtained by visiting `sharepoint.url/subsite/_vti_bin/Lists.asmx?WSDL`. This location should be reflected in the `xhr/config/config.php` file in the `sharepoint.wsdl` field.

3. Point a web server to the directory and visit this with your browser - you should now be good to go!

## MySQL Installation

1. Copy `xhr/config/config.sample.php` to `xhr/config/config.php`.

2. In the config set datastore to `mysql` and uncomment and fill in the MySQL config items.

3. Run the following SQL on your database in order to create the `cards` table.

<pre>
    CREATE TABLE IF NOT EXISTS `cards` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product` varchar(255) NOT NULL,
        `title` varchar(255) NOT NULL,
        `story` text NOT NULL,
        `priority` varchar(5) NOT NULL,
        `acceptance` text NOT NULL,
        `status` varchar(255) NOT NULL,
        `sprint` int(11) NOT NULL,
        `estimate` int(11) NOT NULL,
        `time_spent` int(11) NOT NULL DEFAULT '0',
        `completion_notes` text NOT NULL,
        `assigned` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;
</pre>

4. Point a web server to the directory and visit this with your browser - you should now be good to go!

## Credits

Story-Card was made possible variety of open source packages and scripts.

### JavaScript

 * jQuery - http://jquery.com/
 * jQuery-UI - http://jqueryui.com/
 * Select2 - http://ivaynberg.github.com/select2/
 * jQuery Flip - http://lab.smashup.it/flip/
 * jQuery Single Double Click - https://gist.github.com/399624
 * jQuery HTML5 Editor - https://github.com/nunobaldaia/html5_editor
 * Equalize.js - https://github.com/tsvensen/equalize.js/

### PHP

 * Flight Micro-framework - http://flightphp.com/
 * SharePointAPI - https://github.com/thybag/PHP-SharePoint-Lists-API
 * htmLawed -http://code.google.com/p/htmlawed/

### Graphics

 * Cog icon from http://www.famfamfam.com/lab/icons/silk/