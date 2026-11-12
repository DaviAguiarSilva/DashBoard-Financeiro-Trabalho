<?php
/** @var string $csrf */
/** @var array|null $flash */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | Finance Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f1f5f9;
        }

        .container {
            width: 90%;
            max-width: 400px;
            padding: 35px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 25px #00000012;
        }

        h1 {
            text-align: center;
            color: #1e293b;
        }

        p {
            text-align: center;
            color: #64748b;
        }

        label {
            color: #334155;
        }

        input {
            width: 100%;
            padding: 13px;
            margin: 9px 0 17px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 7px;
            cursor: pointer;
        }

        .mensagem {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 7px;
            background: #fee2e2;
            color: #991b1b;
        }

        .sucesso {
            background: #dcfce7;
            color: #166534;
        }

        .link {
            margin-top: 20px;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Criar conta</h1>
    <p>Comece a organizar sua vida financeira.</p>

    <?php if (!empty($flash)): ?>
        <div class="mensagem <?= $flash['tipo'] === 'sucesso' ? 'sucesso' : '' ?>">
            <?= e($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('cadastro')) ?>">

        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <label>Nome completo</label>
        <input
            type="text"
            name="nome"
            value="<?= e($nome ?? '') ?>"
            placeholder="Seu nome"
            required
        >

        <label>E-mail</label>
        <input
            type="email"
            name="email"
            value="<?= e($email ?? '') ?>"
            placeholder="Seu e-mail"
            required
        >

        <label>Senha</label>
        <input
            type="password"
            name="senha"
            placeholder="Mínimo de 6 caracteres"
            minlength="6"
            required
        >

        <label>Confirmar senha</label>
        <input
            type="password"
            name="confirmar_senha"
            placeholder="Digite a senha novamente"
            minlength="6"
            required
        >

        <button type="submit">Criar minha conta</button>

    </form>

    <div class="link">
        <p>Já possui uma conta?
            <a href="<?= e(url('login')) ?>">Fazer login</a>
        </p>
    </div>

</div>

</body>
</html>
