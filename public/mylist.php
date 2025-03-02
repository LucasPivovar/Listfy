<?php
session_start();
require_once __DIR__ . '/../db.php';
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /../generate.php?error=nologin");
    exit;
}
$userId = $_SESSION['user_id'];
$error = '';
$successMessage = '';


$habits = $_SESSION['generated_habits'] ?? [];
unset($_SESSION['generated_habits']);

function redirect($url) {
    header("Location: $url");
    exit;
}

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    redirect("/../generate.php?success=logout");
}

// Adicionar novo hábito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_habit'])) {
    $habitName = filter_var(trim($_POST['habit_name']), FILTER_SANITIZE_STRING);
    $habitDescription = filter_var(trim($_POST['habit_description']), FILTER_SANITIZE_STRING);
    
    if (!empty($habitName) && !empty($habitDescription)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO habits (user_id, habit_name, habit_description) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $habitName, $habitDescription]);
            redirect("mylist.php?success=habit_added");
        } catch (PDOException $e) {
            redirect("mylist.php?error=habit_add_failed");
        }
    } else {
        redirect("mylist.php?error=empty_fields");
    }
}



// Deletar hábito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_habit'])) {
    $habitId = filter_var($_POST['habit_id'], FILTER_VALIDATE_INT);
    
    if ($habitId) {
        try {
            $stmt = $pdo->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
            $stmt->execute([$habitId, $userId]);
            redirect("mylist.php?success=habit_deleted");
        } catch (PDOException $e) {
            redirect("mylist.php?error=habit_delete_failed");
        }
    } else {
        redirect("mylist.php?error=invalid_habit_id");
    }
}

// Completar hábito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_habit'])) {
    $habitId = filter_var($_POST['habit_id'], FILTER_VALIDATE_INT);
    $date = date('Y-m-d');
    
    if ($habitId) {
        try {
            $stmt = $pdo->prepare("INSERT INTO habit_tracking (user_id, habit_id, date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE date = VALUES(date)");
            $stmt->execute([$userId, $habitId, $date]);
            redirect("mylist.php?success=habit_completed");
        } catch (PDOException $e) {
            redirect("mylist.php?error=habit_complete_failed");
        }
    } else {
        redirect("mylist.php?error=invalid_habit_id");
    }
}

// Buscar hábitos
try {
    $stmt = $pdo->prepare("SELECT id, habit_name, habit_description FROM habits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $habitos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $habitos = [];
}

// Buscar hábitos concluídos
$date = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT habit_id FROM habit_tracking WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $date]);
    $habitosConcluidos = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $habitosConcluidos = [];
}
?>
<!DOCTYPE html>

