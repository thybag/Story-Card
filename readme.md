# Story Card

Story-card is an easy to use, highly customisable, interactive, online Story Card dashboard. By default story-card comes with drivers ready to use with MySQL, flatfile & sharepoint setups, although you can write your own to get story-card working with any datastore you like.

Story-card uses the [Flight](http://flightphp.com/) PHP micro-framework and [jQuery](http://jquery.com/) for all the heavy(ish) lifting. By default a "singleuser" authentication driver is in use, although an LDAP driver is also included.

## Flat file Installation

1. Place the Story-Card files on a PHP enabled webserver.

2. Copy `xhr/config/config.sample.php` to `xhr/config/config.php`.

3. Point your web browser at Story-Card and start using it. (We recommend you change the username & password in the config file first though!)

## MySQL Installation

1. Place the Story-Card files on a PHP enabled webserver.

2. Copy `xhr/config/config.sample.php` to `xhr/config/config.php`.

3. In the config set datastore to `mysql` and uncomment and fill in the MySQL config items.

4. Point your web browser at Story-Card and start using it! (We recommend you change the username & password in the config file first though!)

## SharePoint Installation

1. Place the Story-Card files on a PHP enabled webserver.

2. Copy `xhr/config/config.sample.php` to `xhr/config/config.php` and edit following the prompts.

3. Drop a WDSL file for the Sharepoint lists to poll for cards - this can normally be obtained by visiting `sharepoint.url/subsite/_vti_bin/Lists.asmx?WSDL`. This location should be reflected in the `xhr/config/config.php` file in the `sharepoint.wsdl` field.

4. Update the column_mappings in the config so the field names map to those used in your SharePoint list. (If the list doesn't exist you will need to create it manually)

5. Point a web server to the directory and visit this with your browser.

## Trouble shooting

#### Story-Card is constantly updates the cards
This can be caused if the writable drictory specified in config is not writable. Additionally story-card will automatically refresh its cards based on the interval specified in the config, if you would like it to refresh less often, simply update this value.

#### Story-Card fails in my browser
Story-card is still in early alpha so has only been tested in Chrome & firefox. We will hopefully fix support for other browsers as the product matures.

## Credits

Story-Card was made possible by a wide variety of open source packages and scripts.

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

### Styles

 * Twitter Bootstrap - http://twitter.github.com/bootstrap/


## License (ISC license)

Copyright (c) 2012, Carl Saggs <carl@userbag.co.uk>

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.

This product can also be used under the terms of the MIT license if prefered.

### Included products
Flight, jQuery, jQuery-ui, jQuery Flip, jQuery Single Double Click, jQuery HTML5 Editor, SharePointAPI and Equalize.js are licensed under the MIT License.

Twitter Bootstrap and Select2  are licensed under the Apache Software Foundation License Version 2.0.

htmLawed is licensed under the LGPL 3.