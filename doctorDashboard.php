<?php
   include_once("config.php");

   if (empty($_SESSION['username'])) {
      header("Location: login.php");
   }

   if($_SESSION['admin'] == "false"){
    header("Location: index1.php");
    }
   $appointmentsPerPage = 3;

   $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

   $sqlCount = "SELECT COUNT(*) FROM appointments";
   $prepCount = $conn->prepare($sqlCount);
   $prepCount->execute();
   $totalAppointments = $prepCount->fetchColumn();

   $sql = "SELECT * FROM appointments WHERE status = 'Pending' ORDER BY date, time";
   $prep = $conn->prepare($sql);
   $prep->execute();
   $appointments = $prep->fetchAll(PDO::FETCH_ASSOC);

   $groupedAppointments = [
      'Sunday' => [],
      'Monday' => [],
      'Tuesday' => [],
      'Wednesday' => [],
      'Thursday' => [],
      'Friday' => [],
      'Saturday' => []
   ];


   foreach ($appointments as $appointment) {
      $date = new DateTime($appointment['date']);
      $formattedDate = $date->format('d.m.Y');
      $dayOfWeek = $date->format('l');
      $groupedAppointments[$dayOfWeek][] = $appointment;
   }

   $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
   $totalPages = ceil($totalAppointments / $appointmentsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="style.css" rel="stylesheet">
    <title>Medilab Appointments</title>
</head>
<body>
<?php include('dashboardHeader2.php'); ?>
    <div class="container-fluid">
        <div class="row mt-4 pt-4">
        <?php include_once("sidebar.php"); ?>
            <main role="main" class="col-12 col-md-10 ml-sm-auto px-4 mt-4">
                <h1 class="h2">Appointments</h1>
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="schedule-table">
                                <table class="table bg-white">
                                    <thead></thead>
                                    <tbody>
                                        <?php
                                        foreach ($days as $day) {
                                            echo "<tr>";
                                            echo "<td class='day'>$day</td>";

                                            $appointmentsForDay = $groupedAppointments[$day];
                                            $totalForDay = count($appointmentsForDay);

                                            $start = ($page - 1) * $appointmentsPerPage;
                                            $appointmentsForDayPage = array_slice($appointmentsForDay, $start, $appointmentsPerPage);

                                            foreach ($appointmentsForDayPage as $appointment) {
                                                $appointmentDate = (new DateTime($appointment['date']))->format('d.m.Y');
                                                echo "<td class='active'>
                                                          <h4>" . htmlspecialchars($appointment['name']) . "</h4>
                                                          <p>" . htmlspecialchars($appointment['email']) . "</p>
                                                          <div class='hover'>
                                                              <h4>" . htmlspecialchars($appointmentDate) . "</h4>
                                                              <p>"  . htmlspecialchars($appointment['time']) . "</p>
                                                              <a href='markApponintmentAsDone.php?id=". htmlspecialchars($appointment['id']) ."' class='btn btn-success'>Mark as done</a>
                                                          </div>
                                                      </td>";
                                            }

                                            for ($i = count($appointmentsForDayPage); $i < 3; $i++) {
                                                echo "<td class='active'></td>";
                                            }

                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-primary mx-5">Previous</a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-primary">Next</a>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <?php include('footer.php') ?>
</body>
</html>
