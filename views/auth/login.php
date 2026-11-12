<?php
/** @var string $csrf */
/** @var array|null $flash */
/** @var string $email */
/** @var bool $lembrar */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Finance Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    

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

        input {
            width: 100%;
            padding: 13px;
            margin: 9px 0 17px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
        }

        label {
            color: #334155;
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

        button:hover {
            background: #1d4ed8;
        }

        .lembrar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: -5px 0 20px;
            font-size: 14px;
            cursor: pointer;
        }

        .lembrar input {
            width: auto;
            margin: 0;
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

    <h1>Finance Dashboard</h1>
    <p>Entre na sua conta para continuar.</p>

    <?php if (!empty($flash)): ?>
        <div class="mensagem <?= $flash['tipo'] === 'sucesso' ? 'sucesso' : '' ?>">
            <?= e($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('login')) ?>">

        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <label>E-mail</label>
        <input
            type="email"
            name="email"
            value="<?= e($email ?? '') ?>"
            placeholder="Digite seu e-mail"
            required
        >

        <label>Senha</label>
        <input
            type="password"
            name="senha"
            placeholder="Digite sua senha"
            required
        >

        <label class="lembrar">
            <input type="checkbox" name="lembrar" value="1" <?= !empty($lembrar) ? 'checked' : '' ?>>
            Lembrar meu e-mail neste computador
        </label>

        <button type="submit">Entrar</button>

    </form>

    <div class="link">
        <p>Não tem uma conta?
            <a href="<?= e(url('cadastro')) ?>">Cadastre-se</a>
        </p>
    </div>

</div>

</body>
</html>
