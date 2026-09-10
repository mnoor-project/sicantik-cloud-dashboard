<?php
requireRole($user, 'admin');
$conns = $db->query("SELECT * FROM connections ORDER BY name")->fetchAll();
$editing = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM connections WHERE id=?");
    $st->execute([$_GET['edit']]);
    $editing = $st->fetch();
}
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">🔌 Koneksi API</h2>

<!-- Form -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-8">
  <h3 class="font-semibold text-gray-700 mb-4"><?=$editing?'✏️ Edit':'＋ Tambah'?> Koneksi</h3>
  <form method="POST" action="api/handler.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="action" value="save_connection">
    <input type="hidden" name="id" value="<?=$editing['id']??''?>">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nama Koneksi</label>
      <input type="text" name="name" required value="<?=e($editing['name']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="SiCantik Proses">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Endpoint UUID</label>
      <input type="text" name="endpoint_uuid" required value="<?=e($editing['endpoint_uuid']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="bd77ad8e-a1fe-...">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">API Key Path</label>
      <input type="text" name="api_key_path" value="<?=e($editing['api_key_path']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">API Key Header</label>
      <input type="text" name="apikey_header" value="<?=e($editing['apikey_header']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1">Bearer Token</label>
      <input type="text" name="bearer_token" required value="<?=e($editing['bearer_token']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs font-mono">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Salt Key</label>
      <input type="text" name="salt_key" value="<?=e($editing['salt_key']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-xs">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Query Params (JSON)</label>
      <input type="text" name="query_params" value="<?=e($editing['query_params']??'{}')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-xs" placeholder='{"instansi_id":"99"}'>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1">SQL Referensi (opsional)</label>
      <textarea name="sql_reference" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-xs"><?=e($editing['sql_reference']??'')?></textarea>
    </div>
    <div class="md:col-span-2 flex gap-2">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">💾 Simpan</button>
      <button type="button" onclick="testConnection(this.form)" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition text-sm">🔍 Test Koneksi</button>
      <?php if($editing): ?><a href="?page=connections" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition text-sm inline-block">Batal</a><?php endif; ?>
    </div>
  </form>
  <div id="test-result" class="mt-4 hidden"></div>
</div>

<!-- List -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
  <div class="p-4 border-b border-gray-200 font-semibold text-gray-700">Daftar Koneksi (<?=count($conns)?>)</div>
  <?php if(empty($conns)): ?>
  <div class="p-8 text-center text-gray-400">Belum ada koneksi.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50"><tr>
        <th class="px-4 py-2 text-left text-gray-600">#</th>
        <th class="px-4 py-2 text-left text-gray-600">Nama</th>
        <th class="px-4 py-2 text-left text-gray-600">UUID</th>
        <th class="px-4 py-2 text-left text-gray-600">Fields</th>
        <th class="px-4 py-2 text-left text-gray-600">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($conns as $i=>$c): $fields=json_decode($c['detected_fields']?:'[]',true); ?>
      <tr class="border-t border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-2 text-gray-400"><?=$i+1?></td>
        <td class="px-4 py-2 font-medium"><?=e($c['name'])?></td>
        <td class="px-4 py-2 text-xs text-gray-500 font-mono"><?=e(substr($c['endpoint_uuid'],0,13))?>...</td>
        <td class="px-4 py-2 text-xs text-gray-500"><?=count($fields)?> fields</td>
        <td class="px-4 py-2 flex gap-1">
          <a href="?page=connections&edit=<?=$c['id']?>" class="text-blue-600 hover:underline text-xs">✏️ Edit</a>
          <button onclick="deleteConn(<?=$c['id']?>)" class="text-red-600 hover:underline text-xs">🗑 Hapus</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
