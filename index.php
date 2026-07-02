<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';
$usuarioLogado = requireLogin();

// Recarrega permissões do banco a cada acesso para refletir alterações sem precisar relogar
$_connPerm = getConnection();
$permissoes = carregarPermissoes($usuarioLogado['perfil'], $_connPerm);
$_connPerm->close();
$_SESSION['usuario']['permissoes'] = $permissoes;
$usuarioLogado['permissoes']       = $permissoes;

function temPerm(string $m): bool {
    global $permissoes;
    return in_array($m, $permissoes, true);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Central de Agendamento — Hospital Santo Expedito</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
  <script>
    const USUARIO_LOGADO = <?= json_encode($usuarioLogado) ?>;
    const PERMISSOES     = <?= json_encode($permissoes) ?>;
    // Aplica tema e estado do sidebar antes do render para evitar flash
    (function(){
      const t = localStorage.getItem('tema');
      if (t === 'claro') document.documentElement.setAttribute('data-tema','claro');
      if (localStorage.getItem('sbCollapsed') === '1') document.documentElement.setAttribute('data-sb','collapsed');
    })();
  </script>
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="navbar-app">
  <button class="sb-toggle" id="btn-sb-toggle" onclick="toggleSidebar()" title="Expandir/recolher menu"><i class="fas fa-bars"></i></button>
  <i class="fas fa-hospital-alt" style="color:var(--neon-cyan);font-size:1.5rem;"></i>
  <div class="brand">
    Hospital Santo Expedito
    <small>Central de Agendamento — Dashboard</small>
  </div>
  <!-- Usuário logado + logout -->
  <div style="display:flex;align-items:center;gap:.6rem;margin-left:auto;padding-left:1rem;border-left:1px solid rgba(255,255,255,.08);flex-shrink:0;">
    <span style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;">
      <i class="fas fa-user-circle"></i>
      <?= htmlspecialchars($usuarioLogado['nome']) ?>
      <small style="margin-left:.25rem;opacity:.65;">(<?= $usuarioLogado['perfil'] ?>)</small>
    </span>
    <button onclick="abrirModalPerfil()" class="btn-app sm" title="Alterar senha"
      style="border-color:var(--neon-cyan);color:var(--neon-cyan);">
      <i class="fas fa-key"></i>
    </button>
    <a href="logout.php" class="btn-app dang sm" style="text-decoration:none;">
      <i class="fas fa-sign-out-alt"></i> Sair
    </a>
    <button id="btn-tema" onclick="toggleTema()" title="Alternar modo claro/escuro"
      style="background:none;border:1px solid var(--border);color:var(--text-muted);border-radius:6px;padding:.3rem .55rem;cursor:pointer;font-size:.95rem;transition:all .2s;">
      <i class="fas fa-sun"></i>
    </button>
  </div>
</nav>

<div class="sb-overlay" id="sb-overlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <nav class="sb-nav">
    <?php if (temPerm('dashboard')): ?>
    <button class="tab-btn active" onclick="showTab('dashboard',this)" title="Dashboard">
      <i class="fas fa-chart-line"></i> <span>Dashboard</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('atendimentos')): ?>
    <button class="tab-btn" onclick="showTab('atendimentos',this)" title="Atendimentos">
      <i class="fas fa-calendar-check"></i> <span>Atendimentos</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('picos')): ?>
    <button class="tab-btn" onclick="showTab('picos',this)" title="Horários de Pico">
      <i class="fas fa-clock"></i> <span>Horários de Pico</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('fechamentos')): ?>
    <button class="tab-btn" onclick="showTab('fechamentos',this)" title="Fechamentos">
      <i class="fas fa-door-closed"></i> <span>Fechamentos</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('motivos')): ?>
    <button class="tab-btn" onclick="showTab('motivos',this)" title="Motivos">
      <i class="fas fa-tags"></i> <span>Motivos</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('semanas')): ?>
    <button class="tab-btn" onclick="showTab('semanas',this)" title="Semanas">
      <i class="fas fa-calendar-week"></i> <span>Semanas</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('pesquisa')): ?>
    <button class="tab-btn" onclick="showTab('pesquisa',this)" title="Pesquisa">
      <i class="fas fa-star"></i> <span>Pesquisa</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('usuarios')): ?>
    <button class="tab-btn" onclick="showTab('usuarios',this)" title="Usuários">
      <i class="fas fa-users"></i> <span>Usuários</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('autorizacoes')): ?>
    <button class="tab-btn" onclick="showTab('autorizacoes',this)" title="Autorizações">
      <i class="fas fa-file-medical"></i> <span>Autorizações</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('convenios')): ?>
    <button class="tab-btn" onclick="showTab('convenios',this)" title="Convênios">
      <i class="fas fa-handshake"></i> <span>Convênios</span>
    </button>
    <?php endif; ?>
    <?php if (temPerm('procedimentos')): ?>
    <button class="tab-btn" onclick="showTab('procedimentos',this)" title="Procedimentos">
      <i class="fas fa-stethoscope"></i> <span>Procedimentos</span>
    </button>
    <?php endif; ?>
  </nav>
</aside>

