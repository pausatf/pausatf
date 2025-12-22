







        window.onload = function() {



               initializeMenu("xcMenu", "xcActuator");



               initializeMenu("tfMenu", "tfActuator");



               initializeMenu("infoMenu", "infoActuator");







			   initializeMenu("linksMenu", "linksActuator");



        }



 <!--



function MM_reloadPage(init) {  //reloads the window if Nav4 resized







  if (init==true) with (navigator) {if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {



    document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage; }}







  else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH) history.go(0);







}MM_reloadPage(true);//-->






Spry.Utils.addLoadListener(function() {
  // alert("Executing main javascript");  var saw_flash = getCookie('SAW_FLASH');  if(saw_flash) {     // alert("Found cookie");    // do nothing, just leave image in place  } else {    // alert("no cookie, setting it now");    SetCookie('SAW_FLASH', 1, 30);    InsertFlashMovie(); }function InsertFlashMovie(movieName) {  // from: http://blog.deconcept.com/swfobject/#howitworks  var fo = new FlashObject("/Flash/YouthFlash3.swf", "youthFlashMovie", "800", "271", 6, "#000000");  // fo.addParam("play", "false");  // fo.addParam('swliveconnect', "true");  fo.write("flashcontent");}// original object / embed// <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" height="271" width="800" id="youthFlashMovie">//<param name="movie" value="/Flash/YouthFlash3.swf">//<param name="quality" value="best">//<param name="play" value="false">//<embed height="271" pluginspage="http://www.macromedia.com/go/getflashplayer" src="/Flash/YouthFlash3.swf" type="application/x-shockwave-flash" width="800" quality="best" play="false" name="youthFlashMovie" swliveconnect="true">	//	</object>	function FoundUnload() { // do nothing. But unload event processing seems to be needed to force load when going to new page}function replayFlash() { SetCookie('SAW_FLASH',0,-1); window.location.reload();}function SetCookie(cookieName,cookieValue,nDays) { var today = new Date(); var expire = new Date(); if (nDays==null || nDays==0) nDays=1; expire.setTime(today.getTime() + 3600000*24*nDays); document.cookie = cookieName+"="+escape(cookieValue) + ";expires="+expire.toGMTString();}function getCookie(c_name){if (document.cookie.length>0) { c_start=document.cookie.indexOf(c_name + "=") if (c_start!=-1) { c_start=c_start + c_name.length+1 c_end=document.cookie.indexOf(";",c_start) if (c_end==-1) c_end=document.cookie.length return unescape(document.cookie.substring(c_start,c_end)) } }return ""}	

var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));

var pageTracker = _gat._getTracker("UA-2651783-1");pageTracker._initData();pageTracker._trackPageview();

});
