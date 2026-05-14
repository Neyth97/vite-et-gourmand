<?php
require_once '../PHP/includes/session.php';

session_destroy();
header('Location: /vite-et-gourmand/HTML/index.html');
exit;
