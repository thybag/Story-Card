<?php
/**
 * Welcome to Story-Card.
 * In order to get you started quickly, we've filled out this config file 
 *
 * with some default values. If you rename this file to "config.php" you should be ready to go,
 * although at the very least we do strongly reommend you change the username & password
 * for the single user account.
 *
 * Alternate auth drivers & datastores will need additional options filling out.
 * Just comment out the currently selected block and uncomment the relevent on for the driver
 * you'd like to use instead. For ldap you will need to give some details about the LDAP
 * server you wish to validate against. Additionally for a mysql database we are going to need
 * the database credentals.
 *
 * Anyway, have fun and feel free to feed back any bugs at http://github.com/thybag/Story-Card
 */

# Basic configurtion

$config['title'] = 'Story Cards';
$config['cache.dir'] = dirname(getcwd()).'/data';
$config['refresh_time'] = 1000;	//check once every second
$config['default_product'] = 'Story Card';

# Auth Driver (Uncomment and recomment as required.)

$config['auth_method'] = 'singleuser';
$config['singleuser.name']= 'admin';
$config['singleuser.password']= 'Password';

//$config['auth_method'] = 'ldap';
//$config['ldap.host']= '';
//$config['ldap.port']= '';
//$config['ldap.basedn']= '';

# DataStore Driver (Uncomment and recomment as required.)

$config['datastore'] = 'flatfile';

//$config['datastore'] = 'sharepoint';
//$config['sharepoint.wsdl'] = 'config/Lists.asmx.xml';
//$config['sharepoint.user'] = '';
//$config['sharepoint.password'] = '';
//$config['sharepoint.list'] = 'Product Backlog';
//$config['sharepoint.view'] = 'story_card_api';

//$config['datastore'] = 'mysql';
//$config['mysql.host'] ='';
//$config['mysql.user'] ='';
//$config['mysql.password'] ='';
//$config['mysql.database'] ='';

# Column Mappings

//Translate columns/attributes used within Story-Card to match whatever columns/attributes you use
//within your datastore
$config['column_mappings']= array(
	//Story-Card Attribute => Your datastore attribute
	'id' 		=> 'id',
	'title'		=> 'title',
	'story'		=> 'story',
	'priority'	=> 'priority',
	'acceptance'=> 'acceptance',
	'status'	=> 'status',
	'sprint'	=> 'sprint',
	'product'	=> 'product',
	//Optional items - Feel free to add your own. Just remember to define them in the workflow.json
	'estimate'	=> 'estimate',
	'time_spent'=> 'time_spent',
	'completion_notes'=> 'completion_notes',
);