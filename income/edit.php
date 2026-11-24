<?php
// income/edit.php - Edit income record
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: index.php');
    exit;
}

// Get income record
$stmt = $pdo->prepare("SELECT * FROM income WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$record = $stmt->fetch();

if (!$record) {
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

    $source = trim($_POST['source']);
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $date = $_POST['date'] ?: date('Y-m-d');

    if (empty($source) || empty($amount)) {
        $error = "Source and amount are required";
    } else {
        $editStmt = $pdo->prepare("UPDATE income SET source = ?, description = ?, amount = ?, date = ? WHERE id = ? AND user_id = ?");
        $editStmt->execute([$source, $description, $amount, $date, $id, $userId]);
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
    <title>FAMFIN - Edit Income</title>
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
                    <h1>Edit Income</h1>
                    <p>Update your income record.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Income Details</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form action="edit.php?id=<?= $id ?>" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-group">
                                <label for="source">Source</label>
                                <input type="text" name="source" id="source" value="<?= htmlspecialchars($record['source']) ?>" placeholder="Source (e.g., Salary, Freelance, Investment)" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" name="description" id="description" value="<?= htmlspecialchars($record['description'] ?? '') ?>" placeholder="Optional description">
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="number" name="amount" id="amount" step="0.01" value="<?= $record['amount'] ?>" placeholder="Amount" required min="0.01">
                            </div>
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" name="date" id="date" value="<?= date('Y-m-d', strtotime($record['date'])) ?>" required>
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 8px;">
                                <button type="submit" class="btn-primary">Update Income</button>
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