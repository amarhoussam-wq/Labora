<?php
    include_once("config.php");

    $id = $_GET['id'];

    if(isset($_POST['submit'])){
        $visitdate = $_POST['visitdate'];
        $anamnesis = $_POST['anamnesis'];
        $diagnosis = $_POST['diagnosis'];
        $treatment = $_POST['treatment'];

        $sql = "INSERT INTO visits
        (visitdate, anamnesis, diagnosis, treatment, patient_id)
        VALUES (:visitdate, :anamnesis, :diagnosis, :treatment, :patient_id)";

        $insertMovie = $conn->prepare($sql);
        $insertMovie->bindParam(":visitdate", $visitdate);
        $insertMovie->bindParam(":anamnesis", $anamnesis);
        $insertMovie->bindParam(":diagnosis", $diagnosis);
        $insertMovie->bindParam(":treatment", $treatment);
        $insertMovie->bindParam(":patient_id", $id);
        $insertMovie->execute();

        header("Location: patientdashboard.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Movie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<?php include_once("dashboardHeader2.php"); ?>

    <div class="container-fluid">
        <div class="row mt-5">
            <?php include_once("sidebar.php"); ?>

            <div class="col-sm-12 col-md-10">
                <div class="containe">
                    <div class="row">
                        <div class="col">
                            <h2 class="mt-5">Add Visit</h2>
                            <form action="" method="POST">
                                <input type="text" name="anamnesis" class="form-control mb-3" placeholder="Anamnesis">
                                <input type="text" name="diagnosis" class="form-control mb-3" placeholder="Diagnosis">
                                <input type="text" name="treatment" class="form-control mb-3" placeholder="Treatment">
                                <input type="date" name="visitdate" class="form-control mb-3" placeholder="Visitdate">
                                <button type="submit" name="submit" class="btn btn-success">Add</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once("footer.php"); ?>
