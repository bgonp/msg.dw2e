<?php
/**
 * This file contains class MailController which handle email communications.
 * It requires PHPMailer to work.
 * @link https://github.com/PHPMailer/PHPMailer PHPMailer
 * 
 * @package controller
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Class with static functions to test SMTP email connection
 * and send emails.
 */
class MailController {

	/** @var PHPMailer Main email connection object */
	private static $mailer;

	/**
	 * Send an email using credentials stored in options.
	 * 
	 * @param  string $subject Subject of the email
	 * @param  string $body Body (html) of the email
	 * @param  string $to Single email address which will receive the email
	 * @return bool True if email could be sent or false if not
	 * @throws Exception If error occurred while sending email
	 */
	public static function send($subject, $body, $to) {
		try {
			self::init();
			self::$mailer->Subject = $subject;
			self::$mailer->Body = $body;
			self::$mailer->addAddress($to);
			return boolval(self::$mailer->send());
		} catch (Exception $e) {
			throw new Exception(Text::error("email_config"));
		}
	}

	/**
	 * Try to stablish connection with SMTP server with stored credentials.
	 * 
	 * @return bool True if connection was stablished correctly
	 * @throws Exception If error occurred while trying to connect
	 */
	public static function test() {
		try {
			self::init();
			return boolval(self::$mailer->smtpConnect());
		} catch (Exception $e) {
			throw new Exception(Text::error("email_config"));
		}
	}
	
	/**
	 * Init the SMTP connection with credentials stored in options.
	 * If a connection already exists, clear all recipents in order to
	 * be able to send a new email.
	 */
	private static function init() {
		if (!self::$mailer) {
			self::$mailer = new PHPMailer();
			self::$mailer->isSMTP();
			self::$mailer->SMTPDebug = SMTP::DEBUG_OFF;
			self::$mailer->SMTPAuth = true;
			self::$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			self::$mailer->Port = 587;
			self::$mailer->Host = Option::get('email_host');
			self::$mailer->Username = Option::get('email_user');
			self::$mailer->Password = Option::get('email_pass');
			self::$mailer->From = Option::get('email_from');
			self::$mailer->FromName = Option::get('email_name');
			self::$mailer->CharSet = PHPMailer::CHARSET_UTF8;
			self::$mailer->isHTML(true);
		} else {
			self::$mailer->clearAllRecipients();
		}
	}
	
}