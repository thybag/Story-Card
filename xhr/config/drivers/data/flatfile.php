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
		$this->cache = Config::get('cache.dir').'/cards/';
		if(!file_exists($this->cache)) mkdir($this->cache,true);
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
			return array();
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
		$json = (array) json_decode(file_get_contents($product_file));
		//Apply changes
		foreach($data as $key => $val){
			$json[$id]->{$key} = $val;
		}
		//save file
		file_put_contents($product_file ,json_encode($json));
		return true;

	}
	//Stubs
	public function removeCard($id){}
	public function addProduct($title,$data){}
    public function addSprint($identifier,$data){}

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
		if(!is_array($json)) $json = array();
		//Apply ID and add to JSON array
		$data['id'] = sizeof($json);
		$json[] = (object) $data;
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
			$raw = file_get_contents($product_file);
			return json_decode($raw);
		}else{
			return array();
		}
	}
}
