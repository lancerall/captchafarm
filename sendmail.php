<?php
require_once 'Swift-5.1.0/lib/swift_required.php';
require_once 'email_config.php';
date_default_timezone_set('America/Chicago');

// Create the Transport
$server = 'smtp.emailsrvr.com';

$transport = Swift_SmtpTransport::newInstance($server, 25)
  ->setUsername($email_username)
  ->setPassword($email_password)
  ;

// Create the Mailer using your created Transport
$mailer = Swift_Mailer::newInstance($transport);

// Create a message
$message = Swift_Message::newInstance('There be Captchas!')
  ->setFrom(array('captcha@xpanxion.com' => 'Captcha Monitor'))
  ->setTo(array('tbranstiter@xpanxion.com' => "Tyler Branstiter", 'asorensen@xpanxion.com' => 'Adam Sorensen', 'lrall@xpanxion.com' => "Lance Rall"))
  ->setBody('There are new captchas to solve. Please visit:
http://192.168.27.253/captchafarm/captcha-form.php?debug&cleanup')
  ;

// Send the message
$result = $mailer->send($message);

print $result;
?>