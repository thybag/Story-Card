<?php 
/**
 * MySQL Data Source Driver
 * Implements datastore API to comunicate with a MySQL backend
 *
 * @author Amy
 * @version 0.2
 * @licence MIT
 */
class MysqlStore extends StoreAbstract{

	//DB connection var
	private $dbconn;

	/**
	* Construct
	* Connect to MySQL database via PDO
	*/
	public function __construct(){
		// database name and server
		$dsn = 'mysql:dbname='.Config::get("mysql.database").';host='.Config::get("mysql.host");
		// try to connect to the database 
		try {
		    $this->dbconn = new PDO($dsn, Config::get("mysql.user"), Config::get("mysql.password"));
			$this->dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch (PDOException $e) {
			// if not able to connect to the database die and display error message
			$this->showError("It seems we are unable to connect to the database <strong>".Config::get("mysql.database")."</strong>.</br>The error message was: ".$e->getMessage());
		}
	}	

	/**
	 * Add Card
	 * Add a new StoryCard
	 * 
	 * @param $data Associative array of data to be used in new card.
	 * @return CardObject on success | null on fail
	 */
	public function addCard($data){

		//Remap data to fit schema defined in config
		//This has the added benifit of stopping any dodgy values
		//being passed in as column_names
		$data = (array) $this->remap($data);
		//Create keys SQL
		$keys = implode(',', array_keys($data));
		//Create array of values
		$values= array_values($data);
		//create replacer SQL
		$sql = implode(',', array_pad(array(), sizeof($data), '?'));

		try{
			//Prepare query (add in $keys and the ?'s to be replaced)
			$q = $this->dbconn->prepare("INSERT INTO sc_backlog ({$keys}) VALUES ({$sql})");
			//run it with the $values array
			$q->execute($values);
			//Once card is saved, use its ID to grab a copy of it front the DB & return the card.
			$new_id = $this->dbconn->lastInsertId();
			$n = $this->dbconn->prepare("SELECT * FROM sc_backlog WHERE id = ?");
			$n->execute(array($new_id));
			//return as object
			return (object) $n->fetch(PDO::FETCH_ASSOC);
		}catch (PDOException $e) {
			// just in case it is unable to save
			return null;
		}
	}

	/**
	 * Update Card
	 * Update an existing story card with changes specified in the $data array. ($data=array('myCol'=>'new_val');)
	 * 
	 * @param $id ID of card to update
	 * @param $data Associative array of data to be updated.
	 * @return true|false success of save
	 */
	public function updateCard($id, $data){

		//Remap data to fit schema defined in config
		//This has the added benifit of stopping any dodgy values
		//being passed in as column_names
		$data = $this->remap($data);

		//Crete an array of $keys as $key=? & an array of values
		$values = array();$keys = array();
		foreach($data as $k=>$v){$keys[]=$k.'= ?'; $values[] = $v;}
		//implode keys to create sql
		$sql = implode(',', $keys);
		//Add id as final value for PDO to replace
		$values[] = (int)$id;
		try{
			//preparem SQL to update the card with the new details
			$q = $this->dbconn->prepare("UPDATE sc_backlog SET {$sql} WHERE id = ?");
			//run the query with the $values array
			$q->execute($values);
			//inform the system save was successful
			return true;
		}catch (PDOException $e) {
			// just in case it is unable to save
			return false;
		}

	}

	/**
	 * Update Cards
	 * Batch update cards
	 * 
	 * @param array of card Changesets
	 * @return true|false success of save
	 */
	public function updateCards($data){

		$success = true;
		//Pass each call on to the standard updateCard method
		foreach($data as $card){
			$id = $card['id'];
			unset($card['id']);
			//If any fail, fail the block? (by try and do as many as you can anyway)
			if(!$this->updateCard($id,$card)) $success = false;
		}
		//return status
		return $success;
	}

	/**
	 * getSprints
	 * return an array of all sprints relating to the current product;
	 * @param $product
	 * @return array of sprint ID's
	 */
	public function getSprints($product){

		try{
			//get all product names from the database
			$q = $this->dbconn->prepare('SELECT name FROM sc_sprints WHERE product = ?');
			$q->execute(array($product));
			//create array to store products in.
			$data=array();
			//return null if no products exist
			if($q->rowCount() == 0)return array();
			//Add returned products to array
			while ($result = $q->fetch(PDO::FETCH_ASSOC)) {
				$data[] = $result['name'];
			}

			//return information
			return $data;

		}catch (PDOException $e) {
			return array();
		}	
	}

	 /**
     * Add a new Sprint
     * Create a sprint item by adding to the sprints information in to the sprint db table
     * 
     * @return $identifier ID of sprint
     */
	public function addSprint($identifier, $data){
	 	try{
			//Prepare query 
			$q = $this->dbconn->prepare("INSERT INTO sc_sprints (name, start_date, end_date, hours, product)
			 							 VALUES (?, ?, ?, ?, ?)");
			//run it
			$q->execute(array($identifier, $data['start_date'], $data['end_date'],  $data['hours'], $data['product']));

			//return id if all went well
			return $identifier;
		}catch (PDOException $e) {
			// or null if it fails
			return null;
		}

	 }



	//stub
	public function removeCard($id){	}

	/**
	 * Add product
	 * add new product to products list
	 *
	 * @param $title Product title
	 * @param $data Product attributes (unused)
	 */
	public function addProduct($title,$data){
		try{
			//Prepare query 
			$q = $this->dbconn->prepare("INSERT INTO sc_products (product)  VALUES (?)");
			//run it
			$q->execute(array($title));

			//return id if all went well
			return true;
		}catch (PDOException $e) {
			// or null if it fails
			return false;
		}


	}
   


    /**
     * Setup a new MySQL DataStore
     * Generate required Tables in the MySQL Db. Add inital data & inform
     * user of the changes.
     * 
     * @return Installtion notes.
     */
    public function setup(){

    	//DB schema for Card-Store
 		$sql = "
	 		CREATE TABLE IF NOT EXISTS `sc_backlog` (
		        `id` int(11) NOT NULL AUTO_INCREMENT,
		        `product` varchar(255) NOT NULL,
		        `title` varchar(255) NOT NULL,
		        `story` text NOT NULL,
		        `priority` int(11) NOT NULL,
		        `acceptance` text NOT NULL,
		        `status` varchar(255) NOT NULL,
		        `sprint` varchar(255) NOT NULL,
		        `estimate` int(11) NOT NULL,
		        `time_spent` int(11) NOT NULL DEFAULT '0',
		        `completion_notes` text NOT NULL,
		        `assigned` varchar(255) NOT NULL,
	       	 PRIMARY KEY (`id`)
	    	) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
			CREATE TABLE IF NOT EXISTS `sc_products` (
		        `id` int(11) NOT NULL AUTO_INCREMENT,
		        `product` varchar(255) NOT NULL,
	       	 PRIMARY KEY (`id`)
	    	) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
			CREATE TABLE IF NOT EXISTS `sc_sprints` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `name` varchar(255) NOT NULL,
			  `start_date` varchar(35) NOT NULL,
			  `end_date` varchar(35) NOT NULL,
			  `hours` int(11) NOT NULL,
			  `product` varchar(255)  NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
			INSERT INTO sc_products VALUES (null, '".Config::get('default_product')."');
		";
		//Attempt to run SQL
		try {
			$this->dbconn->exec($sql);
			//Success
			return "Your cardstore database has been successfully created.";
		}catch (PDOException $e) {
			//Fail
			return "Sorry, we were unable to create the database table's for you automatically.<br/> The error was: ".$e->getMessage();
		};
    }
	/**
	 * List products
	 * Returns an array of all products available to view.
	 *
	 * @return array $products
	 */
	public function listProducts(){
		
		try{
			//get all product names from the database
			$q = $this->dbconn->prepare('SELECT product FROM sc_products');
			$q->execute();
			//create array to store products in.
			$data=array();
			//return null if no products exist
			if($q->rowCount() == 0)return null;
			//Add returned products to array
			while ($result = $q->fetch(PDO::FETCH_ASSOC)) {
				$data[]=$result['product'];
			}

			//return information
			return $data;

		}catch (PDOException $e) {
			return null;
		}	
	}

	/**
	 * Get Cards
	 * Make a list of story cards for a spcificed product and sprint
	 *
	 * @param $product Product name
	 * @param $sprint Sprint Number || all
	 * @return array of StoryCard Objects. (array index's must match object Id's)
	*/
	public function getCardsFor($product, $sprint='all'){

		//get cards for the specified product and sprint from the database
		if($sprint == 'all'){
			//run query using just product
			$q = $this->dbconn->prepare("SELECT * FROM sc_backlog WHERE product = ?");
			$q->execute(array($product));
		}else{
			//run query using product and sprint variables
			$q = $this->dbconn->prepare("SELECT * FROM sc_backlog WHERE product = ? AND sprint= ?");
			$q->execute(array($product, $sprint));
		}
		
		//creat new array 
		$data=array();
		//loop through results adding them to array
		while ($result = $q->fetch(PDO::FETCH_ASSOC)) {
			//cast result array into object and store in array with index equaling 'id'  
			$data[$result['id']] = (object) $result;
		};
		//return array of story cards
		return $data;
	}
}