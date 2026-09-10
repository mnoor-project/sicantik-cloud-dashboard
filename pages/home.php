<?php
$totalReports = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$totalConns = $db->query("SELECT COUNT(*) FROM connections")->fetchColumn();
$totalRecords = $db->query("SELECT COALESCE(SUM(record_count),0) FROM data_cache")->fetchColumn();
$lastSyncRow = $db->query("SELECT synced_at FROM sync_log ORDER BY synced_at DESC LIMIT 1")->fetch();
$lastSync = $lastSyncRow ? $lastSyncRow['synced_at'] : 'Belum pernah';
$reportList = $db->query("SELECT r.*,c.name as conn_name,(SELECT record_count FROM data_cache WHERE connection_id=r.connection_id ORDER BY synced_at DESC LIMIT 1) as rec_count FROM reports r LEFT JOIN connections c ON c.id=r.connection_id ORDER BY r.sort_order,r.name")->fetchAll();
// Filter by access
if ($user['role'] !== 'admin') {
    $allowed = getUserAllowedReports($user['id']);
    if ($allowed !== null) {
        $reportList = array_values(array_filter($reportList, fn($r) => in_array($r['id'], $allowed)));
    }
}
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">📊 Dashboard Overview</h2>

<!-- Stats cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
  <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
    <div class="text-2xl font-bold text-blue-600"><?=$totalReports?></div>
    <div class="text-sm text-gray-500">📋 Laporan</div>
  </div>
  <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
    <div class="text-2xl font-bold text-green-600"><?=$totalConns?></div>
    <div class="text-sm text-gray-500">🔌 Koneksi</div>
  </div>
  <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
    <div class="text-2xl font-bold text-purple-600"><?=number_format($totalRecords)?></div>
    <div class="text-sm text-gray-500">📦 Record</div>
  </div>
  <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
    <div class="text-sm font-medium text-gray-700"><?=e($lastSync)?></div>
    <div class="text-sm text-gray-500">🔄 Last Sync</div>
  </div>
</div>

<!-- Reports table -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
  <div class="p-4 border-b border-gray-200 font-semibold text-gray-700">Laporan Terbaru</div>
  <?php if(empty($reportList)): ?>
  <div class="p-8 text-center text-gray-400">Belum ada laporan. <?php if(in_array($user['role'],['admin','editor'])): ?><a href="?page=builder" class="text-blue-600 hover:underline">Buat laporan pertama →</a><?php endif; ?></div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50"><tr>
        <th class="px-4 py-2 text-left text-gray-600">#</th>
        <th class="px-4 py-2 text-left text-gray-600">Nama</th>
        <th class="px-4 py-2 text-left text-gray-600">Sumber</th>
        <th class="px-4 py-2 text-left text-gray-600">Record</th>
        <th class="px-4 py-2 text-left text-gray-600">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($reportList as $i=>$r): ?>
      <tr class="border-t border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-2 text-gray-400"><?=$i+1?></td>
        <td class="px-4 py-2 font-medium"><?=e($r['icon'])?> <?=e($r['name'])?></td>
        <td class="px-4 py-2 text-gray-500"><?=e($r['conn_name']??'-')?></td>
        <td class="px-4 py-2"><?=number_format($r['rec_count']??0)?></td>
        <td class="px-4 py-2"><a href="?page=view&id=<?=$r['id']?>" class="text-blue-600 hover:underline text-xs">Buka →</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
