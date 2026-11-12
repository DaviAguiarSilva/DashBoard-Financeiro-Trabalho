
<?php

/** @var array{receitas: float, despesas: float, saldo: float} $totais */
/** @var string $usuarioNome */
/** @var string $mesAtual */

/** @var array $resumoMensal */
/** @var array $despesasPorCategoria */
/** @var array $lancamentosRecentes */

/** @var array|null $flash */
/** @var string $csrf */
/** @var string $rotaAtual */

$receitas = $totais['receitas'];
$despesas = $totais['despesas'];
$saldo = $totais['saldo'];

?>

<?php
$receitas = $totais['receitas'];
$despesas = $totais['despesas'];
$saldo = $totais['saldo'];
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
            background: white;
            padding: 22px 5%;
            border-bottom: 1px solid #e2e8f0;
        }

        main {
            padding: 35px 5%;
            max-width: 1400px;
            margin: auto;
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

        .secao {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
        }

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
    <h2>Finance Dashboard</h2>
    <p>Olá, <?= e($usuarioNome) ?>!</p>
</header>

<main>

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
            <h2 class="saldo">
                <?= e(dinheiro($saldo)) ?>
            </h2>
        </div>

    </div>

    <section class="secao">

        <h2>Resumo dos últimos seis meses</h2>

        <?php if (empty($resumoMensal)): ?>

            <p class="vazio">Ainda não existem movimentações.</p>

        <?php else: ?>

            <table>
                <thead>
                <tr>
                    <th>Mês</th>
                    <th>Receitas</th>
                    <th>Despesas</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($resumoMensal as $linha): ?>
                    <tr>
                        <td><?= e(mesCurto($linha['mes'])) ?></td>
                        <td class="receita">
                            <?= e(dinheiro($linha['receitas'])) ?>
                        </td>
                        <td class="despesa">
                            <?= e(dinheiro($linha['despesas'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </section>

    <section class="secao">

        <h2>Despesas por categoria</h2>

        <?php if (empty($despesasPorCategoria)): ?>

            <p class="vazio">Nenhuma despesa registrada neste mês.</p>

        <?php else: ?>

            <table>
                <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Total gasto</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($despesasPorCategoria as $categoria): ?>
                    <tr>
                        <td><?= e($categoria['categoria']) ?></td>
                        <td class="despesa">
                            <?= e(dinheiro($categoria['total'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

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
                        <td><?= e($item['tipo']) ?></td>
                        <td><?= e(dinheiro($item['valor'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
