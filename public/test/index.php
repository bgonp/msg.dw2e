<?php

require "ex-5-6.php";

echo $_GET['p'].(is_prime( $_GET['p'] ) ? " SI" : " NO")." es primo<br>";

echo "MCD de {$_GET['a']} y {$_GET['b']}: ".mcd($_GET['a'], $_GET['b']);

/*require_once "mymath.php";

$r = ecuacion($_GET['a'], $_GET['b'], $_GET['c']);
echo $r ? $r[0]." y ".$r[1] : "sin soluciones";

$n = intval($_GET['n']);
$f = factorial($n);
echo "El factorial de $n es $f";

$n = intval($GET['p']);
$p = mypow($n);
echo "El pow de $n es $p";*/