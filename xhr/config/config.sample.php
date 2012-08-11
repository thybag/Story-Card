<?php
	$config = array();

	//Basic configurtion
	$config['title'] = 'Story Cards';

	//Drivers (for authentiction & datastore)
	$config['datastore'] = 'sharepoint';
	$config['auth_method'] = 'ldap';

	//Cache directory
	$config['cache.dir'] = 'tmp';

	//Config for datastore driver (Sharepoint/mysql/whatever)
	$config['sharepoint.wsdl'] = 'config/Lists.asmx.xml';
	$config['sharepoint.user'] = '';
	$config['sharepoint.password'] = '';
	$config['sharepoint.list'] = 'Product Backlog';
	$config['sharepoint.view'] = 'story_card_api';
	//$config['mysql.host'] ='';
	//$config['mysql.user'] ='';
	//$config['mysql.password'] ='';
	//$config['mysql.database'] ='';

	//Config for auth driver (ldap)	
	$config['ldap.host']= '';
	$config['ldap.port']= '';
	$config['ldap.basedn']= '';

	//Default product and refresh time (how to to date should the UI try to be?)
	$config['default_product'] = 'Story Card';
	$config['refresh_time'] = 1000;	//check once every second
	
	//Mapping (to allow use to built in reMap method to translate your col names to those used by the system)
	$config['column_mappings']= array(
			//Attribute => Col Name (in your system)
			'id' 		=> 'ID',
			'title'		=> 'Title',
			'story'		=> 'Description',
			'priority'	=> 'Priority',
			'acceptance'=> 'Acceptance',
			'status'	=> 'Status',
			'sprint'	=> 'Sprint',
			//Optional cols: Comment out to disable
			'estimate'	=> 'Story_x0020_Points',
			'time_spent'=> 'Actual_x0020_Time',
			'completion_notes'=> 'Completion_x0020_Notes',
			'assigned'=> 'Assigned',
		);

	Config::store($config);
