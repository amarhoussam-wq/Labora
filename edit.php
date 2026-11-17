<?php
    include_once("config.php");

    if(empty($_SESSION['username'])){
        header("Location: login.php");
    }

    $id = $_GET['id'];

    // Use the correct SQL query with the named placeholder
    $sql = "SELECT * FROM patients WHERE id = :id";
    $prep = $conn->prepare($sql);
    $prep->bindParam(":id", $id);
    $prep->execute();

    $user = $prep->fetch();
?>

<?php include('dashboardHeader2.php');?>

<div class="container-fluid">
    <div class="row mt-4 pt-4">
        <?php include('sidebar.php');?>

        <main role="main" class="col-10 ml-sm-auto px-4 mt-4">
            <h1 class="h2">Edit User</h1>
            <div class="container">
                <div class="row">
                    <div class="col-md-5">
                        <form action="update.php" method="POST">
                            <!-- Note the change in name values from 'id' to other fields -->
                            <input type="number" class="form-control mb-3" placeholder="ID" name="id"
                            value="<?php echo $user['id']; ?>" readonly>
                            <input type="text" class="form-control mb-3" placeholder="Name" name="name"
                            value="<?php echo $user['name']; ?>">
                            <input type="text" class="form-control mb-3" placeholder="Lastname" name="lastname"
                            value="<?php echo $user['lastname']; ?>">
                            <input type="username" class="form-control mb-3" placeholder="Username" name="username"
                            value="<?php echo $user['username']; ?>">
                            <input type="text" class="form-control mb-3" placeholder="Diagnosis" name="diagnosis"
                            value="<?php echo $user['diagnosis']; ?>">
                            <button type="submit" name="submit" class="btn btn-lg btn-primary">Edit</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include('footer.php');?>
