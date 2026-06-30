<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Central de Agendamento — Hospital Santo Expedito</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
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

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <span class="kpi-label">Agendados</span>
        <span class="kpi-valor" id="kpi-agendados">—</span>
        <i class="fas fa-calendar-plus kpi-icon"></i>
      </div>
      <div class="kpi-card verde">
        <span class="kpi-label">Atendidos</span>
        <span class="kpi-valor" id="kpi-atendidos">—</span>
        <i class="fas fa-user-check kpi-icon"></i>
      </div>
      <div class="kpi-card verm">
        <span class="kpi-label">Cancelados</span>
        <span class="kpi-valor" id="kpi-cancelados">—</span>
        <i class="fas fa-calendar-times kpi-icon"></i>
      </div>
      <div class="kpi-card amar">
        <span class="kpi-label">Faltas</span>
        <span class="kpi-valor" id="kpi-faltas">—</span>
        <i class="fas fa-user-slash kpi-icon"></i>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="charts-grid">
      <div class="painel">
        <div class="painel-titulo"><i class="fas fa-chart-bar"></i> Evolução de Atendimentos (diário)</div>
        <div class="chart-wrap"><canvas id="chart-evolucao"></canvas></div>
      </div>
      <div class="painel">
        <div class="painel-titulo"><i class="fas fa-chart-pie"></i> Distribuição da Semana</div>
        <div class="chart-wrap"><canvas id="chart-pizza"></canvas></div>
      </div>
    </div>

    <div class="charts-grid">
      <div class="painel">
        <div class="painel-titulo"><i class="fas fa-clock"></i> Top 5 Horários de Pico</div>
        <div class="chart-wrap"><canvas id="chart-picos"></canvas></div>
      </div>
      <div class="painel">
        <div class="painel-titulo"><i class="fas fa-door-closed"></i> Motivos de Fechamento</div>
        <div id="resumo-fechamentos" style="font-size:.9rem;color:#555;">
          Selecione uma semana para visualizar.
        </div>
      </div>
    </div>

  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: ATENDIMENTOS
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-atendimentos" class="tab-section">
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-calendar-check"></i> Cadastro de Atendimentos</div>
      <p style="font-size:.85rem;color:#666;margin-bottom:1rem;">
        Preencha os dados para cada dia da semana selecionada (segunda a sexta).
      </p>
      <div id="form-atendimentos">
        <p style="color:#aaa;font-size:.9rem;">Selecione uma semana primeiro.</p>
      </div>
      <div style="margin-top:1rem;">
        <button class="btn-app suc" onclick="salvarAtendimentos()">
          <i class="fas fa-save"></i> Salvar Atendimentos
        </button>
      </div>
    </div>

    <div class="painel" style="margin-top:1.25rem;">
      <div class="painel-titulo"><i class="fas fa-list"></i> Registros Salvos</div>
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr>
              <th>Data</th><th>Agendados</th><th>Atendidos</th>
              <th>Cancelados</th><th>Faltas</th><th>Observação</th><th></th>
            </tr>
          </thead>
          <tbody id="tbody-atendimentos">
            <tr><td colspan="7" style="color:#aaa;">Selecione uma semana.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: HORÁRIOS DE PICO
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-picos" class="tab-section">
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-clock"></i> Cadastrar Horários de Pico</div>
      <div class="form-inline-row" style="margin-bottom:1rem;">
        <div class="form-group">
          <label>Data</label>
          <input type="date" id="pico-data">
        </div>
        <div class="form-group">
          <label>Hora (HH:MM)</label>
          <input type="time" id="pico-hora" step="3600">
        </div>
        <div class="form-group">
          <label>Total Atendimentos</label>
          <input type="number" id="pico-total" min="0" style="width:120px;" value="0">
        </div>
        <button class="btn-app prim" style="align-self:flex-end;" onclick="addPico()">
          <i class="fas fa-plus"></i> Adicionar
        </button>
      </div>

      <!-- Tabela de entrada antes de salvar -->
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>Data</th><th>Hora</th><th>Atendimentos</th><th></th></tr>
          </thead>
          <tbody id="tbody-picos-form"></tbody>
        </table>
      </div>
      <button class="btn-app suc" style="margin-top:1rem;" onclick="salvarPicos()">
        <i class="fas fa-save"></i> Salvar Horários
      </button>
    </div>

    <div class="painel" style="margin-top:1.25rem;">
      <div class="painel-titulo"><i class="fas fa-list"></i> Horários Salvos na Semana</div>
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>Data</th><th>Hora</th><th>Atendimentos</th><th></th></tr>
          </thead>
          <tbody id="tbody-picos-list">
            <tr><td colspan="4" style="color:#aaa;">Selecione uma semana.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════════
       ABA: FECHAMENTOS
  ══════════════════════════════════════════════════════════ -->
  <section id="tab-fechamentos" class="tab-section">
    <div class="painel">
      <div class="painel-titulo"><i class="fas fa-door-closed"></i> Registrar Fechamento</div>
      <div class="form-inline-row" style="margin-bottom:1rem;">
        <div class="form-group">
          <label>Data</label>
          <input type="date" id="fech-data">
        </div>
        <div class="form-group">
          <label>Motivo</label>
          <select id="fech-motivo" style="min-width:200px;">
            <option value="">— Selecione —</option>
          </select>
        </div>
        <div class="form-group" style="flex:1;">
          <label>Observação</label>
          <input type="text" id="fech-obs" placeholder="Opcional" style="width:100%;">
        </div>
        <button class="btn-app prim" style="align-self:flex-end;" onclick="salvarFechamento()">
          <i class="fas fa-plus"></i> Registrar
        </button>
      </div>
    </div>

    <div class="painel" style="margin-top:1.25rem;">
      <div class="painel-titulo"><i class="fas fa-list"></i> Fechamentos da Semana</div>
      <div class="table-responsive">
        <table class="tabela-app">
          <thead>
            <tr><th>Data</th><th>Motivo</th><th>Observação</th><th></th></tr>
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

