<?php

session_start();

$logged_in = isset($_SESSION['user_id']);

function login(array $user): void {
    $_SESSION['user_id']    = $user['User_ID'];
    $_SESSION['first_name'] = $user['First_Name'];
    $_SESSION['last_name']  = $user['Last_Name'];
    $_SESSION['email']      = $user['Email'];
}

function logout(): void {
    session_destroy();
}

function require_login(bool $logged_in): void {
    if (!$logged_in) {
        header('Location: login.php');
        exit;
    }
}

function authenticate(PDO $pdo, string $email, string $phone): array|false {
    $stmt = $pdo->prepare(
        'SELECT * FROM Account_Holder WHERE Email = :email AND Phone_number = :phone'
    );
    $stmt->execute([':email' => $email, ':phone' => $phone]);
    return $stmt->fetch();
}
