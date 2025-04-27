<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    include 'config.php';

    // get user account type
    $stmt = $conn->prepare("SELECT account_type FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $is_admin = ($user['account_type'] === 'Admin');
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'add') {
            $customer_id = $_POST['customer_id'];
            $interaction_type = $_POST['interaction_type'];
            $interaction_date = $_POST['interaction_date'];
            $interaction_remark = $_POST['interaction_remark'];
            
            // for admin, use selected user_id; for sales rep, use their own user_id
            $user_id = $is_admin ? $_POST['user_id'] : $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO interactions (user_id, customer_id, interaction_type, interaction_date, interaction_remark) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $user_id, $customer_id, $interaction_type, $interaction_date, $interaction_remark);
            
            $_SESSION['message'] = $stmt->execute() 
                ? 'Interaction added successfully!' 
                : 'Failed to add interaction.';
            
            $stmt->close();
            header("Location: interaction_history.php");
            exit();
        }

        if ($_POST['action'] === 'update') {
            // for admin, don't check user_id in WHERE clause
            if ($is_admin) {
                $stmt = $conn->prepare("UPDATE interactions SET customer_id=?, interaction_type=?, interaction_date=?, interaction_remark=? WHERE interaction_id=?");
                $stmt->bind_param("isssi", $_POST['customer_id'], $_POST['interaction_type'], $_POST['interaction_date'], $_POST['interaction_remark'], $_POST['id']);
            } else {
                $stmt = $conn->prepare("UPDATE interactions SET customer_id=?, interaction_type=?, interaction_date=?, interaction_remark=? WHERE interaction_id=? AND user_id=?");
                $stmt->bind_param("isssii", $_POST['customer_id'], $_POST['interaction_type'], $_POST['interaction_date'], $_POST['interaction_remark'], $_POST['id'], $_SESSION['user_id']);
            }
            
            $_SESSION['message'] = $stmt->execute() 
                ? 'Interaction updated successfully!' 
                : 'Failed to update interaction.';
            
            $stmt->close();
            header("Location: interaction_history.php");
            exit();
        }

        if ($_POST['action'] === 'delete') {
            // for admin, don't check user_id in WHERE clause
            if ($is_admin) {
                $stmt = $conn->prepare("DELETE FROM interactions WHERE interaction_id=?");
                $stmt->bind_param("i", $_POST['id']);
            } else {
                $stmt = $conn->prepare("DELETE FROM interactions WHERE interaction_id=? AND user_id=?");
                $stmt->bind_param("ii", $_POST['id'], $_SESSION['user_id']);
            }
            
            $_SESSION['message'] = $stmt->execute() 
                ? 'Interaction deleted successfully!' 
                : 'Failed to delete interaction.';
            
            $stmt->close();
            header("Location: interaction_history.php");
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
    // default to sorting by 'interaction_date' in ascending order if not specified
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'interaction_date';
    $order = isset($_GET['order']) ? $_GET['order'] : 'desc';
    
    // define allowed columns that can be sorted (to prevent SQL injection)
    // if the requested sort column is not in the allowed list, default to 'interaction_date'
    $valid_columns = ['interaction_id', 'interaction_date', 'customer_name', 'customer_company', 'interaction_type', 'interaction_remark', 'user_fullname'];
    if (!in_array($sort, $valid_columns)) {
        $sort = 'interaction_date';
    }
    
    // normalize the sort direction to either 'DESC' (descending) or 'ASC' (ascending)
    // only accepts 'desc' (case-insensitive) as descending; everything else becomes 'ASC'
    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

    // fetch interactions from database with search and sorting functionality
    $interactions = [];
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

    $query = $is_admin 
        ? "SELECT i.interaction_id, i.interaction_type, i.interaction_date, i.interaction_remark, 
                  c.customer_id, c.customer_name, c.customer_company,
                  u.user_fullname as sales_rep
           FROM interactions i
           JOIN customers c ON i.customer_id = c.customer_id
           JOIN users u ON i.user_id = u.user_id"
        : "SELECT i.interaction_id, i.interaction_type, i.interaction_date, i.interaction_remark, 
                  c.customer_id, c.customer_name, c.customer_company
           FROM interactions i
           JOIN customers c ON i.customer_id = c.customer_id
           WHERE i.user_id = ?";

    if (!empty($search_query)) {
        $search = "%" . $conn->real_escape_string($search_query) . "%";
        $search = str_replace(['%', '_'], ['\%', '\_'], $search);
        
        if ($is_admin) {
            $query .= " WHERE (c.customer_name LIKE ? 
                      OR c.customer_company LIKE ?
                      OR i.interaction_type LIKE ?
                      OR i.interaction_remark LIKE ?
                      OR u.user_fullname LIKE ?)";
        } else {
            $query .= " AND (c.customer_name LIKE ? 
                      OR c.customer_company LIKE ?
                      OR i.interaction_type LIKE ?
                      OR i.interaction_remark LIKE ?)";
        }
    }

    $query .= " ORDER BY $sort $order";

    $stmt = $conn->prepare($query);
    
    if (!empty($search_query)) {
        if ($is_admin) {
            $stmt->bind_param("sssss", $search, $search, $search, $search, $search);
        } else {
            $stmt->bind_param("issss", $_SESSION['user_id'], $search, $search, $search, $search);
        }
    } elseif (!$is_admin) {
        $stmt->bind_param("i", $_SESSION['user_id']);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $interactions[] = $row;
        }
    }
    $stmt->close();

    // fetch customers for dropdown list
    $customers = [];
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT customer_id, customer_name FROM customers");
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
        }
    } else {
        $stmt = $conn->prepare("SELECT customer_id, customer_name FROM customers WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
        }
    }
    $stmt->close();

    // fetch sales reps for admin dropdown list
    $sales_reps = [];
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT user_id, user_fullname FROM Users WHERE account_type = 'Sales Rep'");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sales_reps[] = $row;
        }
        $stmt->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interaction History - ABB Robotics CRM</title>
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

        .interaction-table {
            min-width: 1200px;
            border-collapse: collapse;
            font-size: 15px;
        }

        .interaction-table th, .interaction-table td {
            padding: 11px 14px;
            border: 2px solid #ffffff;
            text-align: left;
        }

        .interaction-table th {
            background-color: #7d0a0a;
            position: relative;
            color: #ffffff;
        }

        .interaction-table th a {
            display: block;
            margin: -10px -14px;
            padding: 10px 14px;
            color: #ffffff;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .interaction-table th a:hover {
            background-color: #6a0909;
        }

        .interaction-table th a::after {
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

        .interaction-table th a.asc::after {
            border-bottom: 5px solid white;
            opacity: 1;
        }

        .interaction-table th a.desc::after {
            border-top: 5px solid white;
            opacity: 1;
        }

        .interaction-table tr:nth-child(odd) {
            background-color: #eeeeee;
        }

        .interaction-table tr:nth-child(even) {
            background-color: #dddddd;
        }

        .interaction-type {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        .type-call {
            background-color: #e2ffe2;
            color: #155724;
        }

        .type-meeting {
            background-color: #cce5ff;
            color: #004085;
        }

        .type-email {
            background-color: #fff3cd;
            color: #856404;
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

        .action-btn {
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

        .action-btn:hover {
            background-color: #eeeeee;
        }

        .update-btn {
            width: 38.5px;
        }

        .update-btn i {
            position: relative;
            top: 1.8px;
            left: 1px;
        }

        .delete-btn i {
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
            z-index: 1001;
            width: 400px;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            transform: translate(-50%, 0);
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

        #add-modal {   
            top: 12%;
            max-height: 441px;
        }

        #update-modal {
            top: 12%;
            max-height: 384px;
        }

        .add-group, .update-group {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }

        .add-group label, .update-group label {
            align-self: flex-start;
            min-width: 100px;
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

        .add-group select, .update-group select {
            font-family: "Poppins";
            width: 100%;
            padding: 9px 12px;
            box-sizing: border-box;
            border: 2px solid #7d0a0a;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            appearance: none;
            background-image: url(pic/down-arrow.svg);
            background-repeat: no-repeat;
            background-size: 12px;
            background-position: 95%;
        }

        .add-group #add-date, .update-group #update-date{
            cursor: text;
        }

        .add-group #add-date::-webkit-calendar-picker-indicator, 
        .update-group #update-date::-webkit-calendar-picker-indicator {
            cursor: pointer;
            font-size: 18px;
        }

        .add-group textarea, .update-group textarea {
            font-family: "Poppins";
            width: 100%;
            height: 80px;
            padding: 8px 12px;
            box-sizing: border-box;
            border: 2px solid #7d0a0a;
            border-radius: 6px;
            font-size: 15px;
            resize: none;
        }

        #delete-modal {
            top: 28%;
            width: 385px;
            max-height: 145px;
        }

        #delete-modal h2 {
            margin-top: 2px;
        }
        
        #delete-modal p {
            margin-top: -20px;
            margin-bottom: 35px;
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
            <h1>Interaction History</h1>
            <div class="table-controls">
                <button id="add-btn" onclick="openAddModal()"><i class="fa fa-plus-circle" style="font-size:21px;"></i> Add Interaction</button>

                <form method="get" class="search-container">
                    <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                    <input type="hidden" name="order" value="<?php echo strtolower($order) === 'desc' ? 'desc' : 'asc'; ?>">
                    <input type="text" id="search-bar" name="search" placeholder="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" id="search-btn"><i class="fa fa-search" style="font-size:18px;"></i></button>
                </form>
            </div>

            <table class="interaction-table">
                <thead>
                    <tr>
                        <?php
                            // define sortable columns with their display names
                            $columns = [
                                'interaction_date' => 'Date',
                                'customer_name' => 'Customer Name',
                                'customer_company' => 'Customer Company',
                                'interaction_type' => 'Type',
                                'interaction_remark' => 'Remark',
                                'user_fullname' => 'Sales Rep'
                            ];

                            // remove Sales Rep column if not admin
                            if (!$is_admin) {
                                unset($columns['user_fullname']);
                            }

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
                    <?php if (count($interactions) > 0): ?>
                        <?php foreach ($interactions as $interaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($interaction['interaction_date']); ?></td>
                                <td><?php echo htmlspecialchars($interaction['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($interaction['customer_company']); ?></td>
                                <td>
                                    <span class="interaction-type type-<?php echo strtolower($interaction['interaction_type']); ?>">
                                        <?php echo htmlspecialchars($interaction['interaction_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($interaction['interaction_remark']); ?></td>
                                <?php if ($is_admin): ?>
                                    <td><?php echo htmlspecialchars($interaction['sales_rep'] ?? 'Unassigned'); ?></td>
                                <?php endif; ?>
                                <td id="action-cell">
                                    <div id="action-btn-group">
                                        <button class="action-btn update-btn" title="Update" onclick="openUpdateModal(
                                            <?php echo $interaction['interaction_id']; ?>, 
                                            '<?php echo $interaction['customer_id']; ?>', 
                                            '<?php echo htmlspecialchars(addslashes($interaction['interaction_type'])); ?>', 
                                            '<?php echo $interaction['interaction_date']; ?>', 
                                            '<?php echo htmlspecialchars(addslashes($interaction['interaction_remark'])); ?>'
                                        )"><i class="fa fa-edit" style="font-size:21px"></i></button>
                                        <button class="action-btn delete-btn" title="Delete" onclick="openDeleteModal(<?php echo $interaction['interaction_id']; ?>)" title="Delete"><i class="fa fa-trash" style="font-size:21px"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? '7' : '6'; ?>">No interactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <!-- Overlay -->
    <div id="overlay"></div>

    <!-- Add Modal -->
    <div id="add-modal" class="modal">
        <h2>Add New Interaction</h2>
        <form method="post">
            <input type="hidden" name="action" value="add">

            <?php if ($is_admin): ?>
                <div class="add-group">
                    <label for="add-user">Sales Rep:</label>
                    <select id="add-user" name="user_id" required>
                        <option value="" disabled selected hidden>Unassigned</option>
                        <?php foreach ($sales_reps as $rep): ?>
                            <option value="<?php echo $rep['user_id']; ?>"><?php echo htmlspecialchars($rep['user_fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="add-group">
                <label for="add-customer">Customer:</label>
                <select id="add-customer" name="customer_id" required>
                    <option value="" disabled selected hidden>Select customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['customer_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="add-group">
                <label for="add-type">Type:</label>
                <select id="add-type" name="interaction_type" required>
                    <option value="" disabled selected hidden>Select interaction type</option>
                    <option value="Call">Call</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Email">Email</option>
                </select>
            </div>

            <div class="add-group">
                <label for="add-date">Date:</label>
                <input type="date" id="add-date" name="interaction_date" required>
            </div>

            <div class="add-group">
                <label for="add-remark">Remark:</label>
                <textarea id="add-remark" name="interaction_remark" placeholder="Enter remark or description"></textarea>
            </div>
            
            <div class="modal-btn-group">
                <button type="submit" id="apply-btn">Add</button>
                <button type="button" id="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Update Modal -->
    <div id="update-modal" class="modal">
        <h2>Update Interaction</h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="update-id" name="id">

            <div class="update-group">
                <label for="update-customer">Customer:</label>
                <select id="update-customer" name="customer_id" required>
                    <option value="" disabled selected hidden>Select customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['customer_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="update-group">
                <label for="update-type">Type:</label>
                <select id="update-type" name="interaction_type" required>
                    <option value="" disabled selected hidden>Select interaction type</option>
                    <option value="Call">Call</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Email">Email</option>
                </select>
            </div>

            <div class="update-group">
                <label for="update-date">Date:</label>
                <input type="date" id="update-date" name="interaction_date" required>
            </div>

            <div class="update-group">
                <label for="update-remark">Remark:</label>
                <textarea id="update-remark" name="interaction_remark" placeholder="Enter remark or description"></textarea>
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
        <p>Are you sure you want to delete this interaction?</p>
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
        
        function openUpdateModal(id, customer_id, type, date, remark) {
            document.getElementById('update-id').value = id;
            document.getElementById('update-customer').value = customer_id;
            document.getElementById('update-type').value = type;
            document.getElementById('update-date').value = date;
            document.getElementById('update-remark').value = remark;
            
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
    </script>
</body>
</html>