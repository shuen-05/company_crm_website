<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    include 'config.php';

    // initialize password field values
    $current_password_value = '';
    $new_password_value = '';
    $confirm_password_value = '';

    // get current user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // initialize password error message
    $password_error = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // get current values as defaults
        $username = trim($_POST['username'] ?? $user['username']);
        $fullname = trim($_POST['fullname'] ?? $user['user_fullname']);
        $email = trim($_POST['email'] ?? $user['user_email']);
        $dob = trim($_POST['dob'] ?? $user['date_of_birth']);
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        // store password values to repopulate fields
        $current_password_value = htmlspecialchars($current_password);
        $new_password_value = htmlspecialchars($new_password);
        $confirm_password_value = htmlspecialchars($confirm_password);

        // initialize flags
        $password_updated = false;
        $update_success = false;
        $has_password_fields = !empty($current_password) || !empty($new_password) || !empty($confirm_password);

        // check if any password field is filled
        if ($has_password_fields) {
            // validate password change if any field is filled
            if (empty($current_password)) {
                $password_error = "Current password is required to change password.";
            } elseif (empty($new_password)) {
                $password_error = "New password is required to change password.";
            } elseif (empty($confirm_password)) {
                $password_error = "Please confirm your new password by entering it again.";
            } elseif ($new_password !== $confirm_password) {
                $password_error = "New password and confirmation password do not match.";
            } else {
                // verify current password
                $stmt = $conn->prepare("SELECT user_password FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                $stmt->close();

                if (password_verify($current_password, $user_data['user_password'])) {
                    // update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET user_password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                    if ($stmt->execute()) {
                        $password_updated = true;
                        $update_success = true;
                        // clear password values on successful update
                        $current_password_value = '';
                        $new_password_value = '';
                        $confirm_password_value = '';
                    }
                    $stmt->close();
                } else {
                    $password_error = "Current password is incorrect.";
                }
            }
        }

        // update basic info
        $stmt = $conn->prepare("UPDATE users SET username = ?, user_fullname = ?, user_email = ?, date_of_birth = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $username, $fullname, $email, $dob, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $update_success = true;
        }
        $stmt->close();

        // update session variables
        $_SESSION['username'] = $username;
        $_SESSION['user_fullname'] = $fullname;
        $_SESSION['user_email'] = $email;
        $_SESSION['date_of_birth'] = $dob;

        // set success message if no password error and update was successful
        if ($update_success && empty($password_error)) {
            $_SESSION['success_message'] = $password_updated ? 
                "User profile and password updated successfully!" : 
                "User profile updated successfully!";
            $_SESSION['show_success_modal'] = true;
        }

        // if there are password errors, store them in session to persist after redirect
        if (!empty($password_error)) {
            $_SESSION['password_error'] = $password_error;
            $_SESSION['password_values'] = [
                'current_password' => $current_password_value,
                'new_password' => $new_password_value,
                'confirm_password' => $confirm_password_value
            ];
        }

        header("Location: edit_user_profile.php");
        exit();
    }

    // retrieve password error and values from session if they exist
    if (isset($_SESSION['password_error'])) {
        $password_error = $_SESSION['password_error'];
        unset($_SESSION['password_error']);
        
        if (isset($_SESSION['password_values'])) {
            $current_password_value = $_SESSION['password_values']['current_password'];
            $new_password_value = $_SESSION['password_values']['new_password'];
            $confirm_password_value = $_SESSION['password_values']['confirm_password'];
            unset($_SESSION['password_values']);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Profile - ABB Robotics CRM</title>
    <link rel="icon" type="image/x-icon" href="pic/ABBfavicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            font-family: "Poppins";
            background-color: #eeeeee;
            margin: 0;
            padding: 0;
        }

        #content {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 40px 20px;
        }

        .container {
            background-color: #ffffff;
            width: 600px;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .container h1 {
            margin-bottom: 10px;
            text-align: center;
            font-size: 1.7em;
            color: #7d0a0a;
        }

        .error-message {
            margin-bottom: 15px;
            font-size: 15px;
            font-weight: 600;
            color: #d32f2f;
            text-align: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 23px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 15px;
            text-align: left;
        }

        .form-group input {
            font-family: "Poppins";
            width: 100%;
            padding: 8px 12px;
            box-sizing: border-box;
            border: 2px solid #7d0a0a;
            border-radius: 6px;
            font-size: 15px;
        }

        .form-group #dob {
            cursor: text;
        }

        .form-group #dob::-webkit-calendar-picker-indicator {
            cursor: pointer;
            font-size: 18px;
        }

        .password-container {
            position: relative;
        }

        #password-toggle-btn {
            background-color: #ffffff;
            position: absolute;
            top: 11px;
            right: 15px;
            border: none;
            color: #000000;
            cursor: pointer;
            padding: 0;
        }

        #submit-btn {
            font-family: "Poppins";
            background-color: #7d0a0a;
            width: 100%;
            margin-top: 26px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            color: #ffffff;
            cursor: pointer;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
            transition: background-color 0.2s;
        }

        #submit-btn:hover {
            background-color: #6a0909;
        }

        #overlay {
            display: none;
            background-color: rgba(0, 0, 0, 0.6);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: 100%;
            height: 100%;
        }

        .modal {
            display: none;
            background-color: #ffffff;
            position: fixed;
            left: 50%;
            transform: translate(-50%, 0);
            z-index: 1001;
            width: 380px;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        #message-modal {
            top: 32%;
            width: 324px;
            max-height: 114px;
            padding-top: 15px;
        }

        #message-modal #message-text {
            margin-top: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        #ok-btn-container {
            display: flex;
            justify-content: center;
            margin-bottom: 5px;
        }

        #ok-btn {
            font-family: "Poppins";
            background-color: #7d0a0a;
            width: 60px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            color: #ffffff;
            cursor: pointer;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
            transition: background-color 0.2s;
        }

        #ok-btn:hover {
            background-color: #6a0909;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <section id="content">
        <div class="container">
            <h1>Edit User Profile</h1>
            
            <?php if (!empty($password_error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($password_error); ?></div>
            <?php endif; ?>

            <form action="edit_user_profile.php" method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="Enter new username">
                </div>
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['user_fullname']); ?>" placeholder="Enter new full name">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['user_email']); ?>" placeholder="Enter new email address">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>">
                </div>
                <div class="form-group">
                    <label>Change Password (leave blank to keep current password):</label>
                    <div class="password-container">
                        <input type="password" id="current-password" name="current_password" placeholder="Current password"
                            value="<?php echo $current_password_value; ?>">
                        <button type="button" id="password-toggle-btn"><i class="fa fa-eye" style="font-size:18px"></i></button>
                    </div>
                    <div class="password-container">
                        <input type="password" id="new-password" name="new_password" placeholder="New password"
                            value="<?php echo $new_password_value; ?>">
                        <button type="button" id="password-toggle-btn"><i class="fa fa-eye" style="font-size:18px"></i></button>
                    </div>
                    <div class="password-container">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm new password"
                            value="<?php echo $confirm_password_value; ?>">
                        <button type="button" id="password-toggle-btn"><i class="fa fa-eye" style="font-size:18px"></i></button>
                    </div>
                </div>

                <button type="submit" id="submit-btn">Apply Changes</button>
            </form>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    
    <!-- Overlay -->
    <div id="overlay"></div>

    <!-- Success Message Modal -->
    <div id="message-modal" class="modal">
        <p id="message-text"></p>
        <div id="ok-btn-container">
            <button type="button" id="ok-btn" onclick="redirectToProfile()">OK</button>
        </div>
    </div>

    <script>
        // Show success modal if there's a success message
        <?php if (isset($_SESSION['show_success_modal']) && $_SESSION['show_success_modal']): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showMessage('<?php echo htmlspecialchars(addslashes($_SESSION['success_message'])); ?>');
                <?php unset($_SESSION['show_success_modal']); ?>
            });
        <?php endif; ?>

        function showMessage(message) {
            document.getElementById('message-text').innerText = message;
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('message-modal').style.display = 'block';
        }

        function redirectToProfile() {
            window.location.href = 'user_profile.php';
        }

        document.querySelectorAll('#password-toggle-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("fa-eye");
                    icon.classList.add("fa-eye-slash");
                } else {
                    input.type = "password";
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                }
            });
        });
    </script>
</body>
</html>