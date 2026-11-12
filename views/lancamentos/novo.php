<?php
/** @var string $tipo */
/** @var array $categorias */
/** @var array|null $flash */
/** @var string $csrf */

$ehReceita = $tipo === 'receita';
$titulo    = $ehReceita ? 'Nova receita' : 'Nova despesa';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<main class="container-form">

    <?php if (!empty($flash)): ?>
        <div class="flash <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div>
    <?php endif; ?>

    <section class="secao">
        <h2><?= e($titulo) ?></h2>

        <?php if (empty($categorias)): ?>

            <p class="vazio">
                Você ainda não tem categorias de <?= $ehReceita ? 'receita' : 'despesa' ?>.
            </p>
            <a href="index.php?rota=dashboard" class="btn btn-cancelar">Voltar</a>

        <?php else: ?>

            <form method="POST" action="index.php?rota=lancamento_salvar">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

                <label for="descricao">Descrição</label>
                <input type="text" id="descricao" name="descricao" maxlength="150" required>

                <label for="valor">Valor (R$)</label>
                <input type="number" id="valor" name="valor" step="0.01" min="0.01" required>

                <label for="data">Data</label>
                <input type="date" id="data" name="data" value="<?= date('Y-m-d') ?>" required>

                <label for="categoria_id">Categoria</label>
                <select id="categoria_id" name="categoria_id" required>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="botoes">
                    <a href="index.php?rota=dashboard" class="btn btn-cancelar">Cancelar</a>
                    <button type="submit" class="btn <?= $ehReceita ? 'btn-receita' : 'btn-despesa' ?>">Salvar</button>
                </div>
            </form>

        <?php endif; ?>
    </section>
</main>

</body>
</html>