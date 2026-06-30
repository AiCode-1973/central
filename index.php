<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Central de Agendamento — Hospital Santo Expedito</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="navbar-app">
  <i class="fas fa-hospital-alt" style="color:#7ec8ff;font-size:1.5rem;"></i>
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
      <button id="btn-view-mes" class="btn-app" style="background:#e0e8f5;color:#003366;" onclick="setViewMode('mes')">
        <i class="fas fa-calendar-alt"></i> Mês
      </button>
      <div id="mes-selector" style="display:none;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <select id="sel-ano" style="padding:.4rem .65rem;border:1px solid #ccd;border-radius:6px;font-size:.9rem;"></select>
        <select id="sel-mes" style="padding:.4rem .65rem;border:1px solid #ccd;border-radius:6px;font-size:.9rem;">
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

      <div class="charts-grid">
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-clock"></i> Top 5 Horários de Pico — <span id="pico-semana-label" style="font-weight:400;color:#555;font-size:.85rem;">selecione uma semana</span></div>
          <div class="chart-wrap"><canvas id="chart-picos"></canvas></div>
        </div>
        <div class="painel">
          <div class="painel-titulo"><i class="fas fa-door-closed"></i> Motivos de Fechamento</div>
          <div id="resumo-fechamentos" style="font-size:.9rem;color:#555;">
            Selecione uma semana para visualizar.
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
        <div class="painel" style="grid-column:1/-1;">
          <div class="painel-titulo"><i class="fas fa-door-closed"></i> Motivos de Fechamento (mês)</div>
          <div id="resumo-fechamentos-mes" style="font-size:.9rem;color:#555;">Busque um mês para visualizar.</div>
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
        <p style="color:#aaa;font-size:.9rem;">Selecione uma semana primeiro.</p>
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
      <p style="font-size:.85rem;color:#666;margin-bottom:1.1rem;">
        Selecione a semana no topo da página e preencha o total de atendimentos para cada horário.
      </p>
      <div id="picos-grade-wrap">
        <p style="color:#aaa;font-size:.9rem;">Selecione uma semana para carregar os horários.</p>
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
      <p style="font-size:.85rem;color:#666;margin-bottom:1rem;">
        Informe o motivo e a quantidade total de dias fechados por esse motivo na semana.
      </p>

      <!-- Linha para adicionar novo motivo -->
      <div class="form-inline-row" style="margin-bottom:1.1rem;align-items:flex-end;">
        <div class="form-group" style="flex:1;min-width:200px;">
          <label>Motivo <small style="color:#888;font-weight:400;">(selecione ou digite novo)</small></label>
          <input type="text" id="fech-motivo-txt" list="list-motivos"
                 placeholder="Ex: Feriado, Manutenção…"
                 style="width:100%;padding:.4rem .65rem;border:1px solid #ccd;border-radius:6px;font-size:.9rem;">
          <datalist id="list-motivos"></datalist>
        </div>
        <div class="form-group">
          <label>Total de dias</label>
          <input type="number" id="fech-total" min="1" max="5" value="1"
                 style="width:90px;padding:.4rem .65rem;border:1px solid #ccd;border-radius:6px;font-size:.9rem;">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Observação</label>
          <input type="text" id="fech-obs" placeholder="Opcional" style="width:100%;padding:.4rem .65rem;border:1px solid #ccd;border-radius:6px;font-size:.9rem;">
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
            <tr><td colspan="4" style="color:#aaa;">Selecione uma semana.</td></tr>
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
            <tr><td colspan="4" style="color:#aaa;">Carregando…</td></tr>
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
          <input type="date" id="semana-fim" readonly style="background:#f4f4f4;">
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
            <tr><td colspan="4" style="color:#aaa;">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

</div><!-- /.main-wrap -->

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
}


/* ════════════════════════════════════════════════════════
   DASHBOARD
════════════════════════════════════════════════════════ */
let chartEvolucao = null, chartPizza = null, chartPicos = null;
let chartMesSemanas = null, chartMesPicos = null;
let viewMode = 'semana';

