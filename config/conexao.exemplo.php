<?php
/**
 * config/conexao.exemplo.php  (MODELO — este arquivo vai para o GitHub)
 *
 * Como usar:
 *   1. Copie este arquivo e renomeie a cópia para "conexao.php" (na mesma pasta).
 *   2. Preencha os dados do banco na cópia.
 *
 * O conexao.php está no .gitignore, então a senha real nunca vai para o repositório.
 * No XAMPP: usuário "root" e senha vazia.
 * Na hospedagem: use os dados que o painel da hospedagem informar.
 */

// Fuso horário do sistema (afeta date(), DateTime e os filtros por mês)
date_default_timezone_set('America/Sao_Paulo');

$host    = 'localhost';
$banco   = 'financeiro';
$usuario = 'root';
$senha   = '';
$charset = 'utf8mb4';

// DSN (Data Source Name): diz ao PDO qual driver, servidor, banco e charset usar
$dsn = "mysql:host=$host;dbname=$banco;charset=$charset";

// Opções do PDO
$opcoes = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erros viram exceções (try/catch)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements reais (mais seguro)
];

try {
    $pdo = new PDO($dsn, $usuario, $senha, $opcoes);
} catch (PDOException $e) {
    // O detalhe técnico vai para o log do servidor, nunca para a tela do usuário
    error_log('Erro de conexão com o banco: ' . $e->getMessage());
    http_response_code(500);
    die('Não foi possível conectar ao banco de dados. Tente novamente mais tarde.');
}
