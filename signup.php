<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $error = '';

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already exists";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password]);
                
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['name'] = $name;
                header("Location: dashboard.php");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <title>Sign Up - MindCare</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;800&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'nunito', sans-serif;
            font-weight: 600;
        }
        body {
            background-color: #91908c;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
        }
        .box {
            width: 450px;
            height: 600px;
            background: #fff;
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 2px 2px 15px 2px rgba(0,0,0,0.1),
                        -2px -0px 15px 2px rgba(0,0,0,0.1);
            z-index: 10;
        }
        .wrapper {
            position: absolute;
            width: 455px;
            height: 600px;
            border-radius: 30px;
            background: rgba(255,255,255,0.53);
            box-shadow: 2px 2px 15px 2px rgba(0,0,0,0.115),
                        -2px -0px 15px 2px rgba(0,0,0,0.054);
            transform: rotate(5deg);
        }
        .header {
            margin-bottom: 30px;
        }
        .header p {
            font-size: 25px;
            font-weight: 800;
            margin-top: 10px;
        }
        .input-box {
            display: flex;
            flex-direction: column;
            margin: 10px 0;
            position: relative;
        }
        i {
            font-size: 22px;
            position: absolute;
            top: 35px;
            right: 12px;
            color: #595b5e;
        }
        input {
            height: 40px;
            border: 2px solid rgb(153,157,158);
            border-radius: 7px;
            margin: 7px 0;
            outline: none;
        }
        .input-field {
            font-weight: 500;
            padding: 0 10px;
            font-size: 17px;
            color: #333;
            background: transparent;
            transition: all .3s ease-in-out;
        }
        .input-field:focus {
            border: 2px solid rgb(89,53,180);
        }
        .input-field:focus ~ i {
            color: rgb(89,53,180);
        }
        .input-submit {
            margin-top: 20px;
            background: #1e263a;
            border: none;
            color: #fff;
            cursor: pointer;
            transition: all .3s ease-in-out;
        }
        .input-submit:hover {
            background: #122b71;
        }
        .bottom {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            margin-top: 25px;
        }
        .bottom span a {
            color: #727374;
            text-decoration: none;
        }
        .bottom span a:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #ff4757;
            text-align: center;
            margin-bottom: 20px;
        }
        .back-arrow {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 28px;
            color: #1e263a;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 20;
            background: rgba(255, 255, 255, 0.9);
            padding: 12px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border: 2px solid rgba(30, 38, 58, 0.1);
        }
        .back-arrow:hover {
            color: #122b71;
            transform: translateX(-5px) scale(1.05);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-color: rgba(30, 38, 58, 0.2);
        }
        .back-arrow i {
            transition: transform 0.3s ease;
        }
        .back-arrow:hover i {
            transform: translateX(-3px);
        }
    </style>
</head>
<body>  
    <a href="index.php" class="back-arrow">
        <i class='bx bx-arrow-back'></i>
    </a>
    <div class="container">
        <div class="box">
            <div class="header">
                <p>Sign Up</p>
            </div>
            <?php if (isset($error) && $error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="input-box">
                    <label for="name">Full Name</label>
                    <input type="text" class="input-field" id="name" name="name" required>
                    <i class="bx bx-user"></i>
                </div>
                <div class="input-box">
                    <label for="email">E-Mail</label>
                    <input type="email" class="input-field" id="email" name="email" required>
                    <i class="bx bx-envelope"></i>
                </div>
                <div class="input-box">
                    <label for="password">Password</label>
                    <input type="password" class="input-field" id="password" name="password" required>
                    <i class="bx bx-lock"></i>
                </div>
                <div class="input-box">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="input-field" id="confirm_password" name="confirm_password" required>
                    <i class="bx bx-lock"></i>
                </div>
                <div class="input-box">
                    <input type="submit" class="input-submit" value="SIGN UP">
                </div>
                <div class="bottom">
                    <span><a href="login.php">Already have an account? Login</a></span>
                </div>
            </form>
        </div>
        <div class="wrapper"></div>
    </div>
</body>
</html> 