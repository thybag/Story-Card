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
			//$this->dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch (PDOException $e) {
			// if not able to connect to the database die and display error message
		    echo 'Connection failed: ' . $e->getMessage();
			die();
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
		$data = $this->remap($data);

		//Create keys SQL
		$keys = implode(array_keys($data),',');
		//Create array of values
		$values= array_values($data);
		//create replacer SQL
		$sql = implode(array_pad(array(), sizeof($data), '?'),',');

		try{
			//Prepare query (add in $keys and the ?'s to be replaced)
			$q = $this->dbconn->prepare("INSERT INTO cards ({$keys}) VALUES ({$sql})");
			//run it with the $values array
			$q->execute($values);
			//Once card is saved, use its ID to grab a copy of it front the DB & return the card.
			$new_id = $this->dbconn->lastInsertId();
			$n = $this->dbconn->prepare("SELECT * FROM cards WHERE id = ?");
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
		$sql = implode($keys,',');
		//Add id as final value for PDO to replace
		$values[] = (int)$id;
		try{
			//preparem SQL to update the card with the new details
			$q = $this->dbconn->prepare("UPDATE cards SET {$sql} WHERE id = ?");
			//run the query with the $values array
			$q->execute($values);
			//inform the system save was successful
			return true;
		}catch (PDOException $e) {
			// just in case it is unable to save
			return false;
		}

	}

	//stub
	public function removeCard($id){	}

	/**
	 * List products
	 * Returns an array of all products available to view.
	 *
	 * @return array $products
	 */
	public function listProducts(){
		//get all product names from the database
		$q = $this->dbconn->prepare('SELECT product FROM cards GROUP BY product');
		$q->execute();
		//make a array of products
		$data=array();
		while ($result = $q->fetch(PDO::FETCH_ASSOC)) {
			$data[]=$result['product'];
		};
		//return information
		return $data;

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
			$q = $this->dbconn->prepare("SELECT * FROM cards WHERE product = ?");
			$q->execute(array($product));
		}else{
			//run query using product and sprint variables
			$q = $this->dbconn->prepare("SELECT * FROM cards WHERE product = ? AND sprint= ?");
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

	/**
	 * Move Card
	 * Save status of given story card
	 *
	 * @param $id ID of card
	 * @param $status New status of card
	 * @return true|false
	 */
	public function moveCard($id,$status){
		//Pass to update? move card should maybe be depricated?
		return $this->updateCard($id, array('status'=>$status));	
	}

}