<?php
requireRole($user, 'admin', 'editor');
$conns = $db->query("SELECT c.*,(SELECT synced_at FROM sync_log WHERE connection_id=c.id ORDER BY synced_at DESC LIMIT 1) as last_sync,(SELECT record_count FROM data_cache WHERE connection_id=c.id ORDER BY synced_at DESC LIMIT 1) as cached_records FROM connections c ORDER BY c.name")->fetchAll();
$logs = $db->query("SELECT l.*,c.name as conn_name FROM sync_log l LEFT JOIN connections c ON c.id=l.connection_id ORDER BY l.synced_at DESC LIMIT 20")->fetchAll();
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">🔄 Sinkronisasi Data</h2>

<!-- Connections sync status -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
  <div class="p-4 border-b border-gray-200 flex items-center justify-between">
    <span class="font-semibold text-gray-700">Status Koneksi</span>
    <button onclick="syncAll()" id="btn-sync-all" class="bg-blue-600 text-white px-4 py-1.5 rounded-lg hover:bg-blue-700 transition text-sm">🔄 Sync Semua</button>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50"><tr>
        <th class="px-4 py-2 text-left text-gray-600">Koneksi</th>
        <th class="px-4 py-2 text-left text-gray-600">Record</th>
        <th class="px-4 py-2 text-left text-gray-600">Terakhir Sync</th>
        <th class="px-4 py-2 text-left text-gray-600">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($conns as $c): ?>
      <tr class="border-t border-gray-100 hover:bg-gray-50" id="sync-row-<?=$c['id']?>" data-conn-id="<?=$c['id']?>">
        <td class="px-4 py-2 font-medium"><?=e($c['name'])?></td>
        <td class="px-4 py-2"><?=number_format($c['cached_records']??0)?></td>
        <td class="px-4 py-2 text-xs text-gray-500 sync-status"><?=e($c['last_sync']??'Belum pernah')?></td>
        <td class="px-4 py-2">
          <button onclick="syncOne(<?=$c['id']?>,this)" class="bg-blue-50 text-blue-600 px-3 py-1 rounded hover:bg-blue-100 transition text-xs">🔄 Sync</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Sync log -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
  <div class="p-4 border-b border-gray-200 font-semibold text-gray-700">Log Sync</div>
  <?php if(empty($logs)): ?>
  <div class="p-8 text-center text-gray-400">Belum ada log sync.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50"><tr>
        <th class="px-4 py-2 text-left text-gray-600">Waktu</th>
        <th class="px-4 py-2 text-left text-gray-600">Koneksi</th>
        <th class="px-4 py-2 text-left text-gray-600">Status</th>
        <th class="px-4 py-2 text-left text-gray-600">Record</th>
        <th class="px-4 py-2 text-left text-gray-600">Durasi</th>
        <th class="px-4 py-2 text-left text-gray-600">Error</th>
      </tr></thead>
      <tbody>
      <?php foreach($logs as $l): ?>
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2 text-xs text-gray-500"><?=e($l['synced_at'])?></td>
        <td class="px-4 py-2"><?=e($l['conn_name']??'-')?></td>
        <td class="px-4 py-2"><?=$l['status']==='ok'?'<span class="text-green-600 font-medium">✅ OK</span>':'<span class="text-red-600 font-medium">❌ Gagal</span>'?></td>
        <td class="px-4 py-2"><?=$l['record_count']?></td>
        <td class="px-4 py-2"><?=$l['duration']?>s</td>
        <td class="px-4 py-2 text-xs text-red-500"><?=e($l['error_message'])?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
