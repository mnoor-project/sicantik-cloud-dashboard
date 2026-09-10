<!-- Step 1: Source -->
<div id="step-1" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
  <h3 class="font-semibold text-gray-700 mb-4">Step 1 — Sumber Data</h3>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nama Laporan *</label>
      <input type="text" id="b-name" value="<?=e($editing['name']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
      <select id="b-icon" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        <?php foreach(['📋','📊','📁','📈','📉','🗂','📑','🏗','🏛','⚡'] as $ic): ?>
        <option value="<?=$ic?>" <?=($editing['icon']??'📋')===$ic?'selected':''?>><?=$ic?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
      <input type="text" id="b-desc" value="<?=e($editing['description']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Koneksi *</label>
      <div class="space-y-2">
        <?php foreach($conns as $c): ?>
        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition">
          <input type="radio" name="b-conn" value="<?=$c['id']?>" <?=($editing['connection_id']??'')==$c['id']?'checked':''?> class="text-blue-600" onchange="loadFields(<?=$c['id']?>)">
          <div>
            <div class="font-medium text-sm"><?=e($c['name'])?></div>
            <div class="text-xs text-gray-500"><?=count(json_decode($c['detected_fields']?:'[]',true))?> fields detected</div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut Menu</label>
      <input type="number" id="b-sort-order" value="<?=intval($editing['sort_order']??0)?>" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0">
      <p class="text-xs text-gray-400 mt-1">Semakin kecil, semakin atas di sidebar</p>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Menu (Kelompok Laporan)</label>
      <select id="b-menu-id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        <?php $menus = getMenus(); $curMenu = $editing['menu_id'] ?? getDefaultMenuId(); ?>
        <?php foreach($menus as $m): ?>
        <option value="<?=$m['id']?>" <?=($curMenu==$m['id'])?'selected':''?>><?=e($m['name'])?></option>
        <?php endforeach; ?>
      </select>
      <p class="text-xs text-gray-400 mt-1">Laporan dikelompokkan di sidebar berdasarkan menu</p>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Override Params (JSON, opsional)</label>
      <input type="text" id="b-params" value="<?=e($editing['override_params']??'')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-xs">
    </div>
  </div>
  <div class="mt-4 flex justify-end">
    <button onclick="goStep(2)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-sm">Lanjut →</button>
  </div>
</div>


<!-- Step 2: Columns -->
<div id="step-2" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hidden">
  <h3 class="font-semibold text-gray-700 mb-4">Step 2 — Pilih & Atur Kolom</h3>
  <p class="text-sm text-gray-500 mb-4">Centang field yang ingin ditampilkan. Drag ≡ untuk ubah urutan.</p>
  <div id="fields-list" class="space-y-2"></div>
  <div class="mt-4 flex justify-between">
    <button onclick="goStep(1)" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 text-sm">← Kembali</button>
    <button onclick="goStep(3)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-sm">Lanjut →</button>
  </div>
</div>

<!-- Step 3: Filters -->
<div id="step-3" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hidden">
  <h3 class="font-semibold text-gray-700 mb-4">Step 3 — Atur Filter</h3>
  <p class="text-sm text-gray-500 mb-4">Filter yang tersedia bagi pengguna saat melihat laporan.</p>
  <div id="filters-list" class="space-y-3"></div>
  <button onclick="addFilter()" class="mt-3 text-blue-600 hover:underline text-sm">＋ Tambah Filter</button>
  <div class="mt-4 flex justify-between">
    <button onclick="goStep(2)" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 text-sm">← Kembali</button>
    <button onclick="goStep(4)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-sm">Lanjut →</button>
  </div>
</div>

<!-- Step 4: Charts -->
<div id="step-4" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hidden">
  <h3 class="font-semibold text-gray-700 mb-4">Step 4 — Atur Chart</h3>
  <div id="charts-list" class="space-y-4"></div>
  <button onclick="addChart()" class="mt-3 text-blue-600 hover:underline text-sm">＋ Tambah Chart</button>
  <div class="mt-4 flex justify-between">
    <button onclick="goStep(3)" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 text-sm">← Kembali</button>
    <button onclick="saveReport()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 text-sm">💾 Simpan Laporan</button>
  </div>
</div>
