<?php
require_once __DIR__ . '/config/auth.php';
$usuarioLogado = requireLogin();
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
  <script>const USUARIO_LOGADO = <?= json_encode($usuarioLogado) ?>;</script>
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="navbar-app">
  <i class="fas fa-hospital-alt" style="color:var(--neon-cyan);font-size:1.5rem;"></i>
  <div class="brand">
    Hospital Santo Expedito
    <small>Central de Agendamento — Dashboard</small>
  </div>
  <div class="nav-tabs-app">
    <button class="tab-btn active" onclick="showTab('dashboard',this)">
      <i class="fas fa-chart-line"></i> <span>Dashboard</span>
    </button>
    <button class="tab-btn" onclick="showTab('atendimentos',this)">
      <i class="fas fa-calendar-check"></i> <span>Atendimentos</span>
    </button>
    <button class="tab-btn" onclick="showTab('picos',this)">
      <i class="fas fa-clock"></i> <span>Horários de Pico</span>
    </button>
    <button class="tab-btn" onclick="showTab('fechamentos',this)">
      <i class="fas fa-door-closed"></i> <span>Fechamentos</span>
    </button>
    <button class="tab-btn" onclick="showTab('motivos',this)">
      <i class="fas fa-tags"></i> <span>Motivos</span>
    </button>
    <button class="tab-btn" onclick="showTab('semanas',this)">
      <i class="fas fa-calendar-week"></i> <span>Semanas</span>
    </button>
    <button class="tab-btn" onclick="showTab('pesquisa',this)">
      <i class="fas fa-star"></i> <span>Pesquisa</span>
    </button>
    <?php if ($usuarioLogado['perfil'] === 'admin'): ?>
    <button class="tab-btn" onclick="showTab('usuarios',this)">
      <i class="fas fa-users"></i> <span>Usuários</span>
    </button>
    <?php endif; ?>
  </div>
  <!-- Usuário logado + logout -->
  <div style="display:flex;align-items:center;gap:.6rem;padding-left:1rem;border-left:1px solid rgba(255,255,255,.08);flex-shrink:0;">
    <span style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;">
      <i class="fas fa-user-circle"></i>
      <?= htmlspecialchars($usuarioLogado['nome']) ?>
      <small style="margin-left:.25rem;opacity:.65;">(<?= $usuarioLogado['perfil'] ?>)</small>
    </span>
    <a href="logout.php" class="btn-app dang sm" style="text-decoration:none;">
      <i class="fas fa-sign-out-alt"></i> Sair
    </a>
  </div>
</nav>

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
  <section id="tab-dashboard" class="tab-section active">

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
      <div class="charts-grid">
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-chart-bar"></i> Evolução de Atendimentos (diário)</div>
          <div class="chart-wrap"><canvas id="chart-evolucao"></canvas></div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-chart-pie"></i> Motivos de Fechamento — Distribuição</div>
          <div style="display:flex;align-items:center;gap:1rem;">
            <div style="flex:0 0 200px;max-width:200px;"><canvas id="chart-pizza"></canvas></div>
            <div id="pizza-legenda" style="flex:1;font-size:.85rem;"></div>
          </div>
        </div>
      </div>

      <div class="charts-grid" style="grid-template-columns:1fr 1fr 1fr;">
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-clock"></i> Top 5 Horários de Pico — <span id="pico-semana-label" style="font-weight:400;color:var(--text-muted);font-size:.82rem;">selecione uma semana</span></div>
          <div class="chart-wrap"><canvas id="chart-picos"></canvas></div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-door-closed"></i> Motivos de Fechamento</div>
          <div id="resumo-fechamentos" style="font-size:.9rem;color:var(--text);">
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
  <section id="tab-atendimentos" class="tab-section">
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
  <section id="tab-picos" class="tab-section">
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
  <section id="tab-fechamentos" class="tab-section">
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
  <section id="tab-motivos" class="tab-section">
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
  <section id="tab-semanas" class="tab-section">
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
  <section id="tab-pesquisa" class="tab-section">
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
  <section id="tab-usuarios" class="tab-section">
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
            <option value="operador">Operador</option>
            <option value="visualizador">Visualizador</option>
            <option value="admin">Administrador</option>
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
  </section>
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
  // Oculta seletor de semana na aba de usuários
  const sel = document.querySelector('.semana-selector');
  if (sel) sel.style.display = name === 'usuarios' ? 'none' : '';
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
    if (chartPizza) chartPizza.destroy();
    const coresPizza = ['#ff2d78','#ffe600','#00ffff','#00ff88','#b94fff','#ff8c00','#00e5d4','#ff69b4'];
    const pizzaWrap = document.getElementById('chart-pizza')?.closest('div[style*="display:flex"]');
    if (!d.fechamentos.length) {
      if (pizzaWrap) pizzaWrap.innerHTML = '<p style="color:var(--text-muted);font-size:.88rem;padding:.5rem 0;">Nenhum fechamento registrado nesta semana.</p>';
    } else {
      chartPizza = new Chart(document.getElementById('chart-pizza'), {
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
          responsive: true,
          plugins: {
            legend: { display: false },
            datalabels: {
              color: '#0a0a0f',
              font: { weight: 'bold', size: 12 },
              formatter: v => v > 0 ? v : '',
            },
          },
        },
      });
      // Legenda manual à direita
      document.getElementById('pizza-legenda').innerHTML =
        d.fechamentos.map((f, i) => `
          <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.4rem;">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:${coresPizza[i % coresPizza.length]};flex-shrink:0;"></span>
            <span>${f.descricao} <strong>(${f.total})</strong></span>
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
const _PERFIL_LABEL = { admin: 'Administrador', operador: 'Operador', visualizador: 'Visualizador' };
let _usuariosCache  = [];
let _usuarioEditId  = null;
let _senhaAlterarId = null;

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
        <td>${_PERFIL_LABEL[u.perfil] || u.perfil}</td>
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

/* ── Init ────────────────────────────────────────────────── */
(async () => {
  await carregarSemanas();
  await carregarMotivos();
  if (USUARIO_LOGADO.perfil === 'admin') await carregarUsuarios();
})();
</script>
</body>
</html>

