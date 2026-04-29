<?php
    require_once 'includes/database-connection.php';
    require_once 'includes/session.php';

    if ($logged_in) {
        header('Location: index.php');
        exit;
    }

    $error   = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first  = trim($_POST['first_name']   ?? '');
        $last   = trim($_POST['last_name']    ?? '');
        $email  = trim($_POST['email']        ?? '');
        $phone  = trim($_POST['phone_number'] ?? '');

        // Basic validation
        if (!$first || !$last || !$email || !$phone) {
            $error = 'Please fill in all fields.';
        } else {
            // Check if email already exists
            $check = $pdo->prepare('SELECT User_ID FROM Account_Holder WHERE Email = :email');
            $check->execute([':email' => $email]);

            if ($check->fetch()) {
                $error = 'An account with that email already exists.';
            } else {
                // Insert new user
                $stmt = $pdo->prepare(
                    'INSERT INTO Account_Holder (First_Name, Last_Name, Email, Phone_number)
                     VALUES (:first, :last, :email, :phone)'
                );
                $stmt->execute([
                    ':first' => $first,
                    ':last'  => $last,
                    ':email' => $email,
                    ':phone' => $phone,
                ]);

                $success = 'Account created! You can now log in.';
            }
        }
    }

    require_once 'includes/header.php';
?>

<div class="login-container animate-bottom">
    <h1>Create Account</h1>
    <hr>

    <?php if ($error): ?>
        <div class="login-error" style="display:block">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="login-success">
            <?= htmlspecialchars($success) ?>
            <a href="login.php">Log in →</a>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="register.php" class="login-form">

        <div class="form-group">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name"
                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                   placeholder="John" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name"
                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                   placeholder="Doe" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="you@email.com" required>
        </div>

        <div class="form-group">
            <label for="phone_number">Phone Number:</label>
            <input type="text" id="phone_number" name="phone_number"
                   value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>"
                   placeholder="1234567890" required>
        </div>

        <div class="form-group">
            <input type="submit" value="Create Account" class="submit-btn">
        </div>

    </form>

    <p class="form-switch">Already have an account? <a href="login.php">Log in</a></p>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
