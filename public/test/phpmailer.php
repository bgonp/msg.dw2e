<?php

require_once "../../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPDebug  = SMTP::DEBUG_SERVER;
$mail->SMTPAuth   = true;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Host       = "smtp.gmail.com";
$mail->Port       = 587;
$mail->Username   = "rebololovers@gmail.com";
$mail->Password   = "PechitosMcTetis";
$mail->SetFrom('rebololovers@gmail.com', 'TEST');
$mail->Subject    = "Correo de prueba";
$mail->MsgHTML('Prueba');
//$mail->addAttachment("empleado.xsd");
$mail->AddAddress("vayaustecondioh@gmail.com", "TEST");
//$resul = $mail->Send();

if(!$resul) {
  echo "Error" . $mail->ErrorInfo;
} else {
  echo "Sent";
}
