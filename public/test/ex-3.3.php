<?php

$langs = ["English","Español","Esperanto","Klingon"];
$trans = [
	"English" => [],
	"Español" => ["Change language" => "Cambiar idioma", "Select language" => "Cambiar idioma"],
	"Esperanto" => ["Change language" => "ŝanĝi lingvon", "Select language" => "Unuaranga lingvo"],
	"Klingon" => ["Change language" => "Hol choH", "Select language" => "Hol wIv"],
];

if( isset($_POST['lang']) && in_array($_POST['lang'], $langs) ){
	$lang = $_POST['lang'];
	setcookie('lang', $lang, time() + 3600*24);
} else {
	$lang = $_COOKIE['lang'] ?? "English";
}

?><!DOCTYPE html>
<html>
<head>
	<title><?php echo strtr("Change language", $trans[$lang]); ?></title>
</head>
<body>
	<h1><?php echo strtr("Change language", $trans[$lang]); ?></h1>
	<form action="" method="post">
		<label><?php echo strtr("Selecct language", $trans[$lang]);	?><select name="lang">
			<option></option><?php
			foreach ($langs as $option) {
				$selected = $lang == $option ? " selected" : "";
				echo "<option$selected>$option</option>";
			} ?>
		</select></label>
		<input type="submit">
	</form>
</body>
</html>