<?php
include 'koneksi.php';

$data=[];
$res=$conn->query("SELECT * FROM lapangan");

while($row=$res->fetch_assoc()){
$data[]=$row;
}

echo json_encode($data);
?>