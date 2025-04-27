<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    include 'config.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // handle adding lead
        if ($_POST['action'] === 'add') {
            $user_id = $_SESSION['account_type'] === 'Admin' && isset($_POST['user_id']) ? $_POST['user_id'] : $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO leads (lead_name, lead_company, lead_email, lead_phonenum, lead_address, lead_status, lead_followup_date, lead_remark, user_id)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $default_status = 'New';
            $stmt->bind_param("ssssssssi", $_POST['name'], $_POST['company'], $_POST['email'], $_POST['phonenum'], $_POST['address'], $default_status, $_POST['followup_date'], $_POST['remark'], $user_id);
            
            $_SESSION['message'] = $stmt->execute() 
                ? 'Lead added successfully!' 
                : 'Failed to add lead.';
            
            $stmt->close();
            header("Location: lead_management.php");
            exit();
        }

        // handle updating lead
        if ($_POST['action'] === 'update') {
            $updates = [];
            $params = [];
            $types = "";

            foreach ([
                'lead_name' => 'name',
                'lead_company' => 'company',
                'lead_email' => 'email',
                'lead_phonenum' => 'phonenum',
                'lead_address' => 'address',
                'lead_status' => 'status',
                'lead_followup_date' => 'followup_date',
                'lead_remark' => 'remark'
            ] as $column => $form_field) {
                if (!empty($_POST[$form_field])) {
                    $updates[] = "$column=?";
                    $params[] = $_POST[$form_field];
                    $types .= "s";
                }
            }

            // add user_id update for admin
            if ($_SESSION['account_type'] === 'Admin' && !empty($_POST['user_id'])) {
                $updates[] = "user_id=?";
                $params[] = $_POST['user_id'];
                $types .= "i";
            }

            if (!empty($updates)) {
                $params[] = $_POST['id'];
                $types .= "i";
                $stmt = $conn->prepare("UPDATE leads SET " . implode(", ", $updates) . " WHERE lead_id=?");
                $stmt->bind_param($types, ...$params);

                $_SESSION['message'] = $stmt->execute()
                    ? 'Lead updated successfully!'
                    : 'Failed to update lead.';
                
                $stmt->close();
                header("Location: lead_management.php");
                exit();
            }
        }

        // handle deleting lead
        if ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM leads WHERE lead_id=?");
            $stmt->bind_param("i", $_POST['id']);
            
            $_SESSION['message'] = $stmt->execute()
                ? 'Lead deleted successfully!'
                : 'Failed to delete lead.';

            $stmt->close();
            header("Location: lead_management.php");
            exit();
        }

        // handle closing and converting lead to customer
        if ($_POST['action'] === 'close-convert') {
            // start transaction
            $conn->begin_transaction();
            
            try {
                // get the lead details
                $stmt = $conn->prepare("SELECT * FROM leads WHERE lead_id = ?");
                $stmt->bind_param("i", $_POST['id']);
                $stmt->execute();
                $lead = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                // insert into customers table
                $stmt = $conn->prepare("INSERT INTO customers (customer_name, customer_company, customer_email, customer_phonenum, customer_address, user_id)
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", 
                    $lead['lead_name'],
                    $lead['lead_company'],
                    $lead['lead_email'],
                    $lead['lead_phonenum'],
                    $lead['lead_address'],
                    $lead['user_id']
                );
                $stmt->execute();
                $stmt->close();
                
                // update lead status to closed
                $stmt = $conn->prepare("UPDATE leads SET lead_status = 'Closed' WHERE lead_id = ?");
                $stmt->bind_param("i", $_POST['id']);
                $stmt->execute();
                $stmt->close();
                
                // commit transaction
                $conn->commit();
                $_SESSION['message'] = 'Lead successfully closed and converted to customer!';
                
            } catch (Exception $e) {
                // rollback transaction on error
                $conn->rollback();
                $_SESSION['message'] = 'Failed to close and convert lead to customer: ' . $e->getMessage();
            }
            
            header("Location: lead_management.php");
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
    // default to sorting by 'lead_id' in ascending order if not specified
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'lead_id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    // define allowed columns that can be sorted (to prevent SQL injection)
    // if the requested sort column is not in the allowed list, default to 'lead_id'
    $valid_columns = ['lead_id', 'lead_name', 'lead_company', 'lead_email', 'lead_phonenum', 'lead_status', 'lead_followup_date', 'lead_remark', 'user_fullname'];
    if (!in_array($sort, $valid_columns)) {
        $sort = 'lead_id';
    }
    
    // normalize the sort direction to either 'DESC' (descending) or 'ASC' (ascending)
    // only accepts 'desc' (case-insensitive) as descending; everything else becomes 'ASC'
    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

    // get sales reps for admin dropdown list
    $sales_reps = [];
    if ($_SESSION['account_type'] === 'Admin') {
        $stmt = $conn->prepare("SELECT user_id, user_fullname FROM users WHERE account_type = 'Sales Rep'");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sales_reps[$row['user_id']] = $row['user_fullname'];
        }
        $stmt->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Management - ABB Robotics CRM</title>
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

        .lead-table {
            min-width: 1200px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .lead-table th, .lead-table td {
            padding: 11px 14px;
            border: 2px solid #ffffff;
            text-align: left;
        }

        .lead-table th {
            background-color: #7d0a0a;
            position: relative;
            color: #ffffff;
        }

        .lead-table th a {
            display: block;
            margin: -10px -14px;
            padding: 10px 14px;
            color: #ffffff;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .lead-table th a:hover {
            background-color: #6a0909;
        }

        .lead-table th a::after {
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

        .lead-table th a.asc::after {
            border-bottom: 5px solid white;
            opacity: 1;
        }

        .lead-table th a.desc::after {
            border-top: 5px solid white;
            opacity: 1;
        }

        .lead-table tr:nth-child(odd) {
            background-color: #eeeeee;
        }

        .lead-table tr:nth-child(even) {
            background-color: #dddddd;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .status-new { 
            background-color:rgb(255, 210, 210); 
            color: #7d0a0a;        
        }

        .status-contacted { 
            background-color: #fff4e2; 
            color: #956100; 
        }

        .status-in-progress { 
            background-color: #e2ffe2; 
            color: #006100;        
        }

        .status-closed { 
            background-color: #cccccc;
            color: #616161; 
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
            gap: 5px;
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

        .update-btn, .close-convert-btn {
            width: 38.5px;
        }

        .update-btn i {
            position: relative;
            top: 1.8px;
            left: 1px;
        }

        .close-convert-btn i {
            position: relative;
            top: 0.5px;
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
            margin-bottom: 2px;
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
            top: 10.5%;
            max-height: 500px;
            overflow-y: auto;
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

        .add-group label[for="add-followup-date"], 
        .update-group label[for="update-followup-date"] {
            margin-top: -10px;
        }

        .add-group #add-followup-date, .update-group #update-followup-date{
            cursor: text;
        }

        .add-group #add-followup-date::-webkit-calendar-picker-indicator, 
        .update-group #update-followup-date::-webkit-calendar-picker-indicator {
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
            width: 333px;
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
            <h1>Lead Management</h1>
            <div class="table-controls">
                <button id="add-btn" onclick="openAddModal()"><i class="fa fa-plus-circle" style="font-size:21px;"></i> Add Lead</button>

                <form method="get" class="search-container">
                    <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                    <input type="hidden" name="order" value="<?php echo strtolower($order) === 'desc' ? 'desc' : 'asc'; ?>">
                    <input type="text" id="search-bar" name="search" placeholder="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" id="search-btn"><i class="fa fa-search" style="font-size:18px;"></i></button>
                </form>
            </div>

            <table class="lead-table">
                <thead>
                    <tr>
                        <?php
                            // define sortable columns with their display names
                            $columns = [
                                'lead_id' => 'ID',
                                'lead_name' => 'Name',
                                'lead_company' => 'Company',
                                'lead_email' => 'Email',
                                'lead_phonenum' => 'Phone No.',
                                'lead_status' => 'Status',
                                'lead_followup_date' => 'Follow-up Date',
                                'lead_remark' => 'Remark',
                                'user_fullname' => 'Sales Rep'
                            ];

                            // remove Sales Rep column if not admin
                            if ($_SESSION['account_type'] !== 'Admin') {
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
                    <?php                
                        $query = $_SESSION['account_type'] === 'Admin' 
                            ? "SELECT l.*, u.user_fullname FROM leads l LEFT JOIN users u ON l.user_id = u.user_id" 
                            : "SELECT l.*, u.user_fullname FROM leads l LEFT JOIN users u ON l.user_id = u.user_id WHERE l.user_id = ?";

                        if (isset($_GET['search']) && trim($_GET['search']) !== '') {
                            $search_query = $conn->real_escape_string($_GET['search']);
                            $search_query = str_replace(['%', '_'], ['\%', '\_'], $search_query);
                            $search = "%" . $search_query . "%";
                            
                            $query .= $_SESSION['account_type'] === 'Admin' 
                                ? " WHERE (l.lead_name LIKE ? OR l.lead_company LIKE ? OR l.lead_email LIKE ? OR l.lead_phonenum LIKE ? OR l.lead_status LIKE ? OR l.lead_followup_date LIKE ? OR l.lead_remark LIKE ? OR u.user_fullname LIKE ?)" 
                                : " AND (l.lead_name LIKE ? OR l.lead_company LIKE ? OR l.lead_email LIKE ? OR l.lead_phonenum LIKE ? OR l.lead_status LIKE ? OR l.lead_followup_date LIKE ? OR l.lead_remark LIKE ?)";
                            
                            $query .= " ORDER BY $sort $order";
                            
                            $stmt = $conn->prepare($query);
                            
                            if ($_SESSION['account_type'] === 'Admin') {
                                $stmt->bind_param("ssssssss", $search, $search, $search, $search, $search, $search, $search, $search);
                            } else {
                                $stmt->bind_param("issssss", $_SESSION['user_id'], $search, $search, $search, $search, $search, $search);
                            }
                        } else {
                            $query .= " ORDER BY $sort $order";
                            $stmt = $conn->prepare($query);
                            
                            if ($_SESSION['account_type'] !== 'Admin') {
                                $stmt->bind_param("i", $_SESSION['user_id']);
                            }
                        }

                        $stmt->execute();
                        $result = $stmt->get_result();
                        $has_leads = $result->num_rows > 0;

                        if ($has_leads) {
                            while ($row = $result->fetch_assoc()) {
                                $id = $row['lead_id'];
                                $name = htmlspecialchars(addslashes($row['lead_name']));
                                $company = htmlspecialchars(addslashes($row['lead_company']));
                                $email = htmlspecialchars(addslashes($row['lead_email']));
                                $phonenum = htmlspecialchars(addslashes($row['lead_phonenum']));
                                $address = htmlspecialchars(addslashes($row['lead_address']));
                                $status = htmlspecialchars(addslashes($row['lead_status']));
                                $followup_date = htmlspecialchars(addslashes($row['lead_followup_date']));
                                $remark = htmlspecialchars(addslashes($row['lead_remark']));
                                $sales_rep = $row['user_fullname'] ?? 'Unassigned';
                                $user_id = $row['user_id'] ?? null;
                                
                                $status_class = 'status-' . strtolower(str_replace(' ', '-', $status));
                                
                                $update = $has_leads 
                                    ? "openUpdateModal('$id', '$name', '$company', '$email', '$phonenum', '$address', '$status', '$followup_date', '$remark', " . ($user_id ?: 'null') . ")"
                                    : "showMessage('No leads available to update.')";

                                $close_convert = $has_leads 
                                    ? "submitCloseAndConvertForm($id)" 
                                    : "showMessage('No leads available to close and convert to customer.')";

                                $delete = $has_leads 
                                    ? "openDeleteModal($id)" 
                                    : "showMessage('No leads available to delete.')";

                                echo "
                                <tr>
                                    <td>L00{$id}</td>
                                    <td>{$row['lead_name']}</td>
                                    <td>{$row['lead_company']}</td>
                                    <td>{$row['lead_email']}</td>
                                    <td>{$row['lead_phonenum']}</td>
                                    <td><span class=\"status-badge {$status_class}\">{$status}</span></td>
                                    <td>{$row['lead_followup_date']}</td>
                                    <td>{$row['lead_remark']}</td>";
                                    
                                if ($_SESSION['account_type'] === 'Admin') {
                                    echo "<td>{$sales_rep}</td>";
                                }
                                
                                echo "
                                <td id=\"action-cell\">
                                    <div id=\"action-btn-group\">";
                                        
                                        // Update button - block access if status is Closed
                                        if ($status === 'Closed') {
                                            echo "<button class=\"action-btn update-btn\" title=\"Update\" onclick=\"showMessage('Cannot edit a closed lead.')\"><i class=\"fa fa-edit\" style=\"font-size:21px; opacity:0.4;\"></i></button>";
                                        } else {
                                            echo "<button class=\"action-btn update-btn\" title=\"Update\" onclick=\"{$update}\")\"><i class=\"fa fa-edit\" style=\"font-size:21px\"></i></button>";
                                        }
                                        
                                        // Close & Convert button - block access if status is Closed
                                        if ($status === 'Closed') {
                                            echo "<button class=\"action-btn close-convert-btn\" title=\"Close & Convert\" onclick=\"showMessage('This lead is already closed.')\"><i class=\"fa fa-check\" style=\"font-size:21px; opacity:0.4;\"></i></button>";
                                        } else {
                                            echo "<button class=\"action-btn close-convert-btn\" title=\"Close & Convert\" onclick=\"{$close_convert}\"><i class=\"fa fa-check\" style=\"font-size:21px\"></i></button>";
                                        }
                                        
                                        // Delete button (always enabled)
                                        echo "<button class=\"action-btn delete-btn\" title=\"Delete\" onclick=\"{$delete}\"><i class=\"fa fa-trash\" style=\"font-size:21px\"></i></button>";
                                        
                                echo "
                                    </div>
                                </td>";
                            }
                        } else {
                            $colspan = $_SESSION['account_type'] === 'Admin' ? 10 : 9;
                            echo 
                            "<tr>
                                <td colspan='{$colspan}'>No leads found.</td>
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
        <h2>Add New Lead</h2>
        <form method="post">
            <input type="hidden" name="action" value="add">

            <?php if ($_SESSION['account_type'] === 'Admin'): ?>
                <div class="add-group">
                    <label for="add-user">Sales Rep:</label>
                    <select id="add-user" name="user_id" required>
                        <option value="" disabled selected hidden>Unassigned</option>
                        <?php foreach ($sales_reps as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="add-group">
                <label for="add-name">Name:</label>
                <input type="text" id="add-name" name="name" placeholder="Enter lead name" required>
            </div>
            <div class="add-group">
                <label for="add-company">Company:</label>
                <input type="text" id="add-company" name="company" placeholder="Enter company name" required>
            </div>
            <div class="add-group">
                <label for="add-email">Email:</label>
                <input type="email" id="add-email" name="email" placeholder="Enter email address" required>
            </div>
            <div class="add-group">
                <label for="add-phonenum">Phone No.:</label>
                <input type="text" id="add-phonenum" name="phonenum" placeholder="Enter phone number" required>
            </div>
            <div class="add-group">
                <label for="add-address">Address:</label>
                <textarea id="add-address" name="address" placeholder="Enter company address" required></textarea>
            </div>
            <div class="add-group">
                <label for="add-followup-date">Follow-up Date:</label>
                <input type="date" id="add-followup-date" name="followup_date">
            </div>
            <div class="add-group">
                <label for="add-remark">Remark:</label>
                <textarea id="add-remark" name="remark" placeholder="Enter remark or notes"></textarea>
            </div>
            
            <div class="modal-btn-group">
                <button type="submit" id="apply-btn">Add</button>
                <button type="button" id="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Update Modal -->
    <div id="update-modal" class="modal">
        <h2>Update Lead</h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="update-id" name="id">

            <?php if ($_SESSION['account_type'] === 'Admin'): ?>
                <div class="update-group">
                    <label for="update-user">Sales Rep:</label>
                    <select id="update-user" name="user_id">
                        <option value="" disabled selected hidden>Select sales rep</option>
                        <?php foreach ($sales_reps as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="update-group">
                <label for="update-name">Name:</label>
                <input type="text" id="update-name" name="name" placeholder="Enter new lead name" required>
            </div>
            <div class="update-group">
                <label for="update-company">Company:</label>
                <input type="text" id="update-company" name="company" placeholder="Enter new company name" required>
            </div>
            <div class="update-group">
                <label for="update-email">Email:</label>
                <input type="email" id="update-email" name="email" placeholder="Enter new email address" required>
            </div>
            <div class="update-group">
                <label for="update-phonenum">Phone No.:</label>
                <input type="text" id="update-phonenum" name="phonenum" placeholder="Enter new phone number" required>
            </div>
            <div class="update-group">
                <label for="update-address">Address:</label>
                <textarea id="update-address" name="address" placeholder="Enter new company address" required></textarea>
            </div>
            <div class="update-group">
                <label for="update-status">Status:</label>
                <select id="update-status" name="status" required>
                    <option value="New">New</option>
                    <option value="Contacted">Contacted</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="update-group">
                <label for="update-followup-date">Follow-up Date:</label>
                <input type="date" id="update-followup-date" name="followup_date">
            </div>
            <div class="update-group">
                <label for="update-remark">Remark:</label>
                <textarea id="update-remark" name="remark" placeholder="Enter remark or notes"></textarea>
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
        <p>Are you sure you want to delete this lead?</p>
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
        
        function openUpdateModal(id, name, company, email, phonenum, address, status, followup_date, remark, user_id) {
            document.getElementById('update-id').value = id;
            document.getElementById('update-name').value = name;
            document.getElementById('update-company').value = company;
            document.getElementById('update-email').value = email;
            document.getElementById('update-phonenum').value = phonenum;
            document.getElementById('update-address').value = address;
            document.getElementById('update-status').value = status;
            document.getElementById('update-followup-date').value = followup_date;
            document.getElementById('update-remark').value = remark;
            
            <?php if ($_SESSION['account_type'] === 'Admin'): ?>
                if (user_id) {
                    document.getElementById('update-user').value = user_id;
                } else {
                    document.getElementById('update-user').value = '';
                }
            <?php endif; ?>
            
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('update-modal').style.display = 'block';
        }

        function openDeleteModal(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('delete-modal').style.display = 'block';
        }

        function submitCloseAndConvertForm(id) {
            // Create a form dynamically
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            // Add the action and id inputs
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'close-convert';
            form.appendChild(actionInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);

            // Add the form to the document and submit it
            document.body.appendChild(form);
            form.submit();
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