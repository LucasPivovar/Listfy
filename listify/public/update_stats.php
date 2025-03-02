<?php
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];
$currentDate = date('Y-m-d');
$currentMonth = date('Y-m');
$firstDayOfMonth = date('Y-m-01');

// Total de hábitos diários
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_habits FROM habits WHERE user_id = ?");
$stmt->execute([$userId]);
$totalDailyHabits = $stmt->fetchColumn();

// Hábitos concluídos hoje
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS completed_today
    FROM habit_tracking
    WHERE user_id = ? AND date = ?
");
$stmt->execute([$userId, $currentDate]);
$completedToday = $stmt->fetchColumn();

// Hábitos concluídos no mês
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS completed_this_month
    FROM habit_tracking
    WHERE user_id = ? AND date BETWEEN ? AND ?
");
$stmt->execute([$userId, $firstDayOfMonth, $currentDate]);
$completedThisMonth = $stmt->fetchColumn();

// Total esperado no mês
$daysInMonth = date('t');
$totalMonthlyHabits = $totalDailyHabits * $daysInMonth;

// Calcular porcentagens
$percentageToday = $totalDailyHabits > 0 ? round(($completedToday / $totalDailyHabits) * 100, 2) : 0;
$percentageMonthly = $totalMonthlyHabits > 0 ? round(($completedThisMonth / $totalMonthlyHabits) * 100, 2) : 0;

echo json_encode([
    'total_daily' => $totalDailyHabits,
    'completed_today' => $completedToday,
    'percentage_today' => $percentageToday,
    'total_monthly' => $totalMonthlyHabits,
    'completed_this_month' => $completedThisMonth,
    'percentage_monthly' => $percentageMonthly
]);

error_log("Debug: userId=$userId, totalDailyHabits=$totalDailyHabits, completedToday=$completedToday, completedThisMonth=$completedThisMonth");
?>
