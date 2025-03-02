<?php
session_start();
require_once __DIR__ . '/../db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$alertMessage = '';

// Verificação de login
if (!isset($_SESSION['user_id'])) {
    header("Location: /../generate.php?error=nologin");
    exit;
}

$userId = $_SESSION['user_id'];
$currentMonth = date('Y-m');

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: /../generate.php?success=logout");
    exit;
}

// Calcular o total de hábitos diários do usuário
try {
    // Total de hábitos
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total_habits FROM habits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalDailyHabits = $stmt->fetchColumn();
    $daysInMonth = date('t');
    $totalMonthlyHabits = $totalDailyHabits * $daysInMonth;

    // Hábitos concluídos
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS completed_habits
        FROM habit_tracking
        WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $stmt->execute([$userId, $currentMonth]);
    $completedHabits = $stmt->fetchColumn();

    // Calcular progresso percentual
    $progressPercentage = $totalMonthlyHabits > 0 
        ? round(($completedHabits / $totalMonthlyHabits) * 100, 2) 
        : 0;

} catch (PDOException $e) {
    // Em caso de erro no banco de dados
    $alertMessage = "Erro ao carregar estatísticas: " . $e->getMessage();
    $totalDailyHabits = 0;
    $totalMonthlyHabits = 0;
    $completedHabits = 0;
    $progressPercentage = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Estatísticas - Listify</title>
    <link rel="stylesheet" href="./style/header.css">
    <link rel="stylesheet" href="./style/mystats.css">
    <link rel="icon" type="image/png" href="./assets/lua.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
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
        
        <div class="stats-container">
            <p>Total de hábitos concluídos no mês: <br>
                <strong><?php echo $completedHabits; ?> / <?php echo $totalMonthlyHabits; ?></strong>
            </p>
            <p>Progresso: <strong><?php echo $progressPercentage; ?>%</strong></p>
            
            <?php if ($progressPercentage >= 75): ?>
                <p class="congratulations">🎉 Parabéns! Você está indo muito bem! 🎉</p>
            <?php elseif ($progressPercentage >= 50): ?>
                <p class="motivation">Você está no caminho certo! Continue assim!</p>
            <?php else: ?>
                <p class="encouragement">Mantenha o foco! Cada pequeno passo conta.</p>
            <?php endif; ?>
        </div>

        <canvas id="habitsChart" width="400" height="200"></canvas>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Função para exibir alertas
            function showAlert(message, type = 'info') {
                const alertTypes = {
                    'info': '📢 Informação',
                    'success': '✅ Sucesso',
                    'error': '❌ Erro'
                };
                alert(`${alertTypes[type]}\n\n${message}`);
            }

            // Verifica se há mensagens de sucesso ou erro na URL
            const urlParams = new URLSearchParams(window.location.search);
            const successParam = urlParams.get('success');
            const errorParam = urlParams.get('error');

            // Alertas de sucesso
            if (successParam === 'login') {
                showAlert('Login realizado com sucesso!', 'success');
            } else if (successParam === 'logout') {
                showAlert('Logout realizado com sucesso!', 'success');
            }

            // Alertas de erro
            if (errorParam === 'nologin') {
                showAlert('Você precisa estar logado para acessar esta página.', 'error');
            }

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
