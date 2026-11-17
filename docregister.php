<?php include("header.php"); ?>



<div class="container">
    <div class="row">
        <div class= "col-lg-4 col-12 offset-lg-4 offset-0 d-flex align-items-center vh-100">
            <form action="doctoradd.php" class="shadow p-5 rounded border" method="POST">
                <h1 class="mb-3">Sing up doctor</h1>
                <input type="text" class="form-control mb-3" id="inputName" placeholder="Name" name="name" required>
                <input type="text" class="form-control mb-3" id="inputLastname" placeholder="Lastname" name="lastname" required>
                <input type="text" class="form-control mb-3" id="inputLastname" placeholder="Number" name="number" required>
                <input type="text" class="form-control mb-3" id="inputUsername" placeholder="Username" name="username" required>
                <input type="eamil" class="form-control mb-3" id="inputDiagnosis" placeholder="Email" name="email" required>
                <input type="password" class="form-control mb-3" id="inputPassword" placeholder="Password" name="password">
                <button class="btn btn-success btn-lg" type="submit" name="submit">Sign up</button>
                <small>Already have an account? <a href="login.php">Log in</a></small>
            </form>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
