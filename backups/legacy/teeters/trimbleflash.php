<?php
  // This php section must go at the TOP of the page, before ANYTHING else
  $current_flash = 1;  // assign a unique number to each flash content
  $cookie_name = 'SAW_FLASH';
  $flash_already_shown = ($_COOKIE[$cookie_name] == $current_flash);
  if(!$flash_already_shown) {
    // cookie not set, set it now.  Show flash this time
    setcookie($cookie_name, $current_flash, time()+60*60*24*30);  // set cookie to expire in 30 days
  }
?>
<pre>
Page content before flash goes here.  (e.g. doctype, head, ...)<br />

<?php
  if(!$flash_already_shown) {
?>
< !--  flash goes here -- >
<?php
  } else {
?>
< !--  flash replacement goes here -- >
<?php
  }  // close out php if
?>

< !-- Rest of page content goes here -- >
<a href="javascript:replayFlash()">Replay Flash</a><br />
</pre>

<!-- Javascript to implement replay flash link. -->

<script type="text/javascript">
<!--

function replayFlash() {
  SetCookie('SAW_FLASH', -1);
  window.location.reload();
}

function SetCookie(cookieName,cookieValue,nDays) {
 var today = new Date();
 var expire = new Date();
 if (nDays==null || nDays==0) nDays=1;
 expire.setTime(today.getTime() + 3600000*24*nDays);
 document.cookie = cookieName+"="+escape(cookieValue)
                 + ";expires="+expire.toGMTString();
}

</script>
