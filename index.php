<?php
/**
 * index.php (front controller)
 * Ponto de entrada único do sistema. Toda página é acessada por index.php?rota=<nome>
 *
 * IMPORTANTE: nada pode vir antes do "<?php" acima (nem linha em branco).
 * Qualquer saída antes do session_start() ou de um header() causa "headers already sent".
 */

// 1. Conectar ao banco (também define o fuso horário America/Sao_Paulo)
if (!is_file(__DIR__ . '/config/conexao.php')) {
    http_response_code(500);
    exit('Falta o arquivo config/conexao.php. Copie config/conexao.exemplo.php para config/conexao.php e preencha os dados do banco.');
}
require_once __DIR__ . '/config/conexao.php';

// 2. Carregar funções auxiliares
require_once __DIR__ . '/config/helpers.php';

// 3. Iniciar sessão com cookie mais seguro
session_set_cookie_params([
    'lifetime' => 0,          // a sessão termina quando o navegador fecha
    'path'     => '/',
    'httponly' => true,       // JavaScript não consegue ler o cookie da sessão
    'samesite' => 'Lax',      // o cookie não é enviado em requisições vindas de outros sites
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

// 4. Carregar a classe base, os Models e os Controllers
require_once __DIR__ . '/controllers/Controller.php';

require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Categoria.php';
require_once __DIR__ . '/models/Lancamento.php';
require_once __DIR__ . '/models/Meta.php';

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';

// 5. Identificar a rota solicitada (sem rota: dashboard se logado, senão login)
$rota = $_GET['rota'] ?? (empty($_SESSION['usuario_id']) ? 'login' : 'dashboard');

// 6. Criar os Controllers
$auth      = new AuthController($pdo);
$dashboard = new DashboardController($pdo);

// 7. Direcionar para a página correspondente
switch ($rota) {

    case 'login':
        $auth->login();
        break;

    case 'cadastro':
        $auth->cadastro();
        break;

    case 'logout':
        $auth->logout();
        break;

    case 'dashboard':
        $dashboard->index();
        break;

    default:
        http_response_code(404);
        echo 'Página não encontrada.';
        break;
}