async function carregarDashboard(sid) {
  try {
    const d = await api('api/estatisticas.php?semana_id=' + sid);

    // KPIs
    document.getElementById('kpi-agendados').textContent  = d.totais.total_agendados;
    document.getElementById('kpi-atendidos').textContent  = d.totais.total_atendidos;
    document.getElementById('kpi-cancelados').textContent = d.totais.total_cancelados;
    document.getElementById('kpi-faltas').textContent     = d.totais.total_faltas;

    // Gráfico evolução
    const dias   = d.por_dia.map(r => fmtData(r.data));
    const agend  = d.por_dia.map(r => +r.total_agendados);
    const atend  = d.por_dia.map(r => +r.total_atendidos);
    const canc   = d.por_dia.map(r => +r.total_cancelados);
    const falta  = d.por_dia.map(r => +r.total_faltas);

    if (chartEvolucao) chartEvolucao.destroy();
    chartEvolucao = new Chart(document.getElementById('chart-evolucao'), {
      type: 'bar',
      data: {
        labels: dias,
        datasets: [
          { label: 'Agendados',  data: agend, backgroundColor: '#005599' },
          { label: 'Atendidos',  data: atend, backgroundColor: '#198754' },
          { label: 'Cancelados', data: canc,  backgroundColor: '#dc3545' },
          { label: 'Faltas',     data: falta, backgroundColor: '#ffc107' },
        ],
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

    // Gráfico pizza
    if (chartPizza) chartPizza.destroy();
    chartPizza = new Chart(document.getElementById('chart-pizza'), {
      type: 'doughnut',
      data: {
        labels: ['Atendidos', 'Cancelados', 'Faltas'],
        datasets: [{
          data: [
            +d.totais.total_atendidos,
            +d.totais.total_cancelados,
            +d.totais.total_faltas,
          ],
          backgroundColor: ['#198754', '#dc3545', '#ffc107'],
        }],
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

    // Gráfico picos
    if (chartPicos) chartPicos.destroy();
    chartPicos = new Chart(document.getElementById('chart-picos'), {
      type: 'bar',
      data: {
        labels: d.picos.map(p => p.hora),
        datasets: [{
          label: 'Atendimentos',
          data:  d.picos.map(p => +p.total),
          backgroundColor: '#005599',
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
      },
    });

    // Resumo fechamentos
    const rf = document.getElementById('resumo-fechamentos');
    if (!d.fechamentos.length) {
      rf.innerHTML = '<span style="color:#aaa;">Nenhum fechamento registrado nesta semana.</span>';
    } else {
      rf.innerHTML = '<ul style="margin:0;padding-left:1.2rem;">' +
        d.fechamentos.map(f =>
          `<li><strong>${f.descricao}</strong> — ${f.total} dia(s)</li>`
        ).join('') + '</ul>';
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
  const sel    = document.getElementById('sel-semana');
  const opt    = sel.options[sel.selectedIndex];
  const texto  = opt ? opt.textContent : '';
  // Tenta extrair a data de início do texto ou busca da API
  const semanas = await api('api/semanas.php');
  const sem     = semanas.find(s => s.id == sid);
  if (!sem) return;

  const dias   = gerarDiasUteis(sem.data_inicio);
  const saved  = await api('api/atendimentos.php?semana_id=' + sid);

  // Monta mapa data → registro salvo
  const map = {};
  saved.forEach(r => { map[r.data] = r; });

  atendimentosEditaveis = dias.map((dt, i) => ({
    id:               map[dt]?.id               || null,
    semana_id:        sid,
    data:             dt,
    total_agendados:  map[dt]?.total_agendados  || 0,
    total_atendidos:  map[dt]?.total_atendidos  || 0,
    total_cancelados: map[dt]?.total_cancelados || 0,
    total_faltas:     map[dt]?.total_faltas     || 0,
    observacao:       map[dt]?.observacao       || '',
    diaNome:          DIAS_SEMANA[i],
  }));

  renderFormAtendimentos();
  renderTabelaAtendimentos();
}

function renderFormAtendimentos() {
  const c = document.getElementById('form-atendimentos');
  if (!atendimentosEditaveis.length) {
    c.innerHTML = '<p style="color:#aaa;">Selecione uma semana primeiro.</p>';
    return;
  }
  c.innerHTML = `
    <div class="table-responsive">
      <table class="tabela-app">
        <thead>
          <tr>
            <th>Dia</th><th>Data</th>
            <th>Agendados</th><th>Atendidos</th>
            <th>Cancelados</th><th>Faltas</th><th>Observação</th>
          </tr>
        </thead>
        <tbody>
          ${atendimentosEditaveis.map((r, i) => `
            <tr>
              <td><strong>${r.diaNome}</strong></td>
              <td>${fmtData(r.data)}</td>
              <td><input type="number" min="0" class="at-ag" data-i="${i}" value="${r.total_agendados}" style="width:70px;padding:.3rem;border:1px solid #ccd;border-radius:5px;"></td>
              <td><input type="number" min="0" class="at-at" data-i="${i}" value="${r.total_atendidos}" style="width:70px;padding:.3rem;border:1px solid #ccd;border-radius:5px;"></td>
              <td><input type="number" min="0" class="at-ca" data-i="${i}" value="${r.total_cancelados}" style="width:70px;padding:.3rem;border:1px solid #ccd;border-radius:5px;"></td>
              <td><input type="number" min="0" class="at-fa" data-i="${i}" value="${r.total_faltas}" style="width:70px;padding:.3rem;border:1px solid #ccd;border-radius:5px;"></td>
              <td><input type="text" class="at-ob" data-i="${i}" value="${r.observacao}" style="width:140px;padding:.3rem;border:1px solid #ccd;border-radius:5px;" placeholder="Opcional"></td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}

async function salvarAtendimentos() {
  const sid = semanaAtual();
  if (!sid) { toast('Selecione uma semana.', 'erro'); return; }

  // Lê valores dos inputs
  const items = atendimentosEditaveis.map((r, i) => ({
    semana_id:        sid,
    data:             r.data,
    total_agendados:  parseInt(document.querySelector(`.at-ag[data-i="${i}"]`)?.value) || 0,
    total_atendidos:  parseInt(document.querySelector(`.at-at[data-i="${i}"]`)?.value) || 0,
    total_cancelados: parseInt(document.querySelector(`.at-ca[data-i="${i}"]`)?.value) || 0,
    total_faltas:     parseInt(document.querySelector(`.at-fa[data-i="${i}"]`)?.value) || 0,
    observacao:       document.querySelector(`.at-ob[data-i="${i}"]`)?.value || '',
  }));

  try {
    await api('api/atendimentos.php', { method: 'POST', body: JSON.stringify({ items }) });
    toast('Atendimentos salvos!', 'suc');
    carregarAtendimentos(sid);
    carregarDashboard(sid);
  } catch (e) { toast(e.message, 'erro'); }
}

function renderTabelaAtendimentos() {
  const tb = document.getElementById('tbody-atendimentos');
  if (!atendimentosEditaveis.length) {
    tb.innerHTML = '<tr><td colspan="7" style="color:#aaa;">Selecione uma semana.</td></tr>';
    return;
  }
  tb.innerHTML = atendimentosEditaveis.map(r => `
    <tr>
      <td>${fmtData(r.data)}</td>
      <td>${r.total_agendados}</td>
      <td>${r.total_atendidos}</td>
      <td>${r.total_cancelados}</td>
      <td>${r.total_faltas}</td>
      <td>${r.observacao || '—'}</td>
      <td>${r.id ? `<button class="btn-del" onclick="delAtendimento(${r.id})"><i class="fas fa-trash"></i></button>` : ''}</td>
    </tr>`).join('');
}

async function delAtendimento(id) {
  if (!confirm('Remover este registro?')) return;
  try {
    await api('api/atendimentos.php?id=' + id, { method: 'DELETE' });
    toast('Removido.', 'suc');
    carregarAtendimentos(semanaAtual());
    carregarDashboard(semanaAtual());
  } catch (e) { toast(e.message, 'erro'); }
}

/* ════════════════════════════════════════════════════════
   HORÁRIOS DE PICO
════════════════════════════════════════════════════════ */
let picosForm = [];

function addPico() {
  const data  = document.getElementById('pico-data').value;
  const hora  = document.getElementById('pico-hora').value;
  const total = parseInt(document.getElementById('pico-total').value) || 0;
  if (!data || !hora) { toast('Preencha data e hora.', 'erro'); return; }
  picosForm.push({ data, hora, total });
  renderPicosForm();
}

function renderPicosForm() {
  const tb = document.getElementById('tbody-picos-form');
  if (!picosForm.length) { tb.innerHTML = ''; return; }
  tb.innerHTML = picosForm.map((p, i) => `
    <tr>
      <td>${fmtData(p.data)}</td>
      <td>${p.hora}</td>
      <td>${p.total}</td>
      <td><button class="btn-del" onclick="picosForm.splice(${i},1);renderPicosForm()"><i class="fas fa-trash"></i></button></td>
    </tr>`).join('');
}

async function salvarPicos() {
  const sid = semanaAtual();
  if (!sid) { toast('Selecione uma semana.', 'erro'); return; }
  if (!picosForm.length) { toast('Adicione ao menos um horário.', 'erro'); return; }
  const items = picosForm.map(p => ({
    semana_id: sid, data: p.data, hora: p.hora, total_atendimentos: p.total,
  }));
  try {
    await api('api/picos.php', { method: 'POST', body: JSON.stringify({ items }) });
    toast('Horários salvos!', 'suc');
    picosForm = [];
    renderPicosForm();
    carregarPicosList(sid);
    carregarDashboard(sid);
  } catch (e) { toast(e.message, 'erro'); }
}

async function carregarPicosList(sid) {
  const dados = await api('api/picos.php?semana_id=' + sid);
  const tb    = document.getElementById('tbody-picos-list');
  if (!dados.length) {
    tb.innerHTML = '<tr><td colspan="4" style="color:#aaa;">Nenhum registro.</td></tr>';
    return;
  }
  tb.innerHTML = dados.map(p => `
    <tr>
      <td>${fmtData(p.data)}</td>
      <td>${p.hora}</td>
      <td>${p.total_atendimentos}</td>
      <td><button class="btn-del" onclick="delPico(${p.id})"><i class="fas fa-trash"></i></button></td>
    </tr>`).join('');
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
    tb.innerHTML = '<tr><td colspan="4" style="color:#aaa;">Nenhum fechamento registrado.</td></tr>';
    return;
  }
  tb.innerHTML = dados.map(f => `
    <tr>
      <td>${fmtData(f.data)}</td>
      <td>${f.motivo}</td>
      <td>${f.observacao || '—'}</td>
      <td><button class="btn-del" onclick="delFechamento(${f.id})"><i class="fas fa-trash"></i></button></td>
    </tr>`).join('');
}

async function salvarFechamento() {
  const sid    = semanaAtual();
  const data   = document.getElementById('fech-data').value;
  const motivo = parseInt(document.getElementById('fech-motivo').value);
  const obs    = document.getElementById('fech-obs').value;
  if (!sid)    { toast('Selecione uma semana.', 'erro'); return; }
  if (!data)   { toast('Informe a data.', 'erro'); return; }
  if (!motivo) { toast('Selecione o motivo.', 'erro'); return; }
  try {
    await api('api/fechamentos.php', {
      method: 'POST',
      body: JSON.stringify({ semana_id: sid, data, motivo_id: motivo, observacao: obs }),
    });
    toast('Fechamento registrado!', 'suc');
    document.getElementById('fech-data').value  = '';
    document.getElementById('fech-obs').value   = '';
    carregarFechamentos(sid);
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

  // Popula select do fechamento
  const sel = document.getElementById('fech-motivo');
  sel.innerHTML = '<option value="">— Selecione —</option>';
  dados.filter(m => m.ativo == 1).forEach(m => {
    const o = document.createElement('option');
    o.value = m.id; o.textContent = m.descricao;
    sel.appendChild(o);
  });

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
