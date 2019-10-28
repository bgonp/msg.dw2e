<?php

require_once "../../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;

session_start();
$_SESSION['messages'] = [];

$mail = getMailObject();
$mail->Subject = $_POST['email_subject'];
$mail->MsgHTML( $_POST['email_body'] );
$addresses = explode( ',', $_POST['email_to'] );
foreach ($addresses as $address) {
	if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
		$mail->AddAddress(trim($address));
	} else {
		$_SESSION['messages'][] = "Error: wrong e-mail address ($address)";
		break;
	}

}
$sent = empty($_SESSION['messages']) ? $mail->Send() : false;

if ($sent){
	$_SESSION['messages'][] = "E-mail sent successfully";
	unset($_SESSION['email_to'], $_SESSION['email_subject'], $_SESSION['email_body']);
} else {
	if (!empty($mail->ErrorInfo) )
		$_SESSION['messages'][] = "Error: {$mail->ErrorInfo}";
	$_SESSION['email_to'] = $_POST['email_to'];
	$_SESSION['email_subject'] = $_POST['email_subject'];
	$_SESSION['email_body'] = $_POST['email_body'];
}

header('location: mailform.php');

function getMailObject(){
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->SMTPDebug  = 0;
	$mail->SMTPAuth   = true;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
	$mail->Host       = "smtp.gmail.com";
	$mail->Port       = 587;
	$mail->Username   = "rebololovers@gmail.com";
	$mail->Password   = "PechitosMcTetis";
	$mail->SetFrom('rebololovers@gmail.com', 'It\'s a me!' );
	return $mail;
}