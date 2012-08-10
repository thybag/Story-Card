<?php
//Load sharepoint API
include('lib/sharepointAPI.php');

class SharePointStore extends StoreAbstract{
	private $sp;
	private $backlog;

	public function __construct(){
		//If user is logged in access sharepoint using there credentals
		if(isset($_SESSION['auth'])){
			//Setup sharepoint object with user credentals
			$this->sp = new SharePointAPI($_SESSION['username'],$_SESSION['password'],Config::get('sharepoint.wsdl'));
		}else{
			//Setup sharepoint object with default/service account credentals (suggest no edit abilitys)
			$this->sp = new SharePointAPI(Config::get('sharepoint.user') ,Config::get('sharepoint.password'),Config::get('sharepoint.wsdl'));
		}
		$this->sp->setReturnType('object');
		$this->sp->lowercaseIndexs(false);
		//get list CRUD interface
		$this->backlog = $this->sp->CRUD(Config::get('sharepoint.list'));
	}	

	public function addCard($data){



	}
	public function updateCard($id,$data){
		//Make data match db/sharepoint schema
		$data = $this->remap($data);
		//Save
		if($this->backlog->update($id, $data) != null){
			return true;
		}else{
			return false;
		}

	}
	public function removeCard($id){

	}

	public function listProducts(){
		//List products alphabetically
		$data = $this->sp->query("Products")->sort("Title","ASC")->get();;
		$products = array();
		foreach($data as $product){
			$products[] = $product->Title;
		}
		return $products;
	}



	public function getCardsFor($product,$sprint=0){
		//get cards for product/sprint specified.
		if($sprint == 0){
			$data = $this->backlog->query()
				->where('Product','=',$product)
				->limit(100)
				->sort('Priority','DESC')
				->using(Config::get('sharepoint.view'))
				->get();
		}else{
			$data = $this->backlog->query()
				->where('Product','=',$product)
				->and_where('Sprint','=',$sprint)
				->limit(100)
				->sort('Priority','DESC')
				->using(Config::get('sharepoint.view'))
				->get();

		}

		//correct format and make mappable
		foreach($data as $d){
			//Get data in correct format
			$d = $this->reMap($d,true);
			//make values nicer
			$d->priority = round(trim($d->priority));
			$d->time_spent = round(trim($d->time_spent));
			$d->sprint = round(trim($d->sprint));
			$jsondata[$d->id] = $d;
		}

		//return data (id=>carddata)
		return $jsondata;		
	}

	public function moveCard($id,$status){
		//Save result of move
		if($this->backlog->update($id, array('Status'=>$status)) != null){
			return true;
		}else{
			return false;
		}
		
	}




}