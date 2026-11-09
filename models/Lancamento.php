<?php
/**
 * Model Lancamento
 * Gerencia receitas e despesas (tabela `lancamentos`) e os cálculos do dashboard.
 * Toda operação recebe o id do usuário para garantir que ele só acesse os seus dados.
 */
class Lancamento
{
    public const TIPOS = ['receita', 'despesa'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ---------------------------------------------------------------- CRUD

    /** Cria um lançamento e retorna o id criado. */
    public function criar(
        int $usuarioId,
        int $categoriaId,
        string $descricao,
        $valor,
        string $data,
        string $tipo
    ): int {
        $d = $this->validar($usuarioId, $categoriaId, $descricao, $valor, $data, $tipo);

        $stmt = $this->pdo->prepare(
            'INSERT INTO lancamentos (usuario_id, categoria_id, descricao, valor, data, tipo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $usuarioId, $d['categoria_id'], $d['descricao'], $d['valor'], $d['data'], $d['tipo'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Lista os lançamentos do usuário, do mais recente ao mais antigo.
     * Filtros opcionais: data_inicio, data_fim (Y-m-d), categoria_id, tipo.
     */
    public function listar(int $usuarioId, array $filtros = []): array
    {
        $sql = 'SELECT l.id, l.usuario_id, l.categoria_id, l.descricao, l.valor, l.data, l.tipo,
                       c.nome AS categoria_nome
                FROM lancamentos l
                JOIN categorias c ON c.id = l.categoria_id
                WHERE l.usuario_id = ?';
        $params = [$usuarioId];

        if (!empty($filtros['data_inicio'])) {
            $sql     .= ' AND l.data >= ?';
            $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $sql     .= ' AND l.data <= ?';
            $params[] = $filtros['data_fim'];
        }
        if (!empty($filtros['categoria_id'])) {
            $sql     .= ' AND l.categoria_id = ?';
            $params[] = (int) $filtros['categoria_id'];
        }
        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], self::TIPOS, true)) {
            $sql     .= ' AND l.tipo = ?';
            $params[] = $filtros['tipo'];
        }

        $sql .= ' ORDER BY l.data DESC, l.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Busca um lançamento do usuário pelo id. */
    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.usuario_id, l.categoria_id, l.descricao, l.valor, l.data, l.tipo,
                    c.nome AS categoria_nome
             FROM lancamentos l
             JOIN categorias c ON c.id = l.categoria_id
             WHERE l.id = ? AND l.usuario_id = ?'
        );
        $stmt->execute([$id, $usuarioId]);
        $lancamento = $stmt->fetch();
        return $lancamento ?: null;
    }

    /** Atualiza um lançamento. Retorna false se ele não for do usuário. */
    public function atualizar(
        int $id,
        int $usuarioId,
        int $categoriaId,
        string $descricao,
        $valor,
        string $data,
        string $tipo
    ): bool {
        if (!$this->buscarPorId($id, $usuarioId)) {
            return false;
        }

        $d = $this->validar($usuarioId, $categoriaId, $descricao, $valor, $data, $tipo);

        $stmt = $this->pdo->prepare(
            'UPDATE lancamentos
             SET categoria_id = ?, descricao = ?, valor = ?, data = ?, tipo = ?
             WHERE id = ? AND usuario_id = ?'
        );
        $stmt->execute([
            $d['categoria_id'], $d['descricao'], $d['valor'], $d['data'], $d['tipo'],
            $id, $usuarioId,
        ]);
        return true;
    }

    /** Exclui um lançamento. Retorna true se algo foi removido. */
    public function excluir(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM lancamentos WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------- Dashboard

    /**
     * Totais de receitas, despesas e saldo.
     * Se $dataInicio e $dataFim forem informados (Y-m-d), calcula só nesse período.
     * @return array{receitas: float, despesas: float, saldo: float}
     */
    public function totais(int $usuarioId, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $sql    = 'SELECT tipo, SUM(valor) AS total FROM lancamentos WHERE usuario_id = ?';
        $params = [$usuarioId];

        if ($dataInicio !== null) {
            $sql     .= ' AND data >= ?';
            $params[] = $dataInicio;
        }
        if ($dataFim !== null) {
            $sql     .= ' AND data <= ?';
            $params[] = $dataFim;
        }
        $sql .= ' GROUP BY tipo';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $res = ['receitas' => 0.0, 'despesas' => 0.0, 'saldo' => 0.0];
        foreach ($stmt->fetchAll() as $linha) {
            if ($linha['tipo'] === 'receita') {
                $res['receitas'] = (float) $linha['total'];
            } else {
                $res['despesas'] = (float) $linha['total'];
            }
        }
        $res['saldo'] = $res['receitas'] - $res['despesas'];

        return $res;
    }

    /**
     * Total de despesas por categoria em um mês (gráfico de pizza).
     * @return array<int, array{categoria: string, total: string}>
     */
    public function despesasPorCategoria(int $usuarioId, int $mes, int $ano): array
    {
        [$inicio, $fim] = $this->limitesDoMes($mes, $ano);

        $stmt = $this->pdo->prepare(
            "SELECT c.nome AS categoria, SUM(l.valor) AS total
             FROM lancamentos l
             JOIN categorias c ON c.id = l.categoria_id
             WHERE l.usuario_id = ? AND l.tipo = 'despesa' AND l.data BETWEEN ? AND ?
             GROUP BY c.id, c.nome
             ORDER BY total DESC"
        );
        $stmt->execute([$usuarioId, $inicio, $fim]);
        return $stmt->fetchAll();
    }

    /**
     * Receitas e despesas dos últimos N meses (gráfico de barras).
     * Retorna todos os meses do intervalo, mesmo os sem movimentação.
     * @return array<int, array{mes: string, receitas: float, despesas: float}>
     */
    public function resumoMensal(int $usuarioId, int $meses = 6): array
    {
        $meses  = max(1, min($meses, 24));
        $inicio = new DateTime('first day of this month');
        $inicio->modify('-' . ($meses - 1) . ' months');

        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(data, '%Y-%m') AS mes, tipo, SUM(valor) AS total
             FROM lancamentos
             WHERE usuario_id = ? AND data >= ?
             GROUP BY mes, tipo"
        );
        $stmt->execute([$usuarioId, $inicio->format('Y-m-d')]);

        $resumo = [];
        $cursor = clone $inicio;
        for ($i = 0; $i < $meses; $i++) {
            $resumo[$cursor->format('Y-m')] = [
                'mes' => $cursor->format('Y-m'), 'receitas' => 0.0, 'despesas' => 0.0,
            ];
            $cursor->modify('+1 month');
        }

        foreach ($stmt->fetchAll() as $linha) {
            if (isset($resumo[$linha['mes']])) {
                $chave = $linha['tipo'] === 'receita' ? 'receitas' : 'despesas';
                $resumo[$linha['mes']][$chave] = (float) $linha['total'];
            }
        }

        return array_values($resumo);
    }

    // ------------------------------------------------------- Auxiliares

    /** Primeiro e último dia de um mês no formato Y-m-d. */
    private function limitesDoMes(int $mes, int $ano): array
    {
        if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
            throw new InvalidArgumentException('Mês ou ano inválido.');
        }
        $inicio = new DateTime(sprintf('%04d-%02d-01', $ano, $mes));
        return [$inicio->format('Y-m-d'), $inicio->format('Y-m-t')];
    }

    /** Valida e normaliza os dados de um lançamento. */
    private function validar(
        int $usuarioId,
        int $categoriaId,
        string $descricao,
        $valor,
        string $data,
        string $tipo
    ): array {
        $descricao = trim($descricao);
        $tipo      = strtolower(trim($tipo));

        if ($descricao === '' || mb_strlen($descricao) > 150) {
            throw new InvalidArgumentException('A descrição deve ter entre 1 e 150 caracteres.');
        }
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException('Tipo inválido. Use "receita" ou "despesa".');
        }

        // Aceita "35,50", "1.250,90" ou 35.5
        if (is_string($valor)) {
            $valor = trim($valor);
            if (strpos($valor, ',') !== false) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            }
        }
        if (!is_numeric($valor) || (float) $valor <= 0 || (float) $valor > 99999999.99) {
            throw new InvalidArgumentException('Informe um valor maior que zero.');
        }
        $valor = round((float) $valor, 2);

        $dt = DateTime::createFromFormat('Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Data inválida. Use o formato AAAA-MM-DD.');
        }

        // A categoria precisa ser do usuário e do mesmo tipo do lançamento
        $stmt = $this->pdo->prepare('SELECT tipo FROM categorias WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$categoriaId, $usuarioId]);
        $tipoCategoria = $stmt->fetchColumn();

        if ($tipoCategoria === false) {
            throw new InvalidArgumentException('Categoria inválida.');
        }
        if ($tipoCategoria !== $tipo) {
            throw new InvalidArgumentException('O tipo do lançamento deve ser igual ao tipo da categoria.');
        }

        return [
            'categoria_id' => $categoriaId,
            'descricao'    => $descricao,
            'valor'        => $valor,
            'data'         => $data,
            'tipo'         => $tipo,
        ];
    }
}
