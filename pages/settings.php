<?php
requireRole($user, 'admin');
$allReports = $db->query("SELECT id, name, icon, sort_order FROM reports ORDER BY sort_order, name")->fetchAll();
$menus = getMenus();
$activeTab = $_GET['tab'] ?? 'identity';
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">⚙️ Pengaturan</h2>

<!-- Tabs -->
<div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200">
  <?php
  $tabs = [
    'identity' => '🏛️ Identitas',
    'sort'     => '📑 Urutan Menu',
    'menus'    => '🗂 Kelola Menu',
    'autosync' => '🔄 Auto Sync',
    'api'      => '🔑 API',
    'system'   => 'ℹ️ Sistem',
  ];
  foreach ($tabs as $key => $label): ?>
  <button onclick="switchTab('<?=$key?>')" data-tab-btn="<?=$key?>"
    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors <?=$key===$activeTab?'border-blue-600 text-blue-600':'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'?>">
    <?=$label?>
  </button>
  <?php endforeach; ?>
</div>

<!-- Tab: Identitas -->
<div data-tab="identity" class="<?=$activeTab!=='identity'?'hidden':''?>">
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg mb-6">
  <h3 class="font-semibold text-gray-700 mb-4">🏛️ Identitas Website</h3>
  <div class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nama Website</label>
      <input type="text" id="set-title" value="<?=e(getAppTitle())?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="SiCantik Dashboard">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
      <input type="text" id="set-subtitle" value="<?=e(getAppSubtitle())?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="DPMPTSP Kab. Kotim">
    </div>
  </div>
  <button onclick="saveIdentity()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">💾 Simpan</button>
  <span id="identity-msg" class="ml-3 text-sm hidden"></span>
</div>
</div>

<!-- Tab: Urutan Menu -->
<div data-tab="sort" class="<?=$activeTab!=='sort'?'hidden':''?>">
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg mb-6">
  <h3 class="font-semibold text-gray-700 mb-4">📑 Urutan Menu Laporan</h3>
  <p class="text-xs text-gray-500 mb-3">Angka kecil tampil lebih atas. Laporan dengan urutan sama diurutkan berdasarkan nama.</p>
  <div class="space-y-2" id="sort-list">
    <?php foreach ($allReports as $r): ?>
    <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
      <input type="number" value="<?=$r['sort_order']?>" data-report-id="<?=$r['id']?>" class="sort-input w-16 px-2 py-1 border border-gray-300 rounded text-sm text-center" min="0" max="999">
      <span class="text-sm"><?=e($r['icon'])?> <?=e($r['name'])?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (empty($allReports)): ?>
  <p class="text-sm text-gray-400">Belum ada laporan.</p>
  <?php else: ?>
  <button onclick="saveMenuOrder()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">💾 Simpan Urutan</button>
  <span id="sort-msg" class="ml-3 text-sm hidden"></span>
  <?php endif; ?>
</div>
</div>

<!-- Tab: Kelola Menu -->
<div data-tab="menus" class="<?=$activeTab!=='menus'?'hidden':''?>">
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg mb-6">
  <h3 class="font-semibold text-gray-700 mb-4">🗂 Kelola Menu Laporan</h3>
  <p class="text-xs text-gray-500 mb-3">Kelompok laporan di sidebar. Menu default tidak bisa dihapus.</p>
  <div class="space-y-2" id="menu-list">
    <?php foreach ($menus as $m): ?>
    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg flex-wrap">
      <input type="number" value="<?=$m['sort_order']?>" data-menu-id="<?=$m['id']?>" class="menu-sort w-16 px-2 py-1 border border-gray-300 rounded text-sm text-center" min="0" max="999">
      <input type="text" value="<?=e($m['name'])?>" data-menu-id="<?=$m['id']?>" class="menu-name flex-1 min-w-[140px] px-2 py-1 border border-gray-300 rounded text-sm">
      <?php if ($m['id'] !== getDefaultMenuId()): ?>
      <button onclick="deleteMenuItem(<?=$m['id']?>)" class="text-red-500 hover:text-red-700 text-xs" title="Hapus">🗑</button>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="flex items-center gap-2 mt-3">
    <input type="text" id="new-menu-name" placeholder="Nama menu baru" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
    <button onclick="addMenuItem()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">＋ Tambah</button>
  </div>
  <button onclick="saveMenus()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">💾 Simpan Menu</button>
  <span id="menu-msg" class="ml-3 text-sm hidden"></span>
</div>
</div>

