<?php
    include_once("config.php");

    if(isset($_POST['submit'])){
        $id = $_POST['id'];
        $name = $_POST['name'];
        $lastname = $_POST['lastname'];
        $username = $_POST['username'];
        $diagnosis = $_POST['diagnosis'];

        $sql = "
            UPDATE patients SET 
            name=:name, lastname=:lastname, diagnosis=:diagnosis, username=:username WHERE id=:id
        ";
        $prep = $conn->prepare($sql);

        $prep->bindParam(':id', $id);
        $prep->bindParam(':name', $name);
        $prep->bindParam(':lastname', $lastname);
        $prep->bindParam(':username', $username);
        $prep->bindParam(':diagnosis', $diagnosis);
        $prep->execute();

        header("Location: dashboard.php");
    }
?>