<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    include 'config.php';

    // Get user account type
    $stmt = $conn->prepare("SELECT account_type FROM Users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $is_admin = ($user['account_type'] === 'Admin');
    $stmt->close();

    // fetch stats for dashboard
    $stats = [];
    $user_id = $_SESSION['user_id'];

    // get customer count
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM customers");
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM customers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $stats['customers'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // get lead counts by status
    $leadStatuses = ['New', 'Contacted', 'In Progress', 'Closed'];
    foreach ($leadStatuses as $status) {
        if ($is_admin) {
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM leads WHERE lead_status = ?");
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM leads WHERE user_id = ? AND lead_status = ?");
            $stmt->bind_param("is", $user_id, $status);
        }
        $stmt->execute();
        $stats['leads'][$status] = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }

    // get total interaction count - THIS IS THE KEY CHANGE
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM interactions");
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM interactions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $stats['interactions'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // get recent interactions
    $recentInteractions = [];
    if ($is_admin) {
        $stmt = $conn->prepare("
            SELECT i.*, c.customer_name, u.user_fullname as sales_rep 
            FROM interactions i
            JOIN customers c ON i.customer_id = c.customer_id
            JOIN users u ON i.user_id = u.user_id
            ORDER BY i.interaction_date DESC
            LIMIT 5
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT i.*, c.customer_name
            FROM interactions i
            JOIN customers c ON i.customer_id = c.customer_id
            WHERE i.user_id = ?
            ORDER BY i.interaction_date DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentInteractions[] = $row;
    }
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ABB Robotics CRM</title>
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

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
        }

        .welcome-banner {
            background-color: #7d0a0a;
            color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .welcome-banner h1 {
            margin: 0;
            font-size: 28px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 70px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .stat-card h3 {
            color: #7d0a0a;
            margin-top: 0px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin: 25px 0px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
        }

        .stat-actions {
            margin-top: 20px;
        }

        .action-btn {
            background-color: #ffffff;
            border: 2px solid #7d0a0a;
            color: #7d0a0a;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .action-btn:hover {
            background-color: #7d0a0a;
            color: #ffffff;
        }

        .action-btn i {
            margin-right: 5px;
        }

        .recent-activity {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .recent-activity h2 {
            color: #7d0a0a;
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item > div {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }

        .activity-item > p {
            margin-top: 8px; 
            margin-bottom: 0px;
        }

        .activity-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .type-call {
            background-color: #d4edda;
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

        .view-all {
            text-align: right;
            margin-top: 15px;
        }

        .view-all a {
            color: #7d0a0a;
            text-decoration: none;
            font-weight: bold;
        }

        .secondary-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .secondary-btn {
            background-color: #f0f0f0;
            border: none;
            color: #000000;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .secondary-btn:hover {
            background-color: #7d0a0a;
            color: #ffffff;
        }

        .admin-badge {
            background-color: #7d0a0a;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section class="container">
        <div class="welcome-banner">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_fullname']); ?><?php if ($is_admin): ?><span class="admin-badge">ADMIN</span><?php endif; ?></h1>
            <p>Here's what's happening with <?php echo $is_admin ? 'your system' : 'your customers'; ?> today</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="stat-value"><?php echo $stats['customers']; ?></div>
                <div class="stat-actions">
                    <a href="customer_management.php" class="action-btn">
                        <i class="fa fa-users"></i> View All Customers
                    </a>
                </div>
            </div>
            <div class="stat-card">
                <h3>New Leads</h3>
                <div class="stat-value"><?php echo $stats['leads']['New']; ?></div>
                <div class="stat-actions">
                    <a href="lead_management.php?status=New" class="action-btn">
                        <i class="fa fa-eye"></i> View All Leads
                    </a>
                </div>
            </div>
            <div class="stat-card">
                <h3>Closed Leads</h3>
                <div class="stat-value"><?php echo $stats['leads']['Closed']; ?></div>
                <div class="stat-actions">
                    <a href="report.php" class="action-btn">
                        <i class="fa fa-bar-chart"></i> View Reports
                    </a>
                </div>
            </div>
            <div class="stat-card">
                <h3>Total Interactions</h3>
                <div class="stat-value"><?php echo $stats['interactions']; ?></div>
                <div class="stat-actions">
                    <a href="interaction_history.php" class="action-btn">
                        <i class="fa fa-tasks"></i> View All Interactions
                    </a>
                </div>
            </div>
        </div>

        <div class="recent-activity">
            <h2>Recent Interactions</h2>
            <?php if (!empty($recentInteractions)): ?>
                <?php foreach ($recentInteractions as $interaction): ?>
                    <div class="activity-item">
                        <div>
                            <div>
                                <b><?php echo htmlspecialchars($interaction['customer_name']); ?></b>
                                <?php if ($is_admin && isset($interaction['sales_rep'])): ?>
                                    <span style="font-size: 12px;">(by <?php echo htmlspecialchars($interaction['sales_rep']); ?>)</span>
                                <?php endif; ?>
                                <span class="activity-type type-<?php echo strtolower($interaction['interaction_type']); ?>">
                                    <?php echo htmlspecialchars($interaction['interaction_type']); ?>
                                </span>
                            </div>
                            <div><?php echo htmlspecialchars($interaction['interaction_date']); ?></div>
                        </div>
                        <p><?php echo htmlspecialchars($interaction['interaction_remark']); ?></p>
                    </div>
                <?php endforeach; ?>
                <div class="view-all">
                    <a href="interaction_history.php">View All Interactions →</a>
                </div>
            <?php else: ?>
                <p>No recent interactions found.</p>
                <div class="view-all">
                    <a href="add_interaction.php">Log Your First Interaction →</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>