<div class="main-wrap">

  <!-- ══ SELETOR DE SEMANA (global) ══════════════════════════ -->
  <div class="semana-selector">
    <label><i class="fas fa-calendar-week me-1"></i> Semana:</label>
    <select id="sel-semana" onchange="onSemanaChange()">
      <option value="">— Selecione uma semana —</option>
    </select>
    <button class="btn-app prim btn-nova-semana" onclick="showTab('semanas',null)">
      <i class="fas fa-plus"></i> Nova Semana
    </button>
  </div>

  <!-- ══════════════════════════════════════════════════════
       ABA: DASHBOARD
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-dashboard" class="tab-section" <?php if(!temPerm('dashboard')) echo 'style="display:none"'; ?>>

    <!-- Toggle Semana / Mês -->
    <div style="display:flex;gap:.5rem;margin-bottom:1.1rem;align-items:center;flex-wrap:wrap;">
      <button id="btn-view-semana" class="btn-app prim" onclick="setViewMode('semana')">
        <i class="fas fa-calendar-week"></i> Semana
      </button>
      <button id="btn-view-mes" class="btn-app" style="background:transparent;border:1px solid rgba(0,255,255,.2);color:var(--text-muted);" onclick="setViewMode('mes')">
        <i class="fas fa-calendar-alt"></i> Mês
      </button>
      <div id="mes-selector" style="display:none;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <select id="sel-ano" style="padding:.4rem .65rem;border:1px solid rgba(0,255,255,.25);border-radius:6px;font-size:.9rem;background:var(--bg2);color:var(--text);"></select>
        <select id="sel-mes" style="padding:.4rem .65rem;border:1px solid rgba(0,255,255,.25);border-radius:6px;font-size:.9rem;background:var(--bg2);color:var(--text);">
          <option value="1">Janeiro</option><option value="2">Fevereiro</option>
          <option value="3">Março</option><option value="4">Abril</option>
          <option value="5">Maio</option><option value="6">Junho</option>
          <option value="7">Julho</option><option value="8">Agosto</option>
          <option value="9">Setembro</option><option value="10">Outubro</option>
          <option value="11">Novembro</option><option value="12">Dezembro</option>
        </select>
        <button class="btn-app prim" onclick="carregarDashboardMes()">
          <i class="fas fa-search"></i> Buscar
        </button>
      </div>
    </div>

    <!-- Vista Semanal -->
    <div id="view-semana">

      <div class="charts-grid" style="grid-template-columns:2fr 1fr;">
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-chart-bar"></i> Evolução de Atendimentos (diário)</div>
          <div class="chart-wrap"><canvas id="chart-evolucao"></canvas></div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-chart-pie"></i> Motivos de Fechamento — Distribuição</div>
          <div style="display:flex;align-items:center;gap:1rem;">
            <div style="flex-shrink:0;"><canvas id="chart-pizza"></canvas></div>
            <div id="pizza-legenda" style="flex:1;font-size:.82rem;"></div>
          </div>
        </div>
      </div>

      <div class="charts-grid" style="grid-template-columns:1fr 1fr 1fr;">
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-clock"></i> Top 5 Horários de Pico — <span id="pico-semana-label" style="font-weight:400;color:var(--text-muted);font-size:.78rem;">selecione uma semana</span></div>
          <div class="chart-wrap"><canvas id="chart-picos"></canvas></div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-door-closed"></i> Motivos de Fechamento</div>
          <div id="resumo-fechamentos" style="font-size:.88rem;color:var(--text);">
            Selecione uma semana para visualizar.
          </div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-star"></i> Pesquisa de Satisfação</div>
          <div id="pesquisa-semana-wrap">
            <p style="color:var(--text-muted);font-size:.88rem;">Selecione uma semana para visualizar.</p>
          </div>
        </div>
      </div>
    </div><!-- /#view-semana -->

    <!-- Vista Mensal -->
    <div id="view-mes" style="display:none;">
      <div class="charts-grid">
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-chart-bar"></i> Atendimentos por Semana</div>
          <div class="chart-wrap"><canvas id="chart-mes-semanas"></canvas></div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-clock"></i> Top 5 Horários de Pico (mês)</div>
          <div class="chart-wrap"><canvas id="chart-mes-picos"></canvas></div>
        </div>
      </div>

      <div class="charts-grid">
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-door-closed"></i> Motivos de Fechamento (mês)</div>
          <div id="resumo-fechamentos-mes" style="font-size:.9rem;color:var(--text);">Busque um mês para visualizar.</div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-star"></i> Pesquisa de Satisfação (mês)</div>
          <div id="pesquisa-mes-wrap">
            <p style="color:var(--text-muted);font-size:.88rem;">Busque um mês para visualizar.</p>
          </div>
        </div>
      </div>
    </div><!-- /#view-mes -->

  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: ATENDIMENTOS
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-atendimentos" class="tab-section" <?php if(!temPerm('atendimentos')) echo 'style="display:none"'; ?>>  
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-calendar-check"></i> Atendimentos da Semana</div>
      <div id="form-atendimentos">
        <p style="color:var(--text-muted);font-size:.9rem;">Selecione uma semana primeiro.</p>
      </div>
      <div style="margin-top:1rem;">
        <button class="btn-app suc" onclick="salvarAtendimentos()">
          <i class="fas fa-save"></i> Salvar Atendimentos
        </button>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: HORÁRIOS DE PICO
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-picos" class="tab-section" <?php if(!temPerm('picos')) echo 'style="display:none"'; ?>>  
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-clock"></i> Horários de Pico da Semana</div>
      <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.1rem;">
        Selecione a semana no topo da página e preencha o total de atendimentos para cada horário.
      </p>
      <div id="picos-grade-wrap">
        <p style="color:var(--text-muted);font-size:.9rem;">Selecione uma semana para carregar os horários.</p>
      </div>
      <button class="btn-app suc" style="margin-top:1rem;" onclick="salvarPicos()">
        <i class="fas fa-save"></i> Salvar Horários de Pico
      </button>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: FECHAMENTOS
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-fechamentos" class="tab-section" <?php if(!temPerm('fechamentos')) echo 'style="display:none"'; ?>>  
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-door-closed"></i> Motivos de Fechamento da Semana</div>
      <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem;">
        Informe o motivo e a quantidade total de dias fechados por esse motivo na semana.
      </p>

      <!-- Linha para adicionar novo motivo -->
      <div class="form-inline-row" style="margin-bottom:1.1rem;align-items:flex-end;">
        <div class="form-group" style="flex:1;min-width:200px;">
          <label>Motivo <small style="color:var(--text-muted);font-weight:400;">(selecione ou digite novo)</small></label>
          <input type="text" id="fech-motivo-txt" list="list-motivos"
                 placeholder="Ex: Feriado, Manutenção…"
                 style="width:100%;padding:.4rem .65rem;border:1px solid rgba(0,255,255,.25);border-radius:6px;font-size:.9rem;background:var(--bg2);color:var(--text);">
          <datalist id="list-motivos"></datalist>
        </div>
        <div class="form-group">
          <label>Total de dias</label>
          <input type="number" id="fech-total" min="1" max="5" value="1"
                 style="width:90px;padding:.4rem .65rem;border:1px solid rgba(0,255,255,.25);border-radius:6px;font-size:.9rem;background:var(--bg2);color:var(--text);">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Observação</label>
          <input type="text" id="fech-obs" placeholder="Opcional" style="width:100%;padding:.4rem .65rem;border:1px solid rgba(0,255,255,.25);border-radius:6px;font-size:.9rem;background:var(--bg2);color:var(--text);">
        </div>
        <button class="btn-app prim" onclick="salvarFechamento()">
          <i class="fas fa-plus"></i> Adicionar
        </button>
      </div>

      <!-- Tabela de fechamentos da semana -->
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>Motivo</th><th style="width:120px;text-align:center;">Total de dias</th><th>Observação</th><th></th></tr>
          </thead>
          <tbody id="tbody-fechamentos">
            <tr><td colspan="4" style="color:var(--text-muted);">Selecione uma semana.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: MOTIVOS
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-motivos" class="tab-section" <?php if(!temPerm('motivos')) echo 'style="display:none"'; ?>>  
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-tags"></i> Cadastro de Motivos de Fechamento</div>
      <div class="form-inline-row" style="margin-bottom:1rem;">
        <div class="form-group" style="flex:1;">
          <label>Descrição do Motivo</label>
          <input type="text" id="motivo-desc" placeholder="Ex: Feriado Nacional, Manutenção…" style="width:100%;">
        </div>
        <button class="btn-app prim" style="align-self:flex-end;" onclick="salvarMotivo()">
          <i class="fas fa-plus"></i> Cadastrar
        </button>
      </div>

      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>#</th><th>Descrição</th><th>Status</th><th></th></tr>
          </thead>
          <tbody id="tbody-motivos">
            <tr><td colspan="4" style="color:var(--text-muted);">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: SEMANAS
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-semanas" class="tab-section" <?php if(!temPerm('semanas')) echo 'style="display:none"'; ?>>  
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-calendar-week"></i> Cadastro de Semanas (Seg–Sex)</div>
      <div class="form-inline-row" style="margin-bottom:1rem;">
        <div class="form-group">
          <label>Segunda-feira (início)</label>
          <input type="date" id="semana-inicio" oninput="calcFim()">
        </div>
        <div class="form-group">
          <label>Sexta-feira (fim)</label>
          <input type="date" id="semana-fim" readonly style="background:rgba(0,255,255,.05);color:var(--text-muted);">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Descrição (opcional)</label>
          <input type="text" id="semana-desc" placeholder="Ex: Semana 26 — jun/2026">
        </div>
        <button class="btn-app prim" style="align-self:flex-end;" onclick="salvarSemana()">
          <i class="fas fa-plus"></i> Cadastrar
        </button>
      </div>

      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>Início</th><th>Fim</th><th>Descrição</th><th></th></tr>
          </thead>
          <tbody id="tbody-semanas">
            <tr><td colspan="4" style="color:var(--text-muted);">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: PESQUISA DE SATISFAÇÃO
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-pesquisa" class="tab-section" <?php if(!temPerm('pesquisa')) echo 'style="display:none"'; ?>>  
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-star"></i> Pesquisa de Satisfação da Semana</div>
      <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.25rem;">
        Informe a quantidade de respostas recebidas para cada nível de satisfação na semana selecionada.
      </p>

      <div id="pesquisa-form-wrap">
        <p style="color:var(--text-muted);font-size:.9rem;">Selecione uma semana para registrar a pesquisa.</p>
      </div>

      <div style="margin-top:1.25rem;">
        <button class="btn-app suc" onclick="salvarPesquisa()">
          <i class="fas fa-save"></i> Salvar Pesquisa
        </button>
      </div>
    </div>
  </section>

  <?php if ($usuarioLogado['perfil'] === 'admin'): ?>
  <!-- ══════════════════════════════════════════════════════
       ABA: USUÁRIOS
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-usuarios" class="tab-section" <?php if(!temPerm('usuarios')) echo 'style="display:none"'; ?>>  
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-users"></i> <span id="usu-form-titulo">Novo Usuário</span></div>
      <div class="form-inline-row" style="margin-bottom:1.25rem;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="flex:1;min-width:180px;">
          <label>Nome</label>
          <input type="text" id="usu-nome" placeholder="Nome completo" style="width:100%;">
        </div>
        <div class="form-group" style="flex:1;min-width:200px;">
          <label>E-mail</label>
          <input type="email" id="usu-email" placeholder="email@hospital.com" style="width:100%;">
        </div>
        <div class="form-group">
          <label>Perfil</label>
          <select id="usu-perfil">
            <option value="">Carregando…</option>
          </select>
        </div>
        <div class="form-group" id="usu-senha-wrap">
          <label>Senha <span id="usu-senha-hint" style="font-weight:400;color:var(--text-muted);">(obrigatória)</span></label>
          <input type="password" id="usu-senha" placeholder="Mín. 6 caracteres" style="width:160px;">
        </div>
        <div style="display:flex;gap:.4rem;">
          <button class="btn-app suc" onclick="salvarUsuario()">
            <i class="fas fa-save"></i> <span id="usu-btn-label">Cadastrar</span>
          </button>
          <button class="btn-app" id="usu-btn-cancelar" style="display:none;border-color:var(--text-muted);color:var(--text-muted);" onclick="cancelarEdicaoUsuario()">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr>
              <th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th>
              <th>Último Acesso</th><th></th>
            </tr>
          </thead>
          <tbody id="tbody-usuarios">
            <tr><td colspan="6" style="color:var(--text-muted);">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CRUD Perfis -->
    <div class="painel" style="margin-top:1.25rem;">
      <div class="painel-titulo"><i class="fas fa-id-badge"></i> <span id="perf-form-titulo">Novo Perfil</span></div>
      <div class="form-inline-row" style="margin-bottom:1.25rem;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group">
          <label>Slug <small style="color:var(--text-muted);font-weight:400;">(ex: gerente)</small></label>
          <input type="text" id="perf-slug" placeholder="so_letras_numeros" style="width:160px;"
                 oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
        </div>
        <div class="form-group" style="flex:1;min-width:180px;">
          <label>Label (nome exibido)</label>
          <input type="text" id="perf-label" placeholder="Ex: Gerente" style="width:100%;">
        </div>
        <div class="form-group" style="flex:2;min-width:220px;">
          <label>Descrição (opcional)</label>
          <input type="text" id="perf-descricao" placeholder="Descrição do perfil" style="width:100%;">
        </div>
        <div style="display:flex;gap:.4rem;">
          <button class="btn-app suc" onclick="salvarPerfil()">
            <i class="fas fa-save"></i> <span id="perf-btn-label">Cadastrar</span>
          </button>
          <button class="btn-app" id="perf-btn-cancelar" style="display:none;border-color:var(--text-muted);color:var(--text-muted);" onclick="cancelarEdicaoPerfil()">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>Slug</th><th>Label</th><th>Descrição</th><th>Status</th><th></th></tr>
          </thead>
          <tbody id="tbody-perfis">
            <tr><td colspan="5" style="color:var(--text-muted);">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ABA: AUTORIZAÇÕES DE EXAMES -->
  <section id="tab-autorizacoes" class="tab-section" <?php if(!temPerm('autorizacoes')) echo 'style="display:none"'; ?>>
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-file-medical"></i> Autorizações de Exames</div>

      <!-- Formulário de cadastro/edição -->
      <div id="aut-form-wrap" style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:1.25rem;margin-bottom:1.5rem;">
        <div style="font-size:.8rem;font-weight:700;color:var(--neon-cyan);text-transform:uppercase;letter-spacing:.04em;margin-bottom:1rem;" id="aut-form-titulo">Nova Autorização</div>
        <div class="form-inline-row">
          <div class="form-group" style="flex:2;min-width:200px;">
            <label>Nome do Paciente <span style="color:var(--neon-pink);">*</span></label>
            <input type="text" id="aut-paciente-nome" placeholder="Nome completo" style="width:100%;">
          </div>
          <div class="form-group" style="min-width:150px;">
            <label>CPF</label>
            <input type="text" id="aut-cpf" placeholder="000.000.000-00" maxlength="14" style="width:100%;" oninput="mascaraCPF(this)">
          </div>
          <div class="form-group" style="min-width:150px;">
            <label>Telefone</label>
            <input type="text" id="aut-telefone" placeholder="(00) 00000-0000" maxlength="15" style="width:100%;" oninput="mascaraTelefone(this)">
          </div>
        </div>
        <div class="form-inline-row">
          <div class="form-group" style="flex:1;min-width:180px;">
            <label>Convênio <span style="color:var(--neon-pink);">*</span></label>
            <select id="aut-convenio" style="width:100%;padding:.4rem .65rem;border:1px solid rgba(99,179,237,.25);border-radius:6px;background:var(--bg2);color:var(--text);font-size:.9rem;">
              <option value="">— Selecione —</option>
            </select>
          </div>
          <div class="form-group" style="flex:1;min-width:180px;">
            <label>Procedimento (Exame) <span style="color:var(--neon-pink);">*</span></label>
            <select id="aut-procedimento" style="width:100%;padding:.4rem .65rem;border:1px solid rgba(99,179,237,.25);border-radius:6px;background:var(--bg2);color:var(--text);font-size:.9rem;">
              <option value="">— Selecione —</option>
            </select>
          </div>
          <div class="form-group" style="min-width:160px;">
            <label>Data do Agendamento <span style="color:var(--neon-pink);">*</span></label>
            <input type="date" id="aut-data" style="width:100%;">
          </div>
          <?php if (temPerm('autorizar_exames')): ?>
          <div class="form-group" style="min-width:150px;">
            <label>Status</label>
            <select id="aut-status" style="width:100%;padding:.4rem .65rem;border:1px solid rgba(99,179,237,.25);border-radius:6px;background:var(--bg2);color:var(--text);font-size:.9rem;">
              <option value="pendente">Pendente</option>
              <option value="analise">Em Análise</option>
              <option value="autorizado">Autorizado</option>
              <option value="negado">Negado</option>
            </select>
          </div>
          <div class="form-group" style="min-width:160px;">
            <label style="color:var(--neon-green);"><i class="fas fa-calendar-check"></i> Data de Autorização</label>
            <input type="date" id="aut-data-autorizacao" style="width:100%;">
          </div>
          <?php else: ?>
          <input type="hidden" id="aut-status" value="pendente">
          <input type="hidden" id="aut-data-autorizacao" value="">
          <?php endif; ?>
        </div>
        <div class="form-inline-row">
          <div class="form-group" style="flex:1;">
            <label>Pedido Médico <small style="color:var(--text-muted);font-weight:400;">(PDF, JPG ou PNG — máx. 10 MB)</small></label>
            <input type="file" id="aut-arquivo" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple
              style="width:100%;padding:.35rem .65rem;border:1px solid rgba(99,179,237,.25);border-radius:6px;background:var(--bg2);color:var(--text);font-size:.9rem;">
            <div id="aut-arquivo-atual" style="display:none;margin-top:.35rem;font-size:.8rem;color:var(--text-muted);"></div>
          </div>
          <div class="form-group" style="flex:2;">
            <label>Observação</label>
            <input type="text" id="aut-observacao" placeholder="Opcional" style="width:100%;">
          </div>
        </div>
        <?php if (temPerm('autorizar_exames')): ?>
        <div class="form-inline-row" id="aut-wrap-negacao" style="display:none;">
          <div class="form-group" style="flex:1;">
            <label style="color:var(--neon-pink);"><i class="fas fa-ban"></i> Motivo da Negação</label>
            <textarea id="aut-motivo-negacao" rows="2" placeholder="Descreva o motivo pelo qual a autorização foi negada…"
              style="width:100%;padding:.4rem .65rem;border:1px solid rgba(246,135,179,.4);border-radius:6px;background:var(--bg2);color:var(--text);font-size:.9rem;resize:vertical;"></textarea>
          </div>
        </div>
        <div class="form-inline-row" id="aut-wrap-analise" style="display:none;">
          <div class="form-group" style="flex:1;">
            <label style="color:var(--neon-purple);"><i class="fas fa-search"></i> Justificativa da Análise</label>
            <textarea id="aut-motivo-analise" rows="2" placeholder="Descreva o motivo pelo qual o pedido está em análise…"
              style="width:100%;padding:.4rem .65rem;border:1px solid rgba(183,148,244,.4);border-radius:6px;background:var(--bg2);color:var(--text);font-size:.9rem;resize:vertical;"></textarea>
          </div>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:.4rem;margin-top:.5rem;">
          <button class="btn-app suc" onclick="salvarAutorizacao()">
            <i class="fas fa-save"></i> <span id="aut-btn-label">Cadastrar</span>
          </button>
          <button class="btn-app sm" id="aut-btn-cancelar" style="display:none;border-color:var(--text-muted);color:var(--text-muted);" onclick="cancelarEdicaoAutorizacao()">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </div>

      <!-- Tabela -->
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr>
              <th>Paciente</th><th>CPF</th><th>Telefone</th>
              <th>Convênio</th><th>Procedimento</th><th>Agendamento</th>
              <th>Status</th><th>Dt. Autorização</th><th>Operador</th><th></th>
            </tr>
          </thead>
          <tbody id="tbody-autorizacoes">
            <tr><td colspan="8" style="color:var(--text-muted);">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ABA: CONVÊNIOS -->
  <section id="tab-convenios" class="tab-section" <?php if(!temPerm('convenios')) echo 'style="display:none"'; ?>>
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-handshake"></i> Cadastro de Convênios</div>
      <div class="form-inline-row" style="margin-bottom:1rem;">
        <div class="form-group" style="flex:1;">
          <label>Nome do Convênio</label>
          <input type="text" id="conv-nome" placeholder="Ex: Unimed, Bradesco Saúde…" style="width:100%;">
        </div>
        <button class="btn-app prim" style="align-self:flex-end;" onclick="salvarConvenio()">
          <i class="fas fa-plus"></i> <span id="conv-btn-label">Cadastrar</span>
        </button>
        <button class="btn-app sm" id="conv-btn-cancelar" style="display:none;align-self:flex-end;border-color:var(--text-muted);color:var(--text-muted);" onclick="cancelarEdicaoConvenio()">
          <i class="fas fa-times"></i> Cancelar
        </button>
      </div>
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>#</th><th>Nome</th><th>Status</th><th></th></tr>
          </thead>
          <tbody id="tbody-convenios">
            <tr><td colspan="4" style="color:var(--text-muted);">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ABA: PROCEDIMENTOS -->
  <section id="tab-procedimentos" class="tab-section" <?php if(!temPerm('procedimentos')) echo 'style="display:none"'; ?>>
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-stethoscope"></i> Cadastro de Procedimentos (Exames)</div>
      <div class="form-inline-row" style="margin-bottom:1rem;">
        <div class="form-group" style="flex:1;">
          <label>Nome do Procedimento/Exame</label>
          <input type="text" id="proc-nome" placeholder="Ex: Raio-X Tórax, Ecocardiograma…" style="width:100%;">
        </div>
        <button class="btn-app prim" style="align-self:flex-end;" onclick="salvarProcedimento()">
          <i class="fas fa-plus"></i> <span id="proc-btn-label">Cadastrar</span>
        </button>
        <button class="btn-app sm" id="proc-btn-cancelar" style="display:none;align-self:flex-end;border-color:var(--text-muted);color:var(--text-muted);" onclick="cancelarEdicaoProcedimento()">
          <i class="fas fa-times"></i> Cancelar
        </button>
      </div>
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>#</th><th>Nome</th><th>Status</th><th></th></tr>
          </thead>
          <tbody id="tbody-procedimentos">
            <tr><td colspan="4" style="color:var(--text-muted);">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

