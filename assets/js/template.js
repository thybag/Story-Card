 /**
 * TPL provides an ultra light weight, super simple method for quickly templating elements of HTML in javascript.
 * @author Carl Saggs
 * @version 0.1
 * @source https://github.com/thybag/base.js
 */
 (function(){
	/**
	 * template
	 * Template a HTML string by replacing {attributes} with the corisponding value in a js Object.
	 * @param String (Raw HTML or ID of template node)
	 * @param JS Object
	 * @return NodeList|Node
	 */
	this.template = function(tpl, data){
		//Find out if ID was provided, get html to template by id if so.  (else assume have been given raw html)
		var el = document.getElementById(tpl);
		if(el != null){
			tpl = document.getElementById(tpl).innerHTML;
		}
		//transform result in to DOM node(s)
		var tmp = document.createElement("div");
		tmp.innerHTML = this.replaceAttr(tpl,data);
		var results = tmp.children;
		if(tmp.children.length==1)results = tmp.children[0];//if only one node, return as individual result
		return results;
	}
	/**
	 * replaceAttr
	 * Replace all {attribute} with the corisponding value in a js Object.
	 * @param String (raw HTML)
	 * @return String (raw HTML)
	 */
	this.replaceAttr = function(tpl, data, prefix){
		//Foreach data value
		for(var i in data){
			//Used for higher depth items
			var accessor = i;
			if(typeof prefix != 'undefined') i = prefix+'.'+i
			//If object, recusivly call self, else template value.
			if(typeof data[i] == 'object'){
				tpl = this.replaceAttr(tpl,data[i],i);
			}else{
				tpl = tpl.replace(new RegExp('{'+i+'}','g'),data[accessor]);
			}
		}
		//return templated HTML
		return tpl;
	}
	//Add tpl to global scope
	window.tpl = this;
}).call({});