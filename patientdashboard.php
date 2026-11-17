<?php

include_once('config.php');

if(empty($_SESSION['username'])){
    header("Location: login.php");
}
if($_SESSION['admin'] == "false"){
   header("Location: index1.php");
}
$sql = "SELECT * FROM patients";
$prep = $conn->prepare($sql);
$prep->execute();
$users = $prep->fetchAll();


include("dashboardHeader2.php");
?>

<div class="container-fluid">
      <div class="row mt-4 pt-4">  <?php include_once("sidebar.php"); ?>
    

         <main role="main" class="col-12 col-md-10 ml-sm-auto px-4 mt-4">
            <h1 class="h2">Patients</h1>
            <a href="addPatient.php" class="btn btn-primary my-3">
               <i class="fa-solid fa-plus"></i>Add Patient
            </a>
            <table class="table">
               <thead class="table-light">
                  <tr>
                     <th>Id</th>
                     <th>Name</th>
                     <th>LastName</th>
                     <th>Username</th>
                     <th>Diagnosis</th>
                     <th>Actions</th>
                     <!-- <th>Action</th> -->
                  </tr>
               </thead>
               <tbody>
                  <?php foreach($users as $user){ ?>
                     <tr>
                        <td><?php echo $user['id'];?></td>
                        <td><?php echo $user['name'];?></td>
                        <td><?php echo $user['lastname'];?></td>
                        <td><?php echo $user['username'];?></td>
                        <td><?php echo $user['diagnosis'];?></td>
                         <td><?php echo "
                        <a class='btn btn-primary' href='addvisits.php?id=$user[id]'> Add visit </a> 
                        <a class='btn btn-primary' href='visits.php?id=$user[id]'> visits </a>"
                        ?>
                        </td>
                     </tr>
                     <?php } ?>
               </tbody>
            </table>
         </main>


      </div>
    </div>

<?php include("footer.php")?>