<!-- Tab: Auto Sync -->
<div data-tab="autosync" class="<?=$activeTab!=='autosync'?'hidden':''?>">
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg mb-6">
  <h3 class="font-semibold text-gray-700 mb-4">🔄 Auto Sync</h3>
  <?php
  $asEnabled  = getSetting('auto_sync_enabled', 'off');
  $asMode     = getSetting('auto_sync_mode', 'interval');
  $asInterval = getSetting('auto_sync_interval', '60');
  $asDailyTime = getSetting('auto_sync_daily_time', '06:00');
  $asLastRun  = getSetting('auto_sync_last_run', '');
  ?>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm font-medium text-gray-700">Status Auto Sync</div>
        <div class="text-xs text-gray-500">Aktifkan untuk sync otomatis semua koneksi</div>
      </div>
      <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" id="as-enabled" class="sr-only peer" <?=$asEnabled==='on'?'checked':''?>>
        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
      </label>
    </div>
    <div>
      <div class="text-sm font-medium text-gray-700 mb-2">Mode</div>
      <div class="flex gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="as-mode" value="interval" <?=$asMode==='interval'?'checked':''?> onchange="toggleSyncMode()" class="text-blue-600 focus:ring-blue-500">
          <span class="text-sm text-gray-600">Per Interval</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="as-mode" value="daily" <?=$asMode==='daily'?'checked':''?> onchange="toggleSyncMode()" class="text-blue-600 focus:ring-blue-500">
          <span class="text-sm text-gray-600">Harian</span>
        </label>
      </div>
    </div>
    <div id="as-interval-group" class="<?=$asMode!=='interval'?'hidden':''?>">
      <label class="block text-sm font-medium text-gray-700 mb-1">Interval (menit)</label>
      <select id="as-interval" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <?php foreach ([15,30,60,120,360,720,1440] as $m): ?>
        <option value="<?=$m?>" <?=$asInterval==$m?'selected':''?>><?=$m < 60 ? $m.' menit' : ($m/60).' jam'?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="as-daily-group" class="<?=$asMode!=='daily'?'hidden':''?>">
      <label class="block text-sm font-medium text-gray-700 mb-1">Jam Sync</label>
      <select id="as-daily-time" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <?php for ($h = 0; $h < 24; $h++): $t = sprintf('%02d:00', $h); ?>
        <option value="<?=$t?>" <?=$asDailyTime===$t?'selected':''?>><?=$t?></option>
        <?php endfor; ?>
      </select>
    </div>
    <?php if ($asLastRun): ?>
    <div class="text-xs text-gray-500 bg-gray-50 rounded-lg p-3">
      ⏱ Terakhir auto sync: <strong><?=e($asLastRun)?></strong>
    </div>
    <?php endif; ?>
  </div>
  <button onclick="saveAutoSync()" class="mt-5 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">💾 Simpan</button>
  <span id="autosync-msg" class="ml-3 text-sm hidden"></span>
</div>
</div>

<!-- Tab: API -->
<div data-tab="api" class="<?=$activeTab!=='api'?'hidden':''?>">
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg mb-6">
  <h3 class="font-semibold text-gray-700 mb-4">🔑 API Token</h3>
  <p class="text-xs text-gray-500 mb-4">Token untuk mengakses data dari sistem eksternal (Hermes, OpenClaw, Laravel, dll).</p>
  <?php $apiToken = getSetting('api_token', ''); ?>
  <div class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Token</label>
      <div class="flex gap-2">
        <input type="text" id="api-token" value="<?=e($apiToken)?>" readonly class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 font-mono text-xs" placeholder="Belum di-generate">
        <button onclick="copyToken()" class="bg-gray-100 text-gray-600 px-3 py-2 rounded-lg hover:bg-gray-200 text-sm" title="Copy">📋</button>
        <button onclick="generateToken()" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm">🔄 Generate</button>
      </div>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <div class="text-sm font-medium text-gray-700 mb-2">Endpoint</div>
      <div class="space-y-2 text-xs font-mono text-gray-600">
        <div class="bg-white rounded p-2 border border-gray-200">
          <span class="text-green-600 font-semibold">GET</span> /api/public.php?action=api_list_reports
        </div>
        <div class="bg-white rounded p-2 border border-gray-200">
          <span class="text-green-600 font-semibold">GET</span> /api/public.php?action=api_report_data&amp;report_id=<span class="text-blue-600">{id}</span>
        </div>
      </div>
      <div class="mt-3 text-xs text-gray-500">
        Header: <code class="bg-white px-1.5 py-0.5 rounded border text-gray-700">Authorization: Bearer {token}</code>
      </div>
    </div>
  </div>
</div>
</div>

<div data-tab="system" class="<?=$activeTab!=='system'?'hidden':''?>">
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg">
  <h3 class="font-semibold text-gray-700 mb-2">Sistem</h3>
  <div class="text-sm text-gray-600 space-y-1">
    <p>PHP: <?=phpversion()?></p>
    <p>SQLite: <?=getDB()->query("SELECT sqlite_version()")->fetchColumn()?></p>
    <p>DB Size: <?=file_exists(DB_FILE)?round(filesize(DB_FILE)/1024).' KB':'N/A'?></p>
    <p>App: v<?=APP_VERSION?></p>
  </div>
