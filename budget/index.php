<?php
// budget/index.php - Budget management page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$monthName = getMonthName($month);

// Handle add budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $submittedToken = $_POST['_csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        http_response_code(403);
        header('Location: index.php?error=csrf_mismatch');
        exit;
    }

    $category = trim($_POST['category']);
    $amount = floatval($_POST['amount']);

    if (empty($category) || empty($amount)) {
        $error = "Category and amount are required";
    } else {
        $check = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ? AND category = ? AND month = ? AND year = ?");
        $check->execute([$userId, $category, $month, $year]);
        if ($check->rowCount() > 0) {
            $error = "Budget already exists for this category";
        } else {
            $stmt = $pdo->prepare("INSERT INTO budgets (user_id, category, budget_amount, month, year) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $category, $amount, $month, $year]);
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Budget</title>
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
                    <h1>Budget Management</h1>
                    <p>Set monthly budgets for each category for <?= $monthName ?> <?= $year ?>.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Set Budget</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error" style="margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form action="index.php" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="add">
                            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
                                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                                    <label for="category">Category</label>
                                    <select name="category" id="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Food">Food</option>
                                        <option value="Transportation">Transportation</option>
                                        <option value="Bills">Bills</option>
                                        <option value="Education">Education</option>
                                        <option value="Health">Health</option>
                                        <option value="Shopping">Shopping</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                                    <label for="amount">Budget Amount</label>
                                    <input type="number" name="amount" id="amount" step="0.01" placeholder="Budget Amount" required>
                                </div>
                                <button type="submit" class="btn-primary" style="height: fit-content;">Set Budget</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Budget Summary Cards -->
                <div class="card">
                    <div class="card-header">
                        <h3>Budget Overview</h3>
                    </div>
                    <div style="padding: 24px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                            <?php
                            $categories = ['Food', 'Transportation', 'Bills', 'Education', 'Health', 'Shopping', 'Other'];
                            foreach ($categories as $cat): 
                                // Get budget
                                $budStmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ? AND category = ? AND month = ? AND year = ?");
                                $budStmt->execute([$userId, $cat, $month, $year]);
                                $budget = $budStmt->fetch();
                                
                                // Get actual spending
                                $spentStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND category = ? AND MONTH(date) = ? AND YEAR(date) = ?");
                                $spentStmt->execute([$userId, $cat, $month, $year]);
                                $spent = floatval($spentStmt->fetch()['total']);
                                
                                $budgetAmount = $budget ? floatval($budget['budget_amount']) : 0;
                                $pct = $budgetAmount > 0 ? min(100, max(0, round($spent / $budgetAmount * 100))) : 0;
                                $statusClass = $pct >= 100 ? 'exceeded' : ($pct >= 80 ? 'warning' : 'normal');
                                $barColor = $pct >= 100 ? '#E87575' : ($pct >= 80 ? '#F5C94F' : '#2D805F');
                            ?>
                            <div class="budget-<?= $statusClass ?>">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong><?= $cat ?></strong>
                                    <?php if ($budget): ?>
                                        <span><?= formatCurrency($spent) ?> / <?= formatCurrency($budgetAmount) ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--secondary-text); font-size: 13px;">No budget set</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($budget): ?>
                                    <div style="height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                                        <div style="height: 100%; width: <?= $pct ?>%; background: <?= $barColor ?>; transition: width 0.3s ease;"></div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 12px;">
                                        <span class="text-secondary"><?= $pct ?>% used</span>
                                        <span class="<?= $statusClass === 'exceeded' ? 'text-danger' : ($statusClass === 'warning' ? 'text-warning' : 'text-primary') ?>" style="font-weight: 500;">
                                            <?= ucfirst($statusClass) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
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