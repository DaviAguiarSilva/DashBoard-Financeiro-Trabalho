
<?php

// 1. Iniciar sessão
session_start();

// 2. Carregar funções auxiliares
require_once __DIR__ . '/config/helpers.php';

// 3. Conectar ao banco de dados
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=financeiro;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Erro ao conectar ao banco de dados.');
}

// 4. Carregar a classe base
require_once __DIR__ . '/controllers/Controller.php';

// 5. Carregar os Models
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Categoria.php';
require_once __DIR__ . '/models/Lancamento.php';
require_once __DIR__ . '/models/Meta.php';
require_once __DIR__ . '/controllers/DashboardController.php';

// 6. Carregar o Controller de autenticação
require_once __DIR__ . '/controllers/AuthController.php';

// 7. Identificar a rota solicitada
$rota = $_GET['rota'] ?? 'login';

// 8. Criar o Controller
$auth = new AuthController($pdo);
$dashboard = new DashboardController($pdo);
// 9. Direcionar para a página correspondente
switch ($rota) {

    case 'login':
        $auth->login();
        break;

    case 'cadastro':
        $auth->cadastro();
        break;

    case 'dashboard':
        $dashboard->index();
        break;

    default:
        http_response_code(404);
        echo 'Página não encontrada.';
        break;
}
