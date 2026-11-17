<?php
include_once("config.php");

if($_SESSION['admin'] == "false"){
    header("Location: index1.php");
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $diagnosis = $_POST['diagnosis'];
    $tempPassword = $_POST['password'];

    $password = password_hash($tempPassword, PASSWORD_DEFAULT);

    if (empty($name) || empty($lastname) || empty($username) || empty($diagnosis) || empty($tempPassword)) {
        echo "<script>alert('You need to fill all the fields.'); window.history.back();</script>";
    } else {
        $sql = "INSERT INTO patients(name, lastname, username, diagnosis, password)
                VALUES (:name, :lastname, :username, :diagnosis, :password)";
        $insertSQL = $conn->prepare($sql);
        $insertSQL->bindParam(':name', $name);
        $insertSQL->bindParam(':lastname', $lastname);
        $insertSQL->bindParam(':username', $username);
        $insertSQL->bindParam(':diagnosis', $diagnosis);
        $insertSQL->bindParam(':password', $password);

        if ($insertSQL->execute()) {
            echo "<script>alert('Patient added successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error adding patient. Please try again.'); window.history.back();</script>";
        }
    }
}
?>

<?php include('dashboardHeader2.php');?>
<div class="container-fluid">
    <div class="row mt-4 pt-4">
    <?php include_once("sidebar.php"); ?>

        <main role="main" class="col-10 ml-sm-auto px-4 mt-4">
            <h1 class="h2">Add Patient</h1>
            <div class="container">
                <div class="row">
                    <div class="col-md-5">
                        <form action="" method="POST">
                            <input type="text" class="form-control mb-3" placeholder="Name" name="name">
                            <input type="text" class="form-control mb-3" placeholder="Lastname" name="lastname" >
                            <input type="text" class="form-control mb-3" placeholder="Username" name="username">
                            <input type="text" class="form-control mb-3" placeholder="Diagnosis" name="diagnosis">
                            <input type="password" class="form-control mb-3" placeholder="Password" name="password">
                            <button type="submit" name="submit" class="btn btn-lg btn-primary">Add</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include('footer.php');?>
