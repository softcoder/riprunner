<?php
$uname = 'rkissel';
$multiplier = 0;
$num = "";
$chars = str_split($uname);
foreach($chars as $char){
	$multiplier++;$num = ord($char)-96;$int .= $num;
}
$num = $int * $multiplier;
echo $num;
?>