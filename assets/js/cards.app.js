/**
 * Card (Story-Card)
 * JavaScript object defining behavior of the Story-Card interface
 *
 * @author Carl Saggs
 * @version 0.2 (2012-08-11)
 * @licence MIT
 */
 (function(){

	//DataStores
	this.cardStore = {};
	this.workflow = {};
	this.loaded = '0';	// Last loaded time

	//Settings
	this.settings = {
		"authed":0,
		"current_user": '',
		"refresh_time": 5000,
		query_options: {product:"",sprint:"all"}
	};

	//Subsets
	this.ui = {};
	this.actions = {};

	//Setup private vars
	var _this = this;//Internal reference to cards object.
	var p = {};//private methods
	var url = this.settings.query_options;//Quick access to url options

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
			this.settings.query_options[s[0]] = s[1].replace(/%20/g, ' ');
		}
		
		//Hook up resize listener
		$(window).resize(this.ui.scale_window);
		//Load settings
		$.get('xhr/settings', function(data){
				//Proccess data
				var d = JSON.parse(data);
				if(d.setup == true) return _this.newSetup(d);
				//set workflow constraints
				_this.workflow = d.workflow;
				//Get other options
				var qo = _this.settings.query_options;
				if(qo.product=='') qo.product = d.default_product;
				_this.settings.refresh_time = d.refresh_time;
				//Update product list
				_this.ui.renderProductList(d.products);
				//Make in to a select2 dropdown
				$("#product_selector").select2();
				//Call UI Render
				_this.ui.renderMain(d.workflow.Statuses, function(){
					//Load cards for default product
					$.get('xhr/list?product='+qo['product']+'&sprint='+qo['sprint'], _this.setup);
				});	
				//Start checking for changes 5 seconds in.
				setTimeout(function(){_this.checkForChanges()},5000);	
		});	
	}

	/**
	 * Refresh Settings
	 * Reload card settings (for adding new products etc)
	 */
	this.refreshSettings = function(){
		$.get('xhr/settings', function(data){
			var d = JSON.parse(data);
			_this.workflow = d.workflow;
			_this.ui.renderProductList(d.products);
		});

	}

	/**
	 * Setup display
	 * Used to display messages during the setup proccess for a new Story-Card installtion.
	 * Will display both "thanks" messages on success, or error info on failuire.
	 *
	 * @param d JSON info on install
	 */
	this.newSetup = function(d){
		//Hide indicator
		$("#indicator").hide();
		//Setup message & add reload button markup.
		var msg='';
		d.reload = '<p><a href="javascript:document.location.reload();" class="btn">Click here to reload</a></p>'
		//Generate HTML for error / thanks for installing.
		if(d.error==true){
			msg = tpl.template("<div class='greeting'><h2>Oh no! It looks like somthing went wrong.</h2><p>{message}</p> {reload} </div>",d);
		}else{
			msg = tpl.template("<div class='greeting'><h2>Thankyou for installing Story-Card</h2><p>{message}</p> {reload}</div>",d);
		}
		//add to page.
		$('#card_container').append(msg);
	}

	/**
	 * Setup
	 * Display a new set of storycards within the interface
	 *
	 * @param data JSON Object
	 */
	this.setup = function(data){
		//Clear any existing data
		$( "#card_container .container ul" ).html('').sortable("destroy");
		//Hide loading indicator
		$("#indicator").hide();
		//Log
		console.log("Loading cards for "+_this.settings.query_options['product']+ ", sprint "+_this.settings.query_options['sprint']);
		//Proccess data
		var d = JSON.parse(data);

		//Update sprint list (only if sprint is "all" (change product) or is first load)
		if(_this.settings.query_options['sprint'].toLowerCase()=='all' || _this.loaded ==0){
			$( "#sprints" ).html('');
			_this.ui.renderSprintList(d.sprints);
		}

		//Store required data
		_this.settings.current_user = d.authed_user;
		_this.settings.authed = d.authed;
		_this.cardStore = d.data;
		_this.loaded = d.loaded;

		//Since cardstore needs to be a quick access hashmap, it seems some browsers (chrome..) do not 
		//preserve its original order & instead order the indexs numerically. (which sadly is valid as per the spec)
		
		//In order to get the values from our hashmap in the correct order, we need to generate an array of its indexs
		var sorter = []; for(var i in d.data){sorter.push(i);};
		//sort those indexs by the priorities of the items they reference
		sorter.sort(function(a,b){return parseInt(d.data[a].priority) < parseInt(d.data[b].priority) ? 1 : -1;});
		//then iterate through the sorter array, adding cards to the page in the order it suggests
		for(var i=0;i<sorter.length;i++){
			//Render card
			_this.ui.renderCard(d.data[sorter[i]]);
		}
		
		//Do Dragger
		if(d.authed == 1){
		 	$( ".container ul" ).sortable({
				connectWith: ".connectedSortable",
				receive: _this.actions.moveCard,
			}).disableSelection();
		}
		//Render Auth info (login / logout details)
		_this.ui.renderAuthInfo(d.authed, d.authed_user);
		//Ensure window is scaled correctly
		_this.ui.equalize();
		_this.ui.scale_window();
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
		qo = _this.settings.query_options;
		var spr = 'all';
		var pro = qo.product;
		if(typeof sprint != 'undefined') spr = encodeURIComponent(sprint);
		if(typeof product != 'undefined') pro = encodeURIComponent(product);
		//Update query options
		qo.product = pro;
		qo.sprint = spr;
		//Update URL (if we can)
		window.history.pushState({}, document.title , '?product='+pro+'&sprint='+spr);
		//setup new card display
		$.get('xhr/list?product='+pro+'&sprint='+spr, _this.setup);
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
		var con = this.workflow.Statuses[status];
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
		$.get('xhr/list?product='+pro+'&sprint='+s+'&after='+_this.loaded , function(data){
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

	/***********************

	 	UI Methods. Methods relating to user interface components.
	
	***********************/

	/**
	 * renderMain
	 * Builds status groups based on configurition in constraints.
	 *
	 * @param statuses Configurtion information for UI
	 * @param callback Callback to run once UI has been created.
	 */
	this.ui.renderMain = function(statuses,callback){
		var data = {}, templ;
		//For each status
		for(var i in statuses){
			//Get title (if not specified use status name)
			data.title = (typeof statuses[i].title == 'undefined') ? i : statuses[i].title;
			//Set status name
			data.status = i;
			//Get display type
			data.type = statuses[i].display;
			//Build template based on above info
			templ = tpl.template("sectionTPL", data);
			//Append in to the cards container
			document.getElementById('card_container').appendChild(templ);
		}
		//Runcallback & rescale windows to ensure a good fit.
		callback();
		_this.ui.scale_window();
	}

	/**
	 * renderMain
	 * Builds status groups based on configurition in constraints.
	 *
	 * @param statuses Configurtion information for UI
	 * @param callback Callback to run once UI has been created.
	 */
	this.ui.renderProductList = function(products){
		//Blank element
		document.getElementById('product_selector').innerHTML = '';
		//Add items
		var o, qp = _this.settings.query_options;
		products.forEach(function(p){
			o = document.createElement("option");
			o.innerHTML = p;
			if(qp.product == p)o.setAttribute('selected','selected');
			document.getElementById('product_selector').appendChild(o);
		});
	}

	/**
	 * renderAuthInfo
	 * Create the auth info section (username + logout if logged in, else a login button)
	 *
	 * @param authed (is user authenticated)
	 * @param auth_user (username to use if user is authenticated)
	 */
	this.ui.renderAuthInfo = function(authed,auth_user){
		if(authed==1){
			document.getElementById('login').innerHTML = "Logged in as "+auth_user+" ";
			$('.opt-panel').show();
		}else{
			document.getElementById('login').innerHTML = "<a href='javascript:cards.ui.showLogin();'>Login in interact</a>";
			$('.opt-panel').hide();
		}
	}

	/**
	 * renderCard
	 * Render a storycard and append it in to place
	 *
	 * @param data Storycard data
	 * @param append DOM node to append card in to.
	 */
	this.ui.renderCard = function(data, attach){	
		//Template storycard
		var card = $(tpl.template("cardTPL", data));
		//Hook up click handler.
		//Flip on single
		//Open edit on double
		card.single_double_click( _this.ui.flipCard, _this.ui.renderEditDialog);

		//Append Card to status drop zone
		if(typeof attach == 'undefined'){
			$("ul.connectedSortable[data-type='"+data.status+"']").append(card);
		}else{
			attach.append(card);
		}
		//return new cards jQuery object.
		return card;
	}

	/**
	 * renderSprintList
	 * Render list of sprints relating to current product
	 *
	 * @param sprints Array of sprint ID's
	 */
	this.ui.renderSprintList = function(sprints){
		//Add all option to sprint array
		sprints.unshift('All');
		//For each sprint instance, create a Link node and hookup JS to view sprints data.
		sprints.forEach(function(s){
			var a = document.createElement("option");
			a.setAttribute('value',s);
			a.innerHTML = (s!='All') ? 'Sprint '+s : s+' sprints';
			//Append in to place
			document.getElementById('sprints').appendChild(a);
		});
	}

	/**
	 * flipCard
	 * Flips a storycard to reveal completion critera on the back
	 *
	 * @param event Onclick event
	 */
	this.ui.flipCard = function(event){
		//get card details from the cardstore
		var card = _this.cardStore[$(this).attr("data-ref")];
		//Update data and flip the card
		if($(this).attr("data-showtype")=='accept'){
			$(this).attr("data-showtype",'story');
			$(this).children(".inner").html(card.story);	//Get description
			$(this).flip({direction:'lr',color:"#D9E5FA",speed:"3","onEnd":function(){_this.ui.equalize();}});
		}else{
			$(this).attr("data-showtype",'accept');
			$(this).children(".inner").html(card.acceptance);	//get card acceptance
			$(this).flip({direction:'rl',color:"#D9D4FA",speed:"3","onEnd":function(){_this.ui.equalize();}});
		}//E7EFFF l blue
		
	}

	/**
	 * showLogin
	 * Display login dialog
	 */
	this.ui.showLogin = function(){
		$("#dialog").dialog({width: 250});
	}

	/**
	 * alert
	 * Display an alert box
	 *
	 * @param title Alert title
	 * @param message Alert Message
	 */
	this.ui.alert = function(title, msg){
		$("#alert_dialog div").text(msg);
		$("#alert_dialog" ).dialog({
			"title": title,
			"modal": true,
			"resizable": false
		});
	}

	/**
	 * renderInfoDialog
	 * Display a dialog with form fields need to obtain a selection of data from the user.
	 *
	 * @param info An array of form field configs defining what data needs to be collected.
	 * @param ref Reference to card we are requesting data about.
	 */
	this.ui.renderInfoDialog = function(info, ref){
		//Get current card from store
		var cur_data = _this.cardStore[ref];
		//Make form
		var frm = $('<form class="card_data form-horizontal" onSubmit="return cards.actions.updateCard(this);"><input type="hidden" name="id" value="'+ref+'"/></form>');
		frm = _this.ui.renderForm(frm, info, cur_data);
		//Shovel it all in to a dialog.
		$( "#info_dialog" ).html(frm);
		$( "#info_dialog" ).dialog({
			width: 520,
			modal: true,
			title: "Additional information required",
			resizable:false
		});
		//Invoke snazzy editor
		p.invokeEditor();
	}

	/**
	 * renderEditDialog
	 * Display a dialog to allow uses to edit the "edit card" fields specified in the workflow.json.
	 *
	 * @param event Click event.
	 */
	this.ui.renderEditDialog = function(event){
		//Don't pop up if a user isn't permissioned to edit
		if(_this.settings.authed==0) return;

		var ref = $(this).attr('data-ref');
		//Get current card from store
		var cur_data = _this.cardStore[ref];
		//Make form
		var frm = $('<form class="card_data card_edit form-horizontal" onSubmit="return cards.actions.updateCard(this);"><input type="hidden" name="id" value="'+ref+'"/></form>');
		frm = _this.ui.renderForm(frm, _this.workflow.forms.edit_card, cur_data);
		//Shovel it all in to a dialog.
		$( "#info_dialog" ).html(frm);
		$( "#info_dialog" ).dialog({
			width: 520,
			modal: true,
			resizable:false,
			title: "Editing card: "+ref
		});
		//Invoke snazzy editor
		p.invokeEditor();
	}

	/**
	 * renderAddDialog
	 * Display a dialog to allow uses to edit the "add card" fields specified in the workflow.json.
	 *
	 * @param event Click event.
	 */
	this.ui.renderAddDialog = function(){
		//Make form
		var frm = $('<form class="card_data card_edit form-horizontal" onSubmit="return cards.actions.addCard(this);"></form>');
		frm = _this.ui.renderForm(frm, _this.workflow.forms.new_card);
		//Shovel it all in to a dialog.
		$( "#info_dialog" ).html(frm);
		$( "#info_dialog" ).dialog({
			width: 520,
			modal: true,
			title: "Adding new Story"
		});
		//Invoke snazzy editor
		p.invokeEditor();
	}

	/**
	 * renderProductdDialog
	 * Display a dialog to allow uses to edit the "new product" fields specified in the workflow.json.
	 *
	 * @param event Click event.
	 */
	this.ui.renderProductDialog = function(){
		//Make form
		var frm = $('<form class="card_data card_edit form-horizontal" onSubmit="return cards.actions.addProduct(this);"></form>');
		frm = _this.ui.renderForm(frm, _this.workflow.forms.new_product);
		//Shovel it all in to a dialog.
		$( "#info_dialog" ).html(frm);
		$( "#info_dialog" ).dialog({
			width: 520,
			modal: true,
			title: "Adding new product"
		});
		//Invoke snazzy editor
		p.invokeEditor();
	}

	/**
	 * renderFrom
	 * Create & append a list of form fields to a provided form. Form configurtion is grabbed from
	 * the attributes section of the workflow.json. If cur_data is provided the script will attempt
	 * to prepopulate the feilds generated.
	 *
	 * @param form Form Object (jQuery)
	 * @param feilds Array of fields to add to form.
	 * @param cur_data Object containing any data that may want to be set in to fields.
	 * @return form object populated with specified fields.
	 */
	this.ui.renderForm = function(form, fields, cur_data){
		//For each field.
		for(var i in fields){
			//Grab a copy of the attributes config (specified in workflow.json)
			var field = Object.create(_this.workflow.attributes[fields[i]]);
			//If no id attribute, use JSON id
			if(typeof field.id == 'undefined') field.id = fields[i];

			//use id as name if one is not provided
			field.name = (typeof field.name !='undefined') ? field.name : fields[i];
			//If current data exists, attempt to prefill value
			if(typeof cur_data != 'undefined'){
				field.value = (typeof cur_data[fields[i]] == 'undefined') ? '' : cur_data[fields[i]];
			}
			//If value is empty, add default if one is set.
			if(typeof field.value == 'undefined' || field.value==''){
				field.value = (typeof field.default_value != 'undefined') ? field.default_value : '';	
			}
			//Templates for textarea / input box differ slighly
			if(field.type=='textarea'){
				temp_tpl = tpl.template("<div class='control-group'><label class='' for='{id}'>{name}:</label><div class=''><textarea name='{id}'></textarea></div></div>", field);
				$(temp_tpl).find('textarea').val(field.value);
			}else{
				temp_tpl = tpl.template("<div class='control-group'><label class='control-label' for='{id}'>{name}:</label><div class='controls'><input name='{id}' type='text' value='{value}'/></div></div>", field);
			}
			
			form.append(temp_tpl);
		}
		//Add save button
		form.append($("<div class='errorBox' style='display:none;'></div><input type='submit' value='Save' class='btn btn-cards btn-block' />"));

		//return populated form object
		return form;
	}

	/**
	 * Render sprint form
	 * Render UI elements needed to configure / build a new sprint
	 *
	 * @return  UI elements (products & newsprint)
	 */
	this.ui.renderSprintForm = function(){
		//swap zones
		$('#card_container').hide();
		$('#sprint_container').show();
		//Build layout
		var product_panel = tpl.template("sectionTPL", {type:"column", title:"Product Backlog", status:"sprint_none"});
		var newsprint = tpl.template("sectionTPL", {type:"row", title:"Hi", status:"product_backlog"});
		//Append in to the sprint container
		document.getElementById('sprint_container').appendChild(product_panel);
		document.getElementById('sprint_container').appendChild(newsprint);

		//Attach UI
		$(newsprint).wrap('<form id="sprint_form" onSubmit="return cards.actions.createSprint();"></form>')
		//Add head/foot forms
		.prepend(tpl.template('newSprint_headTPL')).append(tpl.template('newSprint_footTPL'))
		//Submit button
		.append($("<input type='submit' style='margin:5px;' value='Create sprint' class='btn btn-cards right' onclick='' /><a class='btn btn-danger' href='javascript:cards.actions.closeSprintBuilder();'>Cancel</a>"))
		.find('span').remove();

		//Activate datapicker
		$( "input[data-type=date]" ).datepicker();

		//Inital scale
		_this.ui.scale_window();

		//return ui panels
		return product_panel;
	}

	/**
	 * Scale_window
	 * Update UI to reflect new window size.
	 */
	this.ui.scale_window = function(){
		var win_width = $(window).width();
		$(".container.row").width(win_width-265);
		$(".opt-panel").width(win_width-265);
		$(".container.half_row").width((win_width-272)/2);
		//$("#todo_container").height($(window).height()-85);
	}
	this.ui.equalize = function(){
		$(".container.row ul, .container.half_row ul").equalize();
	}

	/***********************

	 	Action Methods. Actions invoked via interface components.
	
	***********************/

	/**
	 * login
	 * Called when a user submits a login request from the login dialog. Uses ajax to attempt to authenticate the user.
	 *
	 * @param fo Reference to submitted form.
	 */
	this.actions.login = function(fo){
		//Show loading indicator
		$("#indicator").show();
		//Submit data
		$.post("xhr/login", { "username": fo.elements['username'].value, "password": fo.elements['password'].value },function(data){
			//Was autentication successful?
			if(data==0){
				//If no, show error.
				$("#dialog").find('.errorBox').css('display','block').text("Incorrect username or password.");
			}else{
				//If yes, reload with editable UI.
				_this.reload();
				//Hide error
				$("#dialog").find('.errorBox').css('display','none');
				//and finally close dialog
				$("#dialog").dialog('close');
			}	
		});
		//Stop form submitting
		return false;
	}

	/**
	 * logout
	 * Called when a user clicks logout. Uses ajax to attempt to log user out of the system
	 *
	 * @param fo Reference to submitted form.
	 */
	this.actions.logout = function(){
		$.get('xhr/logout', function(){
			//reload data in light of authoristion change
			_this.reload();
		});
	}

	/**
	 * addCard
	 * Called when a user clicks save on create new card dialog. Use ajax to update datastore & reflect new card in UI
	 *
	 * @param frm Reference to submitted form.
	 */
	this.actions.addCard = function(frm){
		//Show loader
		$("#indicator").show();
		//hide dialog
		$("#info_dialog").dialog('close');
		//Add additional data (default status, current product and current sprint)
		var card_data = $(frm).serialize();
		card_data += '&product='+url.product+'&sprint='+url.sprint;
		card_data += '&status='+_this.workflow.attributes['status'].default_value;

		//Submit data via AJAX
		$.post("xhr/addCard?product="+url.product, card_data, function(data){
			//hide loader
			$("#indicator").hide();
			//If somthing goes wrong
			if(data==0){
				//Show dialog again with error notice
				$( "#info_dialog" ).dialog('open');
				$( "#info_dialog" ).find('.errorBox').css('display','block').text("Warning: Unable to save card.");
			}else{
				//If all goes well...
				var json = JSON.parse(data);			
				//update timestamp
				_this.loaded = json.timestamp;
				//Add the new card to our local cardStore
				_this.cardStore[json.card.id] = json.card;
				//Add new card to UI
				_this.ui.renderCard(json.card);
				_this.ui.equalize();
			}
		});
		//Don't submit form!
		return false;
	}

	/**
	 * updateCard
	 * Called when a user clicks save on edit/update dialogs. Use ajax to update DB then reflect updated card in UI
	 *
	 * @param frm Reference to submitted form.
	 */
	this.actions.updateCard = function(frm){
		//show loading
		$("#indicator").show();
		//hide dialog
		$("#info_dialog").dialog('close');
		//Submit data
		$.post("xhr/updateCard?product="+url.product, $(frm).serialize(), function(data){
			//Hide loader when data is returned & parse json returned
			$("#indicator").hide();
			if(data==0){
				$( "#info_dialog" ).dialog('open');
				$( "#info_dialog" ).find('.errorBox').css('display','block').text("Warning: Unable to save changes.");
			}else{
				var json = JSON.parse(data);
				//update timestamp
				_this.loaded = json.timestamp;
				//Update local copy of card with changed data
				for(var i in json.data){
					_this.cardStore[json.id][i]= json.data[i];//update local cardStore
				}
				//Redraw card
				p.reDrawCard(json.id);
			}
		});
		return false;
	}

	/**
	 * Add Product
	 * Called when a user clicks save on "add product" dialogs. Use ajax add product & update views
	 *
	 * @param frm Reference to submitted form.
	 */
	this.actions.addProduct = function(frm){
		//show loading
		$("#indicator").show();
		//hide dialog
		$("#info_dialog").dialog('close');
		//Submit data
		$.post("xhr/addProduct", $(frm).serialize(), function(data){
			$("#indicator").hide();
			if(data==0){
				$( "#info_dialog" ).dialog('open');
				$( "#info_dialog" ).find('.errorBox').css('display','block').text("Warning: Unable to save changes.");
			}else{
				_this.reload(data);
				_this.refreshSettings();
			}
		});
		return false;
	}

	/**
	 * moveCard
	 * Called when a user drags a story card between two status areas. Ensures move is valid, then updates datasource to reflect change via ajax
	 *
	 * @param frm Reference to submitted form.
	 */
	this.actions.moveCard = function(event, ui){
		//Show loader
	 	$("#indicator").text("Saving").show();
	 	//Get card reference
	 	var ref = ui.item[0].getAttribute('data-ref');
	 	//Get new status
	 	var new_status = ui.item[0].parentNode.getAttribute('data-type');
	 	//Check constraints allow this move
	 	if(!_this.validateConstraints(ref,new_status)) return;

		//Tell ajax to update the datastore
		$.post("xhr/move?product="+url.product, { "id": ref, "status": new_status}, function(data){
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
		//re equalise
		_this.ui.equalize();
		_this.ui.scale_window();
	}
	/**
	 * toggleControls
	 * toggle the display of the control panel (via cog icon)
	 */
	this.actions.toggleControls = function(){
		$('#popmenu').css('right','6px').css('top','52px').attr('tabindex',100);
		$('#popmenu').blur(function(e){ $('#popmenu').hide(); }).show().focus();
	}

	/**
	 * launchNewCardDialog
	 * launch the add new card dialog
	 */
	this.actions.launchNewCardDialog = function(){
		_this.ui.renderAddDialog();
	}

	/**
	 * launchProductDialog
	 * launch the add new product dialog
	 */
	this.actions.launchProductDialog = function(){
		_this.ui.renderProductDialog();
	}
	

	/**
	 * generate "create new sprint from"
	 *
	 */
	this.actions.addSprint = function(){

		//Render sprint form UI.
		var product_panel = _this.ui.renderSprintForm();

		//Load cards
		$.get('xhr/list?product='+url.product+'&sprint=all', function(data){
			data = JSON.parse(data);
			
			//Setup backlog lists
			for(var i in data.sprints){
				var sp = data.sprints[i];
				$(product_panel).append($("<span>Sprint "+sp+"</span>"));
				$(product_panel).append($("<ul class='connectedSortable' data-type='sprint_"+sp+"'></ul>"));
			}

			//Add in cards (from backlogs - need 2 get backlog identifier from "workflow.json")
			for(var i in data.data){
				var c = data.data[i];
				//For now just use backlog
				if(c.status == 'Backlog'){
					if(c.sprint==0 || c.sprint=='' || c.sprint == 'all' || c.sprint=='0' ) c.sprint = 'none';
					_this.ui.renderCard(c, $("ul.connectedSortable[data-type='sprint_"+c.sprint+"']"));
				}
			}

			//Make cards draggable
			$( "#sprint_container .container ul" ).sortable({
				connectWith: ".connectedSortable",
				 receive: function(){
				 	//Do nothing for now.
				 }
			}).disableSelection();

			//refit window.
			_this.ui.scale_window();
		});

	}

	this.actions.createSprint = function(){
		
		//Validate form?

		var formdata = $('#sprint_form').serialize();

		$('#product_backlog').find('li').each(function(i,c){
			formdata += '&card['+i+']='+c.getAttribute('data-ref');
		});

		$("#indicator").show();

		$.post("xhr/addSprint?product="+url.product, formdata, function(data){
			//hide loader
			$("#indicator").hide();

			if(data==1){
				//if all is good
				_this.actions.closeSprintBuilder();
				_this.reload();
			}else{
				//If somthing goes wrong
				console.log(data);
			}
		});

		return false;
	}
	//In Progress content, 
	//SPRINT BUILDER IS NOT YET FUNCTIONAL.
	this.actions.closeSprintBuilder = function(){
		//add check before so they dont accidently delete all there data

		$('#sprint_container').html('').hide();
		$('#card_container').show();
		//refit window.
		_this.ui.scale_window();
	}
	


	/***********************

	 	Private Methods. Internal utility methods
	
	***********************/
	

	/**
     * revertMove
     * Return a storycard to its original location.
     *
     * @param ref Storycard ID
     * @scope private
	 */
	p.revertMove = function(ref){
		$("ul.connectedSortable[data-type='"+_this.cardStore[ref].status+"']").append($(".card[data-ref="+ref+"]"));
	}

	/**
     * visualCardMove
     * Animate the move of a storycard from one location to another (in order to reflect changes made by other users)
     *
     * @param ref Storycard ID
     * @param card DOM node
     * @param time to trigger move.
     * @scope private
	 */
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

	/**
     * reDrawCard
     * re-draw a story card in order to reflect any changes made to it in the UI
     *
     * @param ref Storycard ID
     * @scope private
	 */
	p.reDrawCard = function(ref){
		//get current card
		var current = $(".card[data-ref="+ref+"]");
		//Render a new copy
		var n = _this.ui.renderCard(_this.cardStore[ref]);
		//Add the new one after the old one (to keep positioning)
		current.after(n);
		//Remove the old one
		current.remove();
		//re equalise
		_this.ui.equalize();
	}

	/**
	 * Invoke rich text editor
	 * Converts all textareas in form to editable regions
	 */
	p.invokeEditor = function(){
		$('textarea').html5_editor({
			 'fix-toolbar-on-top': false,
			 'toolbar-items': [
						[
							['bold', 'B', 'Bold'],
							['italic', 'I', 'Italicize'],
							['underline', 'U', 'Underline'],
							['strike', 'Abc', 'Strikethrough'],
							['remove', 'normal', 'Remove Formating'],
							['link', 'Link', 'Insert Link'],
							['ul', 'Bullets', 'Unordered list'],
							['ol', 'Numbered', 'Ordered list']
						],
					]
			 });
		//rescale enviroment
		_this.ui.scale_window();
	}

	//Make cards accessible in the global namespace
	window.cards = this;
}).call({});

//on load
$(function() {
	//Run Cards
	cards.init();
});

// Author:  Jacek Becela
// Source:  http://gist.github.com/399624
// License: MIT
jQuery.fn.single_double_click = function(single_click_callback, double_click_callback, timeout) {
  return this.each(function(){
    var clicks = 0, self = this;
    jQuery(this).click(function(event){
      clicks++;
      if (clicks == 1) {
        setTimeout(function(){
          if(clicks == 1) {
            single_click_callback.call(self, event);
          } else {
            double_click_callback.call(self, event);
          }
          clicks = 0;
        }, timeout || 300);
      }
    });
  });
}