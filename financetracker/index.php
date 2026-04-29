<?php
    require_once 'includes/header.php';
    require_login($logged_in);

    $uid = $_SESSION['user_id']; // logged-in user's ID — used to scope ALL queries

    //Delete transaction 
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare(
            'DELETE t FROM Transactions t
             JOIN Account a ON t.Account_ID = a.Account_ID
             WHERE t.Transaction_ID = :id AND a.User_ID = :uid'
        );
        $stmt->execute([':id' => $_POST['delete_id'], ':uid' => $uid]);
        header('Location: index.php');
        exit;
    }

    //Add transaction
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
        $stmt = $pdo->prepare(
            'INSERT INTO Transactions
                (Account_ID, Category_ID, Pay_ID, date, amount, description, transaction_type)
             VALUES
                (:acc, :cat, :pay, :date, :amount, :desc, :type)'
        );
        $stmt->execute([
            ':acc'    => $_POST['account_id'],
            ':cat'    => $_POST['category_id'],
            ':pay'    => $_POST['pay_id'],
            ':date'   => $_POST['date'],
            ':amount' => $_POST['amount'],
            ':desc'   => $_POST['description'],
            ':type'   => $_POST['transaction_type'],
        ]);

        // Update account balance: Deposit adds, Withdrawal subtracts
        $type = $_POST['transaction_type'];
        $op   = ($type === 'Deposit') ? '+' : '-';
        $bal  = $pdo->prepare(
            "UPDATE Account SET Balance = Balance $op :amount WHERE Account_ID = :id"
        );
        $bal->execute([':amount' => $_POST['amount'], ':id' => $_POST['account_id']]);

        header('Location: index.php');
        exit;
    }

    //Filter values from URL 
    $filter_type   = $_GET['type']   ?? '';
    $filter_cat    = $_GET['cat']    ?? '';
    $filter_acc    = $_GET['acc']    ?? '';
    $filter_search = $_GET['search'] ?? '';

    //This user's accounts only
    $accounts = $pdo->prepare(
        'SELECT Account_ID, Bank_name, Account_type, Balance
         FROM Account
         WHERE User_ID = :uid'
    );
    $accounts->execute([':uid' => $uid]);
    $accounts = $accounts->fetchAll();

    $total_balance = 0;
    foreach ($accounts as $acc) {
        $total_balance += $acc['Balance'];
    }

    $deposits = $pdo->prepare(
        'SELECT COALESCE(SUM(t.amount), 0) AS total, COUNT(*) AS cnt
         FROM Transactions t
         JOIN Account a ON t.Account_ID = a.Account_ID
         WHERE a.User_ID = :uid AND t.transaction_type = "Deposit"'
    );
    $deposits->execute([':uid' => $uid]);
    $dep_row = $deposits->fetch();

    $withdrawals = $pdo->prepare(
        'SELECT COALESCE(SUM(t.amount), 0) AS total, COUNT(*) AS cnt
         FROM Transactions t
         JOIN Account a ON t.Account_ID = a.Account_ID
         WHERE a.User_ID = :uid AND t.transaction_type = "Withdrawal"'
    );
    $withdrawals->execute([':uid' => $uid]);
    $wit_row = $withdrawals->fetch();

    $net = $dep_row['total'] - $wit_row['total'];

    // ── LOAD: Categories and payees for the Add form 
    $categories = $pdo->query('SELECT * FROM Category')->fetchAll();
    $payees     = $pdo->query('SELECT * FROM `Payee/Source`')->fetchAll();

    // Transactions scoped to this user (with filters) ────
    $sql    = '
        SELECT t.Transaction_ID, t.date, t.amount, t.description, t.transaction_type,
               c.Name     AS category_name,
               p.Name     AS payee_name,
               a.Bank_name,
               a.Account_type
        FROM Transactions t
        JOIN Category          c ON t.Category_ID = c.Category_ID
        JOIN `Payee/Source`    p ON t.Pay_ID       = p.Pay_ID
        JOIN Account           a ON t.Account_ID   = a.Account_ID
        WHERE a.User_ID = :uid
    ';
    $params = [':uid' => $uid];

    if ($filter_type !== '') {
        $sql .= ' AND t.transaction_type = :type';
        $params[':type'] = $filter_type;
    }
    if ($filter_cat !== '') {
        $sql .= ' AND t.Category_ID = :cat';
        $params[':cat'] = $filter_cat;
    }
    if ($filter_acc !== '') {
        $sql .= ' AND t.Account_ID = :acc';
        $params[':acc'] = $filter_acc;
    }
    if ($filter_search !== '') {
        $sql .= ' AND (t.description LIKE :search OR p.Name LIKE :search OR c.Name LIKE :search)';
        $params[':search'] = '%' . $filter_search . '%';
    }

    $sql .= ' ORDER BY t.date DESC';

    $stmt         = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Total count for this user only (unfiltered)
    $count_stmt = $pdo->prepare(
        'SELECT COUNT(*) AS n FROM Transactions t
         JOIN Account a ON t.Account_ID = a.Account_ID
         WHERE a.User_ID = :uid'
    );
    $count_stmt->execute([':uid' => $uid]);
    $total_count = $count_stmt->fetch()['n'];
?>