function setViewMode(mode) {
  viewMode = mode;
  document.getElementById('view-semana').style.display  = mode === 'semana' ? '' : 'none';
  document.getElementById('view-mes').style.display     = mode === 'mes'    ? '' : 'none';
  document.getElementById('mes-selector').style.display = mode === 'mes'    ? 'flex' : 'none';
  document.getElementById('btn-view-semana').style.background = mode === 'semana' ? '#005599' : '#e0e8f5';
  document.getElementById('btn-view-semana').style.color      = mode === 'semana' ? '#fff'    : '#003366';
  document.getElementById('btn-view-mes').style.background    = mode === 'mes'    ? '#005599' : '#e0e8f5';
  document.getElementById('btn-view-mes').style.color         = mode === 'mes'    ? '#fff'    : '#003366';
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

    document.getElementById('mkpi-agendados').textContent  = d.totais?.total_agendados  || 0;
    document.getElementById('mkpi-atendidos').textContent  = d.totais?.total_atendidos   || 0;
    document.getElementById('mkpi-cancelados').textContent = d.totais?.total_cancelados  || 0;
    document.getElementById('mkpi-faltas').textContent     = d.totais?.total_faltas      || 0;

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
          backgroundColor: '#005599',
        }],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          datalabels: {
            anchor: 'end', align: 'end',
            color: '#003366', font: { weight: 'bold', size: 11 },
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
          backgroundColor: '#005599',
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
          legend: { display: false },
          datalabels: {
            anchor: 'end', align: 'end',
            color: '#003366', font: { weight: 'bold', size: 11 },
            formatter: v => v > 0 ? v : '',
          },
        },
        layout: { padding: { right: 30 } },
      },
    });

    // Motivos mês
    const rf = document.getElementById('resumo-fechamentos-mes');
    if (!(d.fechamentos || []).length) {
      rf.innerHTML = '<span style="color:#aaa;">Nenhum fechamento registrado neste mês.</span>';
    } else {
      const totalGeral = d.fechamentos.reduce((s, f) => s + +f.total, 0);
      rf.innerHTML =
        '<table style="width:100%;max-width:500px;border-collapse:collapse;font-size:.88rem;">' +
          d.fechamentos.map(f => `
            <tr style="border-bottom:1px solid #e8eef5;">
              <td style="padding:.35rem .4rem;">${f.descricao}</td>
              <td style="padding:.35rem .4rem;text-align:right;font-weight:700;color:#003366;">${f.total}</td>
            </tr>`).join('') +
          `<tr style="border-top:2px solid #003366;background:#e8f0fe;">
            <td style="padding:.4rem .4rem;font-weight:700;">Total</td>
            <td style="padding:.4rem .4rem;text-align:right;font-weight:700;color:#003366;font-size:1rem;">${totalGeral}</td>
          </tr>` +
        '</table>';
    }
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
          { label: 'Agendados',  data: agend, backgroundColor: '#005599' },
          { label: 'Atendidos',  data: atend, backgroundColor: '#198754' },
          { label: 'Cancelados', data: canc,  backgroundColor: '#dc3545' },
          { label: 'Faltas',     data: falta, backgroundColor: '#ffc107' },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          datalabels: {
            anchor: 'end',
            align: 'end',
            color: '#333',
            font: { weight: 'bold', size: 11 },
            formatter: v => v > 0 ? v : '',
          },
        },
        layout: { padding: { top: 20 } },
      },
    });

    // Gráfico pizza — Motivos de Fechamento
    if (chartPizza) chartPizza.destroy();
    const coresPizza = ['#dc3545','#ffc107','#005599','#198754','#6f42c1','#fd7e14','#20c997','#e83e8c'];
    const pizzaWrap = document.getElementById('chart-pizza')?.closest('div[style*="display:flex"]');
    if (!d.fechamentos.length) {
      if (pizzaWrap) pizzaWrap.innerHTML = '<p style="color:#aaa;font-size:.88rem;padding:.5rem 0;">Nenhum fechamento registrado nesta semana.</p>';
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
              color: '#fff',
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
          backgroundColor: '#005599',
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
            color: '#003366',
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
      rf.innerHTML = '<span style="color:#aaa;">Nenhum fechamento registrado nesta semana.</span>';
    } else {
      const totalGeral = d.fechamentos.reduce((s, f) => s + +f.total, 0);
      rf.innerHTML =
        '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">' +
          d.fechamentos.map(f => `
            <tr style="border-bottom:1px solid #e8eef5;">
              <td style="padding:.35rem .4rem;">${f.descricao}</td>
              <td style="padding:.35rem .4rem;text-align:right;font-weight:700;color:#003366;">${f.total}</td>
            </tr>`).join('') +
          `<tr style="border-top:2px solid #003366;background:#e8f0fe;">
            <td style="padding:.4rem .4rem;font-weight:700;">Total</td>
            <td style="padding:.4rem .4rem;text-align:right;font-weight:700;color:#003366;font-size:1rem;">${totalGeral}</td>
          </tr>` +
        '</table>';
    }
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
    c.innerHTML = '<p style="color:#aaa;">Selecione uma semana primeiro.</p>';
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
                       style="width:100px;padding:.3rem .5rem;border:1px solid #ccd;border-radius:5px;text-align:center;">
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
                  style="width:100px;padding:.3rem .5rem;border:1px solid #ccd;border-radius:5px;text-align:center;">
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
    tb.innerHTML = '<tr><td colspan="4" style="color:#aaa;">Nenhum fechamento registrado nesta semana.</td></tr>';
    return;
  }
  tb.innerHTML = dados.map(f => `
    <tr>
      <td>${f.motivo}</td>
      <td style="text-align:center;">
        <input type="number" min="1" max="5" value="${f.total}"
               style="width:70px;padding:.25rem .4rem;border:1px solid #ccd;border-radius:5px;text-align:center;"
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
    tb.innerHTML = '<tr><td colspan="4" style="color:#aaa;">Nenhum motivo cadastrado.</td></tr>';
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
    tb.innerHTML = '<tr><td colspan="4" style="color:#aaa;">Nenhuma semana cadastrada.</td></tr>';
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

/* ── Init ────────────────────────────────────────────────── */
(async () => {
  await carregarSemanas();
  await carregarMotivos();
})();
</script>
</body>
</html>
