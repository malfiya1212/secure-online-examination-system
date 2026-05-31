<?php
include 'db_connect.php';
log_cluster_event($conn, "User Logout", "User logged out from the system");
session_destroy();
header("location: login.html");
exit();
?>