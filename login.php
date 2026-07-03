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
        // NÃO fecha $conn aqui — loginUsuario e update ainda precisam dela

        if ($user && $user['ativo'] && password_verify($senha, $user['senha'])) {
            // Registra último acesso
            $s2 = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
            $s2->bind_param('i', $user['id']);
            $s2->execute();
            loginUsuario($user, $conn);
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
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      background: #0f172a;
    }

    /* ── Painel esquerdo ──────────────────────────────── */
    .lp-left {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 4rem;
      background: linear-gradient(145deg, #0f172a 0%, #1e2d45 60%, #0f3460 100%);
      position: relative;
      overflow: hidden;
    }

    /* Círculos decorativos de fundo */
    .lp-left::before,
    .lp-left::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: .07;
    }
    .lp-left::before {
      width: 500px; height: 500px;
      background: radial-gradient(circle, #60a5fa, transparent 70%);
      top: -120px; left: -120px;
    }
    .lp-left::after {
      width: 380px; height: 380px;
      background: radial-gradient(circle, #34d399, transparent 70%);
      bottom: -100px; right: -80px;
    }

    .lp-left-inner {
      position: relative; z-index: 1;
      text-align: center;
      max-width: 420px;
    }
    .lp-icon {
      width: 90px; height: 90px;
      background: rgba(96,165,250,.12);
      border: 2px solid rgba(96,165,250,.3);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.75rem;
    }
    .lp-icon i { font-size: 2.4rem; color: #60a5fa; }

    .lp-title {
      font-size: 2rem; font-weight: 800; color: #f1f5f9;
      line-height: 1.2; margin-bottom: .6rem;
      letter-spacing: -.01em;
    }
    .lp-subtitle {
      font-size: 1rem; color: #60a5fa; font-weight: 600;
      margin-bottom: 1.75rem; letter-spacing: .04em;
      text-transform: uppercase;
    }
    .lp-desc {
      font-size: .88rem; color: #94a3b8; line-height: 1.7;
      margin-bottom: 2.5rem;
    }

    .lp-features {
      display: flex; flex-direction: column; gap: .75rem;
      text-align: left;
    }
    .lp-feature {
      display: flex; align-items: center; gap: .75rem;
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(96,165,250,.12);
      border-radius: 8px; padding: .6rem .9rem;
    }
    .lp-feature i {
      font-size: .9rem; color: #34d399; flex-shrink: 0; width: 18px; text-align: center;
    }
    .lp-feature span { font-size: .82rem; color: #cbd5e1; }

    /* ── Painel direito ───────────────────────────────── */
    .lp-right {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2.5rem 2.5rem 2rem;
      background: #1e293b;
      border-left: 1px solid rgba(96,165,250,.12);
    }

    .lp-form-header {
      text-align: center; margin-bottom: 2rem; width: 100%;
    }
    .lp-form-header h2 {
      font-size: 1.25rem; font-weight: 700; color: #f1f5f9;
    }
    .lp-form-header p {
      font-size: .82rem; color: #64748b; margin-top: .3rem;
    }

    .form-group {
      display: flex; flex-direction: column; gap: .35rem;
      margin-bottom: 1.1rem; width: 100%;
    }
    .form-group label {
      font-size: .75rem; font-weight: 700; color: #64748b;
      text-transform: uppercase; letter-spacing: .05em;
      display: flex; align-items: center; gap: .4rem;
    }
    .form-group input {
      background: #0f172a; color: #f1f5f9;
      border: 1px solid rgba(96,165,250,.2);
      border-radius: 8px; padding: .65rem .9rem; font-size: .95rem;
      transition: border-color .2s, box-shadow .2s;
      outline: none; width: 100%;
    }
    .form-group input:focus {
      border-color: #60a5fa;
      box-shadow: 0 0 0 3px rgba(96,165,250,.15);
    }
    .form-group input::placeholder { color: #475569; }

    .btn-login {
      width: 100%; padding: .75rem; border: none; border-radius: 8px;
      background: #2563eb; color: #ffffff;
      font-size: .95rem; font-weight: 700; cursor: pointer;
      transition: background .2s, transform .1s, box-shadow .2s;
      margin-top: .5rem;
      display: flex; align-items: center; justify-content: center; gap: .5rem;
      letter-spacing: .03em;
    }
    .btn-login:hover {
      background: #3b82f6;
      box-shadow: 0 4px 16px rgba(37,99,235,.4);
    }
    .btn-login:active { transform: scale(.98); }

    .erro-msg {
      background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.35);
      color: #f87171; border-radius: 8px; padding: .65rem .9rem;
      font-size: .875rem; margin-bottom: 1rem;
      display: flex; align-items: center; gap: .5rem; width: 100%;
    }

    .lp-footer {
      margin-top: 2rem; text-align: center;
      font-size: .73rem; color: #334155;
    }

    /* ── Responsivo ───────────────────────────────────── */
    @media (max-width: 800px) {
      body { flex-direction: column; }
      .lp-left {
        padding: 2.5rem 2rem;
        min-height: auto;
      }
      .lp-features { display: none; }
      .lp-right {
        width: 100%;
        border-left: none;
        border-top: 1px solid rgba(96,165,250,.12);
        padding: 2rem 1.5rem 2.5rem;
      }
    }
  </style>
</head>
<body>

  <!-- Esquerdo: identidade do sistema -->
  <div class="lp-left">
    <div class="lp-left-inner">
      <div class="lp-icon">
        <i class="fas fa-hospital-alt"></i>
      </div>
      <div class="lp-title">Hospital Santo Expedito</div>
      <div class="lp-subtitle">Central de Agendamento</div>
      <p class="lp-desc">
        Sistema integrado para gestão de autorizações de exames,
        controle de atendimentos e acompanhamento de convênios.
      </p>
      <div class="lp-features">
        <div class="lp-feature">
          <i class="fas fa-file-medical-alt"></i>
          <span>Autorização e controle de exames em tempo real</span>
        </div>
        <div class="lp-feature">
          <i class="fas fa-handshake"></i>
          <span>Gestão de convênios e procedimentos</span>
        </div>
        <div class="lp-feature">
          <i class="fas fa-chart-bar"></i>
          <span>Dashboard com indicadores de atendimento</span>
        </div>
        <div class="lp-feature">
          <i class="fas fa-shield-alt"></i>
          <span>Controle de acesso por perfil de usuário</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Direito: formulário de login -->
  <div class="lp-right">
    <div class="lp-form-header">
      <h2><i class="fas fa-sign-in-alt" style="color:#60a5fa;margin-right:.4rem;"></i> Acesso ao Sistema</h2>
      <p>Informe suas credenciais para continuar</p>
    </div>

    <?php if ($erro): ?>
    <div class="erro-msg">
      <i class="fas fa-exclamation-circle"></i>
      <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="on" style="width:100%;">
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

    <div class="lp-footer">
      &copy; <?= date('Y') ?> Hospital Santo Expedito &mdash; Todos os direitos reservados
    </div>
  </div>

</body>
</html>
