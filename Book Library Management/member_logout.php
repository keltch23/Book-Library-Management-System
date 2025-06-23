<?php
// filepath: c:\xampp\htdocs\Book Library Management\member_logout.php
session_start();
session_destroy();
header("Location: member_login.php");
exit;