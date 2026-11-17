<?php
    include_once("config.php");


    if(isset($_POST['submit'])){
        $name = $_POST['name'];
        $email = $_POST['email'];
        $message = $_POST['message'];

        if (empty($name) || empty($email) || empty($message)) {
          echo "Empty values!";
      } else {
            $sql = "INSERT INTO reviews (name, email, message) VALUES (:name, :email, :message)";
            $insertSQL = $conn->prepare($sql);
            $insertSQL->bindParam(':name', $name);
            $insertSQL->bindParam(':email', $email);
            $insertSQL->bindParam(':message', $message);

            if ($insertSQL->execute()) {
                echo "<script>alert('Values added successfully!'); window.location.href='index1.php';</script>";
            } else {
                echo "<script>alert('Error adding values. Please try again.'); window.history.back();</script>";
            }
        }
    }
?>
