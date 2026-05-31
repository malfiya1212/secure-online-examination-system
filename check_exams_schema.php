<?php
include 'db_connect.php';
$res = $conn->query("DESCRIBE exams");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . "\n";
}
?>