<?php if ($usuarioLogado['perfil'] === 'admin'): ?>
<!-- ══ MODAL PERMISSÕES ═══════════════════════════════════ -->
<div id="modal-permissoes" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:2100;align-items:center;justify-content:center;">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.75rem 2rem;width:100%;max-width:480px;box-shadow:0 8px 40px rgba(0,0,0,.6);">
    <div style="font-size:.88rem;font-weight:700;color:var(--neon-purple);text-transform:uppercase;letter-spacing:.04em;margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:center;">
      <span><i class="fas fa-shield-alt"></i> Permissões — <span id="perm-label-titulo"></span></span>
      <button onclick="fecharModalPermissoes()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem;"><i class="fas fa-times"></i></button>
    </div>
    <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem;">Selecione os módulos que este perfil pode acessar:</p>
    <div id="perm-checkboxes" style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem .75rem;margin-bottom:1.5rem;"></div>
    <div style="display:flex;gap:.5rem;justify-content:flex-end;">
      <button class="btn-app sm" style="border-color:var(--text-muted);color:var(--text-muted);" onclick="fecharModalPermissoes()">Cancelar</button>
      <button class="btn-app sm" style="border-color:var(--neon-purple);color:var(--neon-purple);" onclick="salvarPermissoes()"><i class="fas fa-save"></i> Salvar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($usuarioLogado['perfil'] === 'admin'): ?>
<!-- ══════════════════════════════════════════════════════
     MODAL SENHA
════════════════════════════════════════════════════════ -->
<div id="modal-senha" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2000;align-items:center;justify-content:center;">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.75rem 2rem;width:100%;max-width:360px;box-shadow:0 8px 40px rgba(0,0,0,.6);">
    <div style="font-size:.88rem;font-weight:700;color:var(--neon-cyan);text-transform:uppercase;letter-spacing:.04em;margin-bottom:1.25rem;">
      <i class="fas fa-key"></i> Alterar Senha
    </div>
    <div class="form-group" style="display:flex;flex-direction:column;gap:.3rem;margin-bottom:1rem;">
      <label style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Nova Senha</label>
      <input type="password" id="modal-nova-senha" placeholder="Mínimo 6 caracteres"
             style="padding:.5rem .75rem;border:1px solid rgba(99,179,237,.25);border-radius:6px;background:var(--bg2);color:var(--text);font-size:.95rem;">
    </div>
    <div style="display:flex;gap:.5rem;justify-content:flex-end;">
      <button class="btn-app sm" style="border-color:var(--text-muted);color:var(--text-muted);" onclick="fecharModalSenha()">Cancelar</button>
      <button class="btn-app suc sm" onclick="confirmarSenha()"><i class="fas fa-save"></i> Salvar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══ TOAST ════════════════════════════════════════════════ -->
<div id="toast-container"></div>

<!-- ══ MODAL ALTERAR SENHA ══════════════════════════════════ -->
<div class="modal-overlay" id="modal-perfil-senha">
  <div class="modal-box">
    <button class="modal-close" onclick="fecharModalPerfil()" title="Fechar">&times;</button>
    <h3><i class="fas fa-key" style="color:var(--neon-cyan);margin-right:.5rem;"></i>Alterar Minha Senha</h3>
    <div class="modal-campo">
      <label>Senha Atual</label>
      <input type="password" id="ms-senha-atual" placeholder="Senha atual" autocomplete="current-password">
    </div>
    <div class="modal-campo">
      <label>Nova Senha</label>
      <input type="password" id="ms-nova-senha" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
    </div>
    <div class="modal-campo">
      <label>Confirmar Nova Senha</label>
      <input type="password" id="ms-confirmacao" placeholder="Repita a nova senha" autocomplete="new-password">
    </div>
    <div class="modal-actions">
      <button class="btn-app sm" onclick="fecharModalPerfil()" style="border-color:var(--text-muted);color:var(--text-muted);">Cancelar</button>
      <button class="btn-app prim sm" onclick="salvarNovaSenha()"><i class="fas fa-save"></i> Salvar</button>
    </div>
  </div>
</div>

<!-- ══ SCRIPT ═══════════════════════════════════════════════ -->
<script>
/* ── helpers ─────────────────────────────────────────────── */
function toast(msg, tipo = '') {
  const c = document.getElementById('toast-container');
  const d = document.createElement('div');
  d.className = 'toast-msg ' + tipo;
  d.textContent = msg;
  c.appendChild(d);
  setTimeout(() => d.remove(), 3200);
}

async function api(url, options = {}) {
  const r = await fetch(url, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const j = await r.json().catch(() => ({}));
  if (r.status === 401) { window.location.href = 'login.php'; return; }
  if (!r.ok) throw new Error(j.erro || 'Erro na requisição.');
  return j;
}

function fmtData(d) {
  if (!d) return '—';
  const [y, m, dd] = d.split('-');
  return `${dd}/${m}/${y}`;
}

function semanaAtual() {
  return parseInt(document.getElementById('sel-semana').value) || 0;
}

/* ── Tabs ────────────────────────────────────────────────── */
function showTab(name, btn) {
  document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  else {
    document.querySelectorAll('.tab-btn').forEach(b => {
      if (b.getAttribute('onclick').includes("'" + name + "'")) b.classList.add('active');
    });
  }
  // Fecha sidebar no mobile ao navegar
  if (window.innerWidth <= 768) document.documentElement.setAttribute('data-sb', '');
  // Oculta seletor de semana nas abas que não precisam dele
  const _tabsSemSemana = ['usuarios','autorizacoes','convenios','procedimentos'];
  const sel = document.querySelector('.semana-selector');
  if (sel) sel.style.display = _tabsSemSemana.includes(name) ? 'none' : '';
}

/* ── Seletor de Semana ───────────────────────────────────── */
async function carregarSemanas() {
  const semanas = await api('api/semanas.php');
  const sel     = document.getElementById('sel-semana');
  sel.innerHTML  = '<option value="">— Selecione uma semana —</option>';
  window._semanasCache = semanas;
  semanas.forEach(s => {
    const o = document.createElement('option');
    o.value       = s.id;
    o.textContent = s.descricao || `${fmtData(s.data_inicio)} a ${fmtData(s.data_fim)}`;
    sel.appendChild(o);
  });
  renderTabelaSemanas(semanas);
}

function onSemanaChange() {
  const id = semanaAtual();
  if (!id) return;
  carregarDashboard(id);
  carregarAtendimentos(id);
  carregarPicosList(id);
  carregarFechamentos(id);
  carregarPesquisa(id);
}


/* ════════════════════════════════════════════════════════
   DASHBOARD
════════════════════════════════════════════════════════ */
let chartEvolucao = null, chartPizza = null, chartPicos = null;
let chartMesSemanas = null, chartMesPicos = null;
let chartPesquisa = null, chartPesquisaMes = null;
let viewMode = 'semana';

function setViewMode(mode) {
  viewMode = mode;
  document.getElementById('view-semana').style.display  = mode === 'semana' ? '' : 'none';
  document.getElementById('view-mes').style.display     = mode === 'mes'    ? '' : 'none';
  document.getElementById('mes-selector').style.display = mode === 'mes'    ? 'flex' : 'none';
  document.getElementById('btn-view-semana').style.background = mode === 'semana' ? 'rgba(0,255,255,.15)' : 'transparent';
  document.getElementById('btn-view-semana').style.color      = mode === 'semana' ? '#00ffff' : '';
  document.getElementById('btn-view-semana').style.boxShadow  = mode === 'semana' ? '0 0 10px rgba(0,255,255,.3)' : '';
  document.getElementById('btn-view-mes').style.background    = mode === 'mes'    ? 'rgba(0,255,255,.15)' : 'transparent';
  document.getElementById('btn-view-mes').style.color         = mode === 'mes'    ? '#00ffff' : '';
  document.getElementById('btn-view-mes').style.boxShadow     = mode === 'mes'    ? '0 0 10px rgba(0,255,255,.3)' : '';
}

// Popula select de anos (ano atual -2 até +1)
(function() {
  const sel = document.getElementById('sel-ano');
  const ano = new Date().getFullYear();
  for (let y = ano - 2; y <= ano + 1; y++) {
    const o = document.createElement('option');
    o.value = y; o.textContent = y;
    if (y === ano) o.selected = true;
    sel.appendChild(o);
  }
  // Seleciona mês atual
  document.getElementById('sel-mes').value = new Date().getMonth() + 1;
})();

async function carregarDashboardMes() {
  const ano = parseInt(document.getElementById('sel-ano').value);
  const mes = parseInt(document.getElementById('sel-mes').value);
  try {
    const d = await api(`api/estatisticas_mes.php?ano=${ano}&mes=${mes}`);

    // Gráfico por semana
    const semLabels = (d.por_semana || []).map(s =>
      s.descricao || `${fmtData(s.data_inicio)} a ${fmtData(s.data_fim)}`
    );
    if (chartMesSemanas) chartMesSemanas.destroy();
    chartMesSemanas = new Chart(document.getElementById('chart-mes-semanas'), {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: semLabels,
        datasets: [{
          label: 'Atendidos',
          data:  (d.por_semana || []).map(s => +s.total_atendidos),
          backgroundColor: 'rgba(0,255,255,.6)',
        }],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          datalabels: {
            anchor: 'end', align: 'end',
            color: '#00ffff', font: { weight: 'bold', size: 11 },
            formatter: v => v > 0 ? v : '',
          },
        },
        layout: { padding: { top: 20 } },
      },
    });

    // Gráfico picos mês
    const picosOrd = [...(d.picos || [])].sort((a, b) => (a.hora || '').localeCompare(b.hora || ''));
    if (chartMesPicos) chartMesPicos.destroy();
    chartMesPicos = new Chart(document.getElementById('chart-mes-picos'), {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: picosOrd.map(p => p.hora || '—'),
        datasets: [{
          label: 'Atendimentos',
          data:  picosOrd.map(p => +p.total),
          backgroundColor: 'rgba(0,255,136,.6)',
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
          legend: { display: false },
          datalabels: {
            anchor: 'end', align: 'end',
            color: '#00ff88', font: { weight: 'bold', size: 11 },
            formatter: v => v > 0 ? v : '',
          },
        },
        layout: { padding: { right: 30 } },
      },
    });

    // Motivos mês
    const rf = document.getElementById('resumo-fechamentos-mes');
    if (!(d.fechamentos || []).length) {
      rf.innerHTML = '<span style="color:var(--text-muted);">Nenhum fechamento registrado neste mês.</span>';
    } else {
      const totalGeral = d.fechamentos.reduce((s, f) => s + +f.total, 0);
      rf.innerHTML =
        '<table style="width:100%;max-width:500px;border-collapse:collapse;font-size:.88rem;">' +
          d.fechamentos.map(f => `
            <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
              <td style="padding:.35rem .4rem;">${f.descricao}</td>
              <td style="padding:.35rem .4rem;text-align:right;font-weight:700;color:var(--neon-cyan);">${f.total}</td>
            </tr>`).join('') +
          `<tr style="border-top:1px solid var(--neon-cyan);background:rgba(0,255,255,.05);">
            <td style="padding:.4rem .4rem;font-weight:700;">Total</td>
            <td style="padding:.4rem .4rem;text-align:right;font-weight:700;color:var(--neon-cyan);font-size:1rem;">${totalGeral}</td>
          </tr>` +
        '</table>';
    }

    // Pesquisa de satisfação mês
    renderPesquisaChart('pesquisa-mes-wrap', 'chart-pesquisa-mes', d.pesquisa, chartPesquisaMes, c => chartPesquisaMes = c);
  } catch (e) { toast(e.message, 'erro'); }
}

