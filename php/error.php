<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

$error = filter_input(INPUT_GET, 'err', $filter = FILTER_SANITIZE_STRING);
 
if ($error === false) {
    $error = 'Oops! An unknown error happened.';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Secure Login: Error</title>
        <link rel="stylesheet" href="styles/main.css" />
    </head>
    <body>
        <h1>There was a problem</h1>
        <p class="error"><?php echo $error; ?></p>  
    </body>
</html>
