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
	
	private static function init() {
		if (!self::$mailer) {
			$conf = json_decode(file_get_contents(CONFIG_DIR.'email.json'));
			self::$mailer = new PHPMailer();
			self::$mailer->isSMTP();
			self::$mailer->SMTPDebug = 0;
			self::$mailer->SMTPAuth = true;
			self::$mailer->SMTPSecure = $conf->secure;
			self::$mailer->Host = $conf->host;
			self::$mailer->Port = $conf->port;
			self::$mailer->Username = $conf->user;
			self::$mailer->Password = $conf->password;
			self::$mailer->From = $conf->from;
			self::$mailer->FromName = 'DW2E Messaging System';
			self::$mailer->CharSet = 'UTF-8';
			self::$mailer->isHTML(true);
		} else {
			self::$mailer->clearAllRecipients();
		}
	}
	
}