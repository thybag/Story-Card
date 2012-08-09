//Keep all data in json so we can reference in when needed (Without asking sharepoint again)
		var cardStore = {};
		var systemStore = {};
		var constraints = {};

		var refresh_time = 1000;

		var dlg;
		//Proccess URL
		var qp = {product:"Mobile",sprint:"all"};
		var q = window.location.search.substring(1).split('&');
		for(var i=0; i<q.length;i++){
			if(q=='')continue;
			var s = q[i].split('=');
			console.log(s);
			qp[s[0]] = s[1].replace('%20',' ');
		}

		$(function() {
			//Fit boxs using javascript
			enscale();
			$(window).resize(enscale);
			
			$("#dialog").hide();

			//Load cards
			$.get('xhr/settings', function(data){
				var d = JSON.parse(data);
				renderProductList(d.products);
				constraints = d.constraints;
				renderUI(d.constraints.Statuses,function(){
					$.get('xhr/list'+window.location.search, init);
				});
					
			});	
			setTimeout(function(){pollForChanges()},2500);
		});

		function enscale(){
			$(".container.row").width($(window).width()-292);
			$(".container.half_row").width(($(window).width()-306)/2);

			$("#todo_container").height($(window).height()-85);

		}

		function init(data){
			$( ".container ul, #sprints" ).html('').sortable( "destroy" );
			$("#indicator").hide();

			console.log("Loading cards for "+qp['product']+ ", sprint "+qp['sprint']);

			var d = JSON.parse(data);
			systemStore = d;
			cardStore = d.data;
			//Add products
			renderSprintList(d.sprints);
			//Render cards
			 for(var i in cardStore){
					renderCard(cardStore[i], $("ul.connectedSortable[data-type='"+cardStore[i].status+"']"));
			 };
			 //Do Dragger
			 if(d.authed == 1){

			 	document.getElementById('login').innerHTML = "Logged in as "+d.authed_user+" (<a href='javascript:auth_logout();'>logout</a>)";

			 	$( ".container ul" ).sortable({
					connectWith: ".connectedSortable",
					 receive: function(event, ui) {
					 	//Show loader
					 	$("#indicator").text("Saving").show();
					 	var ref = ui.item[0].getAttribute('data-ref');
					 	var new_status = ui.item[0].parentNode.getAttribute('data-type');

					 	if(!checkConstraints(ref,new_status)) return;

						//Tell ajax to update sharepoint
						$.post("xhr/move", { "id": ref, "status": new_status, "previous_status": cardStore[ref].status },function(data){
							//Hide loader
							$("#indicator").text("Loading..").hide();
							console.log(data);
							if(data=='0'){
								$("ul.connectedSortable[data-type='"+cardStore[ref].status+"']").append($(".card[data-ref="+ref+"]"));
								console.log("Update failed: revert change");
							}else{
								systemStore.loaded = data;
								cardStore[ref].status = new_status;
							}
						});
					}
				}).disableSelection();
			 }else{
			 	document.getElementById('login').innerHTML = "<a href='javascript:auth();'>Login in interact</a>";

			 }
			 enscale();
		}	

		function renderUI(statuses,callback){
			var data,temp;
			for(var i in statuses){
				data = {}
				data.title = (typeof statuses[i].title == 'undefined') ? i : statuses[i].title;
				data.status = i;
				data.type = statuses[i].display;
				temp = tpl.template("sectionTPL", data);
				document.getElementById('card_container').appendChild(temp);
			}
			callback();
		}
		function checkConstraints(ref,status){
			var con = constraints.Statuses[status];
			var prev_status = cardStore[ref].status;

			if(typeof con.limit_drag_to != 'undefined'){
				if(con.limit_drag_to.indexOf(prev_status) === -1){
					warning("You can not move cards directlty to "+status);
					$("ul.connectedSortable[data-type='"+prev_status+"']").append($(".card[data-ref="+ref+"]"));
					return false;
				}else{
					console.log("Allow move, status is acceptable");
				}
			}
			if(typeof con.require != 'undefined'){
				infoDialog(con.require,ref);
			}

			return true;
		}

		function reload(product, sprint){

			$("#indicator").show();
			
			var spr = 'all'
			var pro = qp['product'];
			if(typeof sprint != 'undefined')spr = sprint;
			if(typeof product != 'undefined')pro = product;
			qp['product'] = pro;
			qp['sprint'] = spr;

			window.history.pushState({}, document.title , '?product='+encodeURIComponent(pro)+'&sprint='+spr);
			
			$.get('xhr/list?product='+encodeURIComponent(pro)+'&sprint='+spr, init);
		}

		function update(){
			console.log("Begin updated.");
			$.get('xhr/list?product='+qp['product']+'&sprint='+qp['sprint'], function(data){
				var d = JSON.parse(data);
				cardStore = d.data;
				systemStore.loaded = d.loaded;
				var t=10;

				 for(var i in cardStore){
					//renderCard(cardStore[i], $("ul.connectedSortable[data-type='"+cardStore[i].status+"']"));
					var card = $(".card[data-ref="+i+"]");

					if(card.parent().attr('data-type') != cardStore[i].status){
						var old = card.position();
						if(old != null){
							console.log("set interval for "+i);
							(function(card,i,old) {
								setTimeout(function(){
										$("ul.connectedSortable[data-type='"+cardStore[i].status+"']").prepend(card);
										var n = card.position();

										card.css('position','absolute').css('left',old.left).css('top',old.top);
										
										card.animate({left:n.left, top:n.top}, 800, function(){
											$(this).css('position','static');
										});
										
										console.log("Card "+i+' has moved. Updating list to reflect.')
								},t);
							})(card,i,old);
							t +=900;
						}
					}
			 	};

			 	console.log("Update completed.");
			});


		}
		function pollForChanges(){

			console.log("poll");
			$.get('xhr/lastchange', function(data){
				if(systemStore.loaded < data){
					update(); 
					console.log("Apply update: " + systemStore.loaded + " " + data);
				}
				setTimeout(function(){pollForChanges()},refresh_time);
			});
		}


		function renderCard(data, append){
				data.priority = (data.priority == null || data.priority =='') ? 'N/A' : data.priority;
		
				var card = $(tpl.template("cardTPL", data));
				card.click(cardFlip);
				$(append).append(card);
				
		}
		function renderSprintList(sprints){

			sprints.push('all');
			sprints.forEach(function(s){
				var a = document.createElement("a");
				a.setAttribute('href','javascript:reload("'+qp.product+'","'+s+'")');
				a.innerHTML = s;
				document.getElementById('sprints').appendChild(a);
				
			});
		}
		function renderProductList(products){
	
			products.forEach(function(p){
				var o = document.createElement("option");
				o.innerHTML = p;
				if(qp.product == p)o.setAttribute('selected','selected');
				document.getElementById('product_selector').appendChild(o);

			});
		}
		function auth(){
			$("#dialog").dialog();
		}

		function infoDialog(info,ref){

			var cur_data = cardStore[ref];

			var frm = $('<form class="card_data" onSubmit="return updateCard(this);"><input type="hidden" name="id" value="'+ref+'"/></form>');

			for(var i in info){
				console.log(cur_data);
				console.log(cur_data[i]);
				info[i].value = (typeof cur_data[info[i].id] == 'undefined') ? '' : cur_data[info[i].id];

				if(info[i].type=='textarea'){
					frm.append(tpl.template("<div> <span>{name}:</span> <textarea name='{id}'>{value}</textarea></div>", info[i]));
				}else{
					frm.append(tpl.template("<div> <span>{name}:</span> <input name='{id}' value='{value}'/></div>", info[i]));
				}
			}

			frm.append($("<input type='submit' value='Save' class='button' />"));
			
			$( "#info_dialog" ).html(frm);
			$( "#info_dialog" ).dialog({
				width: 340,
				modal: true,
				title: "Additional information required"
			});

		}

		function warning(msg){
			$("#warning_dialog div").text(msg);
			$( "#warning_dialog" ).dialog({
				height: 160,
				modal: true
			});
		}
		
		function auth_login(fo){
			
			$("#indicator").show();
			
			$.post("xhr/login", { "username": fo.elements['username'].value, "password": fo.elements['password'].value },function(data){
				reload();
				$("#dialog").dialog('close');
			});
			return false;
							
			

		}
		function updateCard(frm){
			$("#indicator").show();
			$("#info_dialog").dialog('close');
			$.post("xhr/updateCard", $(frm).serialize(), function(data){
					$("#indicator").hide();
			});
			return false;	
		}



		function auth_logout(){
			$.get('xhr/logout', function(){

				reload();
			});
		}

		
		function cardFlip(event){
				//read store
				var card = cardStore[$(this).attr("data-ref")];
				
				//Switch modes
				if($(this).attr("data-showtype")=='accept'){
					
					$(this).attr("data-showtype",'story');
					$(this).children(".inner").html(card.description)//Get description
					$(this).flip({direction:'lr',color:"#D9E5FA",speed:"3"});
				}else{
					
					$(this).attr("data-showtype",'accept');
					//Add message for empty criterial
					if(card.acceptance=='' || card.acceptance=='<div></div>')card.acceptance='Not provided';
					//get card acceptance
					$(this).children(".inner").html(card.acceptance);
					
					$(this).flip({direction:'rl',color:"#C8D97E",speed:"3"});
					
				}
			}
		

		