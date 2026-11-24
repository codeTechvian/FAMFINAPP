<?php
// goals/edit.php - Edit savings goal
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: index.php');
    exit;
}

// Get goal record
$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$goal = $stmt->fetch();

if (!$goal) {
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

    $goalName = trim($_POST['goal_name']);
    $targetAmount = floatval($_POST['target_amount']);
    $targetDate = $_POST['target_date'] ?: null;
    $description = trim($_POST['description']);

    if (empty($goalName) || empty($targetAmount) || $targetAmount <= 0) {
        $error = "Goal name and valid target amount required";
    } else {
        $editStmt = $pdo->prepare("UPDATE savings_goals SET goal_name = ?, target_amount = ?, target_date = ?, description = ? WHERE id = ? AND user_id = ?");
        $editStmt->execute([$goalName, $targetAmount, $targetDate, $description, $id, $userId]);
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
    <title>FAMFIN - Edit Savings Goal</title>
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
                    <h1>Edit Savings Goal</h1>
                    <p>Update your savings goal details.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Goal Details</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form action="edit.php?id=<?= $id ?>" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-group">
                                <label for="goal_name">Goal Name</label>
                                <input type="text" name="goal_name" id="goal_name" value="<?= htmlspecialchars($goal['goal_name']) ?>" placeholder="Goal Name" required>
                            </div>
                            <div class="form-group">
                                <label for="target_amount">Target Amount</label>
                                <input type="number" name="target_amount" id="target_amount" step="0.01" value="<?= $goal['target_amount'] ?>" placeholder="Target Amount" min="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="target_date">Target Date (Optional)</label>
                                <input type="date" name="target_date" id="target_date" value="<?= $goal['target_date'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label for="description">Description (Optional)</label>
                                <textarea name="description" id="description" rows="3" placeholder="Add details about this goal..."><?= htmlspecialchars($goal['description'] ?? '') ?></textarea>
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 8px;">
                                <button type="submit" class="btn-primary">Update Goal</button>
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