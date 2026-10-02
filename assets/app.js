/* ── SiCantik Dashboard — app.js ── */

// ── Password visibility toggle ──
function togglePw(btn) {
  const input = btn.parentElement.querySelector('input');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.querySelector('.pw-eye-off').classList.toggle('hidden', show);
  btn.querySelector('.pw-eye').classList.toggle('hidden', !show);
}

// ── Sidebar toggle (hamburger) ──
function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('sidebar-overlay');
  sb.classList.toggle('-translate-x-full');
  ov.classList.toggle('hidden');
}

// Close sidebar on nav click (mobile)
document.querySelectorAll('#sidebar a.nav-item').forEach(a => {
  a.addEventListener('click', () => {
    if (window.innerWidth < 1024) toggleSidebar();
  });
});

// ── API helper ──
async function api(action, data = {}) {
  data.action = action;
  const r = await fetch('api/handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  return r.json();
}

// ── Connections page ──
async function testConnection(form) {
  const el = document.getElementById('test-result');
  el.className = 'mt-4 p-4 rounded-lg bg-blue-50 text-blue-700 text-sm';
  el.textContent = '⏳ Testing...';
  el.classList.remove('hidden');
  const fd = new FormData(form);
  const res = await api('test_connection', Object.fromEntries(fd));
  if (res.ok) {
    el.className = 'mt-4 p-4 rounded-lg bg-green-50 text-green-700 text-sm';
    el.innerHTML = `✅ HTTP ${res.http_status} | ${res.record_count} record | ${res.duration}s<br>Fields: ${res.fields.map(f => f.key).join(', ')}`;
  } else {
    el.className = 'mt-4 p-4 rounded-lg bg-red-50 text-red-700 text-sm';
    el.innerHTML = `❌ Error: ${res.error || 'HTTP ' + res.http_status}` +
      (res.raw ? `<pre class="mt-2 p-2 bg-red-100 rounded text-xs overflow-auto max-h-48 whitespace-pre-wrap">${res.raw}</pre>` : '');
  }
}

async function deleteConn(id) {
  if (!confirm('Hapus koneksi ini?')) return;
  await api('delete_connection', { id });
  location.reload();
}

// ── Users page ──
function editUser(u, access) {
  document.getElementById('uf-id').value = u.id;
  document.getElementById('uf-username').value = u.username;
  document.getElementById('uf-name').value = u.name;
  document.getElementById('uf-role').value = u.role;
  document.getElementById('uf-password').value = '';
  document.getElementById('pw-hint').textContent = '(kosongkan jika tidak diubah)';
  document.getElementById('user-form-title').textContent = '✏️ Edit User';
  if (typeof toggleAccessPanel === 'function') toggleAccessPanel();
  // Set access checkboxes
  if (u.role !== 'admin' && typeof toggleAccessChecklist === 'function') {
    if (access && access.length > 0) {
      const selRadio = document.querySelector('input[name="access_mode"][value="selected"]');
      if (selRadio) selRadio.checked = true;
      toggleAccessChecklist();
      document.querySelectorAll('.report-access-cb').forEach(cb => {
        cb.checked = access.includes(parseInt(cb.value));
      });
    } else {
      const allRadio = document.querySelector('input[name="access_mode"][value="all"]');
      if (allRadio) allRadio.checked = true;
      toggleAccessChecklist();
      document.querySelectorAll('.report-access-cb').forEach(cb => cb.checked = false);
    }
  }
}
function resetUserForm() {
  document.getElementById('uf-id').value = '';
  document.getElementById('user-form').reset();
  document.getElementById('pw-hint').textContent = '(wajib)';
  document.getElementById('user-form-title').textContent = '＋ Tambah User';
  if (typeof toggleAccessPanel === 'function') toggleAccessPanel();
  if (typeof toggleAccessChecklist === 'function') toggleAccessChecklist();
}
async function saveUser(e) {
  e.preventDefault();
  const f = document.getElementById('user-form');
  const fd = new FormData(f);
  const data = Object.fromEntries(fd);
  if (!data.id && !data.password) { alert('Password wajib untuk user baru'); return false; }
  // Collect report access
  if (data.role !== 'admin') {
    const modeEl = document.querySelector('input[name="access_mode"]:checked');
    data.access_mode = modeEl ? modeEl.value : 'all';
    if (data.access_mode === 'selected') {
      data.report_access = [...document.querySelectorAll('.report-access-cb:checked')].map(cb => parseInt(cb.value));
      if (data.report_access.length === 0) { alert('Pilih minimal 1 laporan, atau pilih "Semua laporan"'); return false; }
    }
  }
  const res = await api('save_user', data);
  if (res.ok) location.reload();
  else alert(res.error || 'Gagal');
  return false;
}
async function toggleUser(id, current) {
  await api('toggle_user', { id });
  location.reload();
}
async function deleteUser(id) {
  if (!confirm('Hapus user ini?')) return;
  await api('delete_user', { id });
  location.reload();
}

// ── Sync page ──
// ── Sync overlay (blocks all interaction during sync) ──
function showSyncOverlay(total) {
  let ov = document.getElementById('sync-overlay');
  if (!ov) {
    ov = document.createElement('div');
    ov.id = 'sync-overlay';
    ov.className = 'fixed inset-0 z-[9999] bg-black/50 flex items-center justify-center';
    ov.innerHTML = `<div class="bg-white rounded-2xl shadow-2xl p-6 w-80 text-center">
      <div class="text-4xl mb-3" id="sync-ov-icon">🔄</div>
      <h3 class="font-bold text-gray-800 text-lg mb-1">Sinkronisasi Data</h3>
      <p class="text-sm text-gray-500 mb-4">Mohon tunggu, jangan tutup halaman ini.</p>
      <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2"><div id="sync-ov-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width:0%"></div></div>
      <p class="text-xs text-gray-500" id="sync-ov-text">0 / ${total} koneksi</p>
      <div id="sync-ov-log" class="mt-3 text-left text-xs text-gray-500 max-h-40 overflow-y-auto space-y-1"></div>
    </div>`;
    document.body.appendChild(ov);
  }
  ov.classList.remove('hidden');
}
function updateSyncOverlay(done, total, name, ok) {
  const pct = Math.round((done / total) * 100);
  const bar = document.getElementById('sync-ov-bar');
  const txt = document.getElementById('sync-ov-text');
  const log = document.getElementById('sync-ov-log');
  if (bar) bar.style.width = pct + '%';
  if (txt) txt.textContent = done + ' / ' + total + ' koneksi';
  if (log) log.innerHTML += `<div>${ok ? '✅' : '❌'} ${name}</div>`;
}
function closeSyncOverlay(success) {
  const icon = document.getElementById('sync-ov-icon');
  const txt = document.getElementById('sync-ov-text');
  const bar = document.getElementById('sync-ov-bar');
  if (icon) icon.textContent = success ? '✅' : '⚠️';
  if (txt) txt.textContent = success ? 'Selesai!' : 'Selesai (ada error)';
  if (bar) { bar.style.width = '100%'; bar.className = bar.className.replace('bg-blue-600', success ? 'bg-green-500' : 'bg-yellow-500'); }
  setTimeout(() => location.reload(), 1200);
}

async function syncOne(connId, btn) {
  showSyncOverlay(1);
  btn.disabled = true;
  const res = await api('sync', { connection_id: connId });
  updateSyncOverlay(1, 1, 'Koneksi #' + connId, res.ok);
  closeSyncOverlay(res.ok);
}
async function syncAll() {
  if (!confirm('Sync semua koneksi?')) return;
  const rows = document.querySelectorAll('[data-conn-id]');
  if (!rows.length) { alert('Tidak ada koneksi'); return; }
  const total = rows.length;
  showSyncOverlay(total);
  let done = 0, allOk = true;
  for (const row of rows) {
    const cid = row.dataset.connId;
    const name = row.querySelector('td')?.textContent?.trim() || 'Koneksi #' + cid;
    const status = row.querySelector('.sync-status');
    if (status) status.innerHTML = '<span class="text-blue-600">⏳ Syncing...</span>';
    const res = await api('sync', { connection_id: parseInt(cid) });
    done++;
    const ok = !!res.ok;
    if (!ok) allOk = false;
    updateSyncOverlay(done, total, name, ok);
    if (status) status.innerHTML = ok
      ? '<span class="text-green-600">✅ ' + (res.record_count ?? 0) + ' record, ' + (res.duration ?? 0) + 's</span>'
      : '<span class="text-red-600">❌ ' + (res.error || 'Gagal') + '</span>';
  }
  closeSyncOverlay(allOk);
}
async function syncReport(connId) {
  showSyncOverlay(1);
  const res = await api('sync', { connection_id: connId });
  updateSyncOverlay(1, 1, 'Koneksi #' + connId, res.ok);
  closeSyncOverlay(res.ok);
}

// ── Report delete ──
async function deleteReport(id) {
  if (!confirm('Hapus laporan ini?')) return;
  await api('delete_report', { id });
  location.reload();
}

async function deleteReportAndGo(id) {
  if (!confirm('Hapus laporan ini secara permanen? Tindakan ini tidak bisa dibatalkan.')) return;
  await api('delete_report', { id });
  location.href = '?page=reports';
}

// ── Report Builder ──
let builderFields = [];
let builderFilters = [];
let builderCharts = [];
let _builderLoaded = {fields: false, filters: false, charts: false};
let _sortableFields = null;
let _sortableFilters = null;
let currentStep = 1;

function goStep(n) {
  if (n === 2 && !document.querySelector('input[name="b-conn"]:checked') && typeof CONNECTIONS !== 'undefined') {
    alert('Pilih koneksi dulu');
    return;
  }
  for (let i = 1; i <= 4; i++) {
    const el = document.getElementById('step-' + i);
    const ind = document.getElementById('step-ind-' + i);
    if (!el || !ind) continue;
    el.classList.toggle('hidden', i !== n);
    ind.className = i <= n ? 'px-3 py-1 rounded-full bg-blue-600 text-white' : 'px-3 py-1 rounded-full bg-gray-200 text-gray-600';
  }
  currentStep = n;
  if (n === 2) renderFieldsList();
  if (n === 3) { if (!_builderLoaded.fields) renderFieldsList(); renderFiltersList(); }
  if (n === 4) { if (!_builderLoaded.fields) renderFieldsList(); renderChartsList(); }
}

function loadFields(connId) {
  const conn = (typeof CONNECTIONS !== 'undefined' ? CONNECTIONS : []).find(c => c.id == connId);
  if (!conn) return;
  builderFields = (conn.fields || []).map((f, i) => ({
    key: f.key, label: f.label || f.key, type: f.type || 'text', enabled: true, order: i
  }));
}

function renderFieldsList() {
  const el = document.getElementById('fields-list');
  if (!el) return;
  if (typeof EDIT_DATA !== 'undefined' && EDIT_DATA && !_builderLoaded.fields) {
    _builderLoaded.fields = true;
    loadFields(EDIT_DATA.connection_id);
    const saved = JSON.parse(EDIT_DATA.config_fields || '[]');
    if (saved.length) builderFields = saved;
  }
  el.innerHTML = builderFields.map((f, i) => {
    const linkMode = f.is_link === true ? 'link' : (f.is_link === false ? 'text' : 'auto');
    return `
    <div class="p-3 ${f.missing ? 'bg-red-50 border border-red-200' : 'bg-gray-50'} rounded-lg" data-idx="${i}">
      <div class="flex items-center gap-3 flex-wrap">
        <span class="cursor-grab text-gray-400 drag-handle">≡</span>
        <input type="checkbox" ${f.enabled ? 'checked' : ''} onchange="builderFields[${i}].enabled=this.checked" class="text-blue-600">
        <span class="text-xs ${f.missing ? 'text-red-400 line-through' : 'text-gray-400'} font-mono w-32 truncate">${esc(f.key)}</span>
        <input type="text" value="${esc(f.label)}" onchange="builderFields[${i}].label=this.value" class="flex-1 min-w-[160px] px-2 py-1 border ${f.missing ? 'border-red-300' : 'border-gray-300'} rounded text-sm">
        ${f.missing ? '<span class="text-xs text-red-500 whitespace-nowrap" title="Field ini sudah tidak ada di API">⚠️ Hilang</span>' : ''}
      </div>
      <div class="flex items-center gap-2 mt-2 ml-9 flex-wrap">
        <span class="text-[11px] text-gray-400">Tampilkan:</span>
        <select onchange="builderFields[${i}].is_link=this.value==='auto'?undefined:(this.value==='link'?true:false)" class="px-2 py-1 border border-gray-300 rounded text-xs">
          <option value="auto" ${linkMode==='auto'?'selected':''}>Auto</option>
          <option value="link" ${linkMode==='link'?'selected':''}>Link</option>
          <option value="text" ${linkMode==='text'?'selected':''}>Teks</option>
        </select>
        <input type="text" value="${esc(f.link_label || '')}" placeholder="Label link (mis. Download)" onchange="builderFields[${i}].link_label=this.value" class="px-2 py-1 border border-gray-300 rounded text-xs w-44">
      </div>
    </div>`;
  }).join('');
  if (typeof Sortable !== 'undefined') {
    if (_sortableFields) _sortableFields.destroy();
    _sortableFields = Sortable.create(el, { handle: '.drag-handle', animation: 150, onEnd: (evt) => {
      const item = builderFields.splice(evt.oldIndex, 1)[0];
      builderFields.splice(evt.newIndex, 0, item);
    }});
  }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

const URL_RE = /^https?:\/\/\S+$/i;
function isUrlValue(v) { return URL_RE.test(String(v || '').trim()); }
function cellHTML(f, raw) {
  const v = String(raw ?? '');
  if (!v) return '<span class="text-gray-400">—</span>';
  // auto-detect: value is URL → link, unless user forced "text"; "link" forces even non-URL
  const asLink = f.is_link === true || (f.is_link !== false && isUrlValue(v));
  if (asLink) {
    const label = esc(f.link_label || 'link');
    return `<a href="${esc(v)}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline">${label}</a>`;
  }
  return esc(v);
}

function getFieldKeys() { return builderFields.map(f => f.key); }


// ── Filters builder ──
function renderFiltersList() {
  const el = document.getElementById('filters-list');
  if (!el) return;
  if (typeof EDIT_DATA !== 'undefined' && EDIT_DATA && !_builderLoaded.filters) {
    _builderLoaded.filters = true;
    const saved = JSON.parse(EDIT_DATA.config_filters || '[]');
    if (saved.length) builderFilters = saved;
  }
  const keys = getFieldKeys();
  el.innerHTML = builderFilters.map((f, i) => `
    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg flex-wrap" data-idx="${i}">
      <span class="cursor-grab text-gray-400 filter-drag-handle">≡</span>
      <select onchange="builderFilters[${i}].field=this.value" class="px-2 py-1.5 border border-gray-300 rounded text-xs">
        <option value="">-- Field --</option>
        ${keys.map(k => `<option value="${k}" ${f.field === k ? 'selected' : ''}>${k}</option>`).join('')}
      </select>
      <select onchange="builderFilters[${i}].type=this.value" class="px-2 py-1.5 border border-gray-300 rounded text-xs">
        <option value="text" ${f.type === 'text' ? 'selected' : ''}>Text Search</option>
        <option value="dropdown" ${f.type === 'dropdown' ? 'selected' : ''}>Dropdown</option>
        <option value="daterange" ${f.type === 'daterange' ? 'selected' : ''}>Date Range</option>
        <option value="number" ${f.type === 'number' ? 'selected' : ''}>Number Range</option>
      </select>
      <input type="text" value="${esc(f.label || '')}" onchange="builderFilters[${i}].label=this.value" class="flex-1 min-w-[120px] px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Label">
      <button onclick="builderFilters.splice(${i},1);renderFiltersList()" class="text-red-500 text-xs">✕</button>
    </div>
  `).join('');
  if (typeof Sortable !== 'undefined') {
    if (_sortableFilters) _sortableFilters.destroy();
    _sortableFilters = Sortable.create(el, { handle: '.filter-drag-handle', animation: 150, onEnd: (evt) => {
      const item = builderFilters.splice(evt.oldIndex, 1)[0];
      builderFilters.splice(evt.newIndex, 0, item);
    }});
  }
}
function addFilter() {
  builderFilters.push({ field: '', type: 'text', label: '' });
  renderFiltersList();
}


// ── Charts builder ──
function renderChartsList() {
  const el = document.getElementById('charts-list');
  if (!el) return;
  if (typeof EDIT_DATA !== 'undefined' && EDIT_DATA && !_builderLoaded.charts) {
    _builderLoaded.charts = true;
    const saved = JSON.parse(EDIT_DATA.config_charts || '[]');
    if (saved.length) builderCharts = saved;
  }
  const keys = getFieldKeys();
  el.innerHTML = builderCharts.map((c, i) => `
    <div class="p-4 bg-gray-50 rounded-lg space-y-3">
      <div class="flex items-center justify-between">
        <span class="font-medium text-sm text-gray-700">Chart ${i+1}</span>
        <button onclick="builderCharts.splice(${i},1);renderChartsList()" class="text-red-500 text-xs">✕ Hapus</button>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div><label class="text-xs text-gray-600">Judul</label>
          <input type="text" value="${esc(c.title||'')}" onchange="builderCharts[${i}].title=this.value" class="w-full px-2 py-1.5 border rounded text-sm"></div>
        <div><label class="text-xs text-gray-600">Tipe</label>
          <select onchange="builderCharts[${i}].type=this.value" class="w-full px-2 py-1.5 border rounded text-sm">
            <option value="bar" ${c.type==='bar'?'selected':''}>Bar</option>
            <option value="line" ${c.type==='line'?'selected':''}>Line</option>
            <option value="pie" ${c.type==='pie'?'selected':''}>Pie</option>
            <option value="doughnut" ${c.type==='doughnut'?'selected':''}>Donut</option>
          </select></div>
        <div><label class="text-xs text-gray-600">Group By (Axis X)</label>
          <select onchange="builderCharts[${i}].x_field=this.value" class="w-full px-2 py-1.5 border rounded text-sm">
            ${keys.map(k=>`<option value="${k}" ${c.x_field===k?'selected':''}>${k}</option>`).join('')}
          </select></div>
        <div><label class="text-xs text-gray-600">Agregasi (Y)</label>
          <select onchange="builderCharts[${i}].y_agg=this.value" class="w-full px-2 py-1.5 border rounded text-sm">
            <option value="count" ${c.y_agg==='count'?'selected':''}>COUNT</option>
            <option value="sum" ${c.y_agg==='sum'?'selected':''}>SUM</option>
            <option value="avg" ${c.y_agg==='avg'?'selected':''}>AVG</option>
          </select></div>
        <div><label class="text-xs text-gray-600">Value Field (untuk SUM/AVG)</label>
          <select onchange="builderCharts[${i}].y_field=this.value" class="w-full px-2 py-1.5 border rounded text-sm">
            <option value="">-- Pilih --</option>
            ${keys.map(k=>`<option value="${k}" ${c.y_field===k?'selected':''}>${k}</option>`).join('')}
          </select></div>
      </div>
    </div>
  `).join('');
}
function addChart() {
  builderCharts.push({title:'',type:'bar',x_field:'',y_agg:'count',y_field:''});
  renderChartsList();
}

// ── Report View: table, filters, charts, export ──
let viewData = [];
let filteredData = [];
let viewPage = 1;
let VIEW_PER_PAGE = 20;
let chartInstances = [];

function initReportView() {
  if (typeof REPORT_DATA === 'undefined') return;
  viewData = REPORT_DATA || [];
  filteredData = [...viewData];
  populateDropdowns();
  renderTable();
  renderCharts();
}

function populateDropdowns() {
  if (typeof REPORT_FILTERS === 'undefined') return;
  document.querySelectorAll('select[data-filter-type="dropdown"]').forEach(sel => {
    const field = sel.dataset.filter;
    const vals = [...new Set(viewData.map(r => r[field]).filter(Boolean))].sort();
    vals.forEach(v => {
      const o = document.createElement('option');
      o.value = v; o.textContent = v;
      sel.appendChild(o);
    });
  });
}

function normDate(s) {
  // convert dd-mm-yyyy or dd/mm/yyyy to yyyy-mm-dd for comparison
  const m = String(s).match(/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/);
  return m ? m[3] + '-' + m[2] + '-' + m[1] : String(s);
}

function applyFilters() {
  filteredData = viewData.filter(row => {
    let pass = true;
    document.querySelectorAll('.filter-input').forEach(inp => {
      const field = inp.dataset.filter;
      const type = inp.dataset.filterType;
      const val = inp.value;
      if (!val) return;
      const cell = String(row[field] || '');
      if (type === 'text') {
        if (!cell.toLowerCase().includes(val.toLowerCase())) pass = false;
      } else if (type === 'dropdown') {
        if (cell !== val) pass = false;
      } else if (type === 'daterange-from') {
        if (normDate(cell) < val) pass = false;
      } else if (type === 'daterange-to') {
        if (normDate(cell) > val) pass = false;
      }
    });
    return pass;
  });
  viewPage = 1;
  renderTable();
  renderCharts();
}

function resetFilters() {
  document.querySelectorAll('.filter-input').forEach(i => {
    if (i.tagName === 'SELECT') i.selectedIndex = 0;
    else i.value = '';
  });
  filteredData = [...viewData];
  viewPage = 1;
  renderTable();
  renderCharts();
}

function renderTable() {
  const fields = (typeof REPORT_FIELDS !== 'undefined' ? REPORT_FIELDS : []).filter(f => f.enabled && !f.missing);
  const thead = document.getElementById('table-head');
  const tbody = document.getElementById('table-body');
  const info = document.getElementById('table-info');
  const pag = document.getElementById('pagination');
  if (!thead || !tbody) return;

  const total = filteredData.length;
  const perPage = VIEW_PER_PAGE || total || 1;
  const pages = Math.ceil(total / perPage) || 1;
  if (viewPage > pages) viewPage = pages;
  const start = (viewPage - 1) * perPage;
  const slice = filteredData.slice(start, start + perPage);

  thead.innerHTML = '<th class="px-4 py-2 text-left text-gray-600 text-xs">#</th>' +
    fields.map(f => `<th class="px-4 py-2 text-left text-gray-600 text-xs whitespace-nowrap">${esc(f.label || f.key)}</th>`).join('') +
    '<th class="px-4 py-2 text-center text-gray-600 text-xs">Aksi</th>';

  tbody.innerHTML = slice.map((row, i) =>
    '<tr class="border-t border-gray-100 hover:bg-gray-50">' +
    `<td class="px-4 py-2 text-gray-400 text-xs">${start + i + 1}</td>` +
    fields.map(f => `<td class="px-4 py-2 text-xs max-w-[200px] truncate" title="${esc(String(row[f.key]??''))}">${cellHTML(f, row[f.key])}</td>`).join('') +
    `<td class="px-4 py-2 text-center"><button onclick="showDetail(${start + i})" class="text-blue-600 hover:text-blue-800 text-sm" title="Lihat Detail">🔍</button></td>` +
    '</tr>'
  ).join('');

  if (info) info.textContent = `📦 ${total} record` + (VIEW_PER_PAGE ? ` (hal ${viewPage}/${pages})` : '');
  if (pag) {
    // Per-page selector
    let h = '<div class="flex items-center gap-2">';
    h += '<span class="text-xs text-gray-500">Tampil:</span>';
    [20, 50, 100, 0].forEach(n => {
      const label = n === 0 ? 'ALL' : n;
      const active = VIEW_PER_PAGE === n;
      h += `<button onclick="changePerPage(${n})" class="px-2 py-1 text-xs rounded border ${active?'bg-blue-600 text-white':'hover:bg-gray-100'}">${label}</button>`;
    });
    h += '</div>';
    // Pagination buttons
    h += '<div class="flex items-center gap-1">';
    if (pages > 1 && VIEW_PER_PAGE > 0) {
      h += `<button onclick="viewPage=1;renderTable()" class="px-2 py-1 text-xs rounded border ${viewPage===1?'opacity-50':'hover:bg-gray-100'}" ${viewPage===1?'disabled':''}>«</button>`;
      h += `<button onclick="viewPage=Math.max(1,viewPage-1);renderTable()" class="px-2 py-1 text-xs rounded border ${viewPage===1?'opacity-50':'hover:bg-gray-100'}" ${viewPage===1?'disabled':''}>‹</button>`;
      const range = 3;
      let s = Math.max(1, viewPage - range), e = Math.min(pages, viewPage + range);
      if (s > 1) h += '<span class="text-gray-400 text-xs">...</span>';
      for (let p = s; p <= e; p++) {
        h += `<button onclick="viewPage=${p};renderTable()" class="px-2 py-1 text-xs rounded border ${p===viewPage?'bg-blue-600 text-white':'hover:bg-gray-100'}">${p}</button>`;
      }
      if (e < pages) h += '<span class="text-gray-400 text-xs">...</span>';
      h += `<button onclick="viewPage=Math.min(${pages},viewPage+1);renderTable()" class="px-2 py-1 text-xs rounded border ${viewPage===pages?'opacity-50':'hover:bg-gray-100'}" ${viewPage===pages?'disabled':''}>›</button>`;
      h += `<button onclick="viewPage=${pages};renderTable()" class="px-2 py-1 text-xs rounded border ${viewPage===pages?'opacity-50':'hover:bg-gray-100'}" ${viewPage===pages?'disabled':''}>»</button>`;
    }
    h += '</div>';
    pag.innerHTML = h;
  }
}

function changePerPage(n) {
  VIEW_PER_PAGE = n;
  viewPage = 1;
  renderTable();
}

function showDetail(idx) {
  const row = filteredData[idx];
  if (!row) return;
  const fields = (typeof REPORT_FIELDS !== 'undefined' ? REPORT_FIELDS : []).filter(f => f.enabled && !f.missing);
  const body = document.getElementById('detail-body');
  if (!body) return;
  body.innerHTML = fields.map(f => {
    const val = String(row[f.key] ?? '');
    return `<div>
      <label class="block text-xs font-medium text-gray-500 mb-1">${esc(f.label || f.key)}</label>
      <div class="bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-800 whitespace-pre-wrap break-words">${cellHTML(f, val)}</div>
    </div>`;
  }).join('');
  document.getElementById('detail-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeDetail() {
  document.getElementById('detail-modal')?.classList.add('hidden');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });

function renderCharts() {
  if (typeof REPORT_CHARTS === 'undefined' || !REPORT_CHARTS.length) return;
  chartInstances.forEach(c => c.destroy());
  chartInstances = [];
  const colors = ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'];
  REPORT_CHARTS.forEach((ch, ci) => {
    const canvas = document.getElementById('chart-' + ci);
    if (!canvas) return;
    const groups = {};
    const counts = {};
    filteredData.forEach(row => {
      const key = String(row[ch.x_field] || 'Lainnya');
      if (!groups[key]) { groups[key] = 0; counts[key] = 0; }
      counts[key]++;
      if (ch.y_agg === 'count') groups[key]++;
      else groups[key] += parseFloat(row[ch.y_field] || 0) || 0;
    });
    const labels = Object.keys(groups);
    const data = Object.values(groups);
    if (ch.y_agg === 'avg') labels.forEach((l, i) => { data[i] = data[i] / (counts[l] || 1); });
    const bgColors = labels.map((_, i) => colors[i % colors.length]);
    chartInstances.push(new Chart(canvas, {
      type: ch.type || 'bar',
      data: { labels, datasets: [{ label: ch.title || '', data, backgroundColor: bgColors, borderColor: bgColors, borderWidth: 1 }] },
      options: { responsive: true, plugins: { legend: { display: ch.type === 'pie' || ch.type === 'doughnut' } } }
    }));
  });
}

function getExportMeta() {
  const fields = (typeof REPORT_FIELDS !== 'undefined' ? REPORT_FIELDS : []).filter(f => f.enabled && !f.missing);
  const name = typeof REPORT !== 'undefined' ? REPORT.name : 'export';
  return { fields, name };
}

function logExport(format, reportName) {
  fetch('api/handler.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'log_export',format,report_name:reportName}) }).catch(()=>{});
}

function exportCSV() {
  const { fields, name } = getExportMeta();
  logExport('csv', name);
  const header = fields.map(f => f.label || f.key);
  const rows = filteredData.map(row => fields.map(f => '"' + String(row[f.key] ?? '').replace(/"/g, '""') + '"'));
  const csv = [header.join(','), ...rows.map(r => r.join(','))].join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = name + '.csv';
  a.click();
}

function exportExcel() {
  const { fields, name } = getExportMeta();
  logExport('excel', name);
  const header = fields.map(f => f.label || f.key);
  const aoa = [header, ...filteredData.map(row => fields.map(f => row[f.key] ?? ''))];
  const ws = XLSX.utils.aoa_to_sheet(aoa);
  // Auto-width columns
  ws['!cols'] = header.map((h, i) => {
    let max = h.length;
    aoa.slice(1).forEach(r => { const l = String(r[i] ?? '').length; if (l > max) max = l; });
    return { wch: Math.min(max + 2, 50) };
  });
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Data');
  XLSX.writeFile(wb, name + '.xlsx');
}

function exportPDF() {
  const { fields, name } = getExportMeta();
  logExport('pdf', name);
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: fields.length > 5 ? 'l' : 'p', unit: 'mm', format: 'a4' });

  // Title
  doc.setFontSize(14);
  doc.text(name, 14, 15);
  doc.setFontSize(8);
  doc.setTextColor(120);
  doc.text('Diekspor: ' + new Date().toLocaleString('id-ID') + ' | ' + filteredData.length + ' record', 14, 21);
  doc.setTextColor(0);

  // Table
  doc.autoTable({
    startY: 26,
    head: [fields.map(f => f.label || f.key)],
    body: filteredData.map(row => fields.map(f => String(row[f.key] ?? ''))),
    styles: { fontSize: 7, cellPadding: 1.5 },
    headStyles: { fillColor: [37, 99, 235], textColor: 255, fontSize: 7 },
    alternateRowStyles: { fillColor: [248, 250, 252] },
    margin: { left: 10, right: 10 },
    didDrawPage: (data) => {
      // Footer
      doc.setFontSize(7);
      doc.setTextColor(160);
      doc.text('Halaman ' + doc.internal.getNumberOfPages(), data.settings.margin.left, doc.internal.pageSize.height - 5);
      doc.text(name, doc.internal.pageSize.width - data.settings.margin.right, doc.internal.pageSize.height - 5, { align: 'right' });
    }
  });

  doc.save(name + '.pdf');
}

// ── Init on page load ──
document.addEventListener('DOMContentLoaded', () => {
  initReportView();
  // Handle connection form submit via AJAX
  const connForm = document.querySelector('form[action="api/handler.php"]');
  if (connForm) {
    connForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(connForm);
      const res = await api(fd.get('action'), Object.fromEntries(fd));
      if (res.ok && res.redirect) window.location = res.redirect;
      else if (res.error) alert(res.error);
    });
  }
});

// ── Save report ──
async function saveReport() {
  const name = document.getElementById('b-name')?.value;
  const connRadio = document.querySelector('input[name="b-conn"]:checked');
  if (!name) { alert('Nama laporan wajib diisi'); return; }
  if (!connRadio) { alert('Pilih koneksi'); return; }
  const data = {
    id: (typeof EDIT_DATA !== 'undefined' && EDIT_DATA) ? EDIT_DATA.id : 0,
    name,
    description: document.getElementById('b-desc')?.value || '',
    icon: document.getElementById('b-icon')?.value || '📋',
    connection_id: connRadio.value,
    sort_order: parseInt(document.getElementById('b-sort-order')?.value || '0', 10),
    menu_id: parseInt(document.getElementById('b-menu-id')?.value || '0', 10),
    fields: builderFields,
    filters: builderFilters,
    charts: builderCharts,
    override_params: document.getElementById('b-params')?.value || ''
  };
  const res = await api('save_report', data);
  if (res.ok && res.redirect) window.location = res.redirect;
  else alert(res.error || 'Gagal menyimpan');
}
