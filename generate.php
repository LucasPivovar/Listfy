<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gemini.php'; // Inclui a função generateHabits()

$error = '';
$successMessage = '';

// Verifica se há uma sessão ativa do usuário
$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login
    if (isset($_POST['login'])) {
        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $successMessage = 'Login realizado com sucesso!';
            
            // Recarrega a página para evitar reenvio de formulário
            header("Location: generate.php");
            exit;
        } else {
            $error = 'Nome de usuário ou senha incorretos.';
        }
    }

    // Registro
    if (isset($_POST['register'])) {
        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['register-password'], PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        if ($stmt->execute([$username, $password])) {
            $successMessage = 'Registro bem-sucedido! Faça login.';
        } else {
            $error = 'Erro ao registrar. Tente novamente.';
        }
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: generate.php');
        exit;
    }

    // Geração e salvamento de hábitos no banco
    if (isset($_POST['generate_habits']) && $isLoggedIn) {
        $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
        $generatedHabits = generateHabits($description); // Obtém hábitos formatados
        
        if (!empty($generatedHabits)) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO habits (user_id, habit_name, habit_description) VALUES (?, ?, ?)");
            
            foreach ($generatedHabits as $habit) {
                $stmt->execute([$_SESSION['user_id'], $habit['habit_name'], $habit['habit_description']]);
            }
            
            $pdo->commit();
            header('Location: ./public/mylist.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listify</title>
    <link rel="stylesheet" href="public/style/generate.css">
    <link rel="stylesheet" href="public/style/header.css">
    <link rel="icon" type="image/png" href="./public/assets/lua.png">
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
                    <a href="#">Home</a>
                    <a href="./public/mylist.php">Minha Lista de Hábitos</a>
                    <a href="./public/mystats.php">Minha Estatística</a>
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
                    <button type="submit" name="login" id="btn-save-user">Entrar</button>
                </form>
                <p>Não tem uma conta? <a href="#" id="open-register-modal-link">Registre-se</a></p>
                <?php if ($error): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
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
                    <button type="submit" name="register" id="btn-save-user">Registrar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="habit_generate">
            <h1>Gere aqui seus hábitos</h1>
            <p>Descreva seu objetivo ou rotina e criaremos uma lista de hábitos para você!</p>

            <form method="POST">
                <textarea name="description" cols="40" rows="10" placeholder="Digite sua descrição aqui..." required></textarea>
                <button 
                    type="submit" 
                    name="generate_habits"
                    title="<?php echo $isLoggedIn ? '' : 'Você precisa estar logado para gerar hábitos.'; ?>"
                >
                    Gerar Lista de Hábitos
                </button>
            </form>
        </div>
    </div>

    <script>
        // Exibe alerta se houver uma mensagem de sucesso
        if (<?php echo json_encode($successMessage !== ''); ?>) {
            alert(<?php echo json_encode($successMessage); ?>);
        }

        // Seleção de elementos
        const modal = document.getElementById('modal');
        const openModalBtn = document.getElementById('open-modal-btn');
        const closeBtns = document.querySelectorAll('.close-btn');
        const loginModal = document.getElementById('login-modal');
        const openLoginModalBtn = document.getElementById('open-login-modal-btn');
        const registerModal = document.getElementById('register-modal');
        const openRegisterModalLink = document.getElementById('open-register-modal-link');
        const habitForm = document.querySelector('.habit_generate form'); // Formulário de geração de hábitos
        const generateHabitsButton = document.querySelector('button[name="generate_habits"]');

        // Função para exibir um modal com animação
        function showModal(modalElement) {
            modalElement.style.display = 'flex';
            setTimeout(() => {
                modalElement.style.opacity = '1';
                modalElement.style.transform = 'scale(1)';
            }, 10);
        }

        // Função para ocultar um modal com animação
        function closeModal(modalElement) {
            modalElement.style.opacity = '0';
            modalElement.style.transform = 'scale(0.9)';
            setTimeout(() => {
                modalElement.style.display = 'none';
            }, 200);
        }

        // Exibir modal do menu
        openModalBtn?.addEventListener('click', () => showModal(modal));

        // Exibir modal de login ao clicar no botão "Entrar"
        openLoginModalBtn?.addEventListener('click', () => {
            closeModal(modal);
            setTimeout(() => showModal(loginModal), 200);
        });

        // Exibir modal de registro ao clicar no link "Registre-se"
        openRegisterModalLink?.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(loginModal);
            setTimeout(() => showModal(registerModal), 200);
        });

        // Fechar modais ao clicar no botão de fechar
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                closeModal(modal);
                closeModal(loginModal);
                closeModal(registerModal);
            });
        });

        // Verifica se o usuário está logado ao enviar o formulário
        if (habitForm) {
            habitForm.addEventListener('submit', function(e) {
                // Verifica se o usuário não está logado
                if (!<?php echo json_encode($isLoggedIn); ?>) {
                    e.preventDefault(); // Impede o envio do formulário
                    alert('Você precisa estar logado para gerar hábitos. Faça login no menu.');
                }
            });
        }
    </script>
</body>
</html>