<?php
session_start();
$loginMessage = '';
$signupMessage = '';
if (isset($_GET['login']) && $_GET['login'] === 'failed') {
    $loginMessage = "Incorrect username or password. Please try again.";
}
if (isset($_SESSION['signupMessage'])) {
    $signupMessage = $_SESSION['signupMessage'];
    unset($_SESSION['signupMessage']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <title>Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      font-family: 'Inter', Arial, sans-serif;
      display: flex;
      background: #f5f6fa;
    }
    .login-split {
      display: flex;
      width: 100vw;
      height: 100vh;
    }
    .login-image-side {
      flex: 1.2;
      background: url('img/shelf.jpg') no-repeat center center;
      background-size: cover;
      position: relative;
      min-width: 0;
    }
    .login-image-side::after {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(245, 246, 250, 0.1);
    }
    .login-form-side {
      flex: 1;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 340px;
    }
    .login-container {
      width: 100%;
      max-width: 370px;
      margin: 0 auto;
      padding: 40px 32px 32px 32px;
      background: #fff;
      border-radius: 0 18px 18px 0;
      box-shadow: 0 8px 32px rgba(0,0,0,0.10);
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .login-logo {
      font-size: 2em;
      font-weight: 700;
      color: #0072ff;
      margin-bottom: 18px;
      letter-spacing: 2px;
    }
    .login-container h2 {
      margin-bottom: 18px;
      color: #222;
      font-size: 1.6em;
      font-weight: 700;
      letter-spacing: 1px;
    }
    .login-container form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      width: 100%;
    }
    .login-container input {
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1em;
      background: #f5f6fa;
      color: #222;
      outline: none;
      transition: border 0.2s;
    }
    .login-container input:focus {
      border: 1.5px solid #0072ff;
    }
    .login-container button {
      padding: 12px;
      background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%);
      border: none;
      border-radius: 8px;
      color: #fff;
      font-size: 1.1em;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.2s;
    }
    .login-container button:hover {
      background: linear-gradient(90deg, #0072ff 0%, #00c6ff 100%);
    }
    .signup {
      margin-top: 18px;
      font-size: 0.98em;
      color: #555;
    }
    .signup a {
      color: #0072ff;
      text-decoration: none;
      font-weight: 600;
    }
    .signup a:hover {
      text-decoration: underline;
    }
    @media (max-width: 900px) {
      .login-split {
        flex-direction: column;
      }
      .login-image-side {
        min-height: 180px;
        height: 220px;
        border-radius: 0 0 18px 18px;
      }
      .login-form-side {
        min-width: unset;
        border-radius: 0 0 18px 18px;
      }
      .login-container {
        border-radius: 0 0 18px 18px;
      }
    }
    @media (max-width: 600px) {
      .login-container {
        padding: 24px 8px;
        max-width: 98vw;
      }
    }
  </style>
</head>
<body class="bg-light">
  <div class="login-split">
    <div class="login-image-side"></div>
    <div class="login-form-side">
      <div class="login-container">
         <img src="img/key.png" alt="Log in" style="width: 75px; height: 75px; vertical-align: middle;" />
        <div class="login-logo">VF BLMS</div>
        <h2>Admin Login</h2>
        <?php if (!empty($loginMessage)): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo $loginMessage; ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($signupMessage)): ?>
          <div>
            <?php echo $signupMessage; ?>
          </div>
        <?php endif; ?>
        <form action="admin_login_process.php" method="POST">
          <div class="mb-3">
              <input type="text" class="form-control" name="username" placeholder="Username" required>
          </div>
          <div class="mb-3">
              <input type="password" class="form-control" name="password" placeholder="Password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Log In</button>
        </form>
      </div>
    </div>
  </div>
  <script>
    // Removed modal logic
  </script>
</body>
</html>
