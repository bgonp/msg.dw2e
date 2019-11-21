<?php

use PHPMailer\PHPMailer\PHPMailer;

class MailController {

	private static $mailer;

	public static function send($subject, $body, $to) {
		self::init();
		self::$mailer->Subject = $subject;
		self::$mailer->Body = $body;
		self::$mailer->addAddress($to);
		return boolval(self::$mailer->send());
	}

	public static function test() {
		self::init();
		return boolval(self::$mailer->smtpConnect());
	}
	
	private static function init() {
		if (!self::$mailer) {
			self::$mailer = new PHPMailer();
			self::$mailer->isSMTP();
			self::$mailer->SMTPDebug = 0;
			self::$mailer->SMTPAuth = true;
			self::$mailer->SMTPSecure = 'tls';
			self::$mailer->Port = 587;
			self::$mailer->Host = Option::get('email_host');
			self::$mailer->Username = Option::get('email_user');
			self::$mailer->Password = Option::get('email_pass');
			self::$mailer->From = Option::get('email_from');
			self::$mailer->FromName = Option::get('email_name');
			self::$mailer->CharSet = 'UTF-8';
			self::$mailer->isHTML(true);
		} else {
			self::$mailer->clearAllRecipients();
		}
	}
	
}