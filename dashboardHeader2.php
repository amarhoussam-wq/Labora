<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medilab Dashboard</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
  <body>
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top bg-dark fixed-md-nowrap shadow mb-5">
    <div class="container-fluid">
    <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="dashboard.php">
          Welcome, 
          <i><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></i>
         </a>
        <div class="ms-auto">
            <div class="dropdown bg-dark text-white">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="userpfp.png" alt="Profile" class="profile-img">
                </a>
                <ul class="dropdown-menu dropdown-menu-end bg-dark text-white" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item bg-dark text-white" href="editDoctors.php?id=<?php echo $_SESSION['id']; ?>">Profile</a></li>
                    <li><a class="dropdown-item bg-dark text-white" href="dashboard.php">Dashboard</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item bg-dark text-white" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>



