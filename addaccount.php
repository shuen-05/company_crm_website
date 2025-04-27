<?php
    include 'config.php';

    // Predefined accounts: [username, email, fullname, password, date_of_birth, account_type]
    $users = [
        ['crocodilo', 'admin1@example.com', 'crocodilo bombasticko', 'admin123', '1980-01-01', 'admin'],
        ['jackychan', 'user1@example.com', 'Jacky Chan', 'user123', '1995-03-15', 'user'],
        ['jennydoe', 'user2@example.com', 'Jenny Doe', 'jenny123', '1992-07-20', 'user']
    ];

    $stmt = $conn->prepare("INSERT INTO users (username, user_email, fullname, user_password, date_of_birth, account_type) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($users as $user) {
        $username = $user[0];
        $email = $user[1];
        $fullname = $user[2];
        $hashed_password = password_hash($user[3], PASSWORD_DEFAULT);
        $dob = $user[4];
        $account_type = $user[5];

        $stmt->bind_param("ssssss", $username, $email, $fullname, $hashed_password, $dob, $account_type);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();

    echo "Predefined users added successfully!";
?>
