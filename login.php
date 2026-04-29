<?php
    require_once 'includes/database-connection.php';
    require_once 'includes/session.php';

    // Already logged in — go straight to dashboard
    if ($logged_in) {
        header('Location: index.php');
        exit;
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');

        $phone = trim($_POST['phone'] ?? '');
        $user = authenticate($pdo, $email, $phone);

        if ($user) {
            login($user);
            header('Location: index.php');
            exit;
        } else {
            $error = 'No account found with that email address.';
        }
    }

    require_once 'includes/header.php';
?>

<div class="login-container animate-bottom">
    <h1>Log In</h1>
    <hr>

    <?php if ($error): ?>
        <div class="login-error" style="display:block">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>


    <form method="POST" action="login.php" class="login-form">

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="you@email.com" required>
        </div>

        <div class="form-group">
            <label for="password">Phone Number:</label>
            <input type="password" id="phone" name="phone"
                   placeholder="123456789" required>
        </div>

        <div class="form-group">
            <input type="submit" value="Log In" class="submit-btn">
        </div>

    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
