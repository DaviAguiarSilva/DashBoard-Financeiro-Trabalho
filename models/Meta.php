<?php
/**
 * Model Meta
 * Limites de gasto mensais por categoria de despesa (tabela `metas`).
 * O campo `mes_referencia` é sempre salvo como o primeiro dia do mês (ex.: 2026-11-01).
 */
class Meta
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Cria uma meta e retorna o id criado. Só pode haver uma meta por categoria em cada mês. */
    public function criar(int $usuarioId, int $categoriaId, $valorLimite, string $mesReferencia): int
    {
        $mes   = $this->primeiroDiaDoMes($mesReferencia);
        $valor = $this->normalizarValor($valorLimite);
        $this->validarCategoria($usuarioId, $categoriaId);

        if ($this->existe($usuarioId, $categoriaId, $mes)) {
            throw new RuntimeException('Já existe uma meta para essa categoria neste mês.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO metas (usuario_id, categoria_id, valor_limite, mes_referencia)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$usuarioId, $categoriaId, $valor, $mes]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Lista as metas do usuário (opcionalmente de um mês: "AAAA-MM" ou "AAAA-MM-DD"). */
    public function listarPorUsuario(int $usuarioId, ?string $mesReferencia = null): array
    {
        $sql = 'SELECT m.id, m.usuario_id, m.categoria_id, m.valor_limite, m.mes_referencia,
                       c.nome AS categoria_nome
                FROM metas m
                JOIN categorias c ON c.id = m.categoria_id
                WHERE m.usuario_id = ?';
        $params = [$usuarioId];

        if ($mesReferencia !== null) {
            $sql     .= ' AND m.mes_referencia = ?';
            $params[] = $this->primeiroDiaDoMes($mesReferencia);
        }

        $sql .= ' ORDER BY m.mes_referencia DESC, c.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Busca uma meta do usuário pelo id. */
    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.usuario_id, m.categoria_id, m.valor_limite, m.mes_referencia,
                    c.nome AS categoria_nome
             FROM metas m
             JOIN categorias c ON c.id = m.categoria_id
             WHERE m.id = ? AND m.usuario_id = ?'
        );
        $stmt->execute([$id, $usuarioId]);
        $meta = $stmt->fetch();
        return $meta ?: null;
    }

    /** Atualiza o valor limite de uma meta. Retorna false se ela não for do usuário. */
    public function atualizar(int $id, int $usuarioId, $valorLimite): bool
    {
        if (!$this->buscarPorId($id, $usuarioId)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE metas SET valor_limite = ? WHERE id = ? AND usuario_id = ?'
        );
        $stmt->execute([$this->normalizarValor($valorLimite), $id, $usuarioId]);
        return true;
    }

    /** Exclui uma meta. Retorna true se algo foi removido. */
    public function excluir(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM metas WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Acompanhamento das metas de um mês: quanto já foi gasto em cada categoria.
     * status: "ok" (< 80%), "atencao" (80% a 100%) ou "estourada" (> 100%).
     * @return array<int, array<string, mixed>>
     */
    public function progresso(int $usuarioId, int $mes, int $ano): array
    {
        if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
            throw new InvalidArgumentException('Mês ou ano inválido.');
        }
        $inicio = new DateTime(sprintf('%04d-%02d-01', $ano, $mes));
        $di     = $inicio->format('Y-m-d');
        $df     = $inicio->format('Y-m-t');

        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.categoria_id, c.nome AS categoria_nome, m.valor_limite,
                    COALESCE(SUM(l.valor), 0) AS gasto
             FROM metas m
             JOIN categorias c ON c.id = m.categoria_id
             LEFT JOIN lancamentos l
                    ON l.categoria_id = m.categoria_id
                   AND l.usuario_id = m.usuario_id
                   AND l.tipo = 'despesa'
                   AND l.data BETWEEN ? AND ?
             WHERE m.usuario_id = ? AND m.mes_referencia = ?
             GROUP BY m.id, m.categoria_id, c.nome, m.valor_limite
             ORDER BY c.nome"
        );
        $stmt->execute([$di, $df, $usuarioId, $di]);

        $resultado = [];
        foreach ($stmt->fetchAll() as $linha) {
            $limite = (float) $linha['valor_limite'];
            $gasto  = (float) $linha['gasto'];
            $perc   = $limite > 0 ? round($gasto / $limite * 100, 1) : 0.0;

            $resultado[] = [
                'id'             => (int) $linha['id'],
                'categoria_id'   => (int) $linha['categoria_id'],
                'categoria_nome' => $linha['categoria_nome'],
                'valor_limite'   => $limite,
                'gasto'          => $gasto,
                'restante'       => $limite - $gasto,
                'percentual'     => $perc,
                'status'         => $perc > 100 ? 'estourada' : ($perc >= 80 ? 'atencao' : 'ok'),
            ];
        }

        return $resultado;
    }

    // ------------------------------------------------------- Auxiliares

    private function existe(int $usuarioId, int $categoriaId, string $mes): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM metas WHERE usuario_id = ? AND categoria_id = ? AND mes_referencia = ?'
        );
        $stmt->execute([$usuarioId, $categoriaId, $mes]);
        return (bool) $stmt->fetchColumn();
    }

    /** A categoria precisa ser do usuário e do tipo "despesa". */
    private function validarCategoria(int $usuarioId, int $categoriaId): void
    {
        $stmt = $this->pdo->prepare('SELECT tipo FROM categorias WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$categoriaId, $usuarioId]);
        $tipo = $stmt->fetchColumn();

        if ($tipo === false) {
            throw new InvalidArgumentException('Categoria inválida.');
        }
        if ($tipo !== 'despesa') {
            throw new InvalidArgumentException('Metas só podem ser criadas para categorias de despesa.');
        }
    }

    /** Aceita "AAAA-MM" ou "AAAA-MM-DD" e devolve o primeiro dia do mês (Y-m-01). */
    private function primeiroDiaDoMes(string $data): string
    {
        $data = trim($data);
        if (preg_match('/^\d{4}-\d{2}$/', $data)) {
            $data .= '-01';
        }
        $dt = DateTime::createFromFormat('Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Mês de referência inválido. Use AAAA-MM.');
        }
        return $dt->format('Y-m-01');
    }

    /** Aceita "500,00", "1.250,90" ou 500.0 e valida que é maior que zero. */
    private function normalizarValor($valor): float
    {
        if (is_string($valor)) {
            $valor = trim($valor);
            if (strpos($valor, ',') !== false) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            }
        }
        if (!is_numeric($valor) || (float) $valor <= 0 || (float) $valor > 99999999.99) {
            throw new InvalidArgumentException('Informe um valor limite maior que zero.');
        }
        return round((float) $valor, 2);
    }
}
