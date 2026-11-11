<?php
/**
 * Controller (classe base)
 * Recursos comuns a todos os controllers: renderizar views, mensagens flash,
 * redirecionamento, proteção CSRF, leitura de entradas e tratamento de erros.
 */
abstract class Controller
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ------------------------------------------------------------ Views

    /**
     * Carrega views/<view>.php. Cada chave de $variaveisDaView vira uma variável na view.
     * Variáveis sempre disponíveis: $flash, $csrf, $usuarioNome, $rotaAtual.
     *
     * Obs.: o parâmetro tem um nome incomum de propósito. Como extract() usa EXTR_SKIP,
     * uma chave com o mesmo nome do parâmetro (ex.: 'dados' no cadastro) seria ignorada.
     */
    protected function render(string $view, array $variaveisDaView = []): void
    {
        $variaveisDaView['flash']       = $this->pegarFlash();
        $variaveisDaView['csrf']        = $this->csrfToken();
        $variaveisDaView['usuarioNome'] = $_SESSION['usuario_nome'] ?? '';
        $variaveisDaView['rotaAtual']   = $_GET['rota'] ?? '';

        extract($variaveisDaView, EXTR_SKIP);
        require __DIR__ . '/../views/' . $view . '.php';
    }

    protected function redirect(string $rota, array $params = []): void
    {
        redirecionar($rota, $params);
    }

    // ------------------------------------------------- Mensagens (flash)

    /** Guarda uma mensagem para ser exibida na próxima página. $tipo: "sucesso" ou "erro". */
    protected function flash(string $tipo, string $texto): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
    }

    private function pegarFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // ----------------------------------------------------- Autenticação

    /** Retorna o id do usuário logado ou redireciona para o login. */
    protected function usuarioId(): int
    {
        if (empty($_SESSION['usuario_id'])) {
            redirecionar('login');
        }
        return (int) $_SESSION['usuario_id'];
    }

    // ------------------------------------------------------------- CSRF

    /** Token enviado em todos os formulários POST (campo hidden "csrf_token"). */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /** Bloqueia a requisição POST se o token não for o da sessão. */
    protected function exigirCsrf(): void
    {
        $enviado = $_POST['csrf_token'] ?? '';
        if (!is_string($enviado) || !hash_equals($this->csrfToken(), $enviado)) {
            http_response_code(403);
            exit('Requisição inválida ou sessão expirada. Volte, recarregue a página e tente novamente.');
        }
    }

    // ----------------------------------------------------------- Entradas

    /** Lê um campo de texto do POST, sem espaços nas pontas. */
    protected function post(string $campo): string
    {
        $v = $_POST[$campo] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    /** Lê um campo de texto do GET, sem espaços nas pontas. */
    protected function get(string $campo): string
    {
        $v = $_GET[$campo] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    /** Lê uma senha do POST exatamente como foi digitada (sem trim). */
    protected function senha(string $campo): string
    {
        $v = $_POST[$campo] ?? '';
        return is_string($v) ? $v : '';
    }

    /**
     * Lê o parâmetro "mes" (AAAA-MM) do GET ou do POST.
     * Se estiver ausente ou inválido, usa o mês atual.
     * @return array{0:int, 1:int, 2:string} [ano, mês, "AAAA-MM"]
     */
    protected function mesSelecionado(bool $doPost = false): array
    {
        $valor = $doPost ? $this->post('mes') : $this->get('mes');

        if (preg_match('/^(\d{4})-(\d{2})$/', $valor, $m)) {
            $ano = (int) $m[1];
            $mes = (int) $m[2];
            if ($ano >= 2000 && $ano <= 2100 && $mes >= 1 && $mes <= 12) {
                return [$ano, $mes, $valor];
            }
        }
        return [(int) date('Y'), (int) date('n'), date('Y-m')];
    }

    // ------------------------------------------------------------- Erros

    /**
     * Converte uma exceção em texto seguro para o usuário.
     * Mensagens dos models (validações) são exibidas; erros técnicos vão para o log.
     */
    protected function mensagemDeErro(Throwable $e): string
    {
        // PDOException herda de RuntimeException, por isso é verificada primeiro
        if ($e instanceof PDOException) {
            error_log('Erro de banco: ' . $e->getMessage());
            return 'Erro ao acessar o banco de dados. Tente novamente.';
        }
        if ($e instanceof InvalidArgumentException || $e instanceof RuntimeException) {
            return $e->getMessage();
        }
        error_log('Erro inesperado: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
        return 'Ocorreu um erro inesperado. Tente novamente.';
    }

    /**
     * Executa uma ação (criar, atualizar, excluir), guarda a mensagem de sucesso ou erro
     * e redireciona. Se a ação retornar false, considera "registro não encontrado".
     */
    protected function executar(callable $acao, string $mensagemSucesso, string $rota, array $params = []): void
    {
        try {
            $resultado = $acao();
            if ($resultado === false) {
                $this->flash('erro', 'Registro não encontrado.');
            } else {
                $this->flash('sucesso', $mensagemSucesso);
            }
        } catch (Throwable $e) {
            $this->flash('erro', $this->mensagemDeErro($e));
        }
        $this->redirect($rota, $params);
    }
}
