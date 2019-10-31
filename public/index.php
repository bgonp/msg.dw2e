<?php

require_once "../autoload.php";

MainController::main();

/*

session_start();

require_once "../inc/autoload.php";

//$c = Chat::Get(1);
//$c->addUsuario(1);
//$c->addUsuario(2);
//$c = Chat::Get(2);
//$c->addUsuario(1);
//$c->addUsuario(2);
//$c->addUsuario(3);

if ($_SESSION['loggedin']){
	$u = Usuario::Get($_SESSION['usuario_id']);
	if ($_POST['action'] == 'cerrar_sesion' ){
		$_SESSION['loggedin'] = false;
		unset($_SESSION['usuario_id']);
	} else if ($_POST['action'] == 'agregar_contacto'){
		$u->addContacto($_POST['id_o_email']);
	} else if ($_POST['action'] == 'eliminar_contacto'){
		$u->removeContacto($_POST['contacto_id']);
	}
} else if ($_POST['action'] == 'registro'){
	$u = Usuario::New($_POST['email'], $_POST['nombre'], $_POST['password']);
} else if ($_POST['action'] == 'iniciar_sesion'){
	$u = Usuario::Get($_POST['email'], $_POST['password']);
	if ($u){
		$_SESSION['loggedin'] = true;
		$_SESSION['usuario_id'] = $u->id();
	}
}

?><!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body><?php if( isset($_SESSION['loggedin']) && $_SESSION['loggedin'] ) { ?>
	<h3>Hola, <?php echo $u->nombre(); ?></h3>
	<form method="post" action="">
		<input type="hidden" name="action" value="cerrar_sesion">
		<input type="submit" value="Cerrar sesión">
	</form>
	<fieldset>
		<legend>Añadir contacto</legend>
		<form method="post" action="">
			<input type="hidden" name="action" value="agregar_contacto">
			<input type="text" name="id_o_email" placeholder="ID o e-mail...">
			<input type="submit" value="Agregar">
		</form>
	</fieldset>
	<h3>Contactos</h3>
	<ul><?php
		foreach ($u->contactos() as $contacto) {
			echo "<li>{$contacto->nombre()}<form action='' method='post'>
					<input type='hidden' name='contacto_id' value='{$contacto->id()}'>
					<input type='hidden' name='action' value='eliminar_contacto'>
					<input type='submit' value='Eliminar'>
				</li>";
		}
	?></ul>
<?php } else { ?> 
	<fieldset>
		<legend>Registro</legend>
		<form method="post" action="">
			<input type="hidden" name="action" value="registro">
			<input type="text" name="nombre" placeholder="Nombre...">
			<input type="email" name="email" placeholder="E-mail...">
			<input type="password" name="password" placeholder="Contraseña...">
			<input type="submit" value="Registrarme">
		</form>
	</fieldset>
	<fieldset>
		<legend>Iniciar sesión</legend>
		<form method="post" action="">
			<input type="hidden" name="action" value="iniciar_sesion">
			<input type="email" name="email" placeholder="E-mail...">
			<input type="password" name="password" placeholder="Contraseña...">
			<input type="submit" value="Iniciar sesión">
		</form>
	</fieldset>
<?php } ?>
</body>
</html>