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
//Load required classes
require 'controllers/general.php';
include('config/config.php');//get config
include('controllers/cards.controller.php');
//Get datastores (using drivers specified in config)
include('model/CardStore.php');
include('model/auth.php');
//Setup Obj
$cards = new Cards();
//Do routing
Flight::route('/list', array($cards,'showlist'));
Flight::route('/settings', array($cards,'settings'));
Flight::route('/login', array($cards,'login'));
Flight::route('/logout', array($cards,'logout'));
Flight::route('/move', array($cards,'move'));
Flight::route('/lastchange', array($cards,'lastchange'));
Flight::route('/updateCard', array($cards,'updateCard'));
//Go!
Flight::start();
?>
