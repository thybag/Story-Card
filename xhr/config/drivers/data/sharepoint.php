<?php
//Load sharepoint API
include('lib/sharepointAPI.php');

/**
 * SharePoint DataSource Driver
 * Implements dataStore API used by StoryCard to interact with a sharepoint lists based backlog.
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */
class SharePointStore extends StoreAbstract{
	//Private vars
	private $sp;
	private $backlog;

	/**
	 * Construct
	 * Setup SharePointAPI with credentals needed to interact with the SharePoint List.
	 * For none authenticated users a service account is used in order to talk to SharePoint, autenticated users
	 * will be accessing sharepoint with their own credentals in order to keep "edited by" data accurate
	 */
	public function __construct(){
		//If user is logged in access sharepoint using their credentals
		if(isset($_SESSION['auth'])){
			//Setup sharepoint object with user credentals
			$this->sp = new SharePointAPI($_SESSION['username'],$_SESSION['password'],Config::get('sharepoint.wsdl'));
		}else{
			//Setup sharepoint object with default/service account credentals
			$this->sp = new SharePointAPI(Config::get('sharepoint.user'), Config::get('sharepoint.password'), Config::get('sharepoint.wsdl'));
		}
		//Set return type as Object & disable lowercase index's in order to ensure data mappings work as expected.
		$this->sp->setReturnType('object');
		$this->sp->lowercaseIndexs(false);
		//get the backlog list as a CRUD interface
		$this->backlog = $this->sp->CRUD(Config::get('sharepoint.list'));
	}	

	/**
	 * List products
	 * Returns an array of all products available to view.
	 *
	 * @return array $products
	 */
	public function listProducts(){
		//List products alphabetically
		$data = $this->sp->query("Products")->sort("Title","ASC")->get();;
		//Convert results in to flat array
		$products = array();
		foreach($data as $product){
			$products[] = $product->Title;
		}
		//return
		return $products;
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
		//Make data match db/sharepoint schema using build in remap function
		$data = $this->remap($data);
		//invoke sharepoint update method with given data.
		if($this->backlog->update($id, $data) != null){
			//If data returned was not null, save is successful.
			return true;
		}else{
			return false;
		}
	}
	//Stubs
	public function removeCard($id){}


	/**
	 * Add Card
	 * Add a new StoryCard
	 * 
	 * @param $data Associative array of data to be used in new card.
	 * @return CardObject on success | null on fail
	 */
	public function addCard($data){
		//Save new data
		$card = $this->backlog->create($data);
		if($card!==null){
			//reMap $card object to fit systems internal data model 
			return $this->reMap($card[0], true);
		}else{
			//Card was not created, return null.
			return null;
		}
		
	}

	/**
	 * Get Storycards for given "product" & "sprint"
	 * Return an array of StoryCard Objects for the provided sprint & product
	 *
	 * @param $product Product name
	 * @param $sprint Sprint Number || 0/All
	 * @return array of StoryCard Objects. (array index's must match object Id's)
	 */
	public function getCardsFor($product,$sprint=0){
		//A limit of a 150 cards is set, although this can be raised if needed.
		//To ensure the backlog displays in a sane manner results should be ordered by Priority in descending order.
		if($sprint == 0){
			//If sprint is 0, return all cards for product regardless of sprint 
			$data = $this->backlog->query()
				->where('Product','=',$product)
				->limit(150)
				->sort('Priority','DESC')
				->using(Config::get('sharepoint.view'))
				->get();
		}else{
			//return data for product and sprint number specified.
			$data = $this->backlog->query()
				->where('Product','=',$product)
				->and_where('Sprint','=',$sprint)
				->limit(150)
				->sort('Priority','DESC')
				->using(Config::get('sharepoint.view'))
				->get();
		}
		//For each result returned from the SharePoint API
		foreach($data as $d){
			//Remap columns to fit the systems internal datamodle
			$d = $this->reMap($d,true);
			//Tweak values to get better results (Account for some Sharepoint quirks)
			$d->priority = ($d->priority =='' || $d->priority=='?') ? '?' : round(trim($d->priority)); //Priority must be number or "?""
			$d->time_spent = round(trim($d->time_spent));//as number
			$d->sprint = round(trim($d->sprint));//as number (0=none)
			$d->acceptance = ($d->acceptance!='' && $d->acceptance!='<div></div>') ? $d->acceptance : 'None provided'; //Add empty text
			$d->story = ($d->story!='' && $d->story!='<div></div>') ? $d->story : 'None provided'; //add empty text
			
			//ID of storycards in array "MUST" match the id of the storycard.
			//StoryCard must be object not associative array.
			$jsondata[$d->id] = $d;
		}

		//return data (id=>carddata)
		return $jsondata;		
	}

	/**
	 * moveCard
	 * Save a story cards new status.
	 *
	 * @param $id ID of card
	 * @param $status New status of card
	 * @return true|false
	 */
	public function moveCard($id, $status){
		//Save result of move
		if($this->backlog->update($id, array('Status'=>$status)) != null){
			//return true if data saved correctly
			return true;
		}else{
			return false;
		}
	}
}