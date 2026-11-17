<?php
    include_once("config.php");

    if (isset($_GET['id'])) {
        $appointmentId = $_GET['id'];

        $sql = "UPDATE appointments SET status = 'Done' WHERE id = :id";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':id', $appointmentId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "Status updated to 'Done' for appointment ID: " . $appointmentId;
            header("Location: doctorDashboard.php");
        } else {
            echo "Error: Unable to update the status.";
        }
    } else {
        echo "Error: No appointment ID provided.";
    }
?>
