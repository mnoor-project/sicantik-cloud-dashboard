<?php
requireRole($user, 'admin');
$db = getDB();

$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterDate = $_GET['date'] ?? '';
$perPage = 50;
$page_num = max(1, intval($_GET['p'] ?? 1));
$offset = ($page_num - 1) * $perPage;

$where = [];
$params = [];
if ($filterUser) { $where[] = "username LIKE ?"; $params[] = "%$filterUser%"; }
if ($filterAction) { $where[] = "action = ?"; $params[] = $filterAction; }
if ($filterDate) { $where[] = "DATE(created_at) = ?"; $params[] = $filterDate; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$total = $db->prepare("SELECT COUNT(*) FROM audit_log $whereSQL");
$total->execute($params);
$totalCount = $total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$st = $db->prepare("SELECT * FROM audit_log $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$st->execute($params);
$logs = $st->fetchAll();

$actions = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="flex items-center justify-between mb-6">
  <div>
    <h2 class="text-xl font-bold text-gray-800">📋 Log Aktivitas</h2>
    <p class="text-sm text-gray-500"><?=$totalCount?> total log</p>
  </div>
  <button onclick="if(confirm('Hapus semua log?')) clearLogs()" class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100">🗑 Hapus Semua</button>
</div>

<form method="get" class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
  <input type="hidden" name="page" value="audit">
  <div>
    <label class="block text-xs text-gray-500 mb-1">User</label>
    <input type="text" name="user" value="<?=e($filterUser)?>" placeholder="Cari user..." class="border rounded-lg px-3 py-1.5 text-sm w-40">
  </div>
  <div>
    <label class="block text-xs text-gray-500 mb-1">Aksi</label>
    <select name="action" class="border rounded-lg px-3 py-1.5 text-sm">
      <option value="">Semua</option>
      <?php foreach ($actions as $a): ?>
      <option value="<?=e($a)?>" <?=$filterAction===$a?'selected':''?>><?=e($a)?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
    <input type="date" name="date" value="<?=e($filterDate)?>" class="border rounded-lg px-3 py-1.5 text-sm">
  </div>
  <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-blue-700">Filter</button>
  <a href="?page=audit" class="text-sm text-gray-500 hover:text-gray-700 py-1.5">Reset</a>
</form>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-gray-50 text-gray-600 text-xs uppercase">
        <th class="px-4 py-3 text-left">Waktu</th>
        <th class="px-4 py-3 text-left">User</th>
        <th class="px-4 py-3 text-left">Aksi</th>
        <th class="px-4 py-3 text-left">Detail</th>
        <th class="px-4 py-3 text-left">IP</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      <?php if (empty($logs)): ?>
      <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada log</td></tr>
      <?php else: foreach ($logs as $log): ?>
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap text-xs"><?=e($log['created_at'])?></td>
        <td class="px-4 py-2.5 font-medium"><?=e($log['username'] ?: '-')?></td>
        <td class="px-4 py-2.5">
          <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
            <?php
              $c = match(true) {
                str_contains($log['action'], 'login_failed') => 'bg-red-100 text-red-700',
                str_contains($log['action'], 'login') => 'bg-green-100 text-green-700',
                str_contains($log['action'], 'delete') => 'bg-red-100 text-red-700',
                str_contains($log['action'], 'save') => 'bg-blue-100 text-blue-700',
                str_contains($log['action'], 'sync') => 'bg-yellow-100 text-yellow-700',
                default => 'bg-gray-100 text-gray-700',
              };
              echo $c;
            ?>
          "><?=e($log['action'])?></span>
        </td>
        <td class="px-4 py-2.5 text-gray-600 max-w-xs truncate"><?=e($log['detail'] ?: '-')?></td>
        <td class="px-4 py-2.5 text-gray-400 text-xs font-mono"><?=e($log['ip'] ?: '-')?></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-1 mt-4">
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
  <a href="?page=audit&user=<?=urlencode($filterUser)?>&action=<?=urlencode($filterAction)?>&date=<?=urlencode($filterDate)?>&p=<?=$i?>"
     class="px-3 py-1 rounded text-sm <?=$i===$page_num?'bg-blue-600 text-white':'bg-white border text-gray-600 hover:bg-gray-50'?>"><?=$i?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function clearLogs() {
  fetch('api/handler.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action:'clear_audit_log'})
  }).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); });
}
</script>
