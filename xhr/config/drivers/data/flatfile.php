<?php
/**
 * FlatFile DataSource Driver
 * Implements dataStore API used by StoryCard to interact with a flat files.
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */
class FlatFileStore extends StoreAbstract{
	//Private vars
	private $cache;

	/**
	 * Construct
	 * Ensure writable area is ok (attempt to create if not)
	 */
	public function __construct(){
		//If user is logged in access sharepoint using their credentals
		$this->cache = Config::get('cache.dir').'/sc/';
	}	

	/**
	 * List products
	 * Returns an array of all products available to view.
	 *
	 * @return array $products
	 */
	public function listProducts(){
		//If products file exists
		if(file_exists($this->cache.'products.txt')){
			//Return contents as array
			$raw = file_get_contents($this->cache.'products.txt');
			return json_decode($raw);
		}else{
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
	public function updateCard($id,$data){
		//Get product file (md5 of product name)
		$product_file = $this->cache.md5($_GET['product']).'.json';
		//Convert to json array of storys
		$json = json_decode(file_get_contents($product_file));
		//Apply changes
		foreach($data as $key => $val){
			$json->cards[$id]->{$key} = $val;
		}
		//save file
		file_put_contents($product_file, json_encode($json));
		return true;

	}

	/**
	 * Update Cards
	 * Batch update cards
	 * 
	 * @param array of card Changesets
	 * @return true|false success of save
	 */
	public function updateCards($data){
		//Get product file (md5 of product name)
		$product_file = $this->cache.md5($_GET['product']).'.json';
		//Convert to json array of storys
		$json = json_decode(file_get_contents($product_file));
		//Apply changes
		foreach($data as $c){

			if(!isset($c['id'])) continue;
			
			$id = $c['id'];
			unset($c['id']);
			foreach($c as $key => $val){
				$json->cards[$id]->{$key} = $val;
			}
			
		}
		//save file
		file_put_contents($product_file, json_encode($json));
		return true;
	}
	//Stubs
	public function removeCard($id){}
	public function addProduct($title,$data){}

	 /**
     * Add a new Sprint
     * Create a sprint item by adding to the sprints array in the product file json.
     * 
     * @return $identifier ID of sprint
     */
    public function addSprint($identifier, $data){
    	//get product file
    	$product_file = $this->cache.md5($_GET['product']).'.json';
    	$json = json_decode(file_get_contents($product_file));

    	//Setup new product file if one doesnt exist
    	if(!is_object($json)) $json = (object) array('cards'=>array(), 'sprints'=>array());

    	//Setup new sprint item & add to sprints array
    	$json->sprints[] = (object) array(
    			"identifier" => $identifier,
    			"start_date" => $data['start_date'],
    			"end_date" => $data['end_date'],
    			"hours" => $data['hours'],
    	);

    	//write to file
    	file_put_contents($product_file, json_encode($json));

    	//return new ID
    	return $identifier;
    }

    /**
     * Setup a new FlatFile DataStore
     * Create folders & files nesssary for datastore to operate & inform
     * user of this action.
     * 
     * @return Installtion notes.
     */
    public function setup(){
    	//If not already setup
    	if(!file_exists($this->cache.'products.txt')){

    		//Create directory if needed
    		if(!file_exists($this->cache)) mkdir($this->cache,0775,true);
    		//Setup products with default one created.
    		$products = array(Config::get('default_product'));
    		file_put_contents($this->cache.'products.txt', json_encode($products));

    		return "A flatfile database has been automatically created for you. Just hit refresh to to get started.";
    	}
    	return "This Story-Card installtion already appears to be complete.";
    }

	/**
	 * Add Card
	 * Add a new StoryCard
	 * 
	 * @param $data Associative array of data to be used in new card.
	 * @return CardObject on success | null on fail
	 */
	public function addCard($data){
		//Get product file
		$product_file = $this->cache.md5($data['product']).'.json';
		//read contents as json
		$json = json_decode(file_get_contents($product_file));
		if(!is_object($json)) $json = (object) array('cards'=>array(), 'sprints'=>array());
		//Apply ID and add to JSON array
		$data['id'] = sizeof($json->cards);
		$json->cards[] = (object) $data;
		//Save json
		file_put_contents($product_file ,json_encode($json));
		return $data;
	}


	/**
	 * Get Storycards for given "product" & "sprint"
	 * Return an array of StoryCard Objects for the provided sprint & product
	 *
	 * @param $product Product name
	 * @param $sprint Sprint Number || 0/All
	 * @return array of StoryCard Objects. (array index's must match object Id's)
	 */
	public function getCardsFor($product,$sprint='all'){
		//get product file
		$product_file = $this->cache.md5($product).'.json';
		if(file_exists($product_file)){
			//return all cards
			$json = json_decode(file_get_contents($product_file));

			//If not showing "all" spints, remove any cards that dont belong in view
			if($sprint != 'all'){
				foreach($json->cards as $i => $c){
					//If card is not in sprint, remove it from array to be returned.
					if($c->sprint != $sprint){
						unset($json->cards[$i]);
					} 
				}
			}

			return $json->cards;
		}else{
			return array();
		}
	}
}
