<?php

include_once("config.php");

if(isset($_POST['submit'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    if(empty($username) || empty($password)){
        echo "Fill all the fields!";
        header("refresh:2; url=login.php");
    }
    else{
        $sql = "SELECT * FROM doctors WHERE username = :username";

        $selectSQL = $conn->prepare($sql);
        $selectSQL->bindParam(':username', $username);
        $selectSQL->execute();

        if($selectSQL->rowCount() > 0){
            $data = $selectSQL->fetch();
            if(password_verify($password, $data['password'])){
                $_SESSION['username'] = $data['username'];
                $_SESSION['id'] = $data['id'];
                $_SESSION['admin'] = "true";
                header("Location: dashboard.php");
            }
            else{
                echo "Password incorrect!";
                header("refresh:2; url=login.php");
            }
        }
        else{
            echo "User not found!";
            header("refresh:2; url=login.php");
        }
    }

}




?>