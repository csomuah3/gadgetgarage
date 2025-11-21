<?php
echo "Hello World - PHP is working!";
echo "<br>Current directory: " . __DIR__;
echo "<br>Files in directory:";
echo "<pre>";
print_r(scandir(__DIR__));
echo "</pre>";
?>