<?php

/** @var array{receitas: float, despesas: float, saldo: float} $totais */
/** @var string $usuarioNome */
/** @var string $mesAtual */

/** @var array $resumoMensal */
/** @var array $despesasPorCategoria */
/** @var array $lancamentosRecentes */
/** @var array $metas */
/** @var array{labels: string[], valores: float[]} $graficoPizza */
/** @var array{labels: string[], receitas: float[], despesas: float[]} $graficoBarras */

/** @var array|null $flash */
/** @var string $csrf */
/** @var string $rotaAtual */

$receitas = $totais['receitas'];
$despesas = $totais['despesas'];
$saldo = $totais['saldo'];

// Existe algum valor diferente de zero nos últimos seis meses?
$temMovimentacao = array_sum($graficoBarras['receitas']) + array_sum($graficoBarras['despesas']) > 0;

// Texto de cada status de meta
$statusMeta = [
    'ok'        => 'Dentro da meta',
    'atencao'   => 'Atenção',
    'estourada' => 'Meta estourada',
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Financeiro</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 18px 5%;
            border-bottom: 1px solid #e2e8f0;
        }

        header h2 {
            margin: 0;
        }

        header p {
            margin: 4px 0 0;
            color: #64748b;
        }

        .sair {
            padding: 9px 16px;
            background: white;
            color: #334155;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            cursor: pointer;
        }

        .sair:hover {
            background: #f1f5f9;
        }

        main {
            padding: 35px 5%;
            max-width: 1400px;
            margin: auto;
        }

        .mensagem {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 7px;
            background: #fee2e2;
            color: #991b1b;
        }

        .mensagem.sucesso {
            background: #dcfce7;
            color: #166534;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
        }

        .card p {
            color: #64748b;
            margin: 0 0 15px;
        }

        .card h2 {
            font-size: 25px;
            margin: 0;
        }

        .receita {
            color: #16a34a;
        }

        .despesa {
            color: #dc2626;
        }

        .saldo {
            color: #2563eb;
        }

        .graficos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .secao {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
        }

        .graficos .secao {
            margin-top: 0;
        }

        .area-grafico {
            position: relative;
            height: 280px;
        }

        /* Metas */
        .meta {
            padding: 14px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .meta:last-child {
            border-bottom: none;
        }

        .meta-topo {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .meta-valores {
            color: #64748b;
            font-size: 14px;
        }

        .barra {
            height: 10px;
            background: #e2e8f0;
            border-radius: 99px;
            overflow: hidden;
        }

        .barra-preenchida {
            height: 100%;
            border-radius: 99px;
        }

        .meta-status {
            margin-top: 6px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-ok        { color: #16a34a; }
        .status-atencao   { color: #d97706; }
        .status-estourada { color: #dc2626; }

        .fundo-ok        { background: #16a34a; }
        .fundo-atencao   { background: #d97706; }
        .fundo-estourada { background: #dc2626; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            color: #64748b;
        }

        .vazio {
            color: #94a3b8;
            padding: 20px 0;
        }

        @media (max-width: 600px) {
            main {
                padding: 20px 4%;
            }

            .graficos {
                grid-template-columns: 1fr;
            }

            .secao {
                overflow-x: auto;
            }

            table {
                min-width: 500px;
            }
        }
    </style>
</head>

<body>

<header>
    <div>
        <h2>Finance Dashboard</h2>
        <p>Olá, <?= e($usuarioNome) ?>!</p>
    </div>

    <!-- Logout por POST com token CSRF -->
    <form method="POST" action="<?= e(url('logout')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <button type="submit" class="sair">Sair</button>
    </form>
</header>

<main>

    <?php if (!empty($flash)): ?>
        <div class="mensagem <?= $flash['tipo'] === 'sucesso' ? 'sucesso' : '' ?>">
            <?= e($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <h1>Visão geral</h1>
    <p>Resumo financeiro de <?= e($mesAtual) ?></p>

    <div class="cards">

        <div class="card">
            <p>Receitas</p>
            <h2 class="receita">
                <?= e(dinheiro($receitas)) ?>
            </h2>
        </div>

        <div class="card">
            <p>Despesas</p>
            <h2 class="despesa">
                <?= e(dinheiro($despesas)) ?>
            </h2>
        </div>

        <div class="card">
            <p>Saldo</p>
            <h2 class="<?= $saldo < 0 ? 'despesa' : 'saldo' ?>">
                <?= e(dinheiro($saldo)) ?>
            </h2>
        </div>

    </div>

    <div class="graficos">

        <section class="secao">

            <h2>Despesas por categoria</h2>

            <?php if (empty($despesasPorCategoria)): ?>
                <p class="vazio">Nenhuma despesa registrada neste mês.</p>
            <?php else: ?>
                <div class="area-grafico">
                    <canvas id="graficoPizza" aria-label="Gráfico de despesas por categoria" role="img"></canvas>
                </div>
            <?php endif; ?>

        </section>

        <section class="secao">

            <h2>Últimos seis meses</h2>

            <?php if (!$temMovimentacao): ?>
                <p class="vazio">Ainda não existem movimentações.</p>
            <?php else: ?>
                <div class="area-grafico">
                    <canvas id="graficoBarras" aria-label="Gráfico de receitas e despesas por mês" role="img"></canvas>
                </div>
            <?php endif; ?>

        </section>

    </div>

    <section class="secao">

        <h2>Metas de gasto do mês</h2>

        <?php if (empty($metas)): ?>

            <p class="vazio">Nenhuma meta definida para este mês.</p>

        <?php else: ?>

            <?php foreach ($metas as $meta): ?>
                <div class="meta">
                    <div class="meta-topo">
                        <strong><?= e($meta['categoria_nome']) ?></strong>
                        <span class="meta-valores">
                            <?= e(dinheiro($meta['gasto'])) ?> de <?= e(dinheiro($meta['valor_limite'])) ?>
                            (<?= e(number_format($meta['percentual'], 1, ',', '.')) ?>%)
                        </span>
                    </div>

                    <div class="barra">
                        <!-- A barra para em 100%, mesmo se a meta estourar -->
                        <div class="barra-preenchida fundo-<?= e($meta['status']) ?>"
                             style="width: <?= e(min(100, $meta['percentual'])) ?>%"></div>
                    </div>

                    <div class="meta-status status-<?= e($meta['status']) ?>">
                        <?= e($statusMeta[$meta['status']] ?? $meta['status']) ?>
                        <?php if ($meta['restante'] >= 0): ?>
                            · restam <?= e(dinheiro($meta['restante'])) ?>
                        <?php else: ?>
                            · <?= e(dinheiro(-$meta['restante'])) ?> acima do limite
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </section>

    <section class="secao">

        <h2>Lançamentos recentes</h2>

        <?php if (empty($lancamentosRecentes)): ?>

            <p class="vazio">Você ainda não possui lançamentos.</p>

        <?php else: ?>

            <table>
                <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($lancamentosRecentes as $item): ?>
                    <tr>
                        <td><?= e($item['descricao']) ?></td>
                        <td><?= e($item['categoria_nome']) ?></td>
                        <td><?= e(dataBr($item['data'])) ?></td>
                        <td><?= e(ucfirst($item['tipo'])) ?></td>
                        <td class="<?= $item['tipo'] === 'receita' ? 'receita' : 'despesa' ?>">
                            <?= e(dinheiro($item['valor'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </section>

</main>

<!-- Chart.js (gráficos) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    // Dados vindos do PHP (o DashboardController já monta no formato do Chart.js)
    const pizza  = <?= json_encode($graficoPizza, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const barras = <?= json_encode($graficoBarras, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    // Mostra valores como "R$ 1.234,56"
    const formatarReal = (valor) =>
        valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    // Se o CDN não carregar (sem internet), a página continua funcionando sem os gráficos
    if (window.Chart) {

        const canvasPizza = document.getElementById('graficoPizza');
        if (canvasPizza) {
            new Chart(canvasPizza, {
                type: 'doughnut',
                data: {
                    labels: pizza.labels,
                    datasets: [{
                        data: pizza.valores,
                        backgroundColor: ['#2563eb', '#dc2626', '#d97706', '#16a34a', '#7c3aed', '#db2777', '#0891b2', '#65a30d', '#64748b'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: { label: (ctx) => ctx.label + ': ' + formatarReal(ctx.parsed) }
                        }
                    }
                }
            });
        }

        const canvasBarras = document.getElementById('graficoBarras');
        if (canvasBarras) {
            new Chart(canvasBarras, {
                type: 'bar',
                data: {
                    labels: barras.labels,
                    datasets: [
                        { label: 'Receitas', data: barras.receitas, backgroundColor: '#16a34a', borderRadius: 4 },
                        { label: 'Despesas', data: barras.despesas, backgroundColor: '#dc2626', borderRadius: 4 }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (v) => formatarReal(v) } }
                    },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: { label: (ctx) => ctx.dataset.label + ': ' + formatarReal(ctx.parsed.y) }
                        }
                    }
                }
            });
        }
    }
</script>

</body>
</html>
