<?php
require 'inc.php';
$_SESSION = [];
session_destroy();
session_start();
flash('You have been logged out.');
header('Location: login.php');
exit;
