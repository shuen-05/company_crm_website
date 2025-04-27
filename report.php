<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
}

include 'config.php';

// get user account type
$stmt = $conn->prepare("SELECT account_type FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$is_admin = ($user['account_type'] === 'Admin');
$stmt->close();

// get lead status counts
$leadStatuses = ['New', 'Contacted', 'In Progress', 'Closed'];
$leadStats = [];

foreach ($leadStatuses as $status) {
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM leads WHERE lead_status = ?");
        $stmt->bind_param("s", $status);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM leads WHERE user_id = ? AND lead_status = ?");
        $stmt->bind_param("is", $_SESSION['user_id'], $status);
    }
    $stmt->execute();
    $leadStats[$status] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

// prepare data for chart
$chartLabels = json_encode(array_keys($leadStats));
$chartData = json_encode(array_values($leadStats));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Statistics - ABB Robotics CRM</title>
    <link rel="icon" type="image/x-icon" href="pic/ABBfavicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .header {
            background-color: #7d0a0a;
            color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .stats-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .chart-container {
            height: 400px;
            margin: 30px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background-color: #f8f8f8;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-card h3 {
            color: #7d0a0a;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .admin-badge {
            background-color: #7d0a0a;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #7d0a0a;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section class="container">
        <div class="header">
            <h1>Lead Status Statistics <?php if ($is_admin): ?><span class="admin-badge">ADMIN</span><?php endif; ?></h1>
            <p>Overview of leads by status</p>
        </div>

        <div class="stats-container">
            <h2>Lead Distribution</h2>
            <div class="chart-container">
                <canvas id="leadChart"></canvas>
            </div>

            <div class="stats-grid">
                <?php foreach ($leadStats as $status => $count): ?>
                    <div class="stat-card">
                        <h3><?php echo htmlspecialchars($status); ?> Leads</h3>
                        <div class="stat-value"><?php echo $count; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="mainpage.php" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to Main Page
        </a>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        // chart.js implementation
        const ctx = document.getElementById('leadChart').getContext('2d');
        const leadChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $chartLabels; ?>,
                datasets: [{
                    label: 'Number of Leads',
                    data: <?php echo $chartData; ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Leads'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Lead Status'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' leads';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>