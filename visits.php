<?php
include_once('config.php');


$id = $_GET['id'] ?? null;

if (empty($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$sql = "SELECT CONCAT(name, ' ', lastname) AS fullname FROM patients WHERE id = :id";
$prep = $conn->prepare($sql);
$prep->bindParam(":id", $id);
$prep->execute();
$patient = $prep->fetch();


$sql = "SELECT v.*, p.name, p.lastname FROM visits v LEFT JOIN patients p ON p.id = v.patient_id WHERE v.patient_id = :id";
$prep = $conn->prepare($sql);
$prep->bindParam(":id", $id);
$prep->execute();
$visits = $prep->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Visits</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    .rwd-table {
  margin: auto;
  min-width: 300px;
  max-width: 100%;
  border-collapse: collapse;
}

.rwd-table tr:first-child {
  border-top: none;
  background: #428bca;
  color: #fff;
}

.rwd-table tr {
  border-top: 1px solid #ddd;
  border-bottom: 1px solid #ddd;
  background-color: #f5f9fc;
}

.rwd-table tr:nth-child(odd):not(:first-child) {
  background-color: #ebf3f9;
}

.rwd-table th {
  display: none;
}

.rwd-table td {
  display: block;
}

.rwd-table td:first-child {
  margin-top: .5em;
}

.rwd-table td:last-child {
  margin-bottom: .5em;
}

.rwd-table td:before {
  content: attr(data-th) ": ";
  font-weight: bold;
  width: 120px;
  display: inline-block;
  color: #000;
}

.rwd-table th,
.rwd-table td {
  text-align: left;
}

.rwd-table {
  color: #333;
  border-radius: .4em;
  overflow: hidden;
}

.rwd-table tr {
  border-color: #bfbfbf;
}

.rwd-table th,
.rwd-table td {
  padding: .5em 1em;
}
@media screen and (max-width: 601px) {
  .rwd-table tr:nth-child(2) {
    border-top: none;
  }
}
@media screen and (min-width: 600px) {
  .rwd-table tr:hover:not(:first-child) {
    background-color: #d8e7f3;
  }
  .rwd-table td:before {
    display: none;
  }
  .rwd-table th,
  .rwd-table td {
    display: table-cell;
    padding: .25em .5em;
  }
  .rwd-table th:first-child,
  .rwd-table td:first-child {
    padding-left: 0;
  }
  .rwd-table th:last-child,
  .rwd-table td:last-child {
    padding-right: 0;
  }
  .rwd-table th,
  .rwd-table td {
    padding: 1em !important;
  }
}




@-webkit-keyframes leftRight {
  0%    { -webkit-transform: translateX(0)}
  25%   { -webkit-transform: translateX(-10px)}
  75%   { -webkit-transform: translateX(10px)}
  100%  { -webkit-transform: translateX(0)}
}
@keyframes leftRight {
  0%    { transform: translateX(0)}
  25%   { transform: translateX(-10px)}
  75%   { transform: translateX(10px)}
  100%  { transform: translateX(0)}
}

  </style>
</head>
<body>

<?php if ($_SESSION['admin'] == "false"): ?>

  <header id="header" class="header sticky-top">
    <div class="topbar d-flex align-items-center">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-envelope"><a href="mailto:care@medilab.com">care@medilab.com</a></i>
          <i class="bi bi-phone ms-4"><span>+1 558 955 4885</span></i>
        </div>
        <div class="social-links d-none d-md-flex align-items-center">
          <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
    </div>

    <div class="branding d-flex align-items-center">
      <div class="container d-flex align-items-center justify-content-between">
        <a href="index1.php" class="logo me-auto"><h1 class="sitename">Medilab</h1></a>
        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="#hero" class="active">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#departments">Departments</a></li>
            <li><a href="#doctors">Doctors</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><a href="index1.php" class="btn text-white px-3 py-1 border rounded-5" style="background-color:#2C92EB;">Back</a></li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
      </div>
    </div>
  </header>
<?php else: ?>
  
  <?php include_once("dashboardHeader2.php"); ?>
<?php endif; ?>

<div class="container-fluid">
  <div class="row">
    <?php if ($_SESSION['admin'] == "true"): ?>
      <?php include_once("sidebar.php"); ?>
    <?php endif; ?>

    <main role="main" class="col <?php echo ($_SESSION['admin'] == "true") ? 'ml-sm-auto col-lg-10 px-4' : 'col-12'; ?> mt-5">
      <h1 class="h2 text-center mt-4">Visits for <?php echo htmlspecialchars($patient['fullname'] ?? ''); ?></h1>
      
      <table class="rwd-table mt-4">
        <tbody>
          <tr>
            <th>Name</th>
            <th>LastName</th>
            <th>Anamnesis</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
            <th>Visit Date</th>
          </tr>
          <?php foreach ($visits as $visit): ?>
            <tr>
              <td data-th="Name"><?php echo htmlspecialchars($visit['name']); ?></td>
              <td data-th="Last Name"><?php echo htmlspecialchars($visit['lastname']); ?></td>
              <td data-th="Anamnesis"><?php echo htmlspecialchars($visit['anamnesis']); ?></td>
              <td data-th="Diagnosis"><?php echo htmlspecialchars($visit['diagnosis']); ?></td>
              <td data-th="Treatment"><?php echo htmlspecialchars($visit['treatment']); ?></td>
              <td data-th="Visit Date"><?php echo htmlspecialchars($visit['visitdate']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>
