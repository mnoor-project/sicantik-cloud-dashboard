<?php
$reportList = $db->query("SELECT r.*,c.name as conn_name,(SELECT record_count FROM data_cache WHERE connection_id=r.connection_id ORDER BY synced_at DESC LIMIT 1) as rec_count FROM reports r LEFT JOIN connections c ON c.id=r.connection_id ORDER BY r.sort_order,r.name")->fetchAll();
// Filter by access
if ($user['role'] !== 'admin') {
    $allowed = getUserAllowedReports($user['id']);
    if ($allowed !== null) {
        $reportList = array_values(array_filter($reportList, fn($r) => in_array($r['id'], $allowed)));
    }
}
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">📋 Semua Laporan</h2>

<?php if(in_array($user['role'],['admin','editor'])): ?>
<div class="mb-4">
  <a href="?page=builder" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm inline-block">＋ Buat Laporan Baru</a>
</div>
<?php endif; ?>

<?php if(empty($reportList)): ?>
<div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Belum ada laporan.</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
  <?php foreach($reportList as $r): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition p-5">
    <div class="flex items-start justify-between mb-3">
      <div>
        <h3 class="font-semibold text-gray-800"><?=e($r['icon'])?> <?=e($r['name'])?></h3>
        <p class="text-xs text-gray-500 mt-1"><?=e($r['description'])?></p>
      </div>
      <?php if(in_array($user['role'],['admin','editor'])): ?>
      <div class="flex gap-1">
        <a href="?page=builder&edit=<?=$r['id']?>" class="text-gray-400 hover:text-blue-600 text-xs">✏️</a>
        <button onclick="deleteReport(<?=$r['id']?>)" class="text-gray-400 hover:text-red-600 text-xs">🗑</button>
      </div>
      <?php endif; ?>
    </div>
    <div class="flex items-center justify-between text-xs text-gray-500">
      <span>📦 <?=number_format($r['rec_count']??0)?> record</span>
      <span>🔌 <?=e($r['conn_name']??'-')?></span>
    </div>
    <a href="?page=view&id=<?=$r['id']?>" class="mt-3 block text-center bg-blue-50 text-blue-600 py-2 rounded-lg hover:bg-blue-100 transition text-sm font-medium">Buka Laporan →</a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
