/**
 * Card (Story-Card)
 * JavaScript object defining behavior of storycard interface
 *
 * @author Carl Saggs
 * @version 0.2 (2012-08-11)
 * @licence MIT
 */
 (function(){

	//Internal reference to cards object.
	var _this = this;
	//DataStores
	this.cardStore = {};
	this.constraints = {};
	this.loaded = '0';	// Last loaded time
	//Settings
	this.settings = {
		"authed":0,
		"current_user": '',
		"refresh_time": 5000,
		query_options: {product:"",sprint:"all"}
	};

	/**
	 * Initalise the StoryCard system
	 * Parse URL, rescale elements, build layout & invoke card loader.(this.setup)
	 */
	this.init = function(){
		//Parse URL
		var q = window.location.search.substring(1).split('&');
		for(var i=0; i<q.length;i++){
			if(q=='')continue;
			var s = q[i].split('=');
			//Set details to query options
			this.settings.query_options[s[0]] = s[1].replace('%20',' ');
		}
		
		//Hook up resize listener
		$(window).resize(this.scale_window);
		//Load settings
		$.get('xhr/settings', function(data){
				//Proccess data
				var d = JSON.parse(data);
				//set constraints
				_this.constraints = d.constraints;
				//Get other options
				_this.settings.query_options.product = d.default_product;
				console.log(d);
				_this.settings.refresh_time = d.refresh_time;
				//Update product list
				_this.ui.renderProductList(d.products);
				//Call UI Render
				var qo = _this.settings.query_options;
				_this.ui.renderMain(d.constraints.Statuses, function(){
					//Load cards for default product
					$.get('xhr/list?product='+qo['product']+'&sprint='+qo['sprint'], _this.setup);
				});	
				//Start checking for changes 5 seconds in.
				setTimeout(function(){_this.checkForChanges()},5000);	
		});	
	}

	/**
	 * Setup
	 * Display a new set of storycards within the interface
	 *
	 * @param data JSON Object
	 */
	this.setup = function(data){
		//Clear any existing data
		$( ".container ul, #sprints" ).html('').sortable( "destroy" );
		//Hide loading indicator
		$("#indicator").hide();
		//Log
		console.log("Loading cards for "+_this.settings.query_options['product']+ ", sprint "+_this.settings.query_options['sprint']);
		//Proccess data
		var d = JSON.parse(data);
		//Store required data
		_this.settings.current_user = d.authed_user;
		_this.settings.authed = d.authed;
		_this.cardStore = d.data;
		_this.loaded = d.loaded;
		//Add products
		_this.ui.renderSprintList(d.sprints);
		//Render cards
		 for(var i in d.data){
				_this.ui.renderCard(d.data[i], $("ul.connectedSortable[data-type='"+d.data[i].status+"']"));
		 };
		 //Do Dragger
		 if(d.authed == 1){
		 	$( ".container ul" ).sortable({
				connectWith: ".connectedSortable",
				 receive: _this.actions.moveCard
			}).disableSelection();
		 }
		 //Render Auth info (login / logout details)
		 _this.ui.renderAuthInfo(d.authed, d.authed_user);
		 //Ensure window is scaled correctly
		 _this.scale_window();
	}

	/**
	 * reload
	 * Reload story cards and invoke setup to create new display
	 *
	 * @param product Product to display storycards for.
	 * @param sprint Sprint to display story cards for.
	 */
	this.reload = function(product, sprint){
		//show loader
		$("#indicator").show();
		//get new sprint & product
		var spr = 'all'
		var pro = _this.settings.query_options['product'];
		if(typeof sprint != 'undefined')spr = sprint;
		if(typeof product != 'undefined')pro = product;
		//Update query options
		_this.settings.query_options['product'] = pro;
		_this.settings.query_options['sprint'] = spr;
		//Update URL (if we can)
		window.history.pushState({}, document.title , '?product='+encodeURIComponent(pro)+'&sprint='+spr);
		//setup new card display
		$.get('xhr/list?product='+encodeURIComponent(pro)+'&sprint='+spr, _this.setup);
	}

	/**
	 * validateConstraints
	 * Check that a cards move is in accordence with the configured constraints & take any
	 * appropite actions. Return false if move is invalid, else true.
	 *
	 * @param ref ID of storycard being moved
	 * @param status Status card is being moved too.
	 * @return true|false
	 */
	this.validateConstraints = function(ref, status){
		//Get constraints assosiated with status card is being moved to.
		var con = this.constraints.Statuses[status];
		//get current status of card.
		var prev_status = this.cardStore[ref].status;
		//If a "limit drag to" constraint is found
		if(typeof con.limit_drag_to != 'undefined'){
			//Check that the card is being moved from an allowed status
			if(con.limit_drag_to.indexOf(prev_status) === -1){
				//If not, revert the move action and notify the user.
				this.ui.alert("Invalid move", "You can not move cards directlty to "+status);
				p.revertMove(ref);
				$("#indicator").hide();
				//Say move was unsuccessful
				return false;
			}
		}
		//If a request constraint is found
		if(typeof con.request != 'undefined'){
			//Ask the user for extra information
			this.ui.renderInfoDialog(con.request,ref);
		}

		//Move was allowed.
		return true;
	}

	/**
	 * checkForChanges
	 * Polls the system to find out if any cards have been updated.
	 * If so, it triggers a "cardRefresh"
	 */
	this.checkForChanges = function(){
		//Check lastchange timestamps to see if anyone else has updated the cards.
		console.log("poll");
		$.get('xhr/lastchange', function(data){
			//Has anything changed?
			if(_this.loaded < data){
				//If so, perform a cardRefresh()!
				_this.cardRefresh(); 
				console.log("Apply update: " + _this.loaded + " " + data);
			}
			//Check again in the time specified via the refresh rate
			setTimeout(function(){ _this.checkForChanges() }, _this.settings.refresh_time);
		});
	}

	/**
	 * checkForChanges
	 * Polls the system to find out if any cards have been updated.
	 * If so, it triggers a "cardRefresh"
	 */
	this.cardRefresh = function(){
		var pro = _this.settings.query_options['product'];
		var s = _this.settings.query_options['sprint'];
		$.get('xhr/list?product='+pro+'&sprint='+s, function(data){
				var d = JSON.parse(data);
				_this.loaded = d.loaded;
				_this.cardStore = d.data;
				var t = 10;
				for(var i in d.data){
					var card = $(".card[data-ref="+i+"]");
					if(card.parent().attr('data-type') != _this.cardStore[i].status){
						p.visualCardMove(i,card, t);
						t +=900;
					}
				}
		});
	}

	this.ui = {};
	this.ui.renderMain = function(statuses,callback){
		var data,temp;
		for(var i in statuses){
			data = {};
			data.title = (typeof statuses[i].title == 'undefined') ? i : statuses[i].title;
			data.status = i;
			data.type = statuses[i].display;
			temp = tpl.template("sectionTPL", data);
			document.getElementById('card_container').appendChild(temp);
		}
		callback();
		_this.scale_window();
	}
	this.ui.renderProductList = function(products){
		var o, qp = _this.settings.query_options;
		products.forEach(function(p){
			o = document.createElement("option");
			o.innerHTML = p;
			if(qp == p)o.setAttribute('selected','selected');
			document.getElementById('product_selector').appendChild(o);
		});
	}
	this.ui.renderAuthInfo = function(authed,auth_user){
		if(authed==1){
			document.getElementById('login').innerHTML = "Logged in as "+auth_user+" (<a href='javascript:this.actions.logout();'>logout</a>)";
		}else{
			document.getElementById('login').innerHTML = "<a href='javascript:cards.ui.showLogin();'>Login in interact</a>";
		}
	}

	this.ui.renderCard = function(data, append){
				data.priority = (data.priority == null || data.priority =='') ? 'N/A' : data.priority;
		
				var card = $(tpl.template("cardTPL", data));
				card.click(_this.ui.flipCard);
				$(append).append(card);
				
	}
	this.ui.renderSprintList = function(sprints){

			sprints.push('all');
			sprints.forEach(function(s){
				var a = document.createElement("a");
				a.setAttribute('href','javascript:cards.reload("'+_this.settings.query_options.product+'","'+s+'")');
				a.innerHTML = s;
				document.getElementById('sprints').appendChild(a);
				
			});
	}

	this.ui.flipCard = function(event){
		//read store
		var card = _this.cardStore[$(this).attr("data-ref")];
		//Update data and flip the card
		if($(this).attr("data-showtype")=='accept'){
			$(this).attr("data-showtype",'story');
			$(this).children(".inner").html(card.story)//Get description
			$(this).flip({direction:'lr',color:"#D9E5FA",speed:"3"});
		}else{
			$(this).attr("data-showtype",'accept');
			$(this).children(".inner").html(card.acceptance);//get card acceptance
			$(this).flip({direction:'rl',color:"#D9D4FA",speed:"3"});
		}//E7EFFF l blue
	}
	this.ui.showLogin = function(){
		$("#dialog").dialog();
	}
	this.ui.alert = function(title, msg){
		$("#alert_dialog div").text(msg);
		$("#alert_dialog" ).dialog({
			"title": title,
			"modal": true
		});
	}

	this.ui.renderInfoDialog = function(info, ref){
		//Get current card from store
		var cur_data = _this.cardStore[ref];
		//Make form
		var frm = $('<form class="card_data" onSubmit="return cards.actions.updateCard(this);"><input type="hidden" name="id" value="'+ref+'"/></form>');
		for(var i in info){
			//For each bit of data we request, create form field (populated if we already have some data)
			info[i].value = (typeof cur_data[info[i].id] == 'undefined') ? '' : cur_data[info[i].id];
			if(info[i].type=='textarea'){
				frm.append(tpl.template("<div> <span>{name}:</span> <textarea name='{id}'>{value}</textarea></div>", info[i]));
			}else{
				frm.append(tpl.template("<div> <span>{name}:</span> <input name='{id}' value='{value}'/></div>", info[i]));
			}
		}
		//Add save button
		frm.append($("<input type='submit' value='Save' class='button' />"));
		//Shovel it all in to a dialog.
		$( "#info_dialog" ).html(frm);
		$( "#info_dialog" ).dialog({
			width: 340,
			modal: true,
			title: "Additional information required"
		});

	}


	this.actions = {};
	this.actions.login = function(fo){
		$("#indicator").show();
		$.post("xhr/login", { "username": fo.elements['username'].value, "password": fo.elements['password'].value },function(data){
			_this.reload();
			$("#dialog").dialog('close');
		});
		return false;
	}
	this.actions.logout = function(){
		$.get('xhr/logout', function(){
			_this.reload();
		});
	}
	this.actions.saveCard = function(){
		
	}
	this.actions.updateCard = function(frm){

		$("#indicator").show();
		$("#info_dialog").dialog('close');
		$.post("xhr/updateCard", $(frm).serialize(), function(data){
				$("#indicator").hide();
				var json = JSON.parse(data);
				//update timestamp
				_this.loaded = json.timestamp;
				for(var i in json.data){
					_this.cardStore[json.id][i]= json.data[i];//update local cardStore
				}
		});
		return false;
	}
	this.actions.moveCard = function(event, ui){
		//Show loader
	 	$("#indicator").text("Saving").show();
	 	var ref = ui.item[0].getAttribute('data-ref');
	 	var new_status = ui.item[0].parentNode.getAttribute('data-type');

	 	if(!_this.validateConstraints(ref,new_status)) return;

		//Tell ajax to update sharepoint
		$.post("xhr/move", { "id": ref, "status": new_status}, function(data){
			//Hide loader
			$("#indicator").text("Loading..").hide();
			if(data=='0'){
				//If update failed, put card back where it came from
				p.revertMove(ref);
				//Tell user what happened
				_this.ui.alert("Unable to save change", "Current move could not be saved. Please try again later.");
			}else{
				//Update loaded time + data in cardStore.
				_this.loaded = data;
				_this.cardStore[ref].status = new_status;
			}
		});
	}


	


	this.scale_window = function(){
		var win_width = $(window).width();
		$(".container.row").width(win_width-292);
		$(".container.half_row").width((win_width-306)/2);
		//$("#todo_container").height($(window).height()-85);
	}
	//Private methods
	var p = {};
	p.revertMove = function(ref){
		$("ul.connectedSortable[data-type='"+_this.cardStore[ref].status+"']").append($(".card[data-ref="+ref+"]"));
	}
	p.visualCardMove = function(ref, card, time){
		//Store old position
		var old_pos = card.position();
		setTimeout(function(){
			//Append card to new position.
			$("ul.connectedSortable[data-type='"+_this.cardStore[ref].status+"']").prepend(card);
			//Store cards new position
			var new_pos = card.position();
			//Use position absolute to place card in its original location (visually)
			card.css('position','absolute').css('left',old_pos.left).css('top',old_pos.top);
			//Animiate the card moving to its new location
			card.animate({left:new_pos.left, top:new_pos.top}, 800, function(){
				//remove position absolute so card fits back in normally.
				$(this).css('position','static');
			});
		},time);
	}

	window.cards = this;
}).call({});

//on load
$(function() {
	//Run Cards
	cards.init();
});