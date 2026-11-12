
<?php

class AuthController extends Controller
{
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->exigirCsrf();

            $email = $this->post('email');
            $senha = $this->senha('senha');

            try {

                $usuario = $this->usuarios->autenticar($email, $senha);

                if (!$usuario) {
                    $this->flash('erro', 'E-mail ou senha inválidos.');
                    $this->redirect('login');
                }

                session_regenerate_id(true);

                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];

                $this->redirect('dashboard');

            } catch (Throwable $e) {

                $this->flash('erro', $this->mensagemDeErro($e));
                $this->redirect('login');
            }
        }

        $this->render('auth/login');
    }

    // Tela e processamento de cadastro
    public function cadastro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->exigirCsrf();

            $nome = $this->post('nome');
            $email = $this->post('email');
            $senha = $this->senha('senha');

            try {

                $this->pdo->beginTransaction();

                $usuarioId = $this->usuarios->cadastrar(
                    $nome,
                    $email,
                    $senha
                );

                // Cria categorias padrão para o usuário
                $this->categorias->criarPadrao($usuarioId);

                $this->pdo->commit();

                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuarioId;
                $_SESSION['usuario_nome'] = $nome;

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
}
