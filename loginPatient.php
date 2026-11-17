<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Login | Medilab</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      color: #17324d;
      font-family: Arial, Helvetica, sans-serif;
      background: linear-gradient(135deg, rgba(15, 111, 201, 0.94), rgba(21, 149, 183, 0.9)), url("assets/img/about.jpg") center/cover;
    }

    .login-card {
      width: 100%;
      max-width: 460px;
      padding: 36px;
      background: rgba(255, 255, 255, 0.98);
      border: 1px solid rgba(255, 255, 255, 0.55);
      border-radius: 8px;
      box-shadow: 0 24px 70px rgba(13, 43, 73, 0.28);
    }

    .brand-mark {
      width: 58px;
      height: 58px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      background: linear-gradient(145deg, #0f6fc9, #1595b7);
      border-radius: 16px;
      box-shadow: 0 16px 35px rgba(15, 111, 201, 0.25);
      margin-bottom: 18px;
    }

    h1 {
      margin: 0 0 8px;
      font-size: 30px;
      font-weight: 800;
      letter-spacing: 0;
    }

    .intro {
      margin-bottom: 26px;
      color: #617487;
      line-height: 1.6;
    }

    .form-control {
      min-height: 48px;
      border: 1px solid #d8e4ee;
      border-radius: 8px;
    }

    .form-control:focus {
      border-color: #0f6fc9;
      box-shadow: 0 0 0 4px rgba(15, 111, 201, 0.12);
    }

    .btn-primary {
      min-height: 48px;
      border: 0;
      border-radius: 8px;
      font-weight: 700;
      background: linear-gradient(145deg, #0f6fc9, #1595b7);
      box-shadow: 0 14px 28px rgba(15, 111, 201, 0.24);
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 18px 34px rgba(15, 111, 201, 0.3);
    }

    a {
      color: #0f6fc9;
      font-weight: 700;
      text-decoration: none;
    }

    a:hover {
      color: #1595b7;
    }
  </style>
</head>
<body>
  <main class="login-card">
    <div class="brand-mark" aria-hidden="true">
      <i class="fa-solid fa-user"></i>
    </div>
    <h1>Patient login</h1>
    <p class="intro">Sign in to request appointments and view your Medilab patient experience.</p>

    <form action="loginLogicPatient.php" method="POST">
      <div class="mb-3">
        <label for="inputUsername" class="form-label">Username</label>
        <input type="text" class="form-control" id="inputUsername" placeholder="Enter your username" name="username" required>
      </div>
      <div class="mb-3">
        <label for="inputPassword" class="form-label">Password</label>
        <input type="password" class="form-control" id="inputPassword" placeholder="Enter your password" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit" name="submit">Login</button>
    </form>

    <p class="text-center mt-4 mb-0">New patient? <a href="register.php">Create an account</a></p>
  </main>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
