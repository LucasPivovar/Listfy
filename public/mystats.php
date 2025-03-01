<?php
session_start();
require_once __DIR__ . '/../db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: /../generate.php");
    exit;
}

$userId = $_SESSION['user_id'];
$currentMonth = date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: /../generate.php");
    exit;
}

// Calcular o total de hábitos diários do usuário
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_habits FROM habits WHERE user_id = ?");
$stmt->execute([$userId]);
$totalDailyHabits = $stmt->fetchColumn();

$daysInMonth = date('t');
$totalMonthlyHabits = $totalDailyHabits * $daysInMonth;

// Recuperar o total de hábitos concluídos no mês atual
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS completed_habits
    FROM habit_tracking
    WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
");
$stmt->execute([$userId, $currentMonth]);
$completedHabits = $stmt->fetchColumn();

// Depuração
$debugInfo = "<!-- Debug: \n" .
             "userId: $userId\n" .
             "currentMonth: $currentMonth\n" .
             "totalDailyHabits: $totalDailyHabits\n" .
             "daysInMonth: $daysInMonth\n" .
             "totalMonthlyHabits: $totalMonthlyHabits\n" .
             "SQL Query: " . $stmt->queryString . "\n" .
             "SQL Params: " . json_encode([$userId, $currentMonth]) . "\n" .
             "completedHabits: $completedHabits\n" .
             "-->";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Estatísticas</title>
    <link rel="stylesheet" href="./style/header.css">
    <link rel="stylesheet" href="./style/mystats.css">
    <link rel="icon" type="image/png" href="./assets/lua.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php echo $debugInfo; ?>
    <div class="header">
        <h1>Listify</h1>
        <button id="open-modal-btn">Menu</button>
        <div id="modal" class="modal">
            <div class="modal-content">
                <div class="menu-header">
                    <h2>Listify</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <div class="nav">
                    <a href="/../generate.php">Home</a>
                    <a href="mylist.php">Minha Lista de Hábitos</a>
                    <a href="#">Minha Estatística</a>
                </div>
                <div class="footer-modal">
                    <p><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                    <form method="POST">
                        <button type="submit" name="logout">Sair</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="main">
        <h4>Minhas Estatísticas</h4>
        <p>Total de hábitos concluídos no mês: <br>
            <strong><?php echo $completedHabits; ?> / <?php echo $totalMonthlyHabits; ?></strong>
        </p>
        <p><strong>Parabéns!</strong></p>
        <canvas id="habitsChart" width="400" height="200"></canvas>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Modal script
            const modal = document.getElementById("modal");
            const openModalBtn = document.getElementById("open-modal-btn");
            const closeBtns = document.querySelectorAll(".close-btn");

            function showModal(modalElement) {
                if (modalElement) {
                    modalElement.style.display = "flex";
                    setTimeout(() => {
                        modalElement.style.opacity = "1";
                        modalElement.style.transform = "scale(1)";
                    }, 10);
                }
            }

            function closeModal(modalElement) {
                if (modalElement) {
                    modalElement.style.opacity = "0";
                    modalElement.style.transform = "scale(0.9)";
                    setTimeout(() => {
                        modalElement.style.display = "none";
                    }, 200);
                }
            }

            if (openModalBtn && modal) {
                openModalBtn.addEventListener("click", function () {
                    showModal(modal);
                });
            }

            closeBtns.forEach(btn => {
                btn.addEventListener("click", function () {
                    closeModal(modal);
                });
            });

            window.addEventListener("click", function (e) {
                if (e.target === modal) closeModal(modal);
            });

            // Chart script
            var ctx = document.getElementById('habitsChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Hábitos'],
                    datasets: [{
                        label: 'Concluídos',
                        data: [<?php echo $completedHabits; ?>],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Total Esperado',
                        data: [<?php echo $totalMonthlyHabits; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Hábitos Concluídos vs. Total Esperado'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
