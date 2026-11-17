<?php


include_once("config.php");

if(empty($_SESSION['username'])){
    header("Location: login.php");
}

$id = $_GET['id'];

$sql = "DELETE FROM patients WHERE id = :id";
$prep = $conn->prepare($sql);
$prep->bindParam(":id", $id);
$prep->execute();

header("Location: dashboard.php");
    
?>