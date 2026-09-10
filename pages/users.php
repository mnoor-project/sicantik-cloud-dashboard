<?php
requireRole($user, 'admin');
$users = $db->query("SELECT * FROM users ORDER BY id")->fetchAll();
$allReports = $db->query("SELECT id, name, icon FROM reports ORDER BY sort_order, name")->fetchAll();
$accessMap = [];
foreach ($db->query("SELECT user_id, report_id FROM user_report_access")->fetchAll() as $ar) {
    $accessMap[$ar['user_id']][] = $ar['report_id'];
}
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">👥 Kelola User</h2>

<!-- Form -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-8">
  <h3 class="font-semibold text-gray-700 mb-4" id="user-form-title">＋ Tambah User</h3>
  <form id="user-form" onsubmit="return saveUser(event)">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="id" id="uf-id" value="">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <input type="text" name="username" id="uf-username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
        <input type="text" name="name" id="uf-name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password <span id="pw-hint" class="text-gray-400 text-xs">(wajib)</span></label>
        <div class="relative">
          <input type="password" name="password" id="uf-password" class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <button type="button" onclick="togglePw(this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" tabindex="-1" title="Tampilkan/sembunyikan password">
            <svg class="pw-eye-off w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5 1.66 0 3.23-.394 4.62-1.09M9.878 9.878a3 3 0 1 0 4.243 4.243M9.878 9.878 3 3m6.878 6.878L21 21M1 1l22 22" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774" /></svg>
            <svg class="pw-eye w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1.012 1.012 0 0 1 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
          </button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
        <select name="role" id="uf-role" onchange="toggleAccessPanel()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option value="viewer">Viewer — Hanya lihat</option>
          <option value="editor">Editor — Lihat + buat laporan</option>
          <option value="admin">Admin — Semua akses</option>
        </select>
      </div>
    </div>
    <!-- Report Access Panel -->
    <div id="access-panel" class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
      <h4 class="font-medium text-gray-700 text-sm mb-3">📋 Akses Laporan</h4>
      <div class="flex items-center gap-4 mb-3">
        <label class="flex items-center gap-2 text-sm cursor-pointer">
          <input type="radio" name="access_mode" value="all" checked onchange="toggleAccessChecklist()"> Semua laporan
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
          <input type="radio" name="access_mode" value="selected" onchange="toggleAccessChecklist()"> Pilih laporan tertentu
        </label>
      </div>
      <div id="access-checklist" class="hidden space-y-2 ml-1">
        <?php if (empty($allReports)): ?>
        <p class="text-xs text-gray-400">Belum ada laporan.</p>
        <?php else: ?>
        <?php foreach ($allReports as $r): ?>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-1.5 rounded">
          <input type="checkbox" class="report-access-cb rounded" value="<?=$r['id']?>">
          <?=e($r['icon'])?> <?=e($r['name'])?>
        </label>
        <?php endforeach; ?>
        <div class="pt-2 border-t border-gray-200">
          <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
            <input type="checkbox" id="access-select-all" onchange="document.querySelectorAll('.report-access-cb').forEach(c=>c.checked=this.checked)"> Pilih Semua
          </label>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-4 flex gap-2">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">💾 Simpan</button>
      <button type="button" onclick="resetUserForm()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition text-sm">Batal</button>
    </div>
  </form>
</div>

<!-- List -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
  <div class="p-4 border-b border-gray-200 font-semibold text-gray-700">Daftar User (<?=count($users)?>)</div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50"><tr>
        <th class="px-4 py-2 text-left text-gray-600">#</th>
        <th class="px-4 py-2 text-left text-gray-600">Username</th>
        <th class="px-4 py-2 text-left text-gray-600">Nama</th>
        <th class="px-4 py-2 text-left text-gray-600">Role</th>
        <th class="px-4 py-2 text-left text-gray-600">Akses</th>
        <th class="px-4 py-2 text-left text-gray-600">Status</th>
        <th class="px-4 py-2 text-left text-gray-600">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($users as $i=>$u): ?>
      <?php
        $uAccess = $accessMap[$u['id']] ?? [];
        $accessLabel = $u['role'] === 'admin' ? 'Semua' : (empty($uAccess) ? 'Semua' : count($uAccess) . ' laporan');
      ?>
      <tr class="border-t border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-2 text-gray-400"><?=$i+1?></td>
        <td class="px-4 py-2 font-medium font-mono"><?=e($u['username'])?></td>
        <td class="px-4 py-2"><?=e($u['name'])?></td>
        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs <?=$u['role']==='admin'?'bg-red-100 text-red-700':($u['role']==='editor'?'bg-blue-100 text-blue-700':'bg-gray-100 text-gray-700')?>"><?=e($u['role'])?></span></td>
        <td class="px-4 py-2 text-xs text-gray-500"><?=$accessLabel?></td>
        <td class="px-4 py-2"><?=$u['is_active']?'<span class="text-green-600">✅ Aktif</span>':'<span class="text-red-600">❌ Nonaktif</span>'?></td>
        <td class="px-4 py-2 flex gap-1">
          <button onclick='editUser(<?=json_encode($u)?>,<?=json_encode($uAccess)?>)' class="text-blue-600 hover:underline text-xs">✏️</button>
          <?php if($u['id']!==$user['id']): ?>
          <button onclick="toggleUser(<?=$u['id']?>,<?=$u['is_active']?>)" class="text-yellow-600 hover:underline text-xs"><?=$u['is_active']?'⏸':'▶'?></button>
          <button onclick="deleteUser(<?=$u['id']?>)" class="text-red-600 hover:underline text-xs">🗑</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleAccessPanel() {
  const role = document.getElementById('uf-role').value;
  document.getElementById('access-panel').style.display = role === 'admin' ? 'none' : 'block';
}
function toggleAccessChecklist() {
  const mode = document.querySelector('input[name="access_mode"]:checked');
  document.getElementById('access-checklist').classList.toggle('hidden', !mode || mode.value === 'all');
}
toggleAccessPanel();
</script>
