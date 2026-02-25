<?php
if ($_GET['randomId'] != "iCAKlVBQMdIMCv0NqVyqwm66_vN4DNR4PNw1t1inRKF8H3BJuJNYR0Y1K5KUMjL2") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