<div class="dashboard-page">
    <h1>Dashboard</h1>
    <p class="page-sub">Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>!</p>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-label">Total Balance</div>
            <div class="stat-value">$<?= number_format($total_balance, 2) ?></div>
            <div class="stat-sub"><?= count($accounts) ?> account<?= count($accounts) !== 1 ? 's' : '' ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Deposits</div>
            <div class="stat-value">$<?= number_format($dep_row['total'], 2) ?></div>
            <div class="stat-sub"><?= $dep_row['cnt'] ?> entr<?= $dep_row['cnt'] !== 1 ? 'ies' : 'y' ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Withdrawals</div>
            <div class="stat-value red">$<?= number_format($wit_row['total'], 2) ?></div>
            <div class="stat-sub"><?= $wit_row['cnt'] ?> entr<?= $wit_row['cnt'] !== 1 ? 'ies' : 'y' ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Net</div>
            <div class="stat-value <?= $net >= 0 ? 'dark' : 'red' ?>">
                $<?= number_format(abs($net), 2) ?>
            </div>
            <div class="stat-sub"><?= $net >= 0 ? 'Surplus' : 'Deficit' ?></div>
        </div>

    </div>


    <div class="tx-card">

        <div class="tx-card-header">
            <h2>All Transactions</h2>
            <button class="add-btn" onclick="openModal()">+ Add Transaction</button>
        </div>

        <!-- Filters -->
        <form method="GET" action="index.php" class="filters-bar">
            <select name="type" onchange="this.form.submit()">
                <option value="" <?= $filter_type === '' ? 'selected' : '' ?>>All Types</option>
                <option value="Deposit"    <?= $filter_type === 'Deposit'    ? 'selected' : '' ?>>Deposit</option>
                <option value="Withdrawal" <?= $filter_type === 'Withdrawal' ? 'selected' : '' ?>>Withdrawal</option>
            </select>

            <select name="cat" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['Category_ID'] ?>"
                        <?= $filter_cat == $cat['Category_ID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['Name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="acc" onchange="this.form.submit()">
                <option value="">All Accounts</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['Account_ID'] ?>"
                        <?= $filter_acc == $acc['Account_ID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['Bank_name']) ?> (<?= $acc['Account_type'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="search" placeholder="Search transactions…"
                   value="<?= htmlspecialchars($filter_search) ?>"
                   onchange="this.form.submit()">

            <?php if ($filter_type || $filter_cat || $filter_acc || $filter_search): ?>
                <a href="index.php" class="clear-btn">Clear filters</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Payee / Source</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th style="text-align:right">Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr class="empty-row">
                        <td colspan="8">No transactions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td class="date-cell">
                                <?= date('M j, Y', strtotime($tx['date'])) ?>
                            </td>
                            <td><?= htmlspecialchars($tx['description']) ?></td>
                            <td>
                                <span class="badge <?= $tx['transaction_type'] === 'Deposit' ? 'badge-income' : 'badge-expense' ?>">
                                    <?= htmlspecialchars($tx['category_name']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($tx['payee_name']) ?></td>
                            <td><?= htmlspecialchars($tx['Bank_name']) ?> · <?= $tx['Account_type'] ?></td>
                            <td>
                                <span class="badge <?= $tx['transaction_type'] === 'Deposit' ? 'badge-income' : 'badge-expense' ?>">
                                    <?= $tx['transaction_type'] ?>
                                </span>
                            </td>
                            <td style="text-align:right"
                                class="<?= $tx['transaction_type'] === 'Deposit' ? 'amt-in' : 'amt-out' ?>">
                                <?= $tx['transaction_type'] === 'Deposit' ? '+' : '−' ?>$<?= number_format($tx['amount'], 2) ?>
                            </td>
                            <td>
                                <form method="POST" action="index.php"
                                      onsubmit="return confirm('Delete this transaction?')">
                                    <input type="hidden" name="delete_id"
                                           value="<?= $tx['Transaction_ID'] ?>">
                                    <button type="submit" class="x-btn">✕</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="result-count">
            Showing <?= count($transactions) ?> of <?= $total_count ?> transactions
        </div>

    </div>
</div>


<div class="overlay" id="modal-tx">
    <div class="modal">
        <h2>New Transaction</h2>

        <form method="POST" action="index.php" class="modal-form">
            <input type="hidden" name="add_transaction" value="1">

            <div class="form-row">
                <div class="form-group">
                    <label>Account:</label>
                    <select name="account_id" required>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['Account_ID'] ?>">
                                <?= htmlspecialchars($acc['Bank_name']) ?> (<?= $acc['Account_type'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Amount ($):</label>
                    <input type="number" name="amount" placeholder="0.00"
                           min="0.01" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Type:</label>
                    <select name="transaction_type" required>
                        <option value="Deposit">Deposit</option>
                        <option value="Withdrawal">Withdrawal</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category:</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['Category_ID'] ?>">
                                <?= htmlspecialchars($cat['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payee / Source:</label>
                    <select name="pay_id" required>
                        <?php foreach ($payees as $pay): ?>
                            <option value="<?= $pay['Pay_ID'] ?>">
                                <?= htmlspecialchars($pay['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description:</label>
                <input type="text" name="description" placeholder="Brief note…" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit">Add Transaction</button>
            </div>

        </form>
    </div>
</div>


<script>
    function openModal()  { document.getElementById('modal-tx').classList.add('open');    }
    function closeModal() { document.getElementById('modal-tx').classList.remove('open'); }

    document.getElementById('modal-tx').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>

<?php require_once 'includes/footer.php'; ?>
