<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Destroy session
session_destroy();

// Redirect to login
redirect('login.php');

