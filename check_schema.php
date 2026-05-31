<?php
include 'db_connect.php';
$res = $conn->query("DESCRIBE questions");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
