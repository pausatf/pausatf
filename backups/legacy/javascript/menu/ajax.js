var div = document.getElementById('science-of-beauty');
var globalEvents = {
	start:function(type, args){
		div.innerHTML = "<img id='loader-holder' src='images/rel_interstitial_loading.gif' />";
	},
	success:function(type, args){
		if(args[0].responseText !== undefined){			
			div.innerHTML = args[0].responseText;		
		}
	},
	failure:function(type, args){
		div.innerHTML = "<div>Sending unsuccessful. Please try again.</div>";
	},
	abort:function(type, args){
		div.innerHTML = "<div>Sending aborted. Please try again.</div>";
	}
};
YAHOO.util.Connect.startEvent.subscribe(globalEvents.start);
YAHOO.util.Connect.successEvent.subscribe(globalEvents.success);
YAHOO.util.Connect.failureEvent.subscribe(globalEvents.failure);
YAHOO.util.Connect.abortEvent.subscribe(globalEvents.abort);

var links = YAHOO.util.Dom.getElementsByClassName("sub");
var els = YAHOO.util.Dom.getElementsByClassName('cate', 'span');
var noscript = YAHOO.util.Dom.getElementsByClassName('noscript', 'a');
var callback = { argument:["status","success"] };
function makeRequest(element){
	for (var i = 0; i < els.length; i++) {
		YAHOO.util.Dom.setStyle(els[i], 'borderLeftColor', '#FFFFFF');  
		YAHOO.util.Dom.setStyle(els[i], 'borderRightColor', '#FFFFFF');
	}	
	for (var i = 0; i < links.length; i++) {	
		if (YAHOO.util.Dom.hasClass(element, 'sub') == true){
			var dd = YAHOO.util.Dom.getAncestorByTagName(element, 'dd');
			var dt = YAHOO.util.Dom.getPreviousSibling(dd);
			var span = YAHOO.util.Dom.getChildren(dt);
			YAHOO.util.Dom.setStyle(span, 'borderLeftColor', '#E2BBB6');
			YAHOO.util.Dom.setStyle(span, 'borderRightColor', '#E2BBB6');
		}
		YAHOO.util.Dom.setStyle(YAHOO.util.Dom.getChildren(links[i]), 'color', '#666666');
	}
	if (YAHOO.util.Dom.hasClass(element, 'p-cate') == false){
		var sUrl = 'includes/get-content.php';
		var postData = 'withtab=no&article_id='+element.id;
		var request = YAHOO.util.Connect.asyncRequest('POST', sUrl, callback, postData);
	} 
	if (YAHOO.util.Dom.hasClass(element, 'cate') == false) { 
		YAHOO.util.Dom.setStyle(YAHOO.util.Dom.getChildren(element), 'color', '#999999');
	} else {
		YAHOO.util.Dom.setStyle(element, 'borderLeftColor', '#E2BBB6');  
		YAHOO.util.Dom.setStyle(element, 'borderRightColor', '#E2BBB6');
	}
}

//A function that pops up an alert and
//prevents the default behavior of the event
//for which it is a handler:
var interceptLink = function(e) {
	YAHOO.util.Event.preventDefault(e);
	// alert("You clicked on the second YUI link.");
}

//subscribe our interceptLink function
//as a click handler for the second anchor
//element:


for (var i = 0; i < noscript.length; i++) { 
YAHOO.util.Event.addListener(noscript[i], "click", interceptLink);
}
