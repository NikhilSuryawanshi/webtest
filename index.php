<!DOCTYPE html>
<html>
  <head>
    <title>Welcome to the Cloud </title>
  </head>
<body bgcolor="gray">

<h1>"Things do not happen. Things are made to happen !" </h1>

<pre>
<?php

print `ifconfig enp0s3`;
$file = file_get_contents('url.txt');
echo $file;
echo '<img src="'.$file.'"  width="500" height="600">';
?>
</pre>


</body>
</html>
