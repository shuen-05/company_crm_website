<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>     
        #navbar {            
            font-family: "Poppins";
            background-color: #eeeeee;
            display: flex;
            height: 70px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.6);
        }

        .menu {
            display: flex;
            align-items: center;
            flex: 1;
            gap: 30px;
        }

        .menu a {
            font-weight: 600;
            font-size: 15px;
            color: #000000;
            text-decoration: none;
        }

        .menu a:hover {
            color: #7d0a0a;
        }

        .left-menu {
            justify-content: flex-start;            
            padding-left: 22px;
        }

        .right-menu {
            position: relative;
            justify-content: flex-end;
            padding-top: 2px;
            padding-right: 18px;
        }

        #logo {
            position: absolute;
            top: 12px;
            right: 153px;
            height: 45px;   
        }

        .profile-container {
            position: relative;
        }
        
        .profile-container #profile-icon {
            position: absolute;
            top: -17px;
            right: 70px;
        }

        .profile-container .profile-dropdown {
            display: none;
            background-color: #ffffff;
            position: absolute;
            top: 15px;
            right: 68px;
            z-index: 2;
            min-width: 200px;
            margin-top: 10px;            
            padding: 17px;
            border-radius: 5px;
            box-shadow: 0px 2px 12px rgba(0, 0, 0, 0.5);
        }

        .profile-container .profile-dropdown::before {
            content: '';
            position: absolute;
            right: 10px;
            bottom: 100%;
            border: 8px solid;
            border-color: transparent transparent white transparent;
        }

        .profile-container:hover .profile-dropdown {
            display: block;
            animation: fadeIn 0.3s;
        }

        .profile-info {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .profile-info:last-child {
            margin-bottom: 0px;
        }

        .profile-info p {
            margin-block: 0px;
        }

        .profile-info #label {
            color: #7d0a0a;
        }

        #logout-icon {
            position: absolute;
            top: 17px;
            right: 21px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <header id="navbar">
        <div class="menu left-menu">
            <a href="customer_management.php">Customer Management</a>
            <a href="interaction_history.php">Interaction History</a>
            <a href="lead_management.php">Lead Management</a>
            <?php if (isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'Admin'): ?>
                <a href="user_management.php">Manage Users</a>
            <?php endif; ?>
        </div>
        
        <div class="menu right-menu">
            <a href="mainpage.php"><img src="pic/ABB-removebg-preview.png" alt="ABB Robotics CRM" id="logo" title="ABB Robotics"></a>
            <div class="profile-container">
                <a href="user_profile.php" id="profile-icon"><i class="fa fa-user-circle" style="font-size:32px;"></i></a>
                <div class="profile-dropdown">
                    <div class="profile-info">
                        <p id="label"><b>Username:</b></p>
                        <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    <div class="profile-info">    
                        <p id="label"><b>Full Name:</b></p>
                        <p><?php echo htmlspecialchars($_SESSION['user_fullname']); ?></p>
                    </div>
                    <div class="profile-info">
                        <p id="label"><b>Email:</b></p>
                        <p><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    </div>
                    <div class="profile-info">
                        <p id="label"><b>Account Type:</b></p>
                        <p><?php echo htmlspecialchars($_SESSION['account_type']); ?></p>
                    </div>
                </div>
            </div>
            <a href="logout.php" id="logout-icon" title="Log out"><i class="fa fa-sign-out" style="font-size:36px;"></i></a>
        </div>
    </header>
</body>
</html>