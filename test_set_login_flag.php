<?php
session_start();

// Set the just_logged_in flag to simulate a fresh login
$_SESSION['just_logged_in'] = true;

echo "<h1>Login Flag Set</h1>";
echo "<p>The 'just_logged_in' session flag has been set.</p>";
echo "<p><a href='index.php'>Go to Index Page</a> - You should see the newsletter popup</p>";
echo "<p><a href='test_newsletter.php'>Check Newsletter Test Page</a></p>";
?>