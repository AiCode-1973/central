<?php
/**
 * Script de reset do administrador.
 * APAGUE este arquivo após usar!
 */
require_once __DIR__ . '/config/db.php';

$novaSenha = 'Admin@123';
$novoEmail = 'admin@hospital.com';
$hash      = password_hash($novaSenha, PASSWORD_DEFAULT);

$conn = getConnection();

// Verifica se já existe algum admin
$stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE perfil = 'admin' ORDER BY id LIMIT 1");
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if ($admin) {
    // Reseta a senha do primeiro admin encontrado
    $upd = $conn->prepare("UPDATE usuarios SET senha = ?, ativo = 1 WHERE id = ?");
    $upd->bind_param('si', $hash, $admin['id']);
    $upd->execute();
    $msg  = "Senha do administrador <strong>{$admin['nome']} ({$admin['email']})</strong> foi redefinida.";
} else {
    // Cria um novo admin padrão
    $nome = 'Administrador';
    $ins  = $conn->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES (?, ?, ?, 'admin', 1)");
    $ins->bind_param('sss', $nome, $novoEmail, $hash);
    $ins->execute();
    $msg = "Nenhum admin encontrado. Criado novo administrador: <strong>{$novoEmail}</strong>";
}

$conn->close();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Reset Admin</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background:#0d1117; color:#e2e8f0; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
    .box { background:#1a1f2e; border:1px solid rgba(99,179,237,.25); border-radius:12px; padding:2rem 2.5rem; max-width:500px; width:100%; text-align:center; }
    h2 { color:#63b3ed; margin:0 0 1rem; }
    .info { background:rgba(104,211,145,.1); border:1px solid rgba(104,211,145,.3); border-radius:8px; padding:1rem 1.25rem; margin:1rem 0; font-size:.95rem; color:#68d391; }
    .cred { background:rgba(99,179,237,.08); border:1px solid rgba(99,179,237,.2); border-radius:8px; padding:1rem 1.25rem; margin:1rem 0; font-size:.95rem; line-height:1.8; }
    .warn { background:rgba(229,62,62,.1); border:1px solid rgba(229,62,62,.3); border-radius:8px; padding:.75rem 1rem; margin:1.25rem 0; color:#fc8181; font-size:.88rem; }
    a { display:inline-block; margin-top:1rem; background:#63b3ed; color:#0d1117; padding:.55rem 1.5rem; border-radius:7px; text-decoration:none; font-weight:700; }
    a:hover { background:#90cdf4; }
    code { background:rgba(255,255,255,.08); padding:.1rem .4rem; border-radius:4px; font-family:monospace; }
  </style>
</head>
<body>
  <div class="box">
    <h2><i>🔑</i> Reset de Administrador</h2>
    <div class="info">✔ <?= $msg ?></div>
    <div class="cred">
      <strong>Novas credenciais de acesso:</strong><br>
      E-mail: <code><?= $admin ? htmlspecialchars($admin['email']) : $novoEmail ?></code><br>
      Senha: <code><?= $novaSenha ?></code>
    </div>
    <div class="warn">
      ⚠️ <strong>ATENÇÃO:</strong> Delete o arquivo <code>reset_admin.php</code> imediatamente após fazer login!
    </div>
    <a href="login.php">Ir para o Login</a>
  </div>
</body>
</html>
