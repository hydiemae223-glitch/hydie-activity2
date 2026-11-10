<?php
require 'inc.php';
header('Location: ' . (is_logged_in() ? 'dashboard.php' : 'login.php'));
exit;
