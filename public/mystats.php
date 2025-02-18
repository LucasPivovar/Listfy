<?php
session_start();
require_once __DIR__ . '/../db.php';

// Verifica se há uma sessão ativa do usuário
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$currentMonth = date('Y-m'); // Formato: YYYY-MM

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Calcular o total de hábitos diários
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_habits FROM habits WHERE user_id = ?");
$stmt->execute([$userId]);
$totalDailyHabits = $stmt->fetchColumn();

// Calcular o número de dias no mês atual
$daysInMonth = date('t');

// Total de hábitos esperados no mês
$totalMonthlyHabits = $totalDailyHabits * $daysInMonth;

// Buscar estatísticas mensais (hábitos concluídos)
$stmt = $pdo->prepare("SELECT SUM(completed_habits) AS completed_habits 
                       FROM stats 
                       WHERE user_id = ? AND date LIKE ?");
$stmt->execute([$userId, "$currentMonth%"]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$completedHabits = $stats['completed_habits'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Estatísticas</title>
    <link rel="stylesheet" href="./style/header.css">
    <link rel="stylesheet" href="./style/mystats.css">
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
                    <a href="index.php">Home</a>
                    <a href="mylist.php">Minha Lista de Hábitos</a>
                    <a href="#">Minha Estatística</a>
                </div>

                <div class="footer-modal">
                    <?php if ($isLoggedIn): ?>
                        <p><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                        <form method="POST">
                            <button type="submit" name="logout">Sair</button>
                        </form>
                    <?php else: ?>
                        <button id="open-login-modal-btn">Entrar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal de Login -->
        <div id="login-modal" class="modal">
            <div class="modal-content">
                <div class="menu-header">
                    <h2>Entrar</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form method="POST">
                    <label for="username">Nome de usuário:</label>
                    <input type="text" id="username" name="username" required>
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                    <button type="submit" name="login">Entrar</button>
                </form>
                <p>Não tem uma conta? <a href="#" id="open-register-modal-link">Registre-se</a></p>
            </div>
        </div>

        <!-- Modal de Registro -->
        <div id="register-modal" class="modal">
            <div class="modal-content">
                <div class="menu-header">
                    <h2>Registrar</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form method="POST">
                    <label for="username">Nome de usuário:</label>
                    <input type="text" id="username" name="username" required>
                    <label for="register-password">Senha:</label>
                    <input type="password" id="register-password" name="register-password" required>
                    <button type="submit" name="register">Registrar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="main">
        <h4>Minhas Estatísticas</h4>
        <p>No mês, o total de hábitos concluídos foi de: <br> <?php echo $completedHabits; ?>/<?php echo $totalMonthlyHabits; ?></p>
        <p><strong>Parabens!</strong></p>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("modal");
            const openModalBtn = document.getElementById("open-modal-btn");
            const closeBtns = document.querySelectorAll(".close-btn");
            const loginModal = document.getElementById("login-modal");
            const openLoginModalBtn = document.getElementById("open-login-modal-btn");
            const registerModal = document.getElementById("register-modal");
            const openRegisterModalLink = document.getElementById("open-register-modal-link");

            // Função para exibir modal com animação
            function showModal(modalElement) {
                if (modalElement) {
                    modalElement.style.display = "flex";
                    setTimeout(() => {
                        modalElement.style.opacity = "1";
                        modalElement.style.transform = "scale(1)";
                    }, 10);
                }
            }

            // Função para ocultar modal com animação
            function closeModal(modalElement) {
                if (modalElement) {
                    modalElement.style.opacity = "0";
                    modalElement.style.transform = "scale(0.9)";
                    setTimeout(() => {
                        modalElement.style.display = "none";
                    }, 200);
                }
            }

            // Abrir modal do menu
            if (openModalBtn && modal) {
                openModalBtn.addEventListener("click", function () {
                    showModal(modal);
                });
            }

            // Abrir modal de login
            if (openLoginModalBtn && loginModal) {
                openLoginModalBtn.addEventListener("click", function () {
                    closeModal(modal);
                    setTimeout(() => showModal(loginModal), 200);
                });
            }

            // Abrir modal de registro
            if (openRegisterModalLink && registerModal) {
                openRegisterModalLink.addEventListener("click", function (e) {
                    e.preventDefault();
                    closeModal(loginModal);
                    setTimeout(() => showModal(registerModal), 200);
                });
            }

            // Fechar modais ao clicar nos botões de fechar
            closeBtns.forEach(btn => {
                btn.addEventListener("click", function () {
                    closeModal(modal);
                    closeModal(loginModal);
                    closeModal(registerModal);
                });
            });

            // Fechar modal ao clicar fora dele
            window.addEventListener("click", function (e) {
                if (e.target === modal) closeModal(modal);
                if (e.target === loginModal) closeModal(loginModal);
                if (e.target === registerModal) closeModal(registerModal);
            });
        });
    </script>

</body>
</html>
