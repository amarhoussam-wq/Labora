<?php
    include_once("config.php");

    if(isset($_POST['submit'])){
        $id = $_POST['id'];
        $name = $_POST['name'];
        $lastname = $_POST['lastname'];
        $number = $_POST['number'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password']; 

        if (!empty($password)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
            $passwordSql = ", password=:password";
        } else {
            $passwordSql = "";
        }

        $sql = "
            UPDATE doctors SET 
            name=:name, 
            lastname=:lastname, 
            number=:number,  
            username=:username,  
            email=:email 
            $passwordSql
            WHERE id=:id
        ";

        $prep = $conn->prepare($sql);

        $prep->bindParam(':id', $id);
        $prep->bindParam(':name', $name);
        $prep->bindParam(':lastname', $lastname);
        $prep->bindParam(':number', $number);
        $prep->bindParam(':username', $username); 
        $prep->bindParam(':email', $email);

        if (!empty($password)) {
            $prep->bindParam(':password', $password);
        }

        $prep->execute();

        header("Location: dashboard.php");
        exit;
    }
?>
