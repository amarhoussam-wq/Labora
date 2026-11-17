<?php

include_once("config.php");


if(isset($_POST['submit'])){
    $name = $_POST['name'];
    $lastname = $_POST['lastname'];
    $number = $_POST['number'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $tempPassword = $_POST['password'];
    
    $password = password_hash($tempPassword, PASSWORD_DEFAULT);

    if(empty($name)|| empty($lastname) || empty($number) || empty($username) || empty($email) || empty($password)){
        echo "You need to fill all the fields.";
    }
    else{
        $sql ="SELECT username FROM doctors WHERE username = :username";

        $tempSQL = $conn->prepare($sql);
        $tempSQL->bindParam(':username', $username);
        $tempSQL->execute();

        if($tempSQL->rowCount() > 0){
            echo "Username already exists!";
            header("refresh:2; url=register.php");
        }
        else{
            $sql = "INSERT INTO doctors (name, lastname, number, username, email, password) VALUES
            (:name, :lastname, :number, :username, :email, :password)";

            $insertSQL = $conn->prepare($sql);
            $insertSQL->bindParam( ':name', $name);
            $insertSQL->bindParam( ':lastname', $lastname);
            $insertSQL->bindParam( ':number', $number);
            $insertSQL->bindParam( ':username', $username);
            $insertSQL->bindParam( ':email', $email);
            $insertSQL->bindParam( ':password', $password);
            $insertSQL->execute();

            echo "User added successfuly!";
            header("Location: login.php");

        }
    }
  } 


?>