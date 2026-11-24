<?php
// income/index.php - Income management page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get income records
$stmt = $pdo->prepare("SELECT * FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC");
$stmt->execute([$userId, $month, $year]);
$incomeRecords = $stmt->fetchAll();

// Get total income for the month
$totalStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$totalStmt->execute([$userId, $month, $year]);
$totalIncome = floatval($totalStmt->fetch()['total']);

$monthName = getMonthName($month);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Income</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="dashboard-content">
                <div class="welcome-section">
                    <h1>Income Management</h1>
                    <p>Manage your income sources for <?= $monthName ?> <?= $year ?>.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Income Records</h3>
                        <a href="<?= BASE_URL ?>/income/add.php" class="btn-primary" style="padding: 8px 16px; font-size: 13px;">Add Income</a>
                    </div>
                    
                    <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 16px 24px; background: var(--light-gray);">
                        <div>
                            <span>Total Income: <strong><?= formatCurrency($totalIncome) ?></strong></span>
                        </div>
                    </div>

                    <?php if (count($incomeRecords) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incomeRecords as $record): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($record['source']) ?></td>
                                            <td><?= htmlspecialchars($record['description']) ?></td>
                                            <td class="transaction-amount income"><?= formatCurrency($record['amount']) ?></td>
                                            <td><?= formatDate($record['date']) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/income/edit.php?id=<?= $record['id'] ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                                                <a href="<?= BASE_URL ?>/income/delete.php?id=<?= $record['id'] ?>" class="btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Delete this income record?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No income records for <?= $monthName ?> <?= $year ?>.</p>
                            <a href="<?= BASE_URL ?>/income/add.php" class="btn-primary">Add Income</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>

    <script>
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
            hamburger.classList.toggle('active');
        }

        hamburger.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });
    </script>
</body>
</html>