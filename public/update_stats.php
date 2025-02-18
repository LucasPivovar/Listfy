<?php
session_start();
require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$completedHabits = $data['completed_habits'] ?? [];
$date = date('Y-m-d');
$currentMonth = date('Y-m');

try {
    $pdo->beginTransaction();

    // Registrar hábitos concluídos
    foreach ($completedHabits as $habitId) {
        $stmt = $pdo->prepare("INSERT INTO habit_tracking (user_id, habit_id, date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE date = VALUES(date)");
        $stmt->execute([$userId, $habitId, $date]);
    }

    // Calcular o total de hábitos diários
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total_habits FROM habits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalDailyHabits = $stmt->fetchColumn();

    // Calcular o número de dias no mês atual
    $daysInMonth = date('t');

    // Total de hábitos esperados no mês
    $totalMonthlyHabits = $totalDailyHabits * $daysInMonth;

    // Atualizar estatísticas mensais
    $stmt = $pdo->prepare("INSERT INTO stats (user_id, total_habits, completed_habits, date) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE completed_habits = completed_habits + ?");
    $stmt->execute([$userId, $totalMonthlyHabits, count($completedHabits), $currentMonth, count($completedHabits)]);

    $pdo->commit();
    echo "Hábitos concluídos registrados com sucesso!";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro ao registrar hábitos concluídos: " . $e->getMessage();
}
?>