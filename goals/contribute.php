<?php
// goals/contribute.php - Add savings contribution
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$goalId = intval($_POST['goal_id'] ?? $_GET['goal'] ?? 0);

if (empty($goalId)) {
    header('Location: index.php');
    exit;
}

// Get goal record
$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ?");
$stmt->execute([$goalId, $userId]);
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

    $amount = floatval($_POST['amount']);
    
    if (empty($amount) || $amount <= 0) {
        $error = "Valid amount required";
    } else {
        // Check if adding would exceed target
        $newCurrent = floatval($goal['current_amount']) + $amount;
        if ($newCurrent > floatval($goal['target_amount'])) {
            $error = "Contribution would exceed target amount";
        } else {
            $contribStmt = $pdo->prepare("INSERT INTO savings_contributions (goal_id, user_id, amount, date) VALUES (?, ?, ?, ?)");
            $contribStmt->execute([$goalId, $userId, $amount, date('Y-m-d')]);
            
            // Update goal current amount
            $updateStmt = $pdo->prepare("UPDATE savings_goals SET current_amount = ? WHERE id = ? AND user_id = ?");
            $updateStmt->execute([$newCurrent, $goalId, $userId]);
            
            // Check if goal is completed
            if ($newCurrent >= floatval($goal['target_amount'])) {
                $compStmt = $pdo->prepare("UPDATE savings_goals SET status = 'completed' WHERE id = ? AND user_id = ?");
                $compStmt->execute([$goalId, $userId]);
            }
            
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
    <title>FAMFIN - Add Savings Contribution</title>
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
                    <h1>Add Savings Contribution</h1>
                    <p>Contribute to your savings goal.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><?= htmlspecialchars($goal['goal_name']) ?></h3>
                    </div>
                    <div style="padding: 24px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px;">
                            <div style="background: var(--light-gray); padding: 16px; border-radius: 8px;">
                                <div style="font-size: 12px; color: var(--secondary-text); margin-bottom: 4px;">Current Saved</div>
                                <div style="font-weight: 600; color: var(--primary-green);"><?= formatCurrency(floatval($goal['current_amount'])) ?></div>
                            </div>
                            <div style="background: var(--light-gray); padding: 16px; border-radius: 8px;">
                                <div style="font-size: 12px; color: var(--secondary-text); margin-bottom: 4px;">Target Amount</div>
                                <div style="font-weight: 600;"><?= formatCurrency(floatval($goal['target_amount'])) ?></div>
                            </div>
                        </div>

                        <div class="progress" style="height: 10px; margin-bottom: 24px;">
                            <?php 
                            $progress = floatval($goal['target_amount']) > 0 ? min(100, (floatval($goal['current_amount']) / floatval($goal['target_amount'])) * 100) : 0;
                            ?>
                            <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
                        </div>
                        <div style="text-align: right; margin-bottom: 24px; font-size: 14px; color: var(--secondary-text);">
                            <?= number_format($progress, 1) ?>% Complete
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <?php if ($goal['status'] === 'completed'): ?>
                            <div class="alert alert-success">Goal completed! 🎉</div>
                        <?php else: ?>
                            <form action="contribute.php?goal=<?= $goalId ?>" method="POST">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-group">
                                    <label for="amount">Contribution Amount</label>
                                    <input type="number" name="amount" id="amount" step="0.01" placeholder="Contribution Amount" min="0.01" required>
                                </div>
                                <div style="display: flex; gap: 12px; margin-top: 8px;">
                                    <button type="submit" class="btn-primary">Add Contribution</button>
                                    <a href="index.php" class="btn-secondary">Cancel</a>
                                </div>
                            </form>
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