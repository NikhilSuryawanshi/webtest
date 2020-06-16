<!DOCTYPE html>
<html>
<body bgcolor="cyan">

<h1>Welcome to nik clouds</h1>

<pre>
<?php

print `ifconfig`;
$file = file_get_contents('url.txt');
echo $file;
echo '<img src="'.$file.'"  width="500" height="600">';
?>
</pre>


</body>
</html>
