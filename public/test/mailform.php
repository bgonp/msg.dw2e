<!DOCTYPE html>
<html>
<head>
	<title>Send an e-mail</title>
</head>
<body><?php
	session_start();
	if (!empty($_SESSION['messages'])) {
		echo '<p>'.implode('</p><p>', $_SESSION['messages']).'</p>';
		unset($_SESSION['messages']);
	} ?>
	<h2>Envío de e-mail</h2>
	<form method="post" action="mailform_action.php">
		<label for="email_to">To:</label><br>
		<input type="text" name="email_to" id="email_to" value="<?php echo $_SESSION['email_to'] ?? ""; ?>"><br>
		<label for="email_subject">Subject:</label><br>
		<input type="text" name="email_subject" id="email_subject" value="<?php echo $_SESSION['email_subject'] ?? ""; ?>"><br>
		<label for="email_body">Body:</label><br>
		<textarea name="email_body" id="email_body"><?php echo $_SESSION['email_body'] ?? ""; ?></textarea><br>
		<input type="submit" value="Enviar e-mail">
	</form>
</body>
</html>