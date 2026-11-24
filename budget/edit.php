<?php
// budget/edit.php - Edit budget
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: index.php');
    exit;
}

// Get budget record
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$budget = $stmt->fetch();

if (!$budget) {
    header('Location: index.php');
    exit;
}

// Handle form submission
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
        $editStmt = $pdo->prepare("UPDATE budgets SET category = ?, budget_amount = ? WHERE id = ? AND user_id = ?");
        $editStmt->execute([$category, $amount, $id, $userId]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Edit Budget</title>
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
                    <h1>Edit Budget</h1>
                    <p>Update your budget details.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Budget Details</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form action="edit.php?id=<?= $id ?>" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select name="category" id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Food" <?= $budget['category'] === 'Food' ? 'selected' : '' ?>>Food</option>
                                    <option value="Transportation" <?= $budget['category'] === 'Transportation' ? 'selected' : '' ?>>Transportation</option>
                                    <option value="Bills" <?= $budget['category'] === 'Bills' ? 'selected' : '' ?>>Bills</option>
                                    <option value="Education" <?= $budget['category'] === 'Education' ? 'selected' : '' ?>>Education</option>
                                    <option value="Health" <?= $budget['category'] === 'Health' ? 'selected' : '' ?>>Health</option>
                                    <option value="Shopping" <?= $budget['category'] === 'Shopping' ? 'selected' : '' ?>>Shopping</option>
                                    <option value="Other" <?= $budget['category'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="amount">Budget Amount</label>
                                <input type="number" name="amount" id="amount" step="0.01" value="<?= $budget['budget_amount'] ?>" placeholder="Budget Amount" min="0.01" required>
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 8px;">
                                <button type="submit" class="btn-primary">Update Budget</button>
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