<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Lista de Hábitos</title>
    <link rel="stylesheet" href="style/mylist.css">
    <link rel="stylesheet" href="style/header.css">
    <link rel="icon" type="image/png" href="./assets/lua.png">
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
            if (successParam === 'habit_added') {
                showAlert('Hábito adicionado com sucesso!', 'success');
            } else if (successParam === 'habit_deleted') {
                showAlert('Hábito removido com sucesso!', 'success');
            } else if (successParam === 'habit_completed') {
                showAlert('Hábito marcado como concluído!', 'success');
            }

            // Alertas de erro
            if (errorParam === 'habit_add_failed') {
                showAlert('Erro ao adicionar hábito. Tente novamente.', 'error');
            } else if (errorParam === 'habit_delete_failed') {
                showAlert('Erro ao remover hábito. Tente novamente.', 'error');
            } else if (errorParam === 'habit_complete_failed') {
                showAlert('Erro ao marcar hábito como concluído. Tente novamente.', 'error');
            } else if (errorParam === 'empty_fields') {
                showAlert('Por favor, preencha todos os campos.', 'error');
            } else if (errorParam === 'invalid_habit_id') {
                showAlert('ID do hábito inválido.', 'error');
            }

            // Lógica para redefinir hábitos diariamente
            const today = new Date().toISOString().split('T')[0];
            const lastAccessDate = localStorage.getItem('lastAccessDate');
            
            if (!lastAccessDate || lastAccessDate !== today) {
                saveCompletedHabits().then(() => {
                    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    localStorage.setItem('lastAccessDate', today);
                }).catch(error => console.error('Erro ao salvar hábitos:', error));
            }

            // Modal e navegação
            const modal = document.getElementById("modal");
            const openModalBtn = document.getElementById("open-modal-btn");
            const closeBtns = document.querySelectorAll(".close-btn");
            const habitForm = document.getElementById("habit-form");
            const addBtn = document.getElementById("add-btn");
            const closeHabitFormBtn = document.querySelector(".close-btn-list");

            // Funções de modal
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

            // Eventos de modal
            if (openModalBtn && modal) {
                openModalBtn.addEventListener("click", () => showModal(modal));
            }

            if (addBtn && habitForm) {
                addBtn.addEventListener("click", () => showModal(habitForm));
            }

            if (closeHabitFormBtn && habitForm) {
                closeHabitFormBtn.addEventListener("click", () => closeModal(habitForm));
            }

            closeBtns.forEach(btn => {
                btn.addEventListener("click", () => {
                    closeModal(modal);
                    closeModal(habitForm);
                });
            });

            window.addEventListener("click", (e) => {
                if (e.target === modal) closeModal(modal);
                if (e.target === habitForm) closeModal(habitForm);
            });
        });

        // Função para salvar hábitos concluídos
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
                    console.log(data);
                    resolve();
                })
                .catch(error => reject(error));
            });
        }

        // Função para enviar hábitos concluídos
        function sendCompletedHabits() {
            saveCompletedHabits()
                .then(() => {
                    showAlert('Hábitos concluídos salvos com sucesso!', 'success');
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Erro ao salvar hábitos:', error);
                    showAlert('Erro ao salvar hábitos.', 'error');
                });
        }

        // Função para alternar formulário de hábito
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
                    <a href="/../generate.php">Home</a>
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
    </div>

    <h3>Minha Lista de Hábitos</h3>

    <?php if ($successMessage): ?>
        <p style="color: green;"><?php echo $successMessage; ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <div class="list">
        <ul>
            <?php foreach ($habitos as $habito): ?>
                <li>
                    <div class="habit-item">
                        <form method="POST" style="display:flex; align-items: center; width: 100%;">
                            <input type="hidden" name="habit_id" value="<?php echo $habito['id']; ?>">

                            <div class="habit-details">
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="complete_habit" id="check-btn" onchange="this.form.submit()" value="<?php echo $habito['id']; ?>" <?php echo in_array($habito['id'], $habitosConcluidos) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                </label>

                                <p><strong><?php echo htmlspecialchars($habito['habit_name']); ?>:</strong>

                                <?php echo htmlspecialchars($habito['habit_description']); ?></p>
                                <button type="submit" name="delete_habit" class="delete-btn" onclick="return confirmDelete()">🗑️</button>

                            </div>
                        </form>
                    </div>
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

    <button onclick="sendCompletedHabits()" id="complete-btn">Concluir</button>

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

            function showModal(modalElement) {
                if (modalElement) {
                    modalElement.style.display = "flex";
                    setTimeout(() => {
                        modalElement.style.opacity = "1";
                        modalElement.style.transform = "scale(1)";
                    }, 10);
                }
            }

            function confirmDelete() {
                return confirm('Tem certeza que deseja remover este hábito?');
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

            if (openLoginModalBtn && loginModal) {
                openLoginModalBtn.addEventListener("click", function () {
                    closeModal(modal);
                    setTimeout(() => showModal(loginModal), 200);
                });
            }

            if (openRegisterModalLink && registerModal) {
                openRegisterModalLink.addEventListener("click", function (e) {
                    e.preventDefault();
                    closeModal(loginModal);
                    setTimeout(() => showModal(registerModal), 200);
                });
            }

            if (addBtn && habitForm) {
                addBtn.addEventListener("click", function () {
                    showModal(habitForm);
                });
            }

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