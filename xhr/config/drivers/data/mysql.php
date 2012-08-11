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

	private $dbconn;
	/**
	* Construct
	* Connet to mySQL database via PDO
	*/
	public function __construct(){
		// database credentials
		// database name and server
		$dsn = 'mysql:dbname='.Config::get("mysql.database").';host='.Config::get("mysql.host");
		// database user name
		$user = Config::get("mysql.user");
		// database password
		$password = Config::get("mysql.password");
		// try to connect to the database 
		try {
		    $this->dbconn = new PDO($dsn, $user, $password);
		} 
		// if not able to connect to the database die and display error message
		catch (PDOException $e) {
		    echo 'Connection failed: ' . $e->getMessage();
			die();
		}

	}	

	public function addCard($data){


	}
	public function updateCard($id,$data){


	}
	public function removeCard($id){

	}
	/**
	 * List Products
	 *Make a list of all the products in the database
	*/
	public function listProducts(){
		//get all product names from the database
		$q = $this->dbconn->prepare('select product from cards group by product');
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
	*/
	public function getCardsFor($product,$sprint=0){
		//get cards for the specified product and sprint from the database
		$q = $this->dbconn->prepare("SELECT * FROM cards WHERE product= ? AND sprint= ?");
		//run query using product and sprint variables
		$q->execute(array($product, $sprint));
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
	 */
	public function moveCard($id,$status){

		try{
			//update a story card with the specifed 'id' in the database to change the card's status
			$q = $this->dbconn->prepare("UPDATE cards SET status = ? WHERE id = ?");
			//run the query with the 'status' and 'id' parameters
			$q->execute(array($status,$id));
			//inform the system the status was saved successfully
			return true;
		}catch (PDOException $e) {
			// just in case it is unable to save
			return false;
		}
		
	}

}