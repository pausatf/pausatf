<?php
  error_reporting(E_ALL);    
  function test_mail() {
    ini_set('sendmail_from','admin@pausatf.org');
    $to = 'jeffteeters@yahoo.com, jeff@teeters.us'; // iztikt@yahoo.com, ';
    $cc = ''; // 'jeff@teeters.us, jeffteeters@yahoo.com';
    $from = 'admin@pausatf.org';
    $n = rand();
    $subject = "Test email id $n";
    $message = "This is a test message, id $n.\nHope this works.";
    $result = send_mail($to, $subject, $message, $from, $cc);
    echo "<pre>\nmessage sent\n$result</pre>\n";
  }

  test_mail();

  function send_mail($to, $subject, $message, $from, $cc='', $html=false, $test_mode=false) {
    // To send HTML mail, the Content-type header must be set
    $eol = "\r\n";
    $headers = '';
    $headers .= "From: $from" . $eol
         . "Reply-To: $from" . $eol;
    if($cc) {
        $headers .= "Cc: $cc" . $eol;
        // $to .= "," . $cc;
    }
    if($html) {
      $headers .= 'MIME-Version: 1.0' . $eol;
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . $eol;
    }
    $headers .= "X-Mailer: PHP/" . phpversion() . $eol;
    $log = "To: $to\nSubject: $subject\n$headers\n$message";
    if(!$test_mode) {
      // $log = str_replace($eol, "<br />\n", $log);
      // } else {
      $sent = mail($to, $subject, $message, $headers);
      if(!$sent) {
        die("<pre>error sending mail:\n$log\n</pre>");
      }
    }
    return($log);
  }
?>
