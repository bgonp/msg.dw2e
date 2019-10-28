<?php

function factorial( $n ){
	if( $n < 0 ) return -1;
	$f = 1;
	for( $i = $n; $i > 1; $i-- ) $f *= $i;
	return $f;
}

function ecuacion($a, $b, $c){
	$sq = sqrt($b**2-4*$a*$c);
	return $sq < 0 ? false : [
		($b*(-1)+$sq)/(2*$a),
		($b*(-1)-$sq)/(2*$a)
	];
}

function mypow( $base, $exp = 2 ){
	return $base**$exp;
}