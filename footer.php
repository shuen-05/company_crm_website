<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Major+Mono+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        #footer {
            font-family: "Poppins";
            background-color: #7d0a0a;
            position: relative;
            padding-block: 3px;
            padding-inline: 25px;
            text-align: left;
            color: #eeeeee;
        }

        #abb-header {
            font-family: "Major Mono Display";
            font-weight: 800;
            font-size: 40px;
            margin-top: 17px;
            margin-bottom: 13px;
        }

        .contacts {
            margin-bottom: 25px;
            font-size: 14px;
        }

        .contacts p {
            margin-block: 2px;
        }

        #tel .fa-phone {
            position: relative;
            top: 3px;
            padding-right: 5px;
        }

        #mail .fa-envelope {
            position: relative;
            top: 1px;
            padding-right: 5px;
        }

        #mail a {
            color: #eeeeee;
            text-decoration: none;
        }

        #mail a:hover {
            color: #ead196;
        }

        .footer-section {
            margin-bottom: 18px;
        }

        .footer-section h2 {
            margin-top: 0;
            margin-bottom: 4px;
            font-size: 20px;
        }

        .footer-links {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .footer-links li {
            display: inline-block;
            margin-inline: 10px;
            font-size: 14px;
        }

        .footer-links a {
            color: #eeeeee;
            text-decoration: none;
        }

        .footer-links a:hover {
            color: #ead196;
        }

        #copyright {
            text-align: center;
            font-size: 14px;
        }

        hr {
            color: #eeeeee;
        }

    </style>
</head>

<body>
    <footer id="footer">
        <h2 id="abb-header">ABB</h2>
        <div class="contacts">
            <p id="tel"><i class="fa fa-phone" style="font-size:18px;"></i> +603-1234 5678</p>
            <p id="mail"><i class="fa fa-envelope" style="font-size:16px;"></i><a href="mailto:support@abbrobotics.com"> support@abbrobotics.com</a></p>
        </div>

        <div class="footer-section">
            <h2>ABOUT</h2>
            <ul class="footer-links">
                <li><a href="https://en.wikipedia.org/wiki/ABB">WIKIPEDIA</a></li>
                <li><a href="https://www.instagram.com/abbgroup/?hl=en">INSTAGRAM</a></li>
                <li><a href="https://www.youtube.com/@ABBRobotics">YOUTUBE</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h2>SUPPORT</h2>
            <ul class="footer-links">
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">CONTACT US</a></li>
                <li><a href="tnc.php">TERMS & CONDITIONS</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h2>MY ACCOUNT</h2>
            <ul class="footer-links">
                <li><a href="customer_management.php">CUSTOMER MANAGEMENT</a></li>
                <li><a href="interaction_history.php">INTERACTION HISTORY</a></li>
                <li><a href="lead_management.php">LEAD MANAGEMENT</a></li>
                <?php if (isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'Admin'): ?>
                    <li><a href="user_management.php">MANAGE USERS</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <hr>
        <p id="copyright">Copyright &copy; 2024 ABB Robotics. All Rights Reserved.</p>
    </footer>
</body>
</html>