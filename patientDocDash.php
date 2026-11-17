<?php

include_once("config.php");

if(empty($_SESSION['username'])){
   header("Location: login.php");
}

$sql = "SELECT * FROM doctors";
$prep = $conn->prepare($sql);
$prep->execute();
$users = $prep->fetchAll();

?>

<?php include('dashboardHeader2.php');?>
    <div class="container-fluid">
      <div class="row mt-4 pt-4">
         <div class="col-2 bg-light sidebar">
            <nav class="sidebar-sticky">
               <ul class="nav flex-column">
               <li class="nav-item">
                     <a href="dashboardPatient.php" class="nav-link active text-dark"><i class="fa-solid fa-house"></i> Dashboard</a>
                  </li>
                  <li class="nav-item">
                     <a href="patientDocDash.php" class="nav-link active text-dark"><i class="fa-solid fa-shop"></i> Doctors</a>
                  </li>
                  <li class="nav-item">
                     <a href="dashboardPatient.php" class="nav-link active text-dark"><i class="fa-solid fa-shop"></i> Patients</a>
                  </li>
               </ul>
            </div>
         </nav>

         <main role="main" class="col-10 ml-sm-auto px-4">
            <h1 class="h2">Doctors</h1>
            <table class="table">
               <thead class="table-light">
                  <tr>
                     <th>Id</th>
                     <th>Name</th>
                     <th>Lastname</th>
                     <th>Number</th>
                     <th>Username</th>
                     <th>Email</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach($users as $user){ ?>
                     <tr>
                        <td><?php echo $user['id'];?></td>
                        <td><?php echo $user['name'];?></td>
                        <td><?php echo $user['lastname'];?></td>
                        <td><?php echo $user['number'];?></td>
                        <td><?php echo $user['username'];?></td>
                        <td><?php echo $user['email'];?></td>
                        
                     </tr>
                     <?php } ?>
               </tbody>
            </table>
         </main>


      </div>
    </div>
<?php include('footer.php')?>


