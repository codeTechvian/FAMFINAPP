<?php
// reports/index.php - Financial reports page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$whereClause = "WHERE user_id = ? AND date BETWEEN ? AND ?";
$params = [$userId, $startDate, $endDate];

// Get total income
$incomeStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income $whereClause");
$incomeStmt->execute($params);
$totalIncome = floatval($incomeStmt->fetch()['total']);

// Get total expenses
$expenseStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses $whereClause");
$expenseStmt->execute($params);
$totalExpenses = floatval($expenseStmt->fetch()['total']);

// Get expenses by category
$categoryStmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses $whereClause GROUP BY category ORDER BY total DESC");
$categoryStmt->execute($params);
$categories = $categoryStmt->fetchAll();

// Get income by source
$sourceStmt = $pdo->prepare("SELECT source, SUM(amount) as total FROM income $whereClause GROUP BY source ORDER BY total DESC");
$sourceStmt->execute($params);
$sources = $sourceStmt->fetchAll();

$netSavings = $totalIncome - $totalExpenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Reports</title>
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
                    <h1>Financial Reports</h1>
                    <p>View your financial summary and category breakdowns.</p>
                </div>

                <!-- Date Range Filter -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3>Date Range</h3>
                    </div>
                    <div style="padding: 24px;">
                        <form method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="end_date">End Date</label>
                                <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
                            </div>
                            <button type="submit" class="btn-primary" style="height: fit-content;">Generate Report</button>
                        </form>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3>Financial Summary</h3>
                    </div>
                    <div style="padding: 24px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div style="background: var(--green-light); padding: 24px; border-radius: 12px;">
                                <div style="font-size: 13px; color: var(--secondary-text); margin-bottom: 4px;">Total Income</div>
                                <div style="font-size: 28px; font-weight: 700; color: var(--primary-green);"><?= formatCurrency($totalIncome) ?></div>
                            </div>
                            <div style="background: var(--red-light); padding: 24px; border-radius: 12px;">
                                <div style="font-size: 13px; color: var(--secondary-text); margin-bottom: 4px;">Total Expenses</div>
                                <div style="font-size: 28px; font-weight: 700; color: var(--expense-accent);"><?= formatCurrency($totalExpenses) ?></div>
                            </div>
                            <div style="background: <?= $netSavings >= 0 ? 'var(--light-green)' : 'var(--red-light)' ?>; padding: 24px; border-radius: 12px;">
                                <div style="font-size: 13px; color: var(--secondary-text); margin-bottom: 4px;">Net Savings</div>
                                <div style="font-size: 28px; font-weight: 700; color: <?= $netSavings >= 0 ? 'var(--primary-green)' : 'var(--expense-accent)' ?>;"><?= formatCurrency($netSavings) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category-Based Expense Report -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3>Expenses by Category</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (count($categories) > 0): ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Total Spent</th>
                                            <th>Percentage</th>
                                            <th>Visual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $cat): ?>
                                            <?php
                                            $catTotal = floatval($cat['total']);
                                            $percentage = $totalExpenses > 0 ? min(100, max(0, ($catTotal / $totalExpenses) * 100)) : 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cat['category']) ?></td>
                                                <td class="transaction-amount expense"><?= formatCurrency($catTotal) ?></td>
                                                <td><?= number_format($percentage, 1) ?>%</td>
                                                <td>
                                                    <div style="width: 120px; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                                                        <div style="height: 100%; width: <?= $percentage ?>%; background: var(--primary-green);"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No expense data for the selected date range.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Income by Source -->
                <div class="card">
                    <div class="card-header">
                        <h3>Income by Source</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (count($sources) > 0): ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Source</th>
                                            <th>Total Amount</th>
                                            <th>Percentage</th>
                                            <th>Visual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sources as $source): ?>
                                            <?php
                                            $sourceTotal = floatval($source['total']);
                                            $percentage = $totalIncome > 0 ? min(100, max(0, ($sourceTotal / $totalIncome) * 100)) : 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($source['source']) ?></td>
                                                <td class="transaction-amount income"><?= formatCurrency($sourceTotal) ?></td>
                                                <td><?= number_format($percentage, 1) ?>%</td>
                                                <td>
                                                    <div style="width: 120px; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                                                        <div style="height: 100%; width: <?= $percentage ?>%; background: var(--primary-green);"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No income data for the selected date range.</p>
                            </div>
                        <?php endif; ?>
                    </div>
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