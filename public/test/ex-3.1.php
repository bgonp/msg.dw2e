<?php

if( !$_SERVER['REQUEST_METHOD'] == "POST" ) header("Location: ex-3.1.html");

$err = [];

if( !validAlias($_POST['alias']) )
	$err[] = 'Invalid alias';

if( !validPassword($_POST['password'], $_POST['password_repeat']) )
	$err[] = 'Invalid password';

if( !validEmail($_POST['email']) )
	$err[] = 'Invalid email';

if( !validAge($_POST['age']) )
	$err[] = 'Invalid age';

if( !validGender($_POST['gender']) )
	$err[] = 'Invalid gender';

if( !empty($_FILES['avatar']) && !($avatar = savedAvatar($_FILES['avatar'],$_POST['alias'])) )
	$err[] = 'Invalid avatar';

if( !empty($err) ) echo implode('<br>', $err);
else echo "<h3>Success</h3>
	<ul>
		<li><strong>Alias</strong>: {$_POST['alias']}</li>
		<li><strong>E-mail</strong>: {$_POST['email']}</li>
		<li><strong>Age</strong>: {$_POST['age']}</li>
		<li><strong>Gender</strong>: {$_POST['gender']}</li>
		<li><strong>Avatar</strong>: <img src='$avatar'></li>
	</ul>";

echo '<br><a href="ex-3.1.html">Volver</a>';

function validAlias( $alias ){
	$pattern = "/^[a-zA-Z].{7,14}$/";
	return preg_match( $pattern, $alias );
}

function validPassword( $password, $password_repeat ){
	if( $password !== $password_repeat ) return false;
	$pattern = "/^(?=.*[0-9]+)(?=.*[A-Z]+)(?=.*[a-z]+)(?=.*[^a-zA-Z0-9]+).{6,15}$/";
	return preg_match( $pattern, $password );
}

function validEmail( $email ){
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validAge( $age ){
	return is_numeric($age) && $age >= 18 && $age <= 150;
}

function validGender( $gender ){
	return in_array($gender, ['male','female','other']);
}

function savedAvatar( $avatar, $alias ){
	if( $avatar['size'] > 256 * 1024 ) return false;
	$extension = strtolower(end(explode('.',$avatar['name'])));
	if( !in_array($extension,['png','jpg']) ) return false;
	if( !move_uploaded_file($avatar['tmp_name'], "avatars/$alias.$extension") ) return false;
	return "avatars/$alias.$extension";
}
