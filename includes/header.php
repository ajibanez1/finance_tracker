<?php
    require_once 'database-connection.php';
    require_once 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinTrack</title>

    <link rel="stylesheet" href="css/styles.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lilita+One&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="container header-container">

        <div class="logo">
            <a href="index.php">Fin<span>Track</span></a>
        </div>

        <nav class="main-nav">
            <ul>
                <?php if ($logged_in): ?>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="add_account.php">Add Account</a></li>
                    <li class="nav-user">
                        <button class="nav-user-btn" onclick="toggleDropdown(event)">
                            <div class="nav-avatar">
                                <?= strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)) ?>
                            </div>
                            <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>
                            <span style="font-size:10px">▾</span>
                        </button>
                        <div class="nav-dropdown" id="nav-dd">
                            <div class="nav-dd-info">
                                <div class="nav-dd-name"><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></div>
                                <div class="nav-dd-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
                            </div>
                            <a class="nav-dd-btn" href="logout.php">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Log Out
                            </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="login.php">Log In</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>

    </div>
</header>

<main class="container">
