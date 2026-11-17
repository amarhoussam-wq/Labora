<?php
    include_once("config.php");

    if(empty($_SESSION['username'])){
        header("Location: login.php");
    }

    $id = $_GET['id'];

    // Use the correct SQL query with the named placeholder
    $sql = "SELECT * FROM doctors WHERE id = :id";
    $prep = $conn->prepare($sql);
    $prep->bindParam(":id", $id);
    $prep->execute();

    
    $userDoc = $prep->fetch();
    echo json_encode($userDoc);

?>


<?php include('dashboardHeader2.php');?>

<div class="container-fluid">
    <div class="row mt-4 pt-4">
        <?php include('sidebar.php');?>

        <main role="main" class="col-10 ml-sm-auto px-4 mt-4">
            <h1 class="h2">Edit Profile</h1>
            <div class="container">
                <div class="row">
                    <div class="col-md-5">
                        <form action="updateDoctors.php" method="POST">
                            <!-- Note the change in name values from 'id' to other fields -->
                            <input type="number" class="form-control mb-3" placeholder="ID" name="id"
                            value="<?php echo $userDoc['id']; ?>" readonly>
                            <input type="text" class="form-control mb-3" placeholder="Name" name="name"
                            value="<?php echo $userDoc['name']; ?>">
                            <input type="text" class="form-control mb-3" placeholder="Lastname" name="lastname"
                            value="<?php echo $userDoc['lastname']; ?>">
                            <input type="text" class="form-control mb-3" placeholder="Number" name="number"
                            value="<?php echo $userDoc['number']; ?>">
                            <input type="username" class="form-control mb-3" placeholder="Username" name="username"
                            value="<?php echo $userDoc['username']; ?>">
                            <input type="text" class="form-control mb-3" placeholder="Email" name="email"
                            value="<?php echo $userDoc['email']; ?>">
                            <input type="text" class="form-control mb-3" placeholder="Password" name="password"
                            value="">
                            <button type="submit" name="submit" class="btn btn-lg btn-primary">Edit</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include('footer.php');?>
