<!DOCTYPE html>
<html>
  <head>
    <title>Welcome to the Cloud </title>
  </head>
<body>

<h1>"Things do not happen. Things are made to happen !" </h1>

<pre>
<?php

$file = file_get_contents('url.txt');

echo '<img src="'.$file.'"  width="500" height="600">';
?>
</pre>


</body>
</html>
