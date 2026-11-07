<?php
/**
 * Model Usuario
 * Responsável por cadastro, busca e autenticação de usuários (tabela `usuarios`).
 */
class Usuario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Cadastra um novo usuário e retorna o id criado.
     * @throws InvalidArgumentException dados inválidos
     * @throws RuntimeException e-mail já cadastrado
     */
    public function cadastrar(string $nome, string $email, string $senha): int
    {
        $nome  = trim($nome);
        $email = strtolower(trim($email));

        if ($nome === '') {
            throw new InvalidArgumentException('Informe o nome.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }
        if (strlen($senha) < 6) {
            throw new InvalidArgumentException('A senha deve ter pelo menos 6 caracteres.');
        }
        if ($this->emailExiste($email)) {
            throw new RuntimeException('Este e-mail já está cadastrado.');
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$nome, $email, $hash]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Verifica se já existe um usuário com o e-mail informado. */
    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        return (bool) $stmt->fetchColumn();
    }

    /** Busca o usuário completo (inclui senha_hash) pelo e-mail. Uso interno/login. */
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    /** Busca os dados públicos do usuário (sem a senha) pelo id. */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, criado_em FROM usuarios WHERE id = ?'
        );
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    /**
     * Valida e-mail e senha.
     * Retorna os dados do usuário (sem senha_hash) se estiverem corretos, ou null.
     */
    public function autenticar(string $email, string $senha): ?array
    {
        $usuario = $this->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }

        // Atualiza o hash caso o algoritmo padrão do PHP tenha mudado
        if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
            $novo = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
            $stmt->execute([$novo, $usuario['id']]);
        }

        unset($usuario['senha_hash']);
        return $usuario;
    }

    /** Altera a senha após conferir a senha atual. */
    public function alterarSenha(int $id, string $senhaAtual, string $novaSenha): bool
    {
        if (strlen($novaSenha) < 6) {
            throw new InvalidArgumentException('A nova senha deve ter pelo menos 6 caracteres.');
        }

        $stmt = $this->pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $hashAtual = $stmt->fetchColumn();

        if (!$hashAtual || !password_verify($senhaAtual, $hashAtual)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
        return $stmt->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $id]);
    }
}