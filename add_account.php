<?php
    require_once 'includes/header.php';
    require_login($logged_in);

    $uid     = $_SESSION['user_id'];
    $error   = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $bank    = trim($_POST['bank_name']     ?? '');
        $type    = trim($_POST['account_type']  ?? '');
        $balance = trim($_POST['balance']       ?? '0');

        if (!$bank || !$type) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO Account (User_ID, Bank_name, Account_type, Balance)
                 VALUES (:uid, :bank, :type, :balance)'
            );
            $stmt->execute([
                ':uid'     => $uid,
                ':bank'    => $bank,
                ':type'    => $type,
                ':balance' => $balance,
            ]);

            $success = 'Account added successfully!';
        }
    }

    // Load this user's existing accounts to show below the form
    $stmt = $pdo->prepare(
        'SELECT * FROM Account WHERE User_ID = :uid ORDER BY Account_ID DESC'
    );
    $stmt->execute([':uid' => $uid]);
    $my_accounts = $stmt->fetchAll();
?>

<div class="dashboard-page">
    <h1>Add Account</h1>
    <p class="page-sub">Link a new bank account to your profile</p>

    <?php if ($error): ?>
        <div class="form-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="form-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Add Account Form -->
    <div class="form-card">
        <form method="POST" action="add_account.php" class="modal-form">

            <div class="form-row">
                <div class="form-group">
                    <label>Bank Name:</label>
                    <input type="text" name="bank_name"
                           value="<?= htmlspecialchars($_POST['bank_name'] ?? '') ?>"
                           placeholder="e.g. Chase" required>
                </div>
                <div class="form-group">
                    <label>Account Type:</label>
                    <select name="account_type" required>
                        <option value="">Select type…</option>
                        <option value="Checking"     <?= ($_POST['account_type'] ?? '') === 'Checking'     ? 'selected' : '' ?>>Checking</option>
                        <option value="Savings"      <?= ($_POST['account_type'] ?? '') === 'Savings'      ? 'selected' : '' ?>>Savings</option>
                        <option value="Money Market" <?= ($_POST['account_type'] ?? '') === 'Money Market' ? 'selected' : '' ?>>Money Market</option>
                        <option value="Credit Card"  <?= ($_POST['account_type'] ?? '') === 'Credit Card'  ? 'selected' : '' ?>>Credit Card</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Starting Balance ($):</label>
                    <input type="number" name="balance"
                           value="<?= htmlspecialchars($_POST['balance'] ?? '0') ?>"
                           placeholder="0.00" min="0" step="0.01">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end">
                    <input type="submit" value="Add Account" class="submit-btn" style="margin-bottom:0">
                </div>
            </div>

        </form>
    </div>

    <!-- Existing accounts -->
    <?php if (!empty($my_accounts)): ?>
    <h2 style="color:#6191be;margin:30px 0 14px">Your Accounts</h2>
    <div class="tx-card">
        <table>
            <thead>
                <tr>
                    <th>Bank</th>
                    <th>Type</th>
                    <th style="text-align:right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($my_accounts as $acc): ?>
                    <tr>
                        <td><?= htmlspecialchars($acc['Bank_name']) ?></td>
                        <td><?= htmlspecialchars($acc['Account_type']) ?></td>
                        <td style="text-align:right" class="amt-in">
                            $<?= number_format($acc['Balance'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
