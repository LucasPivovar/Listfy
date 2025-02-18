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
$error = '';
$successMessage = '';

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy(); // Destroi a sessão
    header("Location: index.php"); // Redireciona para a página inicial
    exit;
}

// Adicionar novo hábito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_habit'])) {
    $habitName = trim($_POST['habit_name']);
    $habitDescription = trim($_POST['habit_description']);
    if (!empty($habitName) && !empty($habitDescription)) {
        $stmt = $pdo->prepare("INSERT INTO habits (user_id, habit_name, habit_description) VALUES (?, ?, ?)");
        if ($stmt->execute([$userId, $habitName, $habitDescription])) {
            // Redireciona para evitar reenvio do formulário
            header("Location: mylist.php");
            exit;
        } else {
            $error = "Erro ao adicionar hábito.";
        }
    } else {
        $error = "Por favor, preencha todos os campos.";
    }
}

// Remover hábito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_habit'])) {
    $habitId = $_POST['habit_id'];
    $stmt = $pdo->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$habitId, $userId])) {
        // Redireciona para evitar reenvio do formulário
        header("Location: mylist.php");
        exit;
    } else {
        $error = "Erro ao remover hábito.";
    }
}

// Marcar hábito como concluído
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_habit'])) {
    $habitId = $_POST['habit_id'];
    $date = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO habit_tracking (user_id, habit_id, date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE date = VALUES(date)");
    if ($stmt->execute([$userId, $habitId, $date])) {
        // Redireciona para evitar reenvio do formulário
        header("Location: mylist.php");
        exit;
    } else {
        $error = "Erro ao marcar hábito.";
    }
}

// Buscar hábitos do usuário
$stmt = $pdo->prepare("SELECT id, habit_name, habit_description FROM habits WHERE user_id = ?");
$stmt->execute([$userId]);
$habitos = $stmt->fetchAll();

// Buscar hábitos concluídos no dia atual
$date = date('Y-m-d');
$stmt = $pdo->prepare("SELECT habit_id FROM habit_tracking WHERE user_id = ? AND date = ?");
$stmt->execute([$userId, $date]);
$habitosConcluidos = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Lista de Hábitos</title>
    <link rel="stylesheet" href="style/mylist.css">
    <link rel="stylesheet" href="style/header.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const today = new Date().toISOString().split('T')[0]; // Data atual (YYYY-MM-DD)
            const lastAccessDate = localStorage.getItem('lastAccessDate');

            if (!lastAccessDate || lastAccessDate !== today) {
                // Salvar automaticamente os hábitos concluídos antes de limpar os checkboxes
                saveCompletedHabits().then(() => {
                    // Limpar todos os checkboxes após salvar
                    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    // Atualizar a data do último acesso
                    localStorage.setItem('lastAccessDate', today);
                }).catch(error => console.error('Erro ao salvar hábitos:', error));
            }
        });

        function saveCompletedHabits() {
            return new Promise((resolve, reject) => {
                const completedHabits = [];
                document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
                    completedHabits.push(checkbox.value);
                });

                fetch('update_stats.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ completed_habits: completedHabits })
                }).then(response => response.text())
                  .then(data => {
                      console.log(data); // Exibir mensagem de sucesso no console
                      resolve();
                  })
                  .catch(error => reject(error));
            });
        }

        function sendCompletedHabits() {
            saveCompletedHabits().then(() => {
                alert('Hábitos concluídos salvos com sucesso!');
                // Recarregar a página para refletir as mudanças
                window.location.reload();
            }).catch(error => {
                console.error('Erro ao salvar hábitos:', error);
                alert('Erro ao salvar hábitos.');
            });
        }

        function toggleForm() {
            const form = document.getElementById('habit-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
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
                    <a href="#">Minha Lista de Hábitos</a>
                    <a href="mystats.php">Minha Estatística</a>
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
                <?php if ($error): ?>
                    <p style="color: red;"> <?php echo $error; ?> </p>
                <?php endif; ?>
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
    <h3>Minha Lista de Hábitos</h3>
    <?php if ($successMessage): ?>
        <p style="color: green;"> <?php echo $successMessage; ?> </p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color: red;"> <?php echo $error; ?> </p>
    <?php endif; ?>
    <div class="list">
        <ul>
            <?php foreach ($habitos as $habito): ?>
                <li>
                    <form method="POST" style="display:inline;" id="habit-list-view">
                        <input type="hidden" name="habit_id" value="<?php echo $habito['id']; ?>">
                        <input type="checkbox" name="complete_habit"id="check-btn" onchange="this.form.submit()" value="<?php echo $habito['id']; ?>" <?php echo in_array($habito['id'], $habitosConcluidos) ? 'checked' : ''; ?>>
                        <strong><?php echo htmlspecialchars($habito['habit_name']); ?>:</strong> 
                        <?php echo htmlspecialchars($habito['habit_description']); ?>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="habit_id" value="<?php echo $habito['id']; ?>">
                        <button type="submit" name="delete_habit" id="delete-btn">X</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <button onclick="toggleForm()" id="add-btn">+</button>
    
    <div id="habit-form" style="display: none;">
        <div class="habit-form-content">
            <h2>Adicionar Novo Hábito</h2>
            <button type="button" onclick="closeModal('habit-form')" class="close-btn-list">&times;</button>
            <form method="POST">
                <label for="habit_name">Nome do Hábito:</label>
                <input type="text" name="habit_name" required>
                
                <label for="habit_description">Descrição:</label>
                <textarea name="habit_description" rows="3" required></textarea>
                
                <button type="submit" name="add_habit">Adicionar</button>
            </form>
        </div>
    </div>
    <button onclick="sendCompletedHabits()" id="complete-btn">Concluir Hábitos</button>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("modal");
            const openModalBtn = document.getElementById("open-modal-btn");
            const closeBtns = document.querySelectorAll(".close-btn");
            const loginModal = document.getElementById("login-modal");
            const openLoginModalBtn = document.getElementById("open-login-modal-btn");
            const registerModal = document.getElementById("register-modal");
            const openRegisterModalLink = document.getElementById("open-register-modal-link");
            const habitForm = document.getElementById("habit-form");
            const addBtn = document.getElementById("add-btn");
            const closeHabitFormBtn = document.querySelector(".close-btn-list");

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

            // Abrir modal do formulário de hábito
            if (addBtn && habitForm) {
                addBtn.addEventListener("click", function () {
                    showModal(habitForm);
                });
            }

            // Fechar modal do formulário de hábito
            if (closeHabitFormBtn && habitForm) {
                closeHabitFormBtn.addEventListener("click", function () {
                    closeModal(habitForm);
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
                if (e.target === habitForm) closeModal(habitForm);
            });
        });
    </script>
</body>
</html>