<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    include 'config.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // handle adding user profile
        if ($_POST['action'] === 'add') {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, user_fullname, user_email, user_password, date_of_birth, account_type)
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $_POST['username'], $_POST['fullname'], $_POST['email'], $hashed_password, $_POST['dob'], $_POST['acctype']);
            
            $_SESSION['message'] = $stmt->execute() 
                ? 'User profile added successfully!' 
                : 'Failed to add user profile.';
            
            $stmt->close();
            header("Location: user_management.php");
            exit();
        }

        // handle updating user profile
        if ($_POST['action'] === 'update') {
            $updates = [];
            $params = [];
            $types = "";

            foreach ([
                'username' => 'username',
                'user_fullname' => 'fullname', 
                'user_email' => 'email',
                'date_of_birth' => 'dob',
                'account_type' => 'acctype'
            ] as $column => $form_field) {
                if (!empty($_POST[$form_field])) {
                    $updates[] = "$column=?";
                    $params[] = $_POST[$form_field];
                    $types .= "s";
                }
            }

            // handle password update separately
            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $updates[] = "user_password=?";
                $params[] = $hashed_password;
                $types .= "s";
            }

            if (!empty($updates)) {
                $params[] = $_POST['id'];
                $types .= "i";
                $stmt = $conn->prepare("UPDATE users SET " . implode(", ", $updates) . " WHERE user_id=?");
                $stmt->bind_param($types, ...$params);

                $_SESSION['message'] = $stmt->execute()
                    ? 'User profile updated successfully!'
                    : 'Failed to update user profile.';
                
                $stmt->close();
                header("Location: user_management.php");
                exit();
            }
        }

        // handle deleting user profile
        if ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
            $stmt->bind_param("i", $_POST['id']);
            
            $_SESSION['message'] = $stmt->execute()
                ? 'User profile deleted successfully!'
                : 'Failed to delete user profile.';

            $stmt->close();
            header("Location: user_management.php");
            exit();
        }
    }

    // check if there's a message to display, i.e. success/error message
    // if yes, use the message modal to display the message
    if (isset($_SESSION['message'])) {
        echo 
        "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showMessage('" . htmlspecialchars(addslashes($_SESSION['message'])) . "');
            });
        </script>";
        unset($_SESSION['message']);
    }

    // get the sorting column and direction from URL parameters (for table sorting)
    // default to sorting by 'user_id' in ascending order if not specified
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'user_id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    // define allowed columns that can be sorted (to prevent SQL injection)
    // if the requested sort column is not in the allowed list, default to 'user_id'
    $valid_columns = ['user_id', 'username', 'user_fullname', 'user_email', 'user_password', 'date_of_birth', 'account_type'];
    if (!in_array($sort, $valid_columns)) {
        $sort = 'user_id';
    }
    
    // normalize the sort direction to either 'DESC' (descending) or 'ASC' (ascending)
    // only accepts 'desc' (case-insensitive) as descending; everything else becomes 'ASC'
    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - ABB Robotics CRM</title>
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
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .container h1 {
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.7em;
            color: #7d0a0a;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        #add-btn {
            font-family: "Poppins";
            background-color: #ffffff;
            position: relative;
            padding: 7px 10px 10px;
            border: 2px solid #7d0a0a;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
            transition: background-color 0.2s;
        }

        #add-btn i {
            position: relative;
            top: 2.5px;
            padding-right: 4px;
        }

        #add-btn:hover {
            background-color: #eeeeee;
        }

        .search-container {
            display: flex;
            margin: 0;
            padding: 0;
            border: none;
            border-radius: 10px;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
        }

        #search-bar {
            font-family: "Poppins";
            background-color: #ffffff;
            padding: 9px 11px;
            border: 2px solid #7d0a0a;
            border-right: none;
            border-radius: 10px 0 0 10px;
            font-size: 14px;
            outline: none;
        }

        #search-btn {
            background-color: #ffffff;
            position: relative;
            padding: 9px 18px;
            border: 2px solid #7d0a0a;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        #search-btn i {
            position: absolute;
            top: 9.5px;
            right: 9px;
        }

        #search-bar:focus {
            border: 2px solid #000000;
            border-right: none;
        }

        #search-bar:focus + #search-btn {
            border: 2px solid #000000;
        }

        #search-btn:hover {
            background-color: #eeeeee;
        }

        .user-table {
            min-width: 1200px;
            border-collapse: collapse;
            font-size: 15px;
        }

        .user-table th, .user-table td {
            padding: 11px 14px;
            border: 2px solid #ffffff;
            text-align: left;
        }
        
        .user-table th {
            background-color: #7d0a0a;
            position: relative;
            color: #ffffff;
        }

        .user-table th a {
            display: block;
            margin: -10px -14px;
            padding: 10px 14px;
            color: #ffffff;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .user-table th a:hover {
            background-color: #6a0909;
        }

        .user-table th a::after {
            content: '';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            opacity: 0;
        }

        .user-table th a.asc::after {
            border-bottom: 5px solid white;
            opacity: 1;
        }

        .user-table th a.desc::after {
            border-top: 5px solid white;
            opacity: 1;
        }

        .user-table tr:nth-child(odd) {
            background-color: #eeeeee;
        }

        .user-table tr:nth-child(even) {
            background-color: #dddddd;
        }

        #password-cell {
            max-width: 300px;
            word-wrap: break-word;
        }

        #action-cell {
            background-color: #ffffff;
            width: 0;
            padding: 0;
            padding-left: 10px;
            border: none;
        }

        #action-btn-group {
            display: flex;
            gap: 7px;
        }

        #update-btn, #delete-btn {
            background-color: #ffffff;
            position: relative;
            width: 38px;
            height: 38px;
            padding: 5px 7px;
            border: 2px solid #7d0a0a;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
            transition: background-color 0.2s;
        }

        #update-btn:hover, #delete-btn:hover {
            background-color: #eeeeee;
        }
        
        #update-btn {
            width: 39px;
        }

        #update-btn i {
            position: relative;
            top: 1.5px;
            left: 1px;
        }

        #delete-btn i {
            position: relative;
            top: 0.5px;
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
            width: 400px;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .modal h2 {
            color: #7d0a0a;
            margin-top: 10px;
            margin-bottom: 28px;
        }

        .modal-btn-group {
            margin-top: 15px;
            margin-bottom: 5px;
            text-align: right;
        }

        #apply-btn, #cancel-btn {
            font-family: "Poppins";
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        #apply-btn {
            background-color: #7d0a0a;
            margin-right: 5px;
            padding: 8.5px 16px;
            color: #ffffff;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
        }

        #apply-btn:hover {
            background-color: #6a0909;
        }

        #cancel-btn {
            background-color: #eeeeee;
            padding: 8px 16px;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
        }

        #cancel-btn:hover {
            background-color: #dddddd;
        }

        #add-modal, #update-modal {   
            top: 12%;         
            max-height: 460px;
        }

        .add-group, .update-group {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }

        .add-group label, .update-group label {
            align-self: flex-start;
            min-width: 128px;
            padding-top: 9.5px;
            font-weight: 500;
            font-size: 15px;
        }

        .add-group input, .update-group input {
            font-family: "Poppins";
            width: 100%;
            padding: 8px 12px;
            box-sizing: border-box;
            border: 2px solid #7d0a0a;
            border-radius: 6px;
            font-size: 15px;
        }

        .password-container {
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .password-toggle-btn {
            background-color: #ffffff;
            position: absolute;
            top: 12px;
            right: 15px;
            border: none;
            color: #000000;
            cursor: pointer;
            padding: 0;
        }

        .add-group #add-dob, .update-group #update-dob{
            cursor: text;
        }

        .add-group #add-dob::-webkit-calendar-picker-indicator, 
        .update-group #update-dob::-webkit-calendar-picker-indicator {
            cursor: pointer;
            font-size: 18px;
        }

        .add-group select, .update-group select {
            font-family: "Poppins";
            width: 100%;
            padding: 8px 12px 8.5px;
            box-sizing: border-box;
            border: 2px solid #7d0a0a;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            appearance: none;
            background-image: url(pic/down-arrow.svg);
            background-repeat: no-repeat;
            background-size: 12px;
            background-position: 94%;
        }

        #delete-modal {
            top: 28%;
            width: 324px;
            max-height: 159px;
        }

        #delete-modal h2 {
            margin-top: 2px;
        }
        
        #delete-modal p {
            margin-top: -20px;
            margin-bottom: 25px;
        }

        #message-modal {
            top: 32%;
            width: 340px;
        }

        #message-modal #message-text {
            margin-top: 2px;
            margin-bottom: 20px;
            text-align: center;
        }

        #ok-btn-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2px;
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
            <h1>User Management</h1>
            <div class="table-controls">
                <button id="add-btn" onclick="openAddModal()"><i class="fa fa-plus-circle" style="font-size:21px;"></i> Add User</button>

                <form method="get" class="search-container">
                    <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                    <input type="hidden" name="order" value="<?php echo strtolower($order) === 'desc' ? 'desc' : 'asc'; ?>">
                    <input type="text" id="search-bar" name="search" placeholder="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" id="search-btn"><i class="fa fa-search" style="font-size:18px;"></i></button>
                </form>
            </div>

            <table class="user-table">
                <thead>
                    <tr>
                        <?php
                            // define sortable columns with their display names
                            $columns = [
                                'user_id' => 'ID',
                                'username' => 'Username',
                                'user_fullname' => 'Full Name',
                                'user_email' => 'Email',
                                'user_password' => 'Password',
                                'date_of_birth' => 'Date of Birth',
                                'account_type' => 'Account Type'
                            ];

                            foreach ($columns as $column => $display_name) {
                                $sort_order = ($sort == $column && $order == 'ASC') ? 'desc' : 'asc';
                                $sort_class = ($sort == $column) ? strtolower($order) : '';
                                $search_param = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                                
                                echo "
                                <th>
                                    <a href=\"?sort={$column}&order={$sort_order}{$search_param}\" class=\"{$sort_class}\">
                                        {$display_name}
                                    </a>
                                </th>";
                            }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php                
                        if (isset($_GET['search']) && trim($_GET['search']) !== '') {
                            $search_query = $conn->real_escape_string($_GET['search']);
                            $search_query = str_replace(['%', '_'], ['\%', '\_'], $search_query);
                            $search = "%" . $search_query . "%";
                            $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR user_fullname LIKE ? OR user_email LIKE ? OR date_of_birth LIKE ? OR account_type LIKE ? ORDER BY $sort $order");
                            $stmt->bind_param("sssss", $search, $search, $search, $search, $search);
                        } else {
                            $stmt = $conn->prepare("SELECT * FROM users ORDER BY $sort $order");
                        }

                        $stmt->execute();
                        $result = $stmt->get_result();
                        $has_users = $result->num_rows > 0;

                        if ($has_users) {
                            while ($row = $result->fetch_assoc()) {
                                $id = $row['user_id'];
                                $username = $row['username'];
                                $fullname = $row['user_fullname'];
                                $email = $row['user_email'];
                                $password = $row['user_password'];
                                $dob = $row['date_of_birth'];
                                $acctype = $row['account_type'];
                                
                                $update = $has_users 
                                    ? "openUpdateModal(" . 
                                        $id . ", '" . 
                                        htmlspecialchars(addslashes($username)) . "', '" . 
                                        htmlspecialchars(addslashes($fullname)) . "', '" . 
                                        htmlspecialchars(addslashes($email)) . "', '" .
                                        htmlspecialchars(addslashes($password)) . "', '" . 
                                        htmlspecialchars(addslashes($dob)) . "', '" . 
                                        htmlspecialchars(addslashes($acctype)) . "')"
                                    : "showMessage('No user profiles available to update.')";

                                $delete = $has_users 
                                    ? "openDeleteModal($id)" 
                                    : "showMessage('No user profiles available to delete.')";

                                echo
                                "<tr>
                                    <td>U00{$id}</td>
                                    <td>{$username}</td>
                                    <td>{$fullname}</td>
                                    <td>{$email}</td>
                                    <td id=\"password-cell\">{$password}</td>
                                    <td>{$dob}</td>
                                    <td>{$acctype}</td>
                                    <td id=\"action-cell\">
                                        <div id=\"action-btn-group\">
                                            <button id=\"update-btn\" title=\"Update\" onclick=\"{$update}\"><i class=\"fa fa-edit\" style=\"font-size:21px\";></i></button>
                                            <button id=\"delete-btn\" title=\"Delete\" onclick=\"{$delete}\"><i class=\"fa fa-trash\" style=\"font-size:21px\";></i></button>
                                        </div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo 
                            "<tr>
                                <td colspan='8'>No user profiles found.</td>
                            </tr>";
                        }

                        $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <!-- Overlay -->
    <div id="overlay"></div>

    <!-- Add Modal -->
    <div id="add-modal" class="modal">
        <h2>Add User Profile</h2>
        <form method="post">
            <input type="hidden" name="action" value="add">

            <div class="add-group">
                <label for="add-username">Username:</label>
                <input type="text" id="add-username" name="username" placeholder="Enter username" required>
            </div>
            <div class="add-group">
                <label for="add-fullname">Full Name:</label>
                <input type="text" id="add-fullname" name="fullname" placeholder="Enter full name" required>
            </div>
            <div class="add-group">
                <label for="add-email">Email:</label>
                <input type="email" id="add-email" name="email" placeholder="Enter email address" required>
            </div>
            <div class="add-group">
                <label for="add-password">Password:</label>
                <div class="password-container">
                    <input type="password" id="add-password" name="password" placeholder="Enter password" required>
                    <button type="button" class="password-toggle-btn" id="add-password-toggle-btn"><i class="fa fa-eye" style="font-size:18px"></i></button>
                </div>
            </div>
            <div class="add-group">
                <label for="add-dob">Date of Birth:</label>
                <input type="date" id="add-dob" name="dob" required>
            </div>
            <div class="add-group">
                <label for="add-acctype">Account Type:</label>
                <select id="add-acctype" name="acctype" required>
                    <option value="" disabled selected hidden>Select account type</option>
                    <option value="Admin">Admin</option>
                    <option value="Sales Rep">Sales Rep</option>
                </select>
            </div>
            
            <div class="modal-btn-group">
                <button type="submit" id="apply-btn">Add</button>
                <button type="button" id="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Update Modal -->
    <div id="update-modal" class="modal">
        <h2>Update User Profile</h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="update-id" name="id">

            <div class="update-group">
                <label for="update-username">Username:</label>
                <input type="text" id="update-username" name="username" placeholder="Enter new username" required>
            </div>
            <div class="update-group">
                <label for="update-fullname">Full Name:</label>
                <input type="text" id="update-fullname" name="fullname" placeholder="Enter new full name" required>
            </div>
            <div class="update-group">
                <label for="update-email">Email:</label>
                <input type="email" id="update-email" name="email" placeholder="Enter new email address" required>
            </div>
            <div class="update-group">
                <label for="update-password">Password:</label>
                <div class="password-container">
                    <input type="password" id="update-password" name="password" placeholder="Enter new password">
                    <button type="button" class="password-toggle-btn" id="update-password-toggle-btn"><i class="fa fa-eye" style="font-size:18px"></i></button>
                </div>
            </div>
            <div class="update-group">
                <label for="update-dob">Date of Birth:</label>
                <input type="date" id="update-dob" name="dob" required>
            </div>
            <div class="update-group">
                <label for="update-acctype">Account Type:</label>
                <select id="update-acctype" name="acctype" required>
                    <option value="" disabled selected hidden>Select account type</option>
                    <option value="Admin">Admin</option>
                    <option value="Sales Rep">Sales Rep</option>
                </select>
            </div>

            <div class="modal-btn-group">
                <button type="submit" id="apply-btn">Update</button>
                <button type="button" id="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Delete Modal -->
    <div id="delete-modal" class="modal">
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this user profile?</p>
        <form method="post">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" id="delete-id" name="id">

            <div class="modal-btn-group">
                <button type="submit" id="apply-btn">Delete</button>
                <button type="button" id="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Message Modal -->
    <div id="message-modal" class="modal">
        <p id="message-text"></p>
        <div id="ok-btn-container">
            <button type="button" id="ok-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('add-modal').style.display = 'block';
        }
        
        function openUpdateModal(id, username, fullname, email, password, dob, acctype) {
            document.getElementById('update-id').value = id;
            document.getElementById('update-username').value = username;
            document.getElementById('update-fullname').value = fullname;
            document.getElementById('update-email').value = email;
            document.getElementById('update-password').value = '';
            document.getElementById('update-dob').value = dob;
            document.getElementById('update-acctype').value = acctype;
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('update-modal').style.display = 'block';
        }

        function openDeleteModal(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('delete-modal').style.display = 'block';
        }

        function showMessage(message) {
            document.getElementById('message-text').innerText = message;
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('message-modal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('add-modal').style.display = 'none';
            document.getElementById('update-modal').style.display = 'none';
            document.getElementById('delete-modal').style.display = 'none';
            document.getElementById('message-modal').style.display = 'none';
        }

        document.addEventListener("DOMContentLoaded", function() {
            // add password toggle
            const addPasswordInput = document.getElementById("add-password");
            const addToggleBtn = document.getElementById("add-password-toggle-btn");
            
            if (addToggleBtn) {
                const addIcon = addToggleBtn.querySelector('i');
                addToggleBtn.addEventListener("click", function() {
                    if (addPasswordInput.type === "password") {
                        addPasswordInput.type = "text";
                        addIcon.classList.remove("fa-eye");
                        addIcon.classList.add("fa-eye-slash");
                    } else {
                        addPasswordInput.type = "password";
                        addIcon.classList.remove("fa-eye-slash");
                        addIcon.classList.add("fa-eye");
                    }
                });
            }

            // update password toggle
            const updatePasswordInput = document.getElementById("update-password");
            const updateToggleBtn = document.getElementById("update-password-toggle-btn");
            
            if (updateToggleBtn) {
                const updateIcon = updateToggleBtn.querySelector('i');
                updateToggleBtn.addEventListener("click", function() {
                    if (updatePasswordInput.type === "password") {
                        updatePasswordInput.type = "text";
                        updateIcon.classList.remove("fa-eye");
                        updateIcon.classList.add("fa-eye-slash");
                    } else {
                        updatePasswordInput.type = "password";
                        updateIcon.classList.remove("fa-eye-slash");
                        updateIcon.classList.add("fa-eye");
                    }
                });
            }
        });
    </script>
</body>
</html>