<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Library Portal Selection</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      min-height: 100vh;
      margin: 0;
      font-family: 'Inter', Arial, sans-serif;
      /* Use a different background image for portal selection */
      background: url('img/office.jpg') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .portal-container {
      background: rgba(255, 255, 255, 0.768);
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      max-width: 540px;
      width: 95%;
      margin: 40px auto;
      padding: 36px 32px 32px 32px;
      text-align: center;
    }
    .portal-logo {
      width: 90px;
      margin-bottom: 10px;
    }
    .portal-title {
      font-size: 2.1rem;
      font-weight: 700;
      color: #0072ff;
      letter-spacing: 2px;
      margin-bottom: 6px;
    }
    .portal-subtitle {
      font-size: 1.1rem;
      color: #888;
      margin-bottom: 18px;
      letter-spacing: 2px;
    }
    .portal-desc {
      color: #444;
      margin-bottom: 28px;
      font-size: 1.05rem;
    }
    .portal-options {
      display: flex;
      gap: 24px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .portal-card {
      background: rgba(255, 255, 255, 0.689); /* More transparent white */
      border-radius: 10px;
      box-shadow: 0 2px 8px #0001;
      padding: 24px 20px 20px 20px;
      min-width: 210px;
      max-width: 240px;
      flex: 1 1 210px;
      margin-bottom: 10px;
      transition: box-shadow 0.2s;
      /* Optional: add a subtle border for visibility */
      border: 1px solid rgba(0,0,0,0.07);
    }
    .portal-card:hover {
      box-shadow: 0 4px 16px #0072ff33;
    }
    .portal-card h3 {
      margin: 0 0 10px 0;
      font-size: 1.15rem;
      color: #0072ff;
      font-weight: 600;
    }
    .portal-card p {
      color: #555;
      font-size: 0.98rem;
      margin-bottom: 18px;
    }
    .portal-card button {
      background: #0072ff;
      color: #ffffff;
      border: none;
      border-radius: 6px;
      padding: 10px 0;
      width: 100%;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
      box-shadow: 0 2px 8px #0072ff22;
      position: relative;
      z-index: 1;
    }
    .portal-card button:hover {
      background: #005bb5;
    }
    .fade-out {
      opacity: 0;
      transition: opacity 0.6s cubic-bezier(.4,0,.2,1);
      pointer-events: none;
    }
    @media (max-width: 600px) {
      .portal-container {
        padding: 18px 6px;
      }
      .portal-options {
        flex-direction: column;
        gap: 12px;
      }
    }
  </style>
</head>
<body>
  <div class="portal-container" id="fadeContainer">
    <!-- Optional: Add your logo here -->
    <!--<img src="img/logo.png" alt="Victoria Falls Logo" class="portal-logo" />-->
    <div class="portal-title">Victoria Falls</div>
    <div class="portal-subtitle">Book Library Management System</div>
    <div class="portal-desc">
        Welcome to the Victoria Falls Book Library Management System. Please select your portal to continue.
    </div>
    <div class="portal-options">
      <div class="portal-card">
        <h3>Membership Portal</h3>
        <p>Access your borrowed books, fines, and manage your membership details.</p>
        <button onclick="window.location.href='member_login.php'" class="btn btn-primary">Enter Member Login</button>
      </div>
      <div class="portal-card">
        <h3>Admin Portal</h3>
        <p>Manage books, users, and oversee all library administrative tasks.</p>
        <!--<button class="fade-link" data-href="admin_login.php">Enter Admin Portal</button>-->
        <button onclick="window.location.href='admin_login.php'" class="btn btn-secondary">Enter Admin Portal</button>
      </div>
    </div>
  </div>
  
</body>
</html>