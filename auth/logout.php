<?php
require_once dirname(__DIR__) . '/config.php';
logoutUser();
setFlash('info', 'تم تسجيل الخروج بنجاح.');
redirect('auth/login.php');
