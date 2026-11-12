<?php

class AuthController extends Controller
{
    /** Nome do cookie que guarda o e-mail quando o usuário marca "Lembrar meu e-mail". */
    private const COOKIE_EMAIL = 'email_lembrado';

    private Usuario $usuarios;
    private Categoria $categorias;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->usuarios = new Usuario($pdo);
        $this->categorias = new Categoria($pdo);
    }

    // Tela e processamento de login
    public function login(): void
    {
        // Quem já está logado vai direto para o dashboard
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('dashboard');
        }

        // Se o cookie existir, o e-mail já vem preenchido e o checkbox marcado
        $email   = is_string($_COOKIE[self::COOKIE_EMAIL] ?? null) ? $_COOKIE[self::COOKIE_EMAIL] : '';
        $lembrar = $email !== '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->exigirCsrf();

            $email   = $this->post('email');
            $senha   = $this->senha('senha');
            $lembrar = $this->post('lembrar') !== '';

            try {

                $usuario = $this->usuarios->autenticar($email, $senha);

                if (!$usuario) {
                    // Mostra a tela de novo, mantendo o e-mail digitado
                    $this->flash('erro', 'E-mail ou senha inválidos.');
                } else {
                    $this->iniciarSessao((int) $usuario['id'], $usuario['nome']);
                    $this->lembrarEmail($lembrar, $email);
                    $this->redirect('dashboard');
                }

            } catch (Throwable $e) {

                $this->flash('erro', $this->mensagemDeErro($e));
            }
        }

        $this->render('auth/login', [
            'email'   => $email,
            'lembrar' => $lembrar,
        ]);
    }

    // Tela e processamento de cadastro
    public function cadastro(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->exigirCsrf();

            $nome      = $this->post('nome');
            $email     = $this->post('email');
            $senha     = $this->senha('senha');
            $confirmar = $this->senha('confirmar_senha');

            try {

                if ($senha !== $confirmar) {
                    throw new InvalidArgumentException('As senhas não conferem.');
                }

                $this->pdo->beginTransaction();

                $usuarioId = $this->usuarios->cadastrar(
                    $nome,
                    $email,
                    $senha
                );

                // Cria categorias padrão para o usuário
                $this->categorias->criarPadrao($usuarioId);

                $this->pdo->commit();

                $this->iniciarSessao($usuarioId, $nome);

                $this->flash('sucesso', 'Cadastro realizado com sucesso!');

                $this->redirect('dashboard');

            } catch (Throwable $e) {

                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $this->flash('erro', $this->mensagemDeErro($e));
                $this->render('auth/cadastro', [
                    'nome' => $nome,
                    'email' => $email,
                ]);

                return;
            }
        }

        $this->render('auth/cadastro');
    }

    // Encerra a sessão (só por POST, com token CSRF, para nenhum site externo deslogar o usuário)
    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('dashboard');
        }

        $this->exigirCsrf();

        // Limpa os dados da sessão
        $_SESSION = [];

        // Apaga o cookie da sessão no navegador
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        // Destrói a sessão no servidor
        session_destroy();

        $this->redirect('login');
    }

    // ------------------------------------------------------- Auxiliares

    // Grava o usuário na sessão e troca o id da sessão (proteção contra "session fixation")
    private function iniciarSessao(int $id, string $nome): void
    {
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario_nome'] = $nome;
    }

    // Cookie "lembrar e-mail": guarda o e-mail por 30 dias ou apaga o cookie
    private function lembrarEmail(bool $lembrar, string $email): void
    {
        setcookie(self::COOKIE_EMAIL, $lembrar ? $email : '', [
            'expires'  => $lembrar ? time() + 60 * 60 * 24 * 30 : time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
