<?php
require_once __DIR__ . '/config/auth.php';

if (usuarioLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/db.php';
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $conn = getConnection();
        $stmt = $conn->prepare(
            "SELECT id, nome, email, senha, perfil, ativo FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $conn->close();

        if ($user && $user['ativo'] && password_verify($senha, $user['senha'])) {
            loginUsuario($user, $conn);
            // Registra último acesso
            $s2 = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
            $s2->bind_param('i', $user['id']);
            $s2->execute();
            $conn->close();
            header('Location: index.php');
            exit;
        } elseif ($user && !$user['ativo']) {
            $erro = 'Usuário desativado. Contate o administrador.';
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Central de Agendamento</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0; font-family: 'Segoe UI', system-ui, sans-serif;
      background: #0d1117;
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh;
      background-image:
        radial-gradient(ellipse at 30% 20%, rgba(99,179,237,.06) 0%, transparent 55%),
        radial-gradient(ellipse at 70% 80%, rgba(104,211,145,.04) 0%, transparent 55%);
    }
    .login-wrap {
      background: #1a1f2e;
      border: 1px solid rgba(99,179,237,.18);
      border-radius: 14px;
      box-shadow: 0 8px 40px rgba(0,0,0,.6);
      padding: 2.5rem 2.5rem 2rem;
      width: 100%; max-width: 400px;
    }
    .login-logo {
      text-align: center; margin-bottom: 1.75rem;
    }
    .login-logo i {
      font-size: 2.4rem; color: #63b3ed;
    }
    .login-logo h1 {
      font-size: 1.1rem; font-weight: 700; color: #e2e8f0;
      margin: .5rem 0 .2rem;
    }
    .login-logo p {
      font-size: .82rem; color: #718096; margin: 0;
    }
    .form-group {
      display: flex; flex-direction: column; gap: .35rem; margin-bottom: 1rem;
    }
    .form-group label {
      font-size: .78rem; font-weight: 700; color: #718096;
      text-transform: uppercase; letter-spacing: .04em;
    }
    .form-group input {
      background: #161b22; color: #e2e8f0;
      border: 1px solid rgba(99,179,237,.22);
      border-radius: 7px; padding: .6rem .85rem; font-size: .95rem;
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }
    .form-group input:focus {
      border-color: #63b3ed;
      box-shadow: 0 0 0 3px rgba(99,179,237,.15);
    }
    .btn-login {
      width: 100%; padding: .7rem; border: none; border-radius: 8px;
      background: #63b3ed; color: #0d1117;
      font-size: .95rem; font-weight: 700; cursor: pointer;
      transition: background .2s, transform .1s;
      margin-top: .25rem;
    }
    .btn-login:hover { background: #90cdf4; }
    .btn-login:active { transform: scale(.98); }
    .erro-msg {
      background: rgba(229,62,62,.12); border: 1px solid rgba(229,62,62,.4);
      color: #fc8181; border-radius: 7px; padding: .65rem .9rem;
      font-size: .875rem; margin-bottom: 1rem;
      display: flex; align-items: center; gap: .5rem;
    }
    .login-footer {
      text-align: center; margin-top: 1.5rem;
      font-size: .75rem; color: #4a5568;
    }
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="login-logo">
      <i class="fas fa-hospital-alt"></i>
      <h1>Hospital Santo Expedito</h1>
      <p>Central de Agendamento</p>
    </div>

    <?php if ($erro): ?>
    <div class="erro-msg">
      <i class="fas fa-exclamation-circle"></i>
      <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="on">
      <div class="form-group">
        <label for="email"><i class="fas fa-envelope"></i> E-mail</label>
        <input type="email" id="email" name="email"
               placeholder="seu@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autofocus>
      </div>
      <div class="form-group">
        <label for="senha"><i class="fas fa-lock"></i> Senha</label>
        <input type="password" id="senha" name="senha"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt"></i> Entrar
      </button>
    </form>

    <div class="login-footer">
      &copy; <?= date('Y') ?> Hospital Santo Expedito
    </div>
  </div>
</body>
</html>
