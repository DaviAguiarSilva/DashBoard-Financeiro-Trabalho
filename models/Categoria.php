<?php
/**
 * Model Categoria
 * Gerencia as categorias de receitas e despesas (tabela `categorias`).
 * Toda operação recebe o id do usuário para garantir que ele só acesse as suas.
 */
class Categoria
{
    public const TIPOS = ['receita', 'despesa'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Cria uma categoria e retorna o id criado. */
    public function criar(int $usuarioId, string $nome, string $tipo): int
    {
        [$nome, $tipo] = $this->validar($nome, $tipo);

        if ($this->nomeExiste($usuarioId, $nome, $tipo)) {
            throw new RuntimeException('Você já tem uma categoria com esse nome e tipo.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO categorias (usuario_id, nome, tipo) VALUES (?, ?, ?)'
        );
        $stmt->execute([$usuarioId, $nome, $tipo]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Lista as categorias do usuário, opcionalmente filtrando pelo tipo. */
    public function listarPorUsuario(int $usuarioId, ?string $tipo = null): array
    {
        $sql    = 'SELECT id, usuario_id, nome, tipo FROM categorias WHERE usuario_id = ?';
        $params = [$usuarioId];

        if ($tipo !== null) {
            if (!in_array($tipo, self::TIPOS, true)) {
                throw new InvalidArgumentException('Tipo inválido.');
            }
            $sql     .= ' AND tipo = ?';
            $params[] = $tipo;
        }

        $sql .= ' ORDER BY tipo, nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Busca uma categoria do usuário pelo id. */
    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, usuario_id, nome, tipo FROM categorias WHERE id = ? AND usuario_id = ?'
        );
        $stmt->execute([$id, $usuarioId]);
        $categoria = $stmt->fetch();
        return $categoria ?: null;
    }

    /**
     * Atualiza nome e tipo. Retorna false se a categoria não for do usuário.
     * Não permite trocar o tipo se já houver lançamentos (evita inconsistência).
     */
    public function atualizar(int $id, int $usuarioId, string $nome, string $tipo): bool
    {
        [$nome, $tipo] = $this->validar($nome, $tipo);

        $atual = $this->buscarPorId($id, $usuarioId);
        if (!$atual) {
            return false;
        }

        if ($atual['tipo'] !== $tipo && $this->temLancamentos($id)) {
            throw new RuntimeException(
                'Não é possível alterar o tipo de uma categoria que já possui lançamentos.'
            );
        }

        $stmt = $this->pdo->prepare(
            'UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND usuario_id = ?'
        );
        $stmt->execute([$nome, $tipo, $id, $usuarioId]);
        return true;
    }

    /**
     * Exclui a categoria (e as metas ligadas a ela).
     * Se houver lançamentos usando a categoria, a exclusão é bloqueada.
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        if (!$this->buscarPorId($id, $usuarioId)) {
            return false;
        }

        if ($this->temLancamentos($id)) {
            throw new RuntimeException(
                'Não é possível excluir: existem lançamentos nesta categoria.'
            );
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('DELETE FROM metas WHERE categoria_id = ? AND usuario_id = ?');
            $stmt->execute([$id, $usuarioId]);

            $stmt = $this->pdo->prepare('DELETE FROM categorias WHERE id = ? AND usuario_id = ?');
            $stmt->execute([$id, $usuarioId]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return true;
    }

    /** Cria as categorias padrão para um usuário recém-cadastrado. */
    public function criarPadrao(int $usuarioId): void
    {
        $padrao = [
            ['Alimentação', 'despesa'], ['Moradia', 'despesa'], ['Transporte', 'despesa'],
            ['Lazer', 'despesa'], ['Saúde', 'despesa'], ['Educação', 'despesa'],
            ['Salário', 'receita'], ['Freelance', 'receita'], ['Investimentos', 'receita'],
        ];

        $stmt = $this->pdo->prepare(
            'INSERT INTO categorias (usuario_id, nome, tipo) VALUES (?, ?, ?)'
        );
        foreach ($padrao as [$nome, $tipo]) {
            $stmt->execute([$usuarioId, $nome, $tipo]);
        }
    }

    /** Indica se existem lançamentos usando a categoria. */
    public function temLancamentos(int $categoriaId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM lancamentos WHERE categoria_id = ? LIMIT 1');
        $stmt->execute([$categoriaId]);
        return (bool) $stmt->fetchColumn();
    }

    private function nomeExiste(int $usuarioId, string $nome, string $tipo): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM categorias WHERE usuario_id = ? AND nome = ? AND tipo = ?'
        );
        $stmt->execute([$usuarioId, $nome, $tipo]);
        return (bool) $stmt->fetchColumn();
    }

    /** Valida e normaliza nome e tipo. */
    private function validar(string $nome, string $tipo): array
    {
        $nome = trim($nome);
        $tipo = strtolower(trim($tipo));

        if ($nome === '' || mb_strlen($nome) > 60) {
            throw new InvalidArgumentException('O nome deve ter entre 1 e 60 caracteres.');
        }
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException('Tipo inválido. Use "receita" ou "despesa".');
        }

        return [$nome, $tipo];
    }
}
