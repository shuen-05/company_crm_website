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
    <title>Terms and Conditions - ABB Robotics CRM</title>
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
            width: 1000px;
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
        
        .container h2 {
            color: #7d0a0a;
            margin-top: 30px;
            border-bottom: 2px solid #d3d3d3;
            padding-bottom: 10px;
        }
        
        p, li {
            line-height: 1.6;
        }
        
        .back-to-top {
            display: block;
            text-align: right;
            margin-top: 20px;
        }
        
        .back-to-top a {
            color: #7d0a0a;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section id="content">
        <div class="container">
            <h1>Terms and Conditions</h1>
            
            <p><em>Last updated: <?php echo date('F j, Y'); ?></em></p>
            
            <h2 id="section1">1. Acceptance of Terms</h2>
            <p>By accessing and using the ABB Robotics CRM system, you agree to be bound by these Terms and Conditions.</p>
            
            <h2 id="section2">2. User Responsibilities</h2>
            <ul>
                <li>Maintain confidentiality of your account credentials</li>
                <li>Use the system only for authorized purposes</li>
                <li>Comply with all applicable laws and regulations</li>
            </ul>
            
            <h2 id="section3">3. Data Protection</h2>
            <p>We comply with GDPR and other data protection regulations. All customer data must be handled according to our Data Protection Policy.</p>
            
            <h2 id="section4">4. Intellectual Property</h2>
            <p>All content and materials in this CRM system are property of ABB Robotics and protected by copyright laws.</p>
            
            <h2 id="section5">5. Termination</h2>
            <p>ABB Robotics reserves the right to terminate access to any user who violates these terms.</p>
            
            <div class="back-to-top">
                <a href="tnc.php"><i class="fa fa-arrow-up"></i> Back to Top</a>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>