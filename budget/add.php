<?php
// budget/add.php - Add budget
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        http_response_code(403);
        header('Location: index.php?error=csrf_mismatch');
        exit;
    }

    $category = trim($_POST['category']);
    $amount = floatval($_POST['amount']);

    if (empty($category) || empty($amount) || $amount <= 0) {
        $error = "Valid category and positive amount required";
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
    <title>FAMFIN - Add Budget</title>
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
                    <h1>Set Monthly Budget</h1>
                    <p>Create a new budget for a category.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Budget Details</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form action="add.php" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-group">
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
                            <div class="form-group">
                                <label for="amount">Budget Amount</label>
                                <input type="number" name="amount" id="amount" step="0.01" placeholder="Budget Amount" min="0.01" required>
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 8px;">
                                <button type="submit" class="btn-primary">Set Budget</button>
                                <a href="index.php" class="btn-secondary">Cancel</a>
                            </div>
                        </form>
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