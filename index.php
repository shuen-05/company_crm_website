<?php
    session_start();
    include 'config.php';

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['user_email'] ?? '');
        $password = trim($_POST['user_password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['user_password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_fullname'] = $user['user_fullname'];
                    $_SESSION['user_email'] = $user['user_email'];
                    $_SESSION['date_of_birth'] = $user['date_of_birth'];
                    $_SESSION['account_type'] = $user['account_type'];

                    header("Location: mainpage.php");
                    exit();
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No account found with this email.";
            }

            $stmt->close();
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ABB Robotics CRM</title>
    <link rel="icon" type="image/x-icon" href="pic/ABBfavicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            font-family: "Poppins";
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100vw;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        .video-container {
            position: fixed;
            z-index: -1;
            width: 100vw;
            height: 100vh;
        }

        .video-container video {
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            filter: blur(4px);
        }

        .login-container {
            background-color: #eeeeee;
            width: 300px;
            padding: 25px;
            border-radius: 15px;
            color: #000000;
            text-align: center;
            box-shadow: 4px 4px 15px rgba(0, 0, 0, 0.6);
        }

        #logo {
            width: 100%;
            box-sizing: border-box;
        }

        #login-header {
            font-family: "Poppins";
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 22px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 25px;
        }

        .input-group label {
            font-weight: 600;
            font-size: 15px;
            text-align: left;
        }

        .input-group input {
            font-family: "Poppins";
            background-color: #ffffff;
            width: 100%;
            padding: 8.5px 12px;
            box-sizing: border-box;
            border: 1px solid #000000;
            border-radius: 6px;
            font-size: 14px;
        }

        .password-container {
            position: relative;
        }

        #password-toggle-btn {
            background-color: #ffffff;
            position: absolute;
            top: 9px;
            right: 7px;
            border: none;
            color: #000000;
            cursor: pointer;
        }

        #login-btn {
            font-family: "Poppins";
            background-color: #7d0a0a;
            width: 100%;
            margin-top: 24px;
            padding: 8px 10px 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            color: #ffffff;
            cursor: pointer;
            transition: 0.3s;
        }

        #login-btn:hover {
            background-color: #6a0909;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body>
    <div class="video-container">
        <video autoplay loop muted>
            <source src="pic/ABBcommercial.mp4" type="video/mp4">
        </video>
    </div>

    <div class="login-container">
        <img src="pic/ABB-removebg-preview.png" alt="ABB Robotics CRM" id="logo">
        <h2 id="login-header">LOGIN TO CRM</h2>

        <?php if (!empty($error)) echo "<p style='color: red;'>$error</p>"; ?>

        <form action="index.php" method="post">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="user_email" placeholder="Enter your email" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="user_password" placeholder="Enter your password" required>
                    <button type="button" id="password-toggle-btn"><i class="fa fa-eye" style="font-size:18px"></i></button>
                </div>
            </div>

            <button type="submit" id="login-btn" name="login">Login</button>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const passwordInput = document.getElementById("password");
            const togglePasswordButton = document.querySelector("#password-toggle-btn i");

            togglePasswordButton.addEventListener("click", function() {
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    togglePasswordButton.classList.remove("fa-eye");
                    togglePasswordButton.classList.add("fa-eye-slash");
                } else {
                    passwordInput.type = "password";
                    togglePasswordButton.classList.remove("fa-eye-slash");
                    togglePasswordButton.classList.add("fa-eye");
                }
            });
        });
    </script>
</body>
</html>