</div>
</div>

<script>
function switchTab(key) {
  document.querySelectorAll('[data-tab]').forEach(el => el.classList.add('hidden'));
  document.querySelectorAll('[data-tab-btn]').forEach(btn => {
    btn.classList.remove('border-blue-600', 'text-blue-600');
    btn.classList.add('border-transparent', 'text-gray-500');
  });
  const panel = document.querySelector('[data-tab="'+key+'"]');
  const btn = document.querySelector('[data-tab-btn="'+key+'"]');
  if (panel) panel.classList.remove('hidden');
  if (btn) {
    btn.classList.add('border-blue-600', 'text-blue-600');
    btn.classList.remove('border-transparent', 'text-gray-500');
  }
  const url = new URL(window.location);
  url.searchParams.set('tab', key);
  history.replaceState(null, '', url);
}

function showMsg(id, ok) {
  const msg = document.getElementById(id);
  msg.classList.remove('hidden');
  msg.className = 'ml-3 text-sm ' + (ok ? 'text-green-600' : 'text-red-600');
  msg.textContent = ok ? '✅ Tersimpan' : '❌ Gagal';
  if (ok) setTimeout(() => location.reload(), 600);
}

async function saveIdentity() {
  const title = document.getElementById('set-title').value.trim();
  const subtitle = document.getElementById('set-subtitle').value.trim();
  if (!title) { alert('Nama website tidak boleh kosong'); return; }
  const res = await api('save_settings', { settings: { app_title: title, app_subtitle: subtitle } });
  showMsg('identity-msg', res.ok);
}

async function saveMenuOrder() {
  const items = [];
  document.querySelectorAll('.sort-input').forEach(inp => {
    items.push({ id: parseInt(inp.dataset.reportId), sort_order: parseInt(inp.value) || 0 });
  });
  const res = await api('reorder_reports', { items });
  showMsg('sort-msg', res.ok);
}

async function addMenuItem() {
  const name = document.getElementById('new-menu-name').value.trim();
  if (!name) { alert('Nama menu wajib diisi'); return; }
  const res = await api('save_menu', { id: 0, name, sort_order: 0 });
  if (res.ok) location.reload(); else alert(res.error || 'Gagal');
}

async function deleteMenuItem(id) {
  if (!confirm('Hapus menu ini? Laporan di dalamnya akan dipindah ke menu default.')) return;
  const res = await api('delete_menu', { id });
  if (res.ok) location.reload(); else alert(res.error || 'Gagal');
}

async function saveMenus() {
  const updates = [];
  document.querySelectorAll('.menu-name').forEach(inp => {
    updates.push({ id: parseInt(inp.dataset.menuId), name: inp.value.trim() });
  });
  const orderItems = [];
  document.querySelectorAll('.menu-sort').forEach(inp => {
    orderItems.push({ id: parseInt(inp.dataset.menuId), sort_order: parseInt(inp.value) || 0 });
  });
  let ok = true;
  for (const u of updates) {
    if (!u.name) continue;
    const res = await api('save_menu', { id: u.id, name: u.name, sort_order: 0 });
    if (!res.ok) ok = false;
  }
  const resOrder = await api('reorder_menus', { items: orderItems });
  if (!resOrder.ok) ok = false;
  showMsg('menu-msg', ok);
}

function toggleSyncMode() {
  const mode = document.querySelector('input[name="as-mode"]:checked')?.value;
  document.getElementById('as-interval-group').classList.toggle('hidden', mode !== 'interval');
  document.getElementById('as-daily-group').classList.toggle('hidden', mode !== 'daily');
}

async function saveAutoSync() {
  const enabled = document.getElementById('as-enabled').checked ? 'on' : 'off';
  const mode = document.querySelector('input[name="as-mode"]:checked')?.value || 'interval';
  const interval = document.getElementById('as-interval').value;
  const dailyTime = document.getElementById('as-daily-time').value;
  const res = await api('save_settings', {
    settings: { auto_sync_enabled: enabled, auto_sync_mode: mode, auto_sync_interval: interval, auto_sync_daily_time: dailyTime }
  });
  showMsg('autosync-msg', res.ok);
}

function copyToken() {
  const inp = document.getElementById('api-token');
  if (!inp.value) { alert('Belum ada token'); return; }
  navigator.clipboard.writeText(inp.value);
  alert('Token disalin!');
}

async function generateToken() {
  if (!confirm('Generate token baru? Token lama akan tidak berlaku.')) return;
  const res = await api('generate_api_token', {});
  if (res.ok) {
    document.getElementById('api-token').value = res.token;
    alert('Token baru berhasil di-generate');
  } else alert(res.error || 'Gagal');
}
</script>
