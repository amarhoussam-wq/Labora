<?php

include_once("config.php");


if(isset($_POST['submit'])){
    $name = $_POST['name'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $diagnosis = $_POST['diagnosis'];
    $tempPassword = $_POST['password'];
    
    $password = password_hash($tempPassword, PASSWORD_DEFAULT);

    if(empty($name)|| empty($lastname) || empty($username) || empty($diagnosis) || empty($password)){
        echo "You need to fill all the fields.";
    }
    else{
        $sql ="SELECT username FROM patients WHERE username = :username";

        $tempSQL = $conn->prepare($sql);
        $tempSQL->bindParam(':username', $username);
        $tempSQL->execute();

        if($tempSQL->rowCount() > 0){
            echo "Username already exists!";
            header("refresh:2; url=register.php");
        }
        else{
            $sql = "INSERT INTO patients (name, lastname, username, diagnosis, password) VALUES
            (:name, :lastname, :username, :diagnosis, :password)";

            $insertSQL = $conn->prepare($sql);
            $insertSQL->bindParam( ':name', $name);
            $insertSQL->bindParam( ':lastname', $lastname);
            $insertSQL->bindParam( ':username', $username);
            $insertSQL->bindParam( ':diagnosis', $diagnosis);
            $insertSQL->bindParam( ':password', $password);

            $insertSQL->execute();

            echo "User added successfuly!";
            header("Location: loginPatient.php");

        }
    }
  } 


?>