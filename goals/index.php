<?php
// goals/index.php - Savings goals page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Get all goals
$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$goals = $stmt->fetchAll();

// Calculate totals
$totalSaved = 0;
$totalGoalAmount = 0;
$completedGoals = 0;
foreach ($goals as $goal) {
    $totalSaved += floatval($goal['current_amount']);
    $totalGoalAmount += floatval($goal['target_amount']);
    if ($goal['status'] === 'completed') $completedGoals++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Savings Goals</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <style>
        #modalOverlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(0, 0, 0, 0.45) !important;
            z-index: 9998 !important;
            backdrop-filter: blur(6px) !important;
        }

        #contributeModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 24px !important;
            z-index: 9999 !important;
            overflow-y: auto !important;
        }

        #contributeModal .modal-content {
            width: 100% !important;
            max-width: min(520px, calc(100vw - 32px)) !important;
            max-height: calc(100vh - 40px) !important;
            overflow-y: auto !important;
            background: #FFFFFF !important;
            border-radius: 20px !important;
            padding: 32px !important;
            position: relative !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid #E5EAE6 !important;
        }

        /* Modal Header */
        .modal-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 24px !important;
            padding-bottom: 20px !important;
            border-bottom: 1px solid #F2F5F4 !important;
        }

        .modal-header .modal-title {
            font-size: 20px !important;
            font-weight: 700 !important;
            color: #222222 !important;
            margin: 0 !important;
            text-align: left !important;
            letter-spacing: -0.5px !important;
        }

        .modal-close {
            width: 32px !important;
            height: 32px !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: background 0.2s ease !important;
            border: none !important;
            background: transparent !important;
            color: #777777 !important;
            font-size: 24px !important;
            line-height: 1 !important;
            padding: 0 !important;
        }

        .modal-close:hover {
            background: #F2F5F4 !important;
            color: #222222 !important;
        }

        /* Savings Goal Information */
        .modal-info-section {
            margin-bottom: 32px !important;
            padding: 24px !important;
            background: #E8F5F0 !important;
            border-radius: 16px !important;
            border: 1px solid #C1E1D1 !important;
        }

        .modal-info-section .info-title {
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #2D805F !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            margin-bottom: 16px !important;
            display: block !important;
        }

        .modal-info-row {
            display: flex !important;
            justify-content: space-between !important;
            align-items: baseline !important;
            margin-bottom: 14px !important;
        }

        .modal-info-row:last-child {
            margin-bottom: 0 !important;
        }

        .modal-info-label {
            font-size: 14px !important;
            color: #777777 !important;
            font-weight: 500 !important;
        }

        .modal-info-value {
            font-size: 16px !important;
            font-weight: 700 !important;
            color: #222222 !important;
        }

        .modal-info-value.highlight {
            color: #2D805F !important;
        }

        /* Contribution Amount Field */
        .modal-field {
            margin-bottom: 24px !important;
        }

        .modal-field label {
            display: block !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            color: #777777 !important;
            margin-bottom: 8px !important;
        }

        .modal-field input[type="number"] {
            width: 100% !important;
            padding: 14px 16px !important;
            border: 2px solid #E5EAE6 !important;
            border-radius: 12px !important;
            font-size: 16px !important;
            font-family: 'Poppins', sans-serif !important;
            background: #FFFFFF !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
            color: #222222 !important;
        }

        .modal-field input[type="number"]:focus {
            outline: none !important;
            border-color: #2D805F !important;
            box-shadow: 0 0 0 3px #E4F4EC !important;
        }

        .modal-field input[type="number"]::placeholder {
            color: #B9C4C0 !important;
        }

        /* Modal Footer / Actions */
        .modal-actions {
            display: flex !important;
            gap: 12px !important;
            margin-top: 8px !important;
        }

        .modal-btn-cancel {
            flex: 1 !important;
            background: #FFFFFF !important;
            border: 2px solid #2D805F !important;
            color: #2D805F !important;
            padding: 14px 24px !important;
            border-radius: 12px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            font-family: 'Poppins', sans-serif !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-align: center !important;
        }

        .modal-btn-cancel:hover {
            background: #E8F5F0 !important;
        }

        .modal-btn-submit {
            flex: 1 !important;
            background: #2D805F !important;
            color: #FFFFFF !important;
            border: none !important;
            padding: 14px 24px !important;
            border-radius: 12px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            font-family: 'Poppins', sans-serif !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-align: center !important;
        }

        .modal-btn-submit:hover {
            background: #246A4D !important;
        }

        /* Mobile responsive */
        @media (max-width: 576px) {
            #contributeModal {
                padding: 16px !important;
            }

            #contributeModal .modal-content {
                max-width: 100% !important;
                padding: 24px !important;
                border-radius: 16px !important;
            }

            .modal-actions {
                flex-direction: column !important;
            }

            .modal-btn-cancel,
            .modal-btn-submit {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="dashboard-content">
                <div class="welcome-section">
                    <h1>Savings Goals</h1>
                    <p>Track your progress towards your financial goals.</p>
                </div>

                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3>Goals Overview</h3>
                        <a href="<?= BASE_URL ?>/goals/add.php" class="btn-primary" style="padding: 8px 16px; font-size: 13px;">Create Goal</a>
                    </div>
                    <div style="padding: 24px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
                            <div style="background: var(--green-light); padding: 20px; border-radius: 12px;">
                                <div style="font-size: 13px; color: var(--secondary-text); margin-bottom: 4px;">Total Saved</div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--primary-green);"><?= formatCurrency($totalSaved) ?></div>
                            </div>
                            <div style="background: var(--light-green); padding: 20px; border-radius: 12px;">
                                <div style="font-size: 13px; color: var(--secondary-text); margin-bottom: 4px;">Total Goal Amount</div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--primary-text);"><?= formatCurrency($totalGoalAmount) ?></div>
                            </div>
                            <div style="background: var(--yellow-light); padding: 20px; border-radius: 12px;">
                                <div style="font-size: 13px; color: var(--secondary-text); margin-bottom: 4px;">Goals Completed</div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--budget-yellow);"><?= $completedGoals ?></div>
                            </div>
                        </div>

                        <?php if ($totalGoalAmount > 0): ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                                    <span>Overall Progress</span>
                                    <span class="text-primary" style="font-weight: 600;"><?= number_format(min(100, ($totalSaved / $totalGoalAmount) * 100), 1) ?>%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar" style="width: <?= min(100, ($totalSaved / $totalGoalAmount) * 100) ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Individual Savings Goal Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                    <?php if (count($goals) > 0): ?>
                        <?php foreach ($goals as $goal): ?>
                            <?php
                            $current = floatval($goal['current_amount']);
                            $target = floatval($goal['target_amount']);
                            $progress = $target > 0 ? min(100, max(0, ($current / $target) * 100)) : 0;
                            $remaining = $target - $current;
                            $statusClass = $goal['status'] === 'completed' ? 'completed' : 'active';
                            ?>
                            <div class="goal-card">
                                <div class="goal-header">
                                    <h4 class="goal-name"><?= htmlspecialchars($goal['goal_name']) ?></h4>
                                    <span class="goal-status <?= $statusClass ?>"><?= ucfirst($goal['status']) ?></span>
                                </div>
                                <div class="goal-progress">
                                    <div class="goal-progress-info">
                                        <span>Saved: <span class="goal-amount"><?= formatCurrency($current) ?></span></span>
                                        <span>Target: <span class="goal-amount"><?= formatCurrency($target) ?></span></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
                                    </div>
                                    <div class="goal-progress-info">
                                        <span><?= number_format($progress, 1) ?>% Complete</span>
                                        <span class="text-<?= $remaining > 0 ? 'expense' : 'income' ?>" style="font-weight: 500;">
                                            <?= $remaining > 0 ? formatCurrency($remaining) . ' to go' : 'Goal reached!' ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
                                    <button type="button" class="btn-primary contribute-btn" style="flex: 1; text-align: center; padding: 10px;" data-goal-id="<?= $goal['id'] ?>" data-goal-name="<?= htmlspecialchars($goal['goal_name'], ENT_QUOTES) ?>" data-current-amount="<?= $current ?>" data-target-amount="<?= $target ?>" data-remaining="<?= $remaining ?>">Contribute</button>
                                    <a href="<?= BASE_URL ?>/goals/edit.php?id=<?= $goal['id'] ?>" class="btn-secondary" style="flex: 1; text-align: center; padding: 10px;">Edit</a>
                                    <form method="POST" action="<?= BASE_URL ?>/goals/delete.php" style="flex: 1; display: flex;" onsubmit="return confirm('Delete this goal?');">
                                        <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                                        <button type="submit" class="btn-danger" style="flex: 1; text-align: center; padding: 10px; width: 100%;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 60px 24px;">
                            <div style="font-size: 48px; margin-bottom: 16px;">🎯</div>
                            <h3 style="margin-bottom: 12px;">No Goals Yet</h3>
                            <p style="color: var(--secondary-text); margin-bottom: 24px;">Start your savings journey by creating your first goal.</p>
                            <a href="<?= BASE_URL ?>/goals/add.php" class="btn-primary">Create Your First Goal</a>
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

<!-- Contribution Modal -->
    <div id="modalOverlay" class="modal-overlay"></div>
    <div id="contributeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Savings Contribution</h3>
                <button type="button" id="closeModalBtn" class="modal-close">&times;</button>
            </div>

            <div class="modal-info-section">
                <span class="info-title">Savings Goal</span>
                <div class="modal-info-row">
                    <span class="modal-info-label">Goal Name</span>
                    <span id="modalGoalName" class="modal-info-value"></span>
                </div>
                <div class="modal-info-row">
                    <span class="modal-info-label">Current Savings</span>
                    <span id="modalCurrentAmount" class="modal-info-value highlight"></span>
                </div>
                <div class="modal-info-row">
                    <span class="modal-info-label">Remaining Amount</span>
                    <span id="modalRemainingAmount" class="modal-info-value"></span>
                </div>
            </div>

            <form id="contributeForm" action="contribute.php" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="goal_id" id="modalGoalId">
                <div class="modal-field">
                    <label for="modalContributionAmount">Contribution Amount</label>
                    <input type="number" name="amount" id="modalContributionAmount" step="0.01" min="0.01" placeholder="Enter contribution amount" required>
                </div>
                <div class="modal-actions">
                    <button type="button" id="cancelModalBtn" class="modal-btn-cancel">Cancel</button>
                    <button type="submit" id="modal-btn-submit" class="modal-btn-submit">Submit Contribution</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Contribution modal
        const contributeModal = document.getElementById('contributeModal');
        const modalOverlay = document.getElementById('modalOverlay');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelModalBtn = document.getElementById('cancelModalBtn');
        const contributeForm = document.getElementById('contributeForm');
        const modalGoalId = document.getElementById('modalGoalId');
        const modalGoalName = document.getElementById('modalGoalName');
        const modalCurrentAmount = document.getElementById('modalCurrentAmount');
        const modalRemainingAmount = document.getElementById('modalRemainingAmount');
        const modalContributionAmount = document.getElementById('modalContributionAmount');

        // Guard: ensure all modal elements exist before attaching listeners
        if (contributeModal && modalOverlay && closeModalBtn && cancelModalBtn && contributeForm && modalGoalId && modalGoalName && modalCurrentAmount && modalRemainingAmount && modalContributionAmount) {
            document.querySelectorAll('.contribute-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const goalId = this.getAttribute('data-goal-id');
                    const goalName = this.getAttribute('data-goal-name');
                    const currentAmount = parseFloat(this.getAttribute('data-current-amount'));
                    const targetAmount = parseFloat(this.getAttribute('data-target-amount'));
                    const remaining = parseFloat(this.getAttribute('data-remaining'));

                    if (!goalId || isNaN(currentAmount) || isNaN(targetAmount)) {
                        return;
                    }

                    modalGoalId.value = goalId;
                    modalGoalName.textContent = goalName;
                    modalCurrentAmount.textContent = formatCurrency(currentAmount);
                    modalRemainingAmount.textContent = formatCurrency(remaining);
                    modalContributionAmount.value = '';
                    modalContributionAmount.max = remaining;

                    contributeModal.style.display = 'flex';
                    modalOverlay.style.display = 'block';
                });
            });

            function closeContributeModal() {
                contributeModal.style.display = 'none';
                modalOverlay.style.display = 'none';
            }

            closeModalBtn.addEventListener('click', closeContributeModal);
            cancelModalBtn.addEventListener('click', closeContributeModal);
            modalOverlay.addEventListener('click', closeContributeModal);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && contributeModal.style.display === 'flex') {
                    closeContributeModal();
                }
            });

            contributeForm.addEventListener('submit', function(e) {
                const amount = parseFloat(modalContributionAmount.value);
                const max = parseFloat(modalContributionAmount.max);

                if (isNaN(amount) || amount <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid contribution amount.');
                    return;
                }

                if (amount > max) {
                    e.preventDefault();
                    alert('Contribution cannot exceed remaining amount.');
                    return;
                }
            });
        }

        function formatCurrency(amount) {
            return '₱' + parseFloat(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    </script>
</body>
</html>