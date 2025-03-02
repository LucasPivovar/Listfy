<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/db.php';

$error = '';
$successMessage = '';
$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: generate.php");
            exit;
        } else {
            $error = 'Credenciais inválidas';
        }
    }

    // Registro
    if (isset($_POST['register'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = 'Senhas não coincidem';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Usuário já existe';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                    $stmt->execute([$username, $hashedPassword]);
                    $successMessage = 'Registro realizado! Faça login';
                } catch (PDOException $e) {
                    $error = 'Erro no registro: ' . $e->getMessage();
                }
            }
        }
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: generate.php');
        exit;
    }

    // Gerar e salvar hábitos
    if (isset($_POST['generate_habits'])) {
        if (!$isLoggedIn) {
            $error = 'Faça login primeiro';
        } else {
            $description = sanitizeInput($_POST['description']);
            $generatedHabits = generateHabits($description);
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO habits (user_id, habit_name, habit_description)
                    VALUES (:user_id, :name, :description)
                ");

                foreach ($generatedHabits as $habit) {
                    $stmt->execute([
                        ':user_id' => $_SESSION['user_id'],
                        ':name' => $habit['habit_name'],
                        ':description' => $habit['habit_description']
                    ]);
                }
                
                $pdo->commit();
                
                $_SESSION['generated_habits'] = $generatedHabits;
                session_write_close();
                header('Location: /public/mylist.php');
                exit;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Erro ao salvar hábitos: ' . $e->getMessage();
            }
        }
    }
}

function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input)));
}

function generateHabits($description) {
    sleep(1);
    $description = strtolower($description);


    $habitGoals = [
        'produtividade' => [
            ['habit_name' => 'Técnica Pomodoro', 'habit_description' => 'Trabalhar em blocos de 25 minutos com pausas de 5 minutos'],
            ['habit_name' => 'Priorização de Tarefas', 'habit_description' => 'Listar 3 tarefas principais ao iniciar o dia'],
            ['habit_name' => 'Revisão Semanal', 'habit_description' => 'Analisar conquistas e ajustar metas toda sexta-feira'],
            ['habit_name' => 'Gestão de Distrações', 'habit_description' => 'Desligar notificações durante tarefas importantes'],
            ['habit_name' => 'Aprendizado Ativo', 'habit_description' => 'Dedicar 30 minutos diários para desenvolvimento profissional']
        ],
        'saúde mental' => [
            ['habit_name' => 'Meditação Matinal', 'habit_description' => 'Praticar 10 minutos de meditação ao acordar'],
            ['habit_name' => 'Gratidão Diária', 'habit_description' => 'Escrever 3 coisas pelas quais é grato'],
            ['habit_name' => 'Limite Digital', 'habit_description' => 'Desligar dispositivos 1 hora antes de dormir'],
            ['habit_name' => 'Atividade ao Ar Livre', 'habit_description' => 'Passar 30 minutos diários em contato com a natureza'],
            ['habit_name' => 'Autocompaixão', 'habit_description' => 'Praticar afirmações positivas diárias']
        ],
        'finanças' => [
            ['habit_name' => 'Controle de Gastos', 'habit_description' => 'Registrar todas as despesas diariamente'],
            ['habit_name' => 'Investimento Mensal', 'habit_description' => 'Destinar 10% da renda para investimentos'],
            ['habit_name' => 'Revisão de Orçamento', 'habit_description' => 'Avaliar e ajustar orçamento toda semana'],
            ['habit_name' => 'Educação Financeira', 'habit_description' => 'Ler 1 capítulo de livro financeiro por dia'],
            ['habit_name' => 'Compra Consciente', 'habit_description' => 'Esperar 24 horas antes de compras não essenciais']
        ],
        'relacionamentos' => [
            ['habit_name' => 'Conexão Diária', 'habit_description' => 'Ligar para um ente querido diariamente'],
            ['habit_name' => 'Escuta Ativa', 'habit_description' => 'Praticar escuta sem interrupções'],
            ['habit_name' => 'Gestão de Conflitos', 'habit_description' => 'Refletir antes de responder em discussões'],
            ['habit_name' => 'Quality Time', 'habit_description' => 'Reservar 1 noite por semana para atividades em família'],
            ['habit_name' => 'Apreciação', 'habit_description' => 'Expressar gratidão aos outros diariamente']
        ],
        'desenvolvimento pessoal' => [
            ['habit_name' => 'Leitura Inspiradora', 'habit_description' => 'Ler 20 páginas de livro de desenvolvimento pessoal'],
            ['habit_name' => 'Definição de Metas', 'habit_description' => 'Revisar e atualizar metas toda segunda-feira'],
            ['habit_name' => 'Habilidade Nova', 'habit_description' => 'Praticar nova habilidade por 15 minutos diários'],
            ['habit_name' => 'Autoavaliação', 'habit_description' => 'Fazer análise crítica do progresso mensalmente'],
            ['habit_name' => 'Comfort Zone', 'habit_description' => 'Fazer algo desconfortável propositalmente toda semana']
        ],
        'yoga' => [
            ['habit_name' => 'Prática Matinal', 'habit_description' => 'Fazer 20 minutos de yoga ao acordar'],
            ['habit_name' => 'Respiração Consciente', 'habit_description' => 'Praticar pranayama 10 minutos diários'],
            ['habit_name' => 'Alinhamento Postural', 'habit_description' => 'Verificar postura a cada hora'],
            ['habit_name' => 'Meditação Final', 'habit_description' => 'Terminar a prática com 5 minutos de meditação'],
            ['habit_name' => 'Filosofia Yogica', 'habit_description' => 'Estudar textos clássicos 2 vezes por semana']
        ]
    ];

    $keywords = [
        'produtividade' => ['produtividade', 'foco', 'trabalho', 'eficiencia', 'tarefas', 'priorização', 'organização', 'gerenciamento', 'autocompletar', 'auto-completar','ler', 'estudar' ],
        'saúde mental' => ['estresse', 'ansiedade', 'mente', 'meditação', 'relaxar', 'gratidão', 'auto-gratificação', 'autocomprimento',],
        'finanças' => ['dinheiro', 'investimento', 'orçamento', 'economizar', 'finanças' , 'economia', 'financeiro', 'controle', 'gestão', 'gerenciamento'],
        'relacionamentos' => ['família', 'amigos', 'relacionamento', 'casamento', 'social' , 'amizade', 'conexão', 'comunicação', 'apreciação', 'auto-apreciação'],
        'desenvolvimento pessoal' => ['crescimento', 'autoajuda', 'metas', 'habilidade', 'potencial' , 'auto-potencial', 'aprendizagem', 'aprendizado', 'auto-aprendizado', 'desenvolvimento', 'auto-desenvolvimento', ],
        'yoga' => ['yoga', 'asanas', 'pranayama', 'mindfulness', 'meditação', 'respiração', 'postura', 'alinhamento', 'auto-alinhamento', 'auto-respiração', 'auto-meditação', 'auto-postura', 'auto-alinhamento', 'yogica', 'yoga-pranayama', 'mindfulness-meditação', 'mindfulness-postura', 'mindfulness-alinhamento', 'emagrecer'],
    ];

    foreach ($keywords as $goal => $goalKeywords) {
        foreach ($goalKeywords as $keyword) {
            if (strpos($description, $keyword) !== false) {
                return $habitGoals[$goal];
            }
        }
    }

    // Hábitos genéricos aprimorados
    return [
        ['habit_name' => 'Rotina Matinal', 'habit_description' => 'Acordar no mesmo horário todos os dias e seguir uma rotina'],
        ['habit_name' => 'Alimentação Consciente', 'habit_description' => 'Comer sem distrações e mastigar devagar'],
        ['habit_name' => 'Movimento Corporal', 'habit_description' => 'Fazer 5 minutos de alongamento a cada 2 horas'],
        ['habit_name' => 'Aprendizado Diário', 'habit_description' => 'Consumir conteúdo educacional por 30 minutos'],
        ['habit_name' => 'Reflexão Noturna', 'habit_description' => 'Avaliar o dia e planejar o próximo antes de dormir']
    ];
}



