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
    <title>FAQ - ABB Robotics CRM</title>
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

        .quick-links {
            margin-bottom: 35px;
            padding: 18px 25px;;
            background-color: #f1f1f1;
            border-radius: 10px;
            box-shadow: 2.5px 2.5px 8px rgba(0, 0, 0, 0.5);
        }
        
        .quick-links h3 {
            margin-top: 5px;
            color: #7d0a0a;
        }
        
        .quick-links a {
            color: #7d0a0a;
            text-decoration: none;
            display: block;
            margin: 5px 0;
        }
        
        .quick-links a:hover {
            text-decoration: underline;
        }
        
        .faq-section {
            margin-bottom: 35px;
        }

        .faq-section h2 {
            margin-bottom: 15px;
        }
        
        .faq-question {
            background-color: #f1f1f1;
            position: relative;
            margin-bottom: 10px;
            padding: 13px 15px 15px;
            border-radius: 6px;
            font-weight: bold;
            color: #7d0a0a;
            cursor: pointer;
            box-shadow: 1.5px 1.5px 3px rgba(0, 0, 0, 0.5);
        }

        .faq-question i {
            position: relative;
            top: 1.5px;
            padding-right: 8px;
            font-size: 20px;
        }
        
        .faq-answer {
            display: none;
            padding: 2px 15px 15px;
        }
        
        .faq-answer.show {
            display: block;
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
            <h1>Frequently Asked Questions</h1>
            
            <div class="quick-links">
                <h3>Quick Links:</h3>
                <a href="#account">Account Questions</a>
                <a href="#technical">Technical Support</a>
                <a href="#data">Data Management</a>
                <a href="#access">Access Issues</a>
            </div>
            
            <div class="faq-section" id="account">
                <h2>Account Questions</h2>
                
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <i class="fa fa-plus-circle"></i> How do I reset my password?
                </div>
                <div class="faq-answer">
                    <p>To reset your password, go to the login page and click on "Forgot Password". You'll receive an email with instructions to reset your password.</p>
                </div>
                
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <i class="fa fa-plus-circle"></i> How do I update my profile information?
                </div>
                <div class="faq-answer">
                    <p>You can update your profile information by navigating to the "Edit Profile" page from the main menu.</p>
                </div>
            </div>
            
            <div class="faq-section" id="technical">
                <h2>Technical Support</h2>
                
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <i class="fa fa-plus-circle"></i> What browsers are supported?
                </div>
                <div class="faq-answer">
                    <p>Our CRM system supports the latest versions of Chrome, Firefox, Safari, and Edge.</p>
                </div>
                
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <i class="fa fa-plus-circle"></i> The system is running slow, what should I do?
                </div>
                <div class="faq-answer">
                    <p>Try clearing your browser cache or try a different browser. If the problem persists, contact IT support.</p>
                </div>
            </div>
            
            <div class="faq-section" id="data">
                <h2>Data Management</h2>
                
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <i class="fa fa-plus-circle"></i> How often is data backed up?
                </div>
                <div class="faq-answer">
                    <p>All CRM data is backed up daily with encrypted storage.</p>
                </div>
            </div>
            
            <div class="faq-section" id="access">
                <h2>Access Issues</h2>
                
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <i class="fa fa-plus-circle"></i> I can't log in to my account
                </div>
                <div class="faq-answer">
                    <p>First try resetting your password. If you still can't access your account, contact your system administrator.</p>
                </div>
            </div>
            
            <div class="back-to-top">
                <a href="faq.php"><i class="fa fa-arrow-up"></i> Back to Top</a>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        function toggleAnswer(element) {
            const answer = element.nextElementSibling;
            answer.classList.toggle('show');
        }
        
        // Open specific FAQ if anchor in URL
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash) {
                const targetSection = document.querySelector(window.location.hash);
                if (targetSection) {
                    targetSection.scrollIntoView();
                    // Open first question in section
                    const firstQuestion = targetSection.querySelector('.faq-question');
                    if (firstQuestion) {
                        firstQuestion.click();
                    }
                }
            }
        });
    </script>
</body>
</html>