async function carregarDashboard(sid) {
  try {
    const d = await api('api/estatisticas.php?semana_id=' + sid);

    // Gráfico evolução
    const dias   = d.por_dia.map(r => fmtData(r.data));
    const agend  = d.por_dia.map(r => +r.total_agendados);
    const atend  = d.por_dia.map(r => +r.total_atendidos);
    const canc   = d.por_dia.map(r => +r.total_cancelados);
    const falta  = d.por_dia.map(r => +r.total_faltas);

    if (chartEvolucao) chartEvolucao.destroy();
    chartEvolucao = new Chart(document.getElementById('chart-evolucao'), {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: dias,
        datasets: [
          { label: 'Agendados',  data: agend, backgroundColor: 'rgba(0,255,255,.7)'  },
          { label: 'Atendidos',  data: atend, backgroundColor: 'rgba(0,255,136,.7)'  },
          { label: 'Cancelados', data: canc,  backgroundColor: 'rgba(255,45,120,.7)' },
          { label: 'Faltas',     data: falta, backgroundColor: 'rgba(255,230,0,.7)'  },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          datalabels: {
            anchor: 'end',
            align: 'end',
            color: '#c8d8f0',
            font: { weight: 'bold', size: 11 },
            formatter: v => v > 0 ? v : '',
          },
        },
        layout: { padding: { top: 20 } },
      },
    });

    // Gráfico pizza — Motivos de Fechamento
    if (chartPizza) { chartPizza.destroy(); chartPizza = null; }
    const coresPizza = ['#ff2d78','#ffe600','#00ffff','#00ff88','#b94fff','#ff8c00','#00e5d4','#ff69b4'];
    const pizzaLegenda = document.getElementById('pizza-legenda');
    const cvPizza = document.getElementById('chart-pizza');
    if (!d.fechamentos.length) {
      if (pizzaLegenda) pizzaLegenda.innerHTML = '<p style="color:var(--text-muted);font-size:.88rem;">Nenhum fechamento registrado nesta semana.</p>';
    } else {
      // Define tamanho antes de instanciar — único método confiável com responsive:false
      cvPizza.width  = 130;
      cvPizza.height = 130;
      chartPizza = new Chart(cvPizza, {
        type: 'doughnut',
        plugins: [ChartDataLabels],
        data: {
          labels: d.fechamentos.map(f => f.descricao),
          datasets: [{
            data: d.fechamentos.map(f => +f.total),
            backgroundColor: d.fechamentos.map((_, i) => coresPizza[i % coresPizza.length]),
          }],
        },
        options: {
          responsive: false,
          plugins: {
            legend: { display: false },
            datalabels: {
              color: '#0a0a0f',
              font: { weight: 'bold', size: 10 },
              formatter: v => v > 0 ? v : '',
            },
          },
        },
      });
      if (pizzaLegenda) pizzaLegenda.innerHTML =
        d.fechamentos.map((f, i) => `
          <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.4rem;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:${coresPizza[i % coresPizza.length]};flex-shrink:0;"></span>
            <span>${f.descricao} <strong>(${f.total})</strong></span>
          </div>`).join('');
    }
          </div>`).join('');
    }

    // Label da semana no título do gráfico de picos
    const semAtual = (window._semanasCache || []).find(s => s.id == sid);
    if (semAtual) {
      const periodo = `${fmtData(semAtual.data_inicio)} a ${fmtData(semAtual.data_fim)}`;
      const desc    = semAtual.descricao ? `${semAtual.descricao} (${periodo})` : periodo;
      document.getElementById('pico-semana-label').textContent = desc;
    }

    // Gráfico picos — ordena do menor para o maior
    const picosOrdenados = [...d.picos].sort((a, b) => (a.hora || '').localeCompare(b.hora || ''));
    if (chartPicos) chartPicos.destroy();
    chartPicos = new Chart(document.getElementById('chart-picos'), {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: picosOrdenados.map(p => p.hora || '—'),
        datasets: [{
          label: 'Atendimentos',
          data:  picosOrdenados.map(p => +p.total),
          backgroundColor: 'rgba(185,79,255,.7)',
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
          legend: { display: false },
          datalabels: {
            anchor: 'end',
            align: 'end',
            color: '#b94fff',
            font: { weight: 'bold', size: 12 },
            formatter: v => v > 0 ? v : '',
          },
        },
        layout: { padding: { right: 30 } },
      },
    });

    // Resumo fechamentos
    const rf = document.getElementById('resumo-fechamentos');
    if (!d.fechamentos.length) {
      rf.innerHTML = '<span style="color:var(--text-muted);">Nenhum fechamento registrado nesta semana.</span>';
    } else {
      const totalGeral = d.fechamentos.reduce((s, f) => s + +f.total, 0);
      rf.innerHTML =
        '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">' +
          d.fechamentos.map(f => `
            <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
              <td style="padding:.35rem .4rem;">${f.descricao}</td>
              <td style="padding:.35rem .4rem;text-align:right;font-weight:700;color:var(--neon-cyan);">${f.total}</td>
            </tr>`).join('') +
          `<tr style="border-top:1px solid var(--neon-cyan);background:rgba(0,255,255,.05);">
            <td style="padding:.4rem .4rem;font-weight:700;">Total</td>
            <td style="padding:.4rem .4rem;text-align:right;font-weight:700;color:var(--neon-cyan);font-size:1rem;">${totalGeral}</td>
          </tr>` +
        '</table>';
    }

    // Pesquisa de satisfação semana
    renderPesquisaChart('pesquisa-semana-wrap', 'chart-pesquisa-semana', d.pesquisa, chartPesquisa, c => chartPesquisa = c);
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   PESQUISA DE SATISFAÇÃO
════════════════════════════════════════════════════════ */
const SATISFACAO_LABELS = ['Péssimo', 'Ruim', 'Neutro', 'Bom', 'Excelente'];
const SATISFACAO_KEYS   = ['pessimo', 'ruim', 'neutro', 'bom', 'excelente'];
const SATISFACAO_CORES  = ['#e53e3e','#ed8936','#ecc94b','#48bb78','#4299e1'];

function renderPesquisaChart(wrapId, canvasId, dados, chartRef, setChart) {
  const wrap = document.getElementById(wrapId);
  if (!dados || SATISFACAO_KEYS.every(k => +dados[k] === 0)) {
    if (chartRef) chartRef.destroy();
    wrap.innerHTML = '<p style="color:var(--text-muted);font-size:.88rem;">Nenhuma pesquisa registrada.</p>';
    setChart(null);
    return;
  }
  const total  = SATISFACAO_KEYS.reduce((s, k) => s + +dados[k], 0);
  const values = SATISFACAO_KEYS.map(k => +dados[k]);

  // Tabela resumo + canvas
  wrap.innerHTML = `
    <div style="display:flex;flex-wrap:nowrap;gap:1.25rem;align-items:center;">
      <div style="flex:0 0 160px;max-width:160px;">
        <canvas id="${canvasId}"></canvas>
      </div>
      <div style="flex:1;min-width:0;">
        <table style="width:100%;border-collapse:collapse;font-size:.87rem;">
          ${SATISFACAO_LABELS.map((lb, i) => `
          <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
            <td style="padding:.3rem .4rem;">
              <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:${SATISFACAO_CORES[i]};margin-right:.4rem;flex-shrink:0;"></span>${lb}
            </td>
            <td style="padding:.3rem .4rem;text-align:right;font-weight:700;">${values[i]}</td>
            <td style="padding:.3rem .4rem;text-align:right;color:var(--text-muted);font-size:.78rem;">${total ? ((values[i]/total)*100).toFixed(1)+'%' : '0%'}</td>
          </tr>`).join('')}
          <tr style="border-top:1px solid var(--neon-cyan);background:rgba(99,179,237,.05);">
            <td style="padding:.35rem .4rem;font-weight:700;">Total</td>
            <td style="padding:.35rem .4rem;text-align:right;font-weight:700;color:var(--neon-cyan);" colspan="2">${total}</td>
          </tr>
        </table>
      </div>
    </div>`;

  if (chartRef) chartRef.destroy();
  const novo = new Chart(document.getElementById(canvasId), {
    type: 'doughnut',
    plugins: [ChartDataLabels],
    data: {
      labels: SATISFACAO_LABELS,
      datasets: [{ data: values, backgroundColor: SATISFACAO_CORES, borderWidth: 0 }],
    },
    options: {
      responsive: true,
      cutout: '60%',
      plugins: {
        legend: { display: false },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold', size: 11 },
          formatter: (v, ctx) => {
            const t = ctx.dataset.data.reduce((a, b) => a + b, 0);
            return v > 0 && t > 0 ? ((v/t)*100).toFixed(0)+'%' : '';
          },
        },
      },
    },
  });
  setChart(novo);
}

async function carregarPesquisa(sid) {
  if (!sid) return;
  const wrap = document.getElementById('pesquisa-form-wrap');
  if (!wrap) return;
  try {
    const d = await api('api/pesquisa.php?semana_id=' + sid);
    const vals = d || {};
    wrap.innerHTML = `
      <div style="display:flex;flex-wrap:wrap;gap:1rem;">
        ${SATISFACAO_LABELS.map((lb, i) => `
        <div style="display:flex;flex-direction:column;align-items:center;gap:.4rem;">
          <span style="font-size:.78rem;font-weight:700;color:${SATISFACAO_CORES[i]};text-transform:uppercase;letter-spacing:.04em;">${lb}</span>
          <input type="number" id="pesq-${SATISFACAO_KEYS[i]}" min="0" value="${+vals[SATISFACAO_KEYS[i]] || 0}"
            style="width:90px;padding:.5rem;text-align:center;border:2px solid ${SATISFACAO_CORES[i]}40;
                   border-radius:8px;background:var(--bg2);color:var(--text);font-size:1.1rem;font-weight:700;"
            onfocus="this.style.borderColor='${SATISFACAO_CORES[i]}';"
            onblur="this.style.borderColor='${SATISFACAO_CORES[i]}40';">
        </div>`).join('')}
      </div>`;
  } catch (e) {
    wrap.innerHTML = '<p style="color:var(--text-muted);">Selecione uma semana para registrar a pesquisa.</p>';
  }
}

async function salvarPesquisa() {
  const sid = semanaAtual();
  if (!sid) { toast('Selecione uma semana primeiro.', 'erro'); return; }
  const body = { semana_id: sid };
  SATISFACAO_KEYS.forEach(k => {
    body[k] = parseInt(document.getElementById('pesq-' + k)?.value || 0);
  });
  try {
    await api('api/pesquisa.php', { method: 'POST', body: JSON.stringify(body) });
    toast('Pesquisa salva!', 'suc');
    carregarDashboard(sid);
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   ATENDIMENTOS
════════════════════════════════════════════════════════ */
const DIAS_SEMANA = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'];

function gerarDiasUteis(inicio) {
  const dias = [];
  const d = new Date(inicio + 'T12:00:00');
  for (let i = 0; i < 5; i++) {
    const dd = new Date(d);
    dd.setDate(d.getDate() + i);
    dias.push(dd.toISOString().slice(0, 10));
  }
  return dias;
}

let atendimentosEditaveis = [];

async function carregarAtendimentos(sid) {
  const semanas = window._semanasCache || await api('api/semanas.php');
  const sem     = semanas.find(s => s.id == sid);
  if (!sem) return;

  const dias  = gerarDiasUteis(sem.data_inicio);
  const saved = await api('api/atendimentos.php?semana_id=' + sid);
  const map   = {};
  saved.forEach(r => { map[r.data] = r; });

  atendimentosEditaveis = dias.map((dt, i) => ({
    id:              map[dt]?.id             || null,
    semana_id:       sid,
    data:            dt,
    total_atendidos: map[dt]?.total_atendidos || 0,
    diaNome:         DIAS_SEMANA[i],
  }));

  renderFormAtendimentos();
}

function renderFormAtendimentos() {
  const c = document.getElementById('form-atendimentos');
  if (!atendimentosEditaveis.length) {
    c.innerHTML = '<p style="color:var(--text-muted);">Selecione uma semana primeiro.</p>';
    return;
  }
  c.innerHTML = `
    <div class="table-responsive" style="max-width:360px;">
      <table class="tabela-app">
        <thead>
          <tr>
            <th>Dia</th>
            <th>Data</th>
            <th>Total Atendimentos</th>
          </tr>
        </thead>
        <tbody>
          ${atendimentosEditaveis.map((r, i) => `
            <tr>
              <td><strong>${r.diaNome}</strong></td>
              <td>${fmtData(r.data)}</td>
              <td>
                <input type="number" min="0" class="at-at" data-i="${i}"
                       value="${r.total_atendidos}"
                       style="width:100px;padding:.3rem .5rem;border:1px solid rgba(0,255,255,.25);border-radius:5px;text-align:center;background:var(--bg2);color:var(--text);">
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}

async function salvarAtendimentos() {
  const sid = semanaAtual();
  if (!sid) { toast('Selecione uma semana.', 'erro'); return; }

  const items = atendimentosEditaveis.map((r, i) => ({
    semana_id:       sid,
    data:            r.data,
    total_atendidos: parseInt(document.querySelector(`.at-at[data-i="${i}"]`)?.value) || 0,
  }));

  try {
    await api('api/atendimentos.php', { method: 'POST', body: JSON.stringify({ items }) });
    toast('Atendimentos salvos!', 'suc');
    carregarAtendimentos(sid);
    carregarDashboard(sid);
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   HORÁRIOS DE PICO
════════════════════════════════════════════════════════ */
const HORAS_DIA = Array.from({length: 24}, (_, i) => String(i).padStart(2,'0') + ':00');

async function carregarPicosList(sid) {
  const dados   = await api('api/picos.php?semana_id=' + sid);
  // Monta mapa hora -> total
  const map = {};
  dados.forEach(p => { map[p.hora] = p.total_atendimentos; });

  const wrap = document.getElementById('picos-grade-wrap');
  wrap.innerHTML = `
    <div class="table-responsive" style="max-width:400px;">
      <table class="tabela-app">
        <thead>
          <tr>
            <th style="width:120px;">Hora</th>
            <th>Total Atendimentos</th>
          </tr>
        </thead>
        <tbody>
          ${HORAS_DIA.map(h => `
            <tr>
              <td><strong>${h}</strong></td>
              <td>
                <input type="number" min="0" value="${map[h] ?? 0}"
                  id="pico-h-${h.replace(':','')}"
                  style="width:100px;padding:.3rem .5rem;border:1px solid rgba(0,255,255,.25);border-radius:5px;text-align:center;background:var(--bg2);color:var(--text);">
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}

async function salvarPicos() {
  const sid = semanaAtual();
  if (!sid) { toast('Selecione uma semana.', 'erro'); return; }

  const sem   = (window._semanasCache || []).find(s => s.id == sid);
  const data  = sem ? sem.data_inicio : new Date().toISOString().slice(0,10);

  const items = HORAS_DIA.map(h => ({
    semana_id:          sid,
    data,
    hora:               h,
    total_atendimentos: parseInt(document.getElementById('pico-h-' + h.replace(':',''))?.value) || 0,
  }));

  try {
    await api('api/picos.php', { method: 'POST', body: JSON.stringify({ items }) });
    toast('Horários de pico salvos!', 'suc');
    carregarDashboard(sid);
  } catch (e) { toast(e.message, 'erro'); }
}

async function delPico(id) {
  if (!confirm('Remover?')) return;
  try {
    await api('api/picos.php?id=' + id, { method: 'DELETE' });
    toast('Removido.', 'suc');
    carregarPicosList(semanaAtual());
    carregarDashboard(semanaAtual());
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   FECHAMENTOS
════════════════════════════════════════════════════════ */
async function carregarFechamentos(sid) {
  const dados = await api('api/fechamentos.php?semana_id=' + sid);
  const tb    = document.getElementById('tbody-fechamentos');
  if (!dados.length) {
    tb.innerHTML = '<tr><td colspan="4" style="color:var(--text-muted);">Nenhum fechamento registrado nesta semana.</td></tr>';
    return;
  }
  tb.innerHTML = dados.map(f => `
    <tr>
      <td>${f.motivo}</td>
      <td style="text-align:center;">
        <input type="number" min="1" max="5" value="${f.total}"
               style="width:70px;padding:.25rem .4rem;border:1px solid rgba(0,255,255,.25);border-radius:5px;text-align:center;background:var(--bg2);color:var(--text);"
               onchange="atualizarTotalFechamento(${f.id}, this.value, '${(f.observacao||'').replace(/'/g,"\\'")}')"
        >
      </td>
      <td>${f.observacao || '—'}</td>
      <td><button class="btn-del" onclick="delFechamento(${f.id})"><i class="fas fa-trash"></i></button></td>
    </tr>`).join('');
}

async function salvarFechamento() {
  const sid       = semanaAtual();
  const motivoTxt = document.getElementById('fech-motivo-txt').value.trim();
  const total     = parseInt(document.getElementById('fech-total').value) || 1;
  const obs       = document.getElementById('fech-obs').value.trim();
  if (!sid)       { toast('Selecione uma semana.', 'erro'); return; }
  if (!motivoTxt) { toast('Informe o motivo.', 'erro'); return; }

  try {
    const motivos   = await api('api/motivos.php');
    let motivo_id   = null;
    const existente = motivos.find(m => m.descricao.trim().toLowerCase() === motivoTxt.toLowerCase());

    if (existente) {
      motivo_id = existente.id;
      if (existente.ativo != 1) {
        await api('api/motivos.php', { method: 'PUT', body: JSON.stringify({ id: motivo_id, ativo: 1 }) });
      }
    } else {
      const novo = await api('api/motivos.php', {
        method: 'POST',
        body: JSON.stringify({ descricao: motivoTxt }),
      });
      motivo_id = novo.id;
      await carregarMotivos();
    }

    await api('api/fechamentos.php', {
      method: 'POST',
      body: JSON.stringify({ semana_id: sid, motivo_id, total, observacao: obs }),
    });

    toast('Fechamento salvo!', 'suc');
    document.getElementById('fech-motivo-txt').value = '';
    document.getElementById('fech-total').value      = '1';
    document.getElementById('fech-obs').value        = '';
    carregarFechamentos(sid);
    carregarDashboard(sid);
  } catch (e) { toast(e.message, 'erro'); }
}

async function atualizarTotalFechamento(id, total, obs) {
  const sid = semanaAtual();
  // Busca o motivo_id do registro atual para re-salvar
  try {
    const dados = await api('api/fechamentos.php?semana_id=' + sid);
    const reg   = dados.find(f => f.id == id);
    if (!reg) return;
    await api('api/fechamentos.php', {
      method: 'POST',
      body: JSON.stringify({ semana_id: sid, motivo_id: reg.motivo_id, total: parseInt(total), observacao: obs }),
    });
    toast('Total atualizado!', 'suc');
    carregarDashboard(sid);
  } catch (e) { toast(e.message, 'erro'); }
}

async function delFechamento(id) {
  if (!confirm('Remover?')) return;
  try {
    await api('api/fechamentos.php?id=' + id, { method: 'DELETE' });
    toast('Removido.', 'suc');
    carregarFechamentos(semanaAtual());
    carregarDashboard(semanaAtual());
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   MOTIVOS
════════════════════════════════════════════════════════ */
async function carregarMotivos() {
  const dados = await api('api/motivos.php');

  // Popula datalist do fechamento
  const dl = document.getElementById('list-motivos');
  if (dl) {
    dl.innerHTML = '';
    dados.filter(m => m.ativo == 1).forEach(m => {
      const o = document.createElement('option');
      o.value = m.descricao;
      dl.appendChild(o);
    });
  }

  // Tabela
  const tb = document.getElementById('tbody-motivos');
  if (!dados.length) {
    tb.innerHTML = '<tr><td colspan="4" style="color:var(--text-muted);">Nenhum motivo cadastrado.</td></tr>';
    return;
  }
  tb.innerHTML = dados.map(m => `
    <tr>
      <td>${m.id}</td>
      <td>${m.descricao}</td>
      <td>
        <span class="${m.ativo == 1 ? 'badge-ativo' : 'badge-inativo'}">
          ${m.ativo == 1 ? 'Ativo' : 'Inativo'}
        </span>
      </td>
      <td style="display:flex;gap:.3rem;">
        <button class="btn-app sm peri" onclick="toggleMotivo(${m.id}, ${m.ativo})">
          <i class="fas fa-toggle-${m.ativo == 1 ? 'on' : 'off'}"></i>
        </button>
        <button class="btn-del" onclick="delMotivo(${m.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`).join('');
}

async function salvarMotivo() {
  const desc = document.getElementById('motivo-desc').value.trim();
  if (!desc) { toast('Informe a descrição.', 'erro'); return; }
  try {
    await api('api/motivos.php', { method: 'POST', body: JSON.stringify({ descricao: desc }) });
    document.getElementById('motivo-desc').value = '';
    toast('Motivo cadastrado!', 'suc');
    carregarMotivos();
  } catch (e) { toast(e.message, 'erro'); }
}

async function toggleMotivo(id, ativo) {
  try {
    await api('api/motivos.php', { method: 'PUT', body: JSON.stringify({ id, ativo: ativo == 1 ? 0 : 1 }) });
    carregarMotivos();
  } catch (e) { toast(e.message, 'erro'); }
}

async function delMotivo(id) {
  if (!confirm('Remover motivo?')) return;
  try {
    await api('api/motivos.php?id=' + id, { method: 'DELETE' });
    toast('Removido.', 'suc');
    carregarMotivos();
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   SEMANAS
════════════════════════════════════════════════════════ */
function calcFim() {
  const v = document.getElementById('semana-inicio').value;
  if (!v) return;
  const d = new Date(v + 'T12:00:00');
  // Valida que é segunda-feira
  if (d.getDay() !== 1) {
    toast('Selecione uma segunda-feira.', 'erro');
    document.getElementById('semana-inicio').value = '';
    document.getElementById('semana-fim').value    = '';
    return;
  }
  const fim = new Date(d);
  fim.setDate(d.getDate() + 4);
  document.getElementById('semana-fim').value = fim.toISOString().slice(0, 10);
}

async function salvarSemana() {
  const di   = document.getElementById('semana-inicio').value;
  const df   = document.getElementById('semana-fim').value;
  const desc = document.getElementById('semana-desc').value.trim();
  if (!di || !df) { toast('Selecione a data de início (segunda).', 'erro'); return; }
  try {
    await api('api/semanas.php', {
      method: 'POST',
      body: JSON.stringify({ data_inicio: di, data_fim: df, descricao: desc }),
    });
    toast('Semana cadastrada!', 'suc');
    document.getElementById('semana-inicio').value = '';
    document.getElementById('semana-fim').value    = '';
    document.getElementById('semana-desc').value   = '';
    await carregarSemanas();
  } catch (e) { toast(e.message, 'erro'); }
}

function renderTabelaSemanas(semanas) {
  const tb = document.getElementById('tbody-semanas');
  if (!semanas.length) {
    tb.innerHTML = '<tr><td colspan="4" style="color:var(--text-muted);">Nenhuma semana cadastrada.</td></tr>';
    return;
  }
  tb.innerHTML = semanas.map(s => `
    <tr>
      <td>${fmtData(s.data_inicio)}</td>
      <td>${fmtData(s.data_fim)}</td>
      <td>${s.descricao || '—'}</td>
      <td><button class="btn-del" onclick="delSemana(${s.id})"><i class="fas fa-trash"></i></button></td>
    </tr>`).join('');
}

async function delSemana(id) {
  if (!confirm('Remover semana e todos os dados vinculados?')) return;
  try {
    await api('api/semanas.php?id=' + id, { method: 'DELETE' });
    toast('Semana removida.', 'suc');
    await carregarSemanas();
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   USUÁRIOS
════════════════════════════════════════════════════════ */
let _perfilMap     = {};  // slug -> label
let _perfisCache   = [];
let _usuariosCache = [];
let _usuarioEditId = null;
let _senhaAlterarId = null;
let _perfilEditId  = null;

async function carregarPerfis() {
  try {
    const perfis = await api('api/perfis.php');
    if (!perfis) return;
    _perfisCache = perfis;
    // Mapa slug -> label
    _perfilMap = {};
    perfis.forEach(p => { _perfilMap[p.slug] = p.label; });

    // Atualiza select de usuários
    const sel = document.getElementById('usu-perfil');
    if (sel) {
      const cur = sel.value;
      sel.innerHTML = perfis.filter(p => +p.ativo).map(p =>
        `<option value="${p.slug}">${p.label}</option>`
      ).join('');
      if (cur) sel.value = cur;
    }

    // Tabela de perfis
    const tb = document.getElementById('tbody-perfis');
    if (!tb) return;
    if (!perfis.length) {
      tb.innerHTML = '<tr><td colspan="5" style="color:var(--text-muted);">Nenhum perfil cadastrado.</td></tr>';
      return;
    }
    const padrao = ['admin','operador','visualizador'];
    tb.innerHTML = perfis.map(p => `
      <tr>
        <td><code style="background:rgba(99,179,237,.1);color:var(--neon-cyan);padding:.15rem .45rem;border-radius:4px;font-size:.82rem;">${p.slug}</code></td>
        <td style="font-weight:600;">${p.label}</td>
        <td style="color:var(--text-muted);font-size:.85rem;">${p.descricao || '—'}</td>
        <td>${+p.ativo ? '<span class="badge-ativo">Ativo</span>' : '<span class="badge-inativo">Inativo</span>'}</td>
        <td style="white-space:nowrap;display:flex;gap:.35rem;">
          <button class="btn-app prim sm" title="Editar" onclick="editarPerfil(${p.id})"><i class="fas fa-edit"></i></button>
          ${p.slug !== 'admin' ? `<button class="btn-app sm" style="border-color:var(--neon-purple);color:var(--neon-purple);" title="Permissões" onclick="abrirPermissoes('${p.slug}','${p.label}')"><i class="fas fa-shield-alt"></i></button>` : ''}
          ${!padrao.includes(p.slug) ? `
            <button class="btn-app sm" style="border-color:var(--text-muted);color:var(--text-muted);" title="${+p.ativo ? 'Desativar' : 'Ativar'}" onclick="toggleAtivoPerfil(${p.id},${+p.ativo?0:1})"><i class="fas fa-${+p.ativo?'ban':'check'}"></i></button>
            <button class="btn-del" title="Excluir" onclick="delPerfil(${p.id})"><i class="fas fa-trash"></i></button>
          ` : '<span style="color:var(--text-muted);font-size:.75rem;padding:.2rem .4rem;">padrão</span>'}
        </td>
      </tr>`).join('');
  } catch(e) { toast(e.message, 'erro'); }
}

function editarPerfil(id) {
  const p = _perfisCache.find(x => +x.id === +id);
  if (!p) return;
  _perfilEditId = id;
  document.getElementById('perf-slug').value      = p.slug;
  document.getElementById('perf-slug').disabled   = true;
  document.getElementById('perf-label').value     = p.label;
  document.getElementById('perf-descricao').value = p.descricao || '';
  document.getElementById('perf-form-titulo').textContent = 'Editar Perfil';
  document.getElementById('perf-btn-label').textContent   = 'Salvar';
  document.getElementById('perf-btn-cancelar').style.display = '';
  document.getElementById('perf-label').focus();
}

function cancelarEdicaoPerfil() {
  _perfilEditId = null;
  document.getElementById('perf-slug').value      = '';
  document.getElementById('perf-slug').disabled   = false;
  document.getElementById('perf-label').value     = '';
  document.getElementById('perf-descricao').value = '';
  document.getElementById('perf-form-titulo').textContent = 'Novo Perfil';
  document.getElementById('perf-btn-label').textContent   = 'Cadastrar';
  document.getElementById('perf-btn-cancelar').style.display = 'none';
}

async function salvarPerfil() {
  const slug      = document.getElementById('perf-slug').value.trim();
  const label     = document.getElementById('perf-label').value.trim();
  const descricao = document.getElementById('perf-descricao').value.trim();
  if (!label) { toast('Label é obrigatório.', 'erro'); return; }
  if (!_perfilEditId && !slug) { toast('Slug é obrigatório.', 'erro'); return; }
  try {
    if (_perfilEditId) {
      await api('api/perfis.php?id=' + _perfilEditId, { method: 'PUT', body: JSON.stringify({ label, descricao }) });
      toast('Perfil atualizado!', 'suc');
    } else {
      await api('api/perfis.php', { method: 'POST', body: JSON.stringify({ slug, label, descricao }) });
      toast('Perfil criado!', 'suc');
    }
    cancelarEdicaoPerfil();
    await carregarPerfis();
  } catch(e) { toast(e.message, 'erro'); }
}

async function toggleAtivoPerfil(id, novoAtivo) {
  try {
    await api('api/perfis.php?id=' + id, { method: 'PUT', body: JSON.stringify({ ativo: novoAtivo }) });
    toast(novoAtivo ? 'Perfil ativado.' : 'Perfil desativado.', 'suc');
    await carregarPerfis();
  } catch(e) { toast(e.message, 'erro'); }
}

async function delPerfil(id) {
  if (!confirm('Excluir este perfil?')) return;
  try {
    await api('api/perfis.php?id=' + id, { method: 'DELETE' });
    toast('Perfil excluído.', 'suc');
    await carregarPerfis();
  } catch(e) { toast(e.message, 'erro'); }
}

/* ── Permissões por Perfil ────────────────────────────────── */
const MODULOS_LABELS = {
  dashboard:        'Dashboard',
  atendimentos:     'Atendimentos',
  picos:            'Horários de Pico',
  fechamentos:      'Fechamentos',
  motivos:          'Motivos',
  semanas:          'Semanas',
  pesquisa:         'Pesquisa de Satisfação',
  autorizacoes:     'Autorizações de Exames',
  convenios:        'Convênios',
  procedimentos:    'Procedimentos',
  autorizar_exames: 'Autorizar Exames (alterar status)',
};
let _permSlug = '';

async function abrirPermissoes(slug, label) {
  _permSlug = slug;
  document.getElementById('perm-label-titulo').textContent = label;
  const el = document.getElementById('modal-permissoes');
  el.style.display = 'flex';

  const box = document.getElementById('perm-checkboxes');
  box.innerHTML = '<span style="color:var(--text-muted);font-size:.85rem;">Carregando…</span>';

  try {
    const data = await api('api/permissoes.php?slug=' + encodeURIComponent(slug));
    const ativos = Array.isArray(data) ? data : [];
    box.innerHTML = Object.entries(MODULOS_LABELS).map(([mod, lbl]) => `
      <label style="display:flex;align-items:center;gap:.45rem;cursor:pointer;font-size:.88rem;">
        <input type="checkbox" value="${mod}" ${ativos.includes(mod) ? 'checked' : ''}
          style="width:15px;height:15px;accent-color:var(--neon-purple);">
        ${lbl}
      </label>`).join('');
  } catch(e) {
    box.innerHTML = `<span style="color:var(--neon-pink);">${e.message}</span>`;
  }
}

function fecharModalPermissoes() {
  document.getElementById('modal-permissoes').style.display = 'none';
  _permSlug = '';
}

async function salvarPermissoes() {
  const checks = document.querySelectorAll('#perm-checkboxes input[type=checkbox]:checked');
  const modulos = Array.from(checks).map(c => c.value);
  try {
    await api('api/permissoes.php', {
      method: 'POST',
      body: JSON.stringify({ slug: _permSlug, modulos }),
    });
    fecharModalPermissoes();
    toast('Permissões salvas. Recarregando…', 'suc');
    // Recarrega a página para que o PHP re-renderize as abas com as novas permissões
    setTimeout(() => window.location.reload(), 900);
  } catch(e) { toast(e.message, 'erro'); }
}

async function carregarUsuarios() {
  try {
    const users = await api('api/usuarios.php');
    if (!users) return;
    _usuariosCache = users;
    const tb = document.getElementById('tbody-usuarios');
    if (!tb) return;
    if (!users.length) {
      tb.innerHTML = '<tr><td colspan="6" style="color:var(--text-muted);">Nenhum usuário cadastrado.</td></tr>';
      return;
    }
    tb.innerHTML = users.map(u => `
      <tr>
        <td>${u.nome}</td>
        <td style="color:var(--text-muted);">${u.email}</td>
        <td>${_perfilMap[u.perfil] || u.perfil}</td>
        <td>${+u.ativo ? '<span class="badge-ativo">Ativo</span>' : '<span class="badge-inativo">Inativo</span>'}</td>
        <td style="color:var(--text-muted);font-size:.82rem;">${u.ultimo_acesso || '—'}</td>
        <td style="white-space:nowrap;display:flex;gap:.35rem;">
          <button class="btn-app prim sm" title="Editar" onclick="editarUsuario(${u.id})"><i class="fas fa-edit"></i></button>
          <button class="btn-app peri sm" title="Alterar senha" onclick="abrirModalSenha(${u.id})"><i class="fas fa-key"></i></button>
          <button class="btn-app sm" style="border-color:var(--text-muted);color:var(--text-muted);" title="${+u.ativo ? 'Desativar' : 'Ativar'}"
            onclick="toggleAtivoUsuario(${u.id}, ${+u.ativo ? 0 : 1})">
            <i class="fas fa-${+u.ativo ? 'ban' : 'check'}"></i>
          </button>
          ${u.id != USUARIO_LOGADO.id ? `<button class="btn-del" title="Excluir" onclick="delUsuario(${u.id})"><i class="fas fa-trash"></i></button>` : ''}
        </td>
      </tr>`).join('');
  } catch(e) { toast(e.message, 'erro'); }
}

function editarUsuario(id) {
  const u = _usuariosCache.find(x => +x.id === +id);
  if (!u) return;
  _usuarioEditId = id;
  document.getElementById('usu-nome').value  = u.nome;
  document.getElementById('usu-email').value = u.email;
  document.getElementById('usu-perfil').value = u.perfil;
  document.getElementById('usu-senha').value  = '';
  document.getElementById('usu-form-titulo').textContent  = 'Editar Usuário';
  document.getElementById('usu-btn-label').textContent    = 'Salvar';
  document.getElementById('usu-btn-cancelar').style.display = '';
  document.getElementById('usu-senha-hint').textContent   = '(deixe em branco para não alterar)';
  document.getElementById('usu-nome').focus();
}

function cancelarEdicaoUsuario() {
  _usuarioEditId = null;
  document.getElementById('usu-nome').value  = '';
  document.getElementById('usu-email').value = '';
  document.getElementById('usu-perfil').value = 'operador';
  document.getElementById('usu-senha').value  = '';
  document.getElementById('usu-form-titulo').textContent  = 'Novo Usuário';
  document.getElementById('usu-btn-label').textContent    = 'Cadastrar';
  document.getElementById('usu-btn-cancelar').style.display = 'none';
  document.getElementById('usu-senha-hint').textContent   = '(obrigatória)';
}

async function salvarUsuario() {
  const nome   = document.getElementById('usu-nome').value.trim();
  const email  = document.getElementById('usu-email').value.trim();
  const perfil = document.getElementById('usu-perfil').value;
  const senha  = document.getElementById('usu-senha').value;

  if (!nome || !email) { toast('Nome e e-mail são obrigatórios.', 'erro'); return; }
  if (!_usuarioEditId && !senha) { toast('Informe a senha para o novo usuário.', 'erro'); return; }

  try {
    const body = { nome, email, perfil };
    if (senha) body.senha = senha;

    if (_usuarioEditId) {
      await api('api/usuarios.php?id=' + _usuarioEditId, { method: 'PUT', body: JSON.stringify(body) });
      toast('Usuário atualizado!', 'suc');
    } else {
      await api('api/usuarios.php', { method: 'POST', body: JSON.stringify(body) });
      toast('Usuário criado!', 'suc');
    }
    cancelarEdicaoUsuario();
    await carregarUsuarios();
  } catch(e) { toast(e.message, 'erro'); }
}

async function toggleAtivoUsuario(id, novoAtivo) {
  const u = _usuariosCache.find(x => +x.id === +id);
  if (!u) return;
  try {
    await api('api/usuarios.php?id=' + id, {
      method: 'PUT',
      body: JSON.stringify({ nome: u.nome, email: u.email, perfil: u.perfil, ativo: novoAtivo }),
    });
    toast(novoAtivo ? 'Usuário ativado.' : 'Usuário desativado.', 'suc');
    await carregarUsuarios();
  } catch(e) { toast(e.message, 'erro'); }
}

async function delUsuario(id) {
  if (!confirm('Excluir este usuário permanentemente?')) return;
  try {
    await api('api/usuarios.php?id=' + id, { method: 'DELETE' });
    toast('Usuário excluído.', 'suc');
    await carregarUsuarios();
  } catch(e) { toast(e.message, 'erro'); }
}

function abrirModalSenha(id) {
  _senhaAlterarId = id;
  document.getElementById('modal-nova-senha').value = '';
  const m = document.getElementById('modal-senha');
  m.style.display = 'flex';
  document.getElementById('modal-nova-senha').focus();
}
function fecharModalSenha() {
  document.getElementById('modal-senha').style.display = 'none';
  _senhaAlterarId = null;
}
async function confirmarSenha() {
  const nova = document.getElementById('modal-nova-senha').value;
  if (!nova || nova.length < 6) { toast('Senha deve ter no mínimo 6 caracteres.', 'erro'); return; }
  try {
    await api('api/usuarios.php?id=' + _senhaAlterarId, {
      method: 'PUT',
      body: JSON.stringify({ senha: nova }),
    });
    toast('Senha alterada!', 'suc');
    fecharModalSenha();
  } catch(e) { toast(e.message, 'erro'); }
}
// Fechar modal clicando fora
document.getElementById('modal-senha')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) fecharModalSenha();
});

// Show/hide campos de justificativa conforme status selecionado
document.getElementById('aut-status')?.addEventListener('change', function() {
  const wrapNeg = document.getElementById('aut-wrap-negacao');
  const wrapAna = document.getElementById('aut-wrap-analise');
  if (wrapNeg) wrapNeg.style.display = this.value === 'negado'  ? '' : 'none';
  if (wrapAna) wrapAna.style.display = this.value === 'analise' ? '' : 'none';
});

/* ── Init ────────────────────────────────────────────────── */
(async () => {
  // Ativa a primeira aba que o usuário tem permissão
  const _ordemAbas = ['dashboard','atendimentos','picos','fechamentos','motivos','semanas','pesquisa','usuarios','autorizacoes','convenios','procedimentos'];
  const _primeiraAba = _ordemAbas.find(t => PERMISSOES.includes(t));
  if (_primeiraAba) showTab(_primeiraAba, null);

  await carregarSemanas();
  await carregarMotivos();
  if (USUARIO_LOGADO.perfil === 'admin') {
    await carregarPerfis();
    await carregarUsuarios();
  }
  if (PERMISSOES.includes('autorizacoes') || PERMISSOES.includes('convenios') || PERMISSOES.includes('procedimentos')) {
    await carregarConvenios();
    await carregarProcedimentos();
  }
  if (PERMISSOES.includes('autorizacoes')) {
    await carregarAutorizacoes();
  }
})();

/* ══════════════════════════════════════════════════════════
   MÓDULO: CONVÊNIOS
══════════════════════════════════════════════════════════ */
let _conveniosCache = [];
let _convenioEditId  = null;

async function carregarConvenios() {
  try {
    const lista = await api('api/convenios.php');
    if (!lista) return;
    _conveniosCache = lista;

    // Preenche selects de autorizações
    const selConv = document.getElementById('aut-convenio');
    if (selConv) {
      const cur = selConv.value;
      selConv.innerHTML = '<option value="">— Selecione —</option>' +
        lista.filter(c => +c.ativo).map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
      if (cur) selConv.value = cur;
    }

    const tb = document.getElementById('tbody-convenios');
    if (!tb) return;
    if (!lista.length) {
      tb.innerHTML = '<tr><td colspan="4" style="color:var(--text-muted);">Nenhum convênio cadastrado.</td></tr>';
      return;
    }
    tb.innerHTML = lista.map(c => `
      <tr>
        <td>${c.id}</td>
        <td style="font-weight:600;">${c.nome}</td>
        <td>${+c.ativo ? '<span class="badge-ativo">Ativo</span>' : '<span class="badge-inativo">Inativo</span>'}</td>
        <td style="white-space:nowrap;display:flex;gap:.35rem;">
          <button class="btn-app prim sm" title="Editar" onclick="editarConvenio(${c.id})"><i class="fas fa-edit"></i></button>
          <button class="btn-app sm" style="border-color:var(--text-muted);color:var(--text-muted);" title="${+c.ativo?'Desativar':'Ativar'}" onclick="toggleAtivoConvenio(${c.id},${+c.ativo?0:1})">
            <i class="fas fa-${+c.ativo?'ban':'check'}"></i>
          </button>
          <button class="btn-del" title="Excluir" onclick="delConvenio(${c.id})"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('');
  } catch(e) { toast(e.message, 'erro'); }
}

async function salvarConvenio() {
  const nome = document.getElementById('conv-nome').value.trim();
  if (!nome) { toast('Informe o nome do convênio.', 'erro'); return; }
  try {
    if (_convenioEditId) {
      await api('api/convenios.php?id=' + _convenioEditId, { method: 'PUT', body: JSON.stringify({ nome }) });
      toast('Convênio atualizado.', 'suc');
    } else {
      await api('api/convenios.php', { method: 'POST', body: JSON.stringify({ nome }) });
      toast('Convênio criado.', 'suc');
    }
    cancelarEdicaoConvenio();
    await carregarConvenios();
  } catch(e) { toast(e.message, 'erro'); }
}

function editarConvenio(id) {
  const c = _conveniosCache.find(x => +x.id === +id);
  if (!c) return;
  _convenioEditId = id;
  document.getElementById('conv-nome').value = c.nome;
  document.getElementById('conv-btn-label').textContent = 'Salvar';
  document.getElementById('conv-btn-cancelar').style.display = '';
}

function cancelarEdicaoConvenio() {
  _convenioEditId = null;
  document.getElementById('conv-nome').value = '';
  document.getElementById('conv-btn-label').textContent = 'Cadastrar';
  document.getElementById('conv-btn-cancelar').style.display = 'none';
}

async function toggleAtivoConvenio(id, novoAtivo) {
  try {
    await api('api/convenios.php?id=' + id, { method: 'PUT', body: JSON.stringify({ ativo: novoAtivo }) });
    await carregarConvenios();
  } catch(e) { toast(e.message, 'erro'); }
}

async function delConvenio(id) {
  if (!confirm('Excluir este convênio?')) return;
  try {
    await api('api/convenios.php?id=' + id, { method: 'DELETE' });
    toast('Convênio excluído.', 'suc');
    await carregarConvenios();
  } catch(e) { toast(e.message, 'erro'); }
}

/* ══════════════════════════════════════════════════════════
   MÓDULO: PROCEDIMENTOS
══════════════════════════════════════════════════════════ */
let _procedimentosCache = [];
let _procedimentoEditId  = null;

async function carregarProcedimentos() {
  try {
    const lista = await api('api/procedimentos.php');
    if (!lista) return;
    _procedimentosCache = lista;

    // Preenche selects de autorizações
    const selProc = document.getElementById('aut-procedimento');
    if (selProc) {
      const cur = selProc.value;
      selProc.innerHTML = '<option value="">— Selecione —</option>' +
        lista.filter(p => +p.ativo).map(p => `<option value="${p.id}">${p.nome}</option>`).join('');
      if (cur) selProc.value = cur;
    }

    const tb = document.getElementById('tbody-procedimentos');
    if (!tb) return;
    if (!lista.length) {
      tb.innerHTML = '<tr><td colspan="4" style="color:var(--text-muted);">Nenhum procedimento cadastrado.</td></tr>';
      return;
    }
    tb.innerHTML = lista.map(p => `
      <tr>
        <td>${p.id}</td>
        <td style="font-weight:600;">${p.nome}</td>
        <td>${+p.ativo ? '<span class="badge-ativo">Ativo</span>' : '<span class="badge-inativo">Inativo</span>'}</td>
        <td style="white-space:nowrap;display:flex;gap:.35rem;">
          <button class="btn-app prim sm" title="Editar" onclick="editarProcedimento(${p.id})"><i class="fas fa-edit"></i></button>
          <button class="btn-app sm" style="border-color:var(--text-muted);color:var(--text-muted);" title="${+p.ativo?'Desativar':'Ativar'}" onclick="toggleAtivoProcedimento(${p.id},${+p.ativo?0:1})">
            <i class="fas fa-${+p.ativo?'ban':'check'}"></i>
          </button>
          <button class="btn-del" title="Excluir" onclick="delProcedimento(${p.id})"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('');
  } catch(e) { toast(e.message, 'erro'); }
}

async function salvarProcedimento() {
  const nome = document.getElementById('proc-nome').value.trim();
  if (!nome) { toast('Informe o nome do procedimento.', 'erro'); return; }
  try {
    if (_procedimentoEditId) {
      await api('api/procedimentos.php?id=' + _procedimentoEditId, { method: 'PUT', body: JSON.stringify({ nome }) });
      toast('Procedimento atualizado.', 'suc');
    } else {
      await api('api/procedimentos.php', { method: 'POST', body: JSON.stringify({ nome }) });
      toast('Procedimento criado.', 'suc');
    }
    cancelarEdicaoProcedimento();
    await carregarProcedimentos();
  } catch(e) { toast(e.message, 'erro'); }
}

function editarProcedimento(id) {
  const p = _procedimentosCache.find(x => +x.id === +id);
  if (!p) return;
  _procedimentoEditId = id;
  document.getElementById('proc-nome').value = p.nome;
  document.getElementById('proc-btn-label').textContent = 'Salvar';
  document.getElementById('proc-btn-cancelar').style.display = '';
}

function cancelarEdicaoProcedimento() {
  _procedimentoEditId = null;
  document.getElementById('proc-nome').value = '';
  document.getElementById('proc-btn-label').textContent = 'Cadastrar';
  document.getElementById('proc-btn-cancelar').style.display = 'none';
}

async function toggleAtivoProcedimento(id, novoAtivo) {
  try {
    await api('api/procedimentos.php?id=' + id, { method: 'PUT', body: JSON.stringify({ ativo: novoAtivo }) });
    await carregarProcedimentos();
  } catch(e) { toast(e.message, 'erro'); }
}

async function delProcedimento(id) {
  if (!confirm('Excluir este procedimento?')) return;
  try {
    await api('api/procedimentos.php?id=' + id, { method: 'DELETE' });
    toast('Procedimento excluído.', 'suc');
    await carregarProcedimentos();
  } catch(e) { toast(e.message, 'erro'); }
}

/* ══════════════════════════════════════════════════════════
   MÓDULO: AUTORIZAÇÕES DE EXAMES
══════════════════════════════════════════════════════════ */
let _autorizacoesCache = [];
let _autorizacaoEditId  = null;
let _autArquivosAtuais  = []; // filenames mantidos durante edição

function renderArquivosAtuais() {
  const wrap = document.getElementById('aut-arquivo-atual');
  if (!wrap) return;
  if (_autArquivosAtuais.length === 0) { wrap.style.display = 'none'; wrap.innerHTML = ''; return; }
  wrap.style.display = '';
  wrap.innerHTML = '<i class="fas fa-paperclip"></i> <strong style="margin-right:.3rem;">Existentes:</strong>' +
    _autArquivosAtuais.map((f, i) =>
      `<span style="display:inline-flex;align-items:center;gap:.2rem;margin-right:.5rem;">` +
      `<a href="uploads/pedidos/${encodeURIComponent(f)}" target="_blank" style="color:var(--neon-cyan);">Arquivo ${i+1}</a>` +
      `<button type="button" onclick="removerArquivoAtual(${i})" ` +
      `style="background:none;border:none;color:var(--neon-pink);cursor:pointer;font-size:1rem;line-height:1;padding:0 .1rem;" title="Remover">&times;</button>` +
      `</span>`
    ).join('');
}

function removerArquivoAtual(idx) {
  _autArquivosAtuais.splice(idx, 1);
  renderArquivosAtuais();
}

const STATUS_BADGE = {
  pendente:   '<span style="background:rgba(246,224,94,.15);color:#f6e05e;border:1px solid rgba(246,224,94,.3);border-radius:4px;padding:.1rem .45rem;font-size:.75rem;font-weight:700;">Pendente</span>',
  analise:    '<span style="background:rgba(183,148,244,.15);color:#b794f4;border:1px solid rgba(183,148,244,.3);border-radius:4px;padding:.1rem .45rem;font-size:.75rem;font-weight:700;">Em Análise</span>',
  autorizado: '<span style="background:rgba(104,211,145,.15);color:#68d391;border:1px solid rgba(104,211,145,.3);border-radius:4px;padding:.1rem .45rem;font-size:.75rem;font-weight:700;">Autorizado</span>',
  negado:     '<span style="background:rgba(246,135,179,.15);color:#f687b3;border:1px solid rgba(246,135,179,.3);border-radius:4px;padding:.1rem .45rem;font-size:.75rem;font-weight:700;">Negado</span>',
};

async function carregarAutorizacoes() {
  try {
    const lista = await api('api/autorizacoes.php');
    if (!lista) return;
    _autorizacoesCache = lista;
    const tb = document.getElementById('tbody-autorizacoes');
    if (!tb) return;
    if (!lista.length) {
      tb.innerHTML = '<tr><td colspan="8" style="color:var(--text-muted);">Nenhuma autorização cadastrada.</td></tr>';
      return;
    }
    tb.innerHTML = lista.map(a => `
      <tr>
        <td style="font-weight:600;">${a.paciente_nome}</td>
        <td style="font-size:.82rem;">${a.paciente_cpf || '—'}</td>
        <td style="font-size:.82rem;">${a.paciente_telefone || '—'}</td>
        <td>${a.convenio_nome}</td>
        <td>${a.procedimento_nome}</td>
        <td style="font-size:.85rem;">${a.data_agendamento}</td>
        <td>${STATUS_BADGE[a.status] || a.status}${
          a.status === 'negado' && a.motivo_negacao
            ? `<br><small style="color:var(--neon-pink);font-size:.75rem;" title="${a.motivo_negacao.replace(/"/g,'&quot;')}">ℹ️ ${a.motivo_negacao.length > 40 ? a.motivo_negacao.substring(0,40)+'…' : a.motivo_negacao}</small>`
            : ''
        }${
          a.status === 'analise' && a.motivo_analise
            ? `<br><small style="color:var(--neon-purple);font-size:.75rem;" title="${a.motivo_analise.replace(/"/g,'&quot;')}">ℹ️ ${a.motivo_analise.length > 40 ? a.motivo_analise.substring(0,40)+'…' : a.motivo_analise}</small>`
            : ''
        }</td>
        <td style="font-size:.82rem;white-space:nowrap;">
          <span style="color:var(--neon-green);">${a.data_autorizacao || '—'}</span>${a.autorizado_por_nome ? `<br><small style="color:var(--text-muted);">${a.autorizado_por_nome}</small>` : ''}
        </td>
        <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;">${a.criado_por_nome || '—'}</td>
        <td style="white-space:nowrap;display:flex;gap:.35rem;">
          ${a.pedido_arquivo ? (() => {
            let arqs;
            try { arqs = JSON.parse(a.pedido_arquivo); } catch(e) { arqs = [a.pedido_arquivo]; }
            if (!Array.isArray(arqs)) arqs = [arqs];
            return arqs.map((f,i) => `<a href="uploads/pedidos/${encodeURIComponent(f)}" target="_blank" class="btn-app sm" style="border-color:var(--neon-cyan);color:var(--neon-cyan);text-decoration:none;" title="Ver pedido ${i+1}"><i class="fas fa-file-alt"></i>${arqs.length > 1 ? ' '+(i+1) : ''}</a>`).join('');
          })() : ''}
          <button class="btn-app prim sm" title="Editar" onclick="editarAutorizacao(${a.id})"><i class="fas fa-edit"></i></button>
          <button class="btn-del" title="Excluir" onclick="delAutorizacao(${a.id})"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('');
  } catch(e) { toast(e.message, 'erro'); }
}

async function salvarAutorizacao() {
  const nome   = document.getElementById('aut-paciente-nome').value.trim();
  const cpf    = document.getElementById('aut-cpf').value.trim();
  const tel    = document.getElementById('aut-telefone').value.trim();
  const conv   = document.getElementById('aut-convenio').value;
  const proc   = document.getElementById('aut-procedimento').value;
  const data   = document.getElementById('aut-data').value;
  const status    = document.getElementById('aut-status').value;
  const obs        = document.getElementById('aut-observacao').value.trim();
  const motivoNeg  = document.getElementById('aut-motivo-negacao')?.value.trim() || '';
  const files      = document.getElementById('aut-arquivo').files;

  if (!nome || !conv || !proc || !data) {
    toast('Paciente, convênio, procedimento e data são obrigatórios.', 'erro'); return;
  }

  const fd = new FormData();
  fd.append('paciente_nome',     nome);
  fd.append('paciente_cpf',      cpf);
  fd.append('paciente_telefone', tel);
  fd.append('convenio_id',       conv);
  fd.append('procedimento_id',   proc);
  fd.append('data_agendamento',  data);
  fd.append('status',            status);
  fd.append('observacao',        obs);
  fd.append('motivo_negacao',    status === 'negado'  ? motivoNeg  : '');
  fd.append('motivo_analise',    status === 'analise' ? (document.getElementById('aut-motivo-analise')?.value.trim() || '') : '');
  fd.append('data_autorizacao',  document.getElementById('aut-data-autorizacao')?.value || '');
  for (const f of files) fd.append('pedido_arquivo[]', f);
  for (const f of _autArquivosAtuais) fd.append('arquivos_manter[]', f);

  try {
    let url, method;
    if (_autorizacaoEditId) {
      // PHP não popula $_POST/$_FILES em PUT multipart — usa POST + _method=PUT
      url    = 'api/autorizacoes.php?id=' + _autorizacaoEditId;
      method = 'POST';
      fd.append('_method', 'PUT');
    } else {
      url    = 'api/autorizacoes.php';
      method = 'POST';
    }
    const resp = await fetch(url, { method, body: fd });
    const json = await resp.json().catch(() => ({}));
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    if (!resp.ok) throw new Error(json.erro || 'Erro ao salvar.');
    toast(_autorizacaoEditId ? 'Autorização atualizada.' : 'Autorização criada.', 'suc');
    cancelarEdicaoAutorizacao();
    await carregarAutorizacoes();
  } catch(e) { toast(e.message, 'erro'); }
}

function editarAutorizacao(id) {
  const a = _autorizacoesCache.find(x => +x.id === +id);
  if (!a) return;
  const podeAutorizar = (PERMISSOES || []).includes('autorizar_exames');
  if (!podeAutorizar && a.status === 'autorizado') {
    toast('Registro já autorizado. Apenas o autorizador pode editá-lo.', 'erro');
    return;
  }
  _autorizacaoEditId = id;
  document.getElementById('aut-paciente-nome').value = a.paciente_nome;
  document.getElementById('aut-cpf').value           = a.paciente_cpf || '';
  document.getElementById('aut-telefone').value      = a.paciente_telefone || '';
  document.getElementById('aut-convenio').value      = a.convenio_id;
  document.getElementById('aut-procedimento').value  = a.procedimento_id;
  // data vem como dd/mm/yyyy, converte para yyyy-mm-dd
  const [dd,mm,yy] = (a.data_agendamento || '').split('/');
  document.getElementById('aut-data').value     = yy && mm && dd ? `${yy}-${mm}-${dd}` : '';
  document.getElementById('aut-status').value   = a.status;
  document.getElementById('aut-observacao').value = a.observacao || '';
  const mnEl = document.getElementById('aut-motivo-negacao');
  if (mnEl) {
    mnEl.value = a.motivo_negacao || '';
    const wrap = document.getElementById('aut-wrap-negacao');
    if (wrap) wrap.style.display = a.status === 'negado' ? '' : 'none';
  }
  const maEl = document.getElementById('aut-motivo-analise');
  if (maEl) {
    maEl.value = a.motivo_analise || '';
    const wrapA = document.getElementById('aut-wrap-analise');
    if (wrapA) wrapA.style.display = a.status === 'analise' ? '' : 'none';
  }
  const dtAutEl = document.getElementById('aut-data-autorizacao');
  if (dtAutEl) {
    // data vem como dd/mm/yyyy, converte para yyyy-mm-dd
    if (a.data_autorizacao) {
      const [dda,mma,yya] = a.data_autorizacao.split('/');
      dtAutEl.value = yya && mma && dda ? `${yya}-${mma}-${dda}` : '';
    } else {
      dtAutEl.value = '';
    }
  }
  document.getElementById('aut-form-titulo').textContent = 'Editar Autorização';
  document.getElementById('aut-btn-label').textContent   = 'Salvar';
  document.getElementById('aut-btn-cancelar').style.display = '';
  let arqsAtuais = [];
  if (a.pedido_arquivo) {
    try { arqsAtuais = JSON.parse(a.pedido_arquivo); } catch(e) { arqsAtuais = [a.pedido_arquivo]; }
    if (!Array.isArray(arqsAtuais)) arqsAtuais = [arqsAtuais];
  }
  _autArquivosAtuais = arqsAtuais.filter(Boolean);
  renderArquivosAtuais();
  document.getElementById('aut-form-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelarEdicaoAutorizacao() {
  _autorizacaoEditId = null;
  ['aut-paciente-nome','aut-cpf','aut-telefone','aut-observacao'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  const mnEl2 = document.getElementById('aut-motivo-negacao');
  if (mnEl2) mnEl2.value = '';
  const maEl2 = document.getElementById('aut-motivo-analise');
  if (maEl2) maEl2.value = '';
  const wrapNeg = document.getElementById('aut-wrap-negacao');
  if (wrapNeg) wrapNeg.style.display = 'none';
  const wrapAna = document.getElementById('aut-wrap-analise');
  if (wrapAna) wrapAna.style.display = 'none';
  const dtAutEl2 = document.getElementById('aut-data-autorizacao');
  if (dtAutEl2) dtAutEl2.value = '';
  document.getElementById('aut-convenio').value    = '';
  document.getElementById('aut-procedimento').value = '';
  document.getElementById('aut-data').value         = '';
  document.getElementById('aut-status').value       = 'pendente';
  document.getElementById('aut-arquivo').value      = '';
  _autArquivosAtuais = [];
  renderArquivosAtuais();
  document.getElementById('aut-form-titulo').textContent = 'Nova Autorização';
  document.getElementById('aut-btn-label').textContent   = 'Cadastrar';
  document.getElementById('aut-btn-cancelar').style.display = 'none';
}

async function delAutorizacao(id) {
  if (!confirm('Excluir esta autorização?')) return;
  try {
    await api('api/autorizacoes.php?id=' + id, { method: 'DELETE' });
    toast('Autorização excluída.', 'suc');
    await carregarAutorizacoes();
  } catch(e) { toast(e.message, 'erro'); }
}

/* ── Máscaras de input ─────────────────────────────────── */
function mascaraCPF(el) {
  let v = el.value.replace(/\D/g,'').substring(0,11);
  v = v.replace(/(\d{3})(\d)/,'$1.$2')
       .replace(/(\d{3})(\d)/,'$1.$2')
       .replace(/(\d{3})(\d{1,2})$/,'$1-$2');
  el.value = v;
}
function mascaraTelefone(el) {
  let v = el.value.replace(/\D/g,'').substring(0,11);
  if (v.length > 10) v = v.replace(/(\d{2})(\d{5})(\d{4})/,'($1) $2-$3');
  else if (v.length > 6) v = v.replace(/(\d{2})(\d{4})(\d+)/,'($1) $2-$3');
  else if (v.length > 2) v = v.replace(/(\d{2})(\d+)/,'($1) $2');
  el.value = v;
}

/* ── Alterar senha (próprio usuário) ────────────────────── */
function abrirModalPerfil() {
  ['ms-senha-atual','ms-nova-senha','ms-confirmacao'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  document.getElementById('modal-perfil-senha').style.display = 'flex';
  document.getElementById('ms-senha-atual').focus();
}
function fecharModalPerfil() {
  document.getElementById('modal-perfil-senha').style.display = 'none';
}
async function salvarNovaSenha() {
  const atual  = document.getElementById('ms-senha-atual').value;
  const nova   = document.getElementById('ms-nova-senha').value;
  const conf   = document.getElementById('ms-confirmacao').value;
  if (!atual || !nova || !conf) { toast('Preencha todos os campos.', 'erro'); return; }
  if (nova.length < 6)          { toast('A nova senha deve ter no mínimo 6 caracteres.', 'erro'); return; }
  if (nova !== conf)             { toast('A nova senha e a confirmação não coincidem.', 'erro'); return; }
  try {
    const res = await fetch('api/perfil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ senha_atual: atual, nova_senha: nova, confirmacao: conf })
    });
    const data = await res.json();
    if (!res.ok) { toast(data.erro || 'Erro ao alterar senha.', 'erro'); return; }
    toast(data.mensagem || 'Senha alterada com sucesso!', 'suc');
    fecharModalPerfil();
  } catch(e) { toast('Erro de conexão.', 'erro'); }
}
// Fecha modal ao clicar no overlay
document.addEventListener('click', function(e) {
  const m = document.getElementById('modal-perfil-senha');
  if (m && e.target === m) fecharModalPerfil();
});
// Fecha modal com Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') fecharModalPerfil();
});

function toggleTema() {
  const html = document.documentElement;
  const claro = html.getAttribute('data-tema') === 'claro';
  if (claro) {
    html.removeAttribute('data-tema');
    localStorage.setItem('tema', 'escuro');
  } else {
    html.setAttribute('data-tema', 'claro');
    localStorage.setItem('tema', 'claro');
  }
  const btn = document.getElementById('btn-tema');
  if (btn) btn.innerHTML = claro ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
}

function toggleSidebar() {
  const html = document.documentElement;
  const mobile = window.innerWidth <= 768;
  if (mobile) {
    const open = html.getAttribute('data-sb') === 'open';
    html.setAttribute('data-sb', open ? '' : 'open');
  } else {
    const collapsed = html.getAttribute('data-sb') === 'collapsed';
    html.setAttribute('data-sb', collapsed ? '' : 'collapsed');
    localStorage.setItem('sbCollapsed', collapsed ? '0' : '1');
  }
}
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('btn-tema');
  if (!btn) return;
  const claro = document.documentElement.getAttribute('data-tema') === 'claro';
  btn.innerHTML = claro ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-sun"></i>';
});
</script>
</body>
</html>

