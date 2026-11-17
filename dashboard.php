<?php
require_once 'config.php';
if (!isLoggedIn()) {
    redirect('auth/login.php');
}
if (currentUserRole() === 'lab_manager') {
    redirect('lab/dashboard.php');
}
redirect('patient/dashboard.php');
