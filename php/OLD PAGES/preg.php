<?php

$CALLOUT_STREET_REWRITE = array(
		" EAGLE VIEW " => " EAGLEVIEW ",
		" EAGLE " => " HAWK "
		);

$callAddress="3275 EAGLE VIEW RD, SHELL-GLEN, BC";
echo "<br>Original: $callAddress<br>";

$callAddress = translate_arrays($callAddress,$CALLOUT_STREET_REWRITE);
	

echo "<br />Modified $callAddress<br />";

# -------------------------------------------------------------------------------------------------
function translate_arrays($callAddress,$CALLOUT_STREET_REWRITE) {
	return (preg_replace_callback("/ (\w+\s\w+) /i ", function($match) use ($CALLOUT_STREET_REWRITE) {
		if(isset($CALLOUT_STREET_REWRITE[$match[0]])){return ($CALLOUT_STREET_REWRITE[$match[0]]);}else{
			return($match[0]);
			}
		},  $callAddress));
}
# -------------------------------------------------------------------------------------------------

?>