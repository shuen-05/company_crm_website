<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - ABB Robotics CRM</title>
    <link rel="icon" type="image/x-icon" href="pic/ABBfavicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
            width: 700px;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .container h1 {
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.7em;
            color: #7d0a0a;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #ffffff;
        }

        .user-table th {
            background-color: #7d0a0a;
            color: #ffffff;
            width: 28%;
        }

        .user-table tr:nth-child(odd) {
            background-color: #eeeeee;
        }

        .user-table tr:nth-child(even) {
            background-color: #dadada;
        }

        #edit-btn-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }

        #edit-btn {
            font-family: "Poppins";
            background-color: #ffffff;
            width: 115px;
            padding: 8px 16px;
            border: 2px solid #7d0a0a;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            color: #7d0a0a;
            cursor: pointer;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
            transition: background-color 0.2s;
        }
        
        #edit-btn:hover {
            background-color: #eeeeee;
        }

        #edit-btn a {
            color: #7d0a0a;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <section id="content">
        <div class="container">
            <h1>User Profile</h1>
            <table class="user-table">
                <tr>
                    <th>Username</th>
                    <td><?php echo htmlspecialchars($_SESSION['username']); ?></td>
                </tr>
                <tr>
                    <th>Full Name</th>
                    <td><?php echo htmlspecialchars($_SESSION['user_fullname']); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($_SESSION['user_email']); ?></td>
                </tr>
                <tr>
                    <th>Date of Birth</th>
                    <td><?php echo htmlspecialchars($_SESSION['date_of_birth']); ?></td>
                </tr>
                <tr>
                    <th>Account Type</th>
                    <td><?php echo htmlspecialchars($_SESSION['account_type']); ?></td>
                </tr>
            </table>

            <div id="edit-btn-container">
                <a href="edit_user_profile.php"><button id="edit-btn">Edit Profile</button></a>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>