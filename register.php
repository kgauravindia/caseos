<?php 
//require_once('../op_config.php'); 
require_once('function.php'); 
$table_name = 'op_user';
?>

<!DOCTYPE html>
<html lang="en">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f2f2f2;
            margin: 0;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .form-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #45a049;
        }
        .error-message {
            color: red;
            font-size: 14px;
        }
        .success-message {
            color: green;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Signup</h2>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
       
       $conn = new mysqli($host_name, $db_user, $db_password, $db_name);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Form data
        $full_name = $_POST['full_name'];
        $user_name = $_POST['user_name'];
        $user_email = $_POST['user_email'];
        $user_mobile = $_POST['user_mobile'];
        $user_pass = password_hash($_POST['user_pass'], PASSWORD_BCRYPT);

        // Validate and Insert
        if (!empty($full_name) && !empty($user_name) && !empty($user_email) && !empty($_POST['user_pass'])) {
            $stmt = $conn->prepare("INSERT INTO op_user (full_name, user_name, user_email, user_mobile, user_pass) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $user_name, $user_email, $user_mobile, $user_pass);

            if ($stmt->execute()) {
                echo "<p class='success-message'>Signup successful!</p>";
            } else {
                echo "<p class='error-message'>Error: " . $stmt->error . "</p>";
            }

            $stmt->close();
        } else {
            echo "<p class='error-message'>All fields are required.</p>";
        }

        $conn->close();
    }
    ?>

    <form action="register.php" method="post">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required>
        </div>
        <div class="form-group">
            <label for="user_name">Username</label>
            <input type="text" id="user_name" name="user_name" required>
        </div>
        <div class="form-group">
            <label for="user_email">Email</label>
            <input type="email" id="user_email" name="user_email" required>
        </div>
        <div class="form-group">
            <label for="user_mobile">Mobile</label>
            <input type="text" id="user_mobile" name="user_mobile">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="user_pass" required>
        </div>
        <div class="form-group">
            <button type="submit">Sign Up</button>
        </div>
    </form>
</div>

</body>
</html>

