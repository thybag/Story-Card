<?php
/**
 * Index
 * Routes requests to controller actions.
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */

//Start session
session_start();
//Start flight
require 'flight/Flight.php';
require 'lib/htmLawed.php';
//Load required classes
require 'controllers/general.php';

//get config
$config = array();
if((include 'config/config.php')==false){#
	echo '{"setup":true, "error":true, "message":"The <strong>config.php</strong> file could not be found. <br/> Please double check you have renamed your <strong>config.sample.php</strong> file to <strong>config.php</strong>."}';
	die();
}
Config::store($config);
//Load cards controller.
require 'controllers/cards.controller.php';
//Get datastores (using drivers specified in config)
require 'model/CardStore.php';
require 'model/auth.php';
//Setup Obj
$cards = new Cards();
//Do routing
Flight::route('/list', array($cards,'showlist'));
Flight::route('/settings', array($cards,'settings'));
Flight::route('/login', array($cards,'login'));
Flight::route('/logout', array($cards,'logout'));
Flight::route('/move', array($cards,'move'));
Flight::route('/addCard', array($cards,'addCard'));
Flight::route('/addSprint', array($cards,'addSprint'));
Flight::route('/addProduct', array($cards,'addProduct'));
Flight::route('/updateCard', array($cards,'updateCard'));
Flight::route('/lastchange', array($cards,'lastchange'));
//Go!
Flight::start();
?>