?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listify</title>
    <link rel="stylesheet" href="./public/style/generate.css">
    <link rel="stylesheet" href="./public/style/header.css">
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
                    <a href="/public/mylist.php">Minha Lista de Hábitos</a>
                    <a href="/public/mystats.php">Minha Estatística</a>
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
                    <p style="color: red;"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div id="register-modal" class="modal">
            <div class="modal-content">
                <div class="menu-header">
                    <h2>Registrar</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form method="POST">
                    <label for="username">Nome de usuário:</label>
                    <input type="text" id="username" name="username" required>
                    
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                    
                    <label for="confirm_password">Confirmar Senha:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    
                    <button type="submit" name="register">Registrar</button>
                </form>
                <?php if ($error): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="main">
        <div class="habit_generate">
            <h1>Gere aqui seus hábitos</h1>
            <p>Descreva seu objetivo ou rotina e criaremos uma lista de hábitos para você!</p>
            <form method="POST">
                <textarea name="description" cols="40" rows="10" placeholder="Digite sua descrição aqui..."></textarea>
                <button type="submit" name="generate_habits" <?php echo $isLoggedIn ? '' : 'disabled'; ?>>Gerar Lista de Hábitos</button>
            </form>
        </div>
    </div>
    <script>
        if (<?php echo json_encode($successMessage !== ''); ?>) {
            alert(<?php echo json_encode($successMessage); ?>);
        }
        const modal = document.getElementById('modal');
        const openModalBtn = document.getElementById('open-modal-btn');
        const closeBtns = document.querySelectorAll('.close-btn');
        const loginModal = document.getElementById('login-modal');
        const openLoginModalBtn = document.getElementById('open-login-modal-btn');
        const registerModal = document.getElementById('register-modal');
        const openRegisterModalLink = document.getElementById('open-register-modal-link');
        function showModal(modalElement) {
            modalElement.style.display = 'flex';
            setTimeout(() => {
                modalElement.style.opacity = '1';
                modalElement.style.transform = 'scale(1)';
            }, 10);
        }
        function closeModal(modalElement) {
            modalElement.style.opacity = '0';
            modalElement.style.transform = 'scale(0.9)';
            setTimeout(() => {
                modalElement.style.display = 'none';
            }, 200);
        }
        openModalBtn?.addEventListener('click', () => showModal(modal));
        openLoginModalBtn?.addEventListener('click', () => {
            closeModal(modal);
            setTimeout(() => showModal(loginModal), 200);
        });
        openRegisterModalLink?.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(loginModal);
            setTimeout(() => showModal(registerModal), 200);
        });
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                closeModal(modal);
                closeModal(loginModal);
                closeModal(registerModal);
            });
        });
    </script>
</body>
</html>
