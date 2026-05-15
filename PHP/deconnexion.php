<?php
require_once '../PHP/includes/session.php';

session_destroy();
header('Location: /HTML/index.html');
exit;
