<?php
session_start();
require_once __DIR__ . '/../db.php';

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /../generate.php"); 
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$successMessage = '';

function redirect($url) {
    header("Location: $url");
    exit;
}

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    redirect("/../generate.php");
}

// Adicionar novo hábito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_habit'])) {
    $habitName = filter_var(trim($_POST['habit_name']), FILTER_SANITIZE_STRING);
    $habitDescription = filter_var(trim($_POST['habit_description']), FILTER_SANITIZE_STRING);
    if (!empty($habitName) && !empty($habitDescription)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO habits (user_id, habit_name, habit_description) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $habitName, $habitDescription]);
            $successMessage = "Hábito adicionado com sucesso!";
            redirect("mylist.php");
        } catch (PDOException $e) {
            $error = "Erro ao adicionar hábito: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, preencha todos os campos.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_habit'])) {
    $habitId = filter_var($_POST['habit_id'], FILTER_VALIDATE_INT);
    if ($habitId) {
        try {
            $stmt = $pdo->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
            $stmt->execute([$habitId, $userId]);
            $successMessage = "Hábito removido com sucesso!";
            redirect("mylist.php");
        } catch (PDOException $e) {
            $error = "Erro ao remover hábito: " . $e->getMessage();
        }
    } else {
        $error = "ID do hábito inválido.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_habit'])) {
    $habitId = filter_var($_POST['habit_id'], FILTER_VALIDATE_INT);
    $date = date('Y-m-d');
    if ($habitId) {
        try {
            $stmt = $pdo->prepare("INSERT INTO habit_tracking (user_id, habit_id, date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE date = VALUES(date)");
            $stmt->execute([$userId, $habitId, $date]);
            $successMessage = "Hábito marcado como concluído!";
            redirect("mylist.php");
        } catch (PDOException $e) {
            $error = "Erro ao marcar hábito: " . $e->getMessage();
        }
    } else {
        $error = "ID do hábito inválido.";
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, habit_name, habit_description FROM habits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $habitos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar hábitos: " . $e->getMessage();
    $habitos = [];
}

$date = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT habit_id FROM habit_tracking WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $date]);
    $habitosConcluidos = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $error = "Erro ao buscar hábitos concluídos: " . $e->getMessage();
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
                      console.log(data);
                      resolve();
                  })
                  .catch(error => reject(error));
            });
        }

        function sendCompletedHabits() {
            saveCompletedHabits().then(() => {
                alert('Hábitos concluídos salvos com sucesso!');
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
                    <form method="POST" style="display:inline;" id="habit-list-view">
                        <input type="hidden" name="habit_id" value="<?php echo $habito['id']; ?>">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="complete_habit" id="check-btn" onchange="this.form.submit()" value="<?php echo $habito['id']; ?>" <?php echo in_array($habito['id'], $habitosConcluidos) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                        </label>
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

            if (closeHabitFormBtn && habitForm) {
                closeHabitFormBtn.addEventListener("click", function () {
                    closeModal(habitForm);
                });
            }

            closeBtns.forEach(btn => {
                btn.addEventListener("click", function () {
                    closeModal(modal);
                    closeModal(loginModal);
                    closeModal(registerModal);
                });
            });

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