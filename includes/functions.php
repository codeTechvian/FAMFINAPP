<?php
// functions.php - Helper functions for FAMFIN

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return 'N/A';
    $timestamp = strtotime($date);
    if ($format === 'M d, Y') {
        return date('M d, Y', $timestamp);
    } elseif ($format === 'F j, Y') {
        return date('F j, Y', $timestamp);
    } elseif ($format === 'Y-m-d') {
        return date('Y-m-d', $timestamp);
    }
    return $date;
}

// Get month name
function getMonthName($monthNum) {
    $months = [
        1 => 'January', 'February', 'March', 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[$monthNum] ?? 'Unknown';
}

// Check budget status
function getBudgetStatus($percentage) {
    if ($percentage >= 100) return ['status' => 'exceeded', 'color' => '#E87575'];
    if ($percentage >= 80) return ['status' => 'warning', 'color' => '#F5C94F'];
    return ['status' => 'normal', 'color' => '#2D805F'];
}

// Check goal progress
function getGoalProgress($current, $target) {
    if ($target == 0) return 0;
    $progress = ($current / $target) * 100;
    return min(100, max(0, $progress));
}
?>