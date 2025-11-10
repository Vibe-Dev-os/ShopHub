<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Destroy session and logout
session_unset();
session_destroy();

// Start new session for message
session_start();
set_message('success', 'You have been logged out successfully.');

redirect('login.php');
?>
