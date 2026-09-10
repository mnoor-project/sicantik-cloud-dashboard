<?php
$rid = intval($_GET['id'] ?? 0);
if (!$rid) { echo '<p class="text-red-600">Laporan tidak ditemukan.</p>'; return; }
if (!canAccessReport($user, $rid)) { echo '<div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center"><p class="text-red-600 font-semibold">⛔ Anda tidak memiliki akses ke laporan ini.</p><a href="?page=home" class="text-blue-600 hover:underline text-sm mt-2 inline-block">← Kembali ke Home</a></div>'; return; }
$st = $db->prepare("SELECT r.*,c.name as conn_name,c.sql_reference FROM reports r LEFT JOIN connections c ON c.id=r.connection_id WHERE r.id=?");
$st->execute([$rid]);
$report = $st->fetch();
if (!$report) { echo '<p class="text-red-600">Laporan tidak ditemukan.</p>'; return; }

$fields = json_decode($report['config_fields'] ?: '[]', true);
$filters = json_decode($report['config_filters'] ?: '[]', true);
$charts = json_decode($report['config_charts'] ?: '[]', true);
$data = getCachedData($report['connection_id']);
$lastSync = getLastSync($report['connection_id']);
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
  <div>
    <h2 class="text-2xl font-bold text-gray-800"><?=e($report['icon'])?> <?=e($report['name'])?></h2>
    <p class="text-sm text-gray-500"><?=e($report['description'])?> • 🔄 <?=$lastSync?e($lastSync['synced_at']):'Belum sync'?></p>
  </div>
  <div class="flex gap-2">
    <button onclick="syncReport(<?=$report['connection_id']?>)" class="bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100 text-sm">🔄 Sync</button>
    <?php if(in_array($user['role'],['admin','editor'])): ?>
    <a href="?page=builder&edit=<?=$report['id']?>" class="bg-gray-50 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-100 text-sm">✏️ Edit</a>
    <?php endif; ?>
    <button onclick="exportCSV()" class="bg-green-50 text-green-600 px-3 py-1.5 rounded-lg hover:bg-green-100 text-sm">📥 CSV</button>
    <button onclick="exportExcel()" class="bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg hover:bg-emerald-100 text-sm">📗 Excel</button>
    <button onclick="exportPDF()" class="bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 text-sm">📕 PDF</button>
    <button onclick="window.print()" class="bg-gray-50 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-100 text-sm">🖨️</button>
  </div>
</div>

<!-- Filters -->
<?php if(!empty($filters)): ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6" id="filter-bar">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <?php foreach($filters as $f): ?>
    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1"><?=e($f['label']??$f['field'])?></label>
      <?php if($f['type']==='daterange'): ?>
      <div class="flex gap-1">
        <input type="date" data-filter="<?=e($f['field'])?>" data-filter-type="daterange-from" class="filter-input w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
        <input type="date" data-filter="<?=e($f['field'])?>" data-filter-type="daterange-to" class="filter-input w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
      </div>
      <?php elseif($f['type']==='dropdown'): ?>
      <select data-filter="<?=e($f['field'])?>" data-filter-type="dropdown" class="filter-input w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
        <option value="">Semua</option>
      </select>
      <?php else: ?>
      <input type="text" data-filter="<?=e($f['field'])?>" data-filter-type="text" class="filter-input w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Cari...">
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3 flex gap-2">
    <button onclick="applyFilters()" class="bg-blue-600 text-white px-4 py-1.5 rounded text-xs hover:bg-blue-700">🔍 Terapkan</button>
    <button onclick="resetFilters()" class="bg-gray-100 text-gray-600 px-4 py-1.5 rounded text-xs hover:bg-gray-200">↺ Reset</button>
  </div>
</div>
<?php endif; ?>

<!-- Charts -->
<?php if(!empty($charts)): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6" id="charts-area">
  <?php foreach($charts as $ci=>$ch): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <h4 class="font-semibold text-gray-700 text-sm mb-3"><?=e($ch['title']??'Chart')?></h4>
    <canvas id="chart-<?=$ci?>" height="200"></canvas>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Data table -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
  <div class="p-4 border-b border-gray-200 flex items-center justify-between">
    <span class="font-semibold text-gray-700" id="table-info">📦 <?=count($data)?> record</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm" id="data-table">
      <thead class="bg-gray-50"><tr id="table-head"></tr></thead>
      <tbody id="table-body"></tbody>
    </table>
  </div>
  <div class="p-4 flex items-center justify-between border-t border-gray-100" id="pagination"></div>
</div>

<!-- SQL Reference -->
<?php if($report['sql_reference']): ?>
<details class="mt-4">
  <summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700">▶ SQL Referensi</summary>
  <pre class="mt-2 bg-gray-900 text-green-400 p-4 rounded-xl text-xs overflow-x-auto"><?=e($report['sql_reference'])?></pre>
</details>
<?php endif; ?>

<!-- Detail Modal -->
<div id="detail-modal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/50" onclick="closeDetail()"></div>
  <div class="absolute inset-4 sm:inset-auto sm:top-1/2 sm:left-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:w-full sm:max-w-lg bg-white rounded-xl shadow-2xl flex flex-col max-h-[90vh]">
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
      <h3 class="font-semibold text-gray-800">📋 Detail Record</h3>
      <button onclick="closeDetail()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
    </div>
    <div id="detail-body" class="p-4 overflow-y-auto space-y-3"></div>
    <div class="p-4 border-t border-gray-100 flex justify-end">
      <button onclick="closeDetail()" class="bg-gray-100 text-gray-700 px-4 py-1.5 rounded-lg hover:bg-gray-200 text-sm">✕ Tutup</button>
    </div>
  </div>
</div>

<script>
const REPORT = <?=json_encode($report)?>;
const REPORT_FIELDS = <?=json_encode($fields)?>;
const REPORT_FILTERS = <?=json_encode($filters)?>;
const REPORT_CHARTS = <?=json_encode($charts)?>;
const REPORT_DATA = <?=json_encode($data)?>;
</script>
