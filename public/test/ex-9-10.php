<?php
/*
Write a function that receives a string and checks if it is a valid registration plate number.
Write a function that receives a string and checks if it is a valid password:
- Length between 6 and 15 characters.
- At least one digit.
- At least one uppercase letter.
- At least one lowercase letter.
- At least one non alphanumeric character.
*/

function plate( $plate_number ){
	$pattern = "/^[0-9]{4}(?=[A-Z]{3})[^AEIOUQ]{3}$/";
	return preg_match( $pattern, $plate_number );
}

function password( $password ){
	$pattern = "/^(?=.*[0-9]+)(?=.*[A-Z]+)(?=.*[a-z]+)(?=.*[^a-zA-Z0-9]+).{6,15}$/";
	return preg_match( $pattern, $plate_number );
}