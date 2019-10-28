<?php

function mcd( $a, $b ){
	for( $i = min($a,$b); $i > 0; $i-- )
		if( is_int($a/$i) && is_int($b/$i) ) break;
	return $i;
}

function is_prime( $a ){
	for( $i = intval($a/2); $i > 1; $i-- )
		if( is_int($a/$i) ) return false;
	return true;
}
