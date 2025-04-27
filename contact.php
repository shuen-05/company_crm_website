<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $subject = htmlspecialchars($_POST['subject']);
        $message = htmlspecialchars($_POST['message']);

        $success = "Thank you! Your message has been sent. We'll contact you soon.";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ABB Robotics CRM</title>
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
            margin-bottom: 35px;
            text-align: center;
            font-size: 1.7em;
            color: #7d0a0a;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .contact-card {
            background-color: #f1f1f1;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #7d0a0a;
            box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5);
        }

        .contact-card h3 {
            color: #7d0a0a;
            margin-top: 0;
        }

        .contact-card i {
            padding-right: 6px;
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

        .form-group select {
            font-family: "Poppins";
            width: 100%;
            padding: 8.5px 12px;
            box-sizing: border-box;
            border: 2px solid #7d0a0a;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            appearance: none;
            background-image: url(pic/down-arrow.svg);
            background-repeat: no-repeat;
            background-size: 12px;
            background-position: 98.5%;
        }

        .form-group textarea {
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

        .submit-btn {
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

        .submit-btn:hover {
            background-color: #6a0909;
        }

        .success-message {
            color: #5cb85c;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f0fff0;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <section id="content">
        <div class="container">
            <h1>Contact ABB Robotics Support</h1>

            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="contact-info">
                <div class="contact-card">
                    <h3><i class="fa fa-map-marker"></i> Headquarters</h3>
                    <p>ABB Robotics Ltd.<br>
                       Robotics Plaza<br>
                       Zurich, Switzerland</p>
                </div>

                <div class="contact-card">
                    <h3><i class="fa fa-phone"></i> Phone</h3>
                    <p>+603-1234 5678<br>
                       Working hours (Monday - Friday):<br>
                       8:00AM - 5:00PM</p>
                </div>

                <div class="contact-card">
                    <h3><i class="fa fa-envelope"></i> Email</h3>
                    <p>support@abbrobotics.com<br>
                       crmhelp@abbrobotics.com</p>
                </div>
            </div>

            <form action="contact.php" method="POST">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_SESSION['user_fullname']); ?>" placeholder="Enter your name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" placeholder="Enter your email address" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a subject</option>
                        <option value="Technical Support">Technical Support</option>
                        <option value="Account Help">Account Help</option>
                        <option value="Feature Request">Feature Request</option>
                        <option value="Bug Report">Bug Report</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" placeholder="Write a message..." required></textarea>
                </div>

                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>