<?php
requireRole($user, 'admin', 'editor');
$conns = $db->query("SELECT * FROM connections ORDER BY name")->fetchAll();
$editId = $_GET['edit'] ?? '';
$editing = null;
if ($editId) {
    $st = $db->prepare("SELECT * FROM reports WHERE id=?");
    $st->execute([$editId]);
    $editing = $st->fetch();
}
?>
<div class="flex items-center justify-between mb-6 flex-wrap gap-2">
  <h2 class="text-2xl font-bold text-gray-800"><?=$editing?'✏️ Edit':'＋ Buat'?> Laporan</h2>
  <?php if($editing): ?>
  <button type="button" onclick="deleteReportAndGo(<?=intval($editing['id'])?>)" class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-lg hover:bg-red-100 text-sm">🗑 Hapus Laporan</button>
  <?php endif; ?>
</div>

<?php if(empty($conns)): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
  <p class="text-yellow-700 mb-2">Belum ada koneksi API.</p>
  <?php if($user['role']==='admin'): ?><a href="?page=connections" class="text-blue-600 hover:underline">Buat koneksi dulu →</a><?php endif; ?>
</div>
<?php else: ?>

<div id="builder-app" class="space-y-6">
  <div class="flex items-center gap-2 text-sm mb-6 flex-wrap">
    <button onclick="goStep(1)" id="step-ind-1" class="px-3 py-1 rounded-full bg-blue-600 text-white">1 Sumber</button>
    <span class="text-gray-300">→</span>
    <button onclick="goStep(2)" id="step-ind-2" class="px-3 py-1 rounded-full bg-gray-200 text-gray-600">2 Kolom</button>
    <span class="text-gray-300">→</span>
    <button onclick="goStep(3)" id="step-ind-3" class="px-3 py-1 rounded-full bg-gray-200 text-gray-600">3 Filter</button>
    <?php /* Fitur chart dinonaktifkan sementara: set $CHARTS_ENABLED=true untuk mengaktifkan lagi */ $CHARTS_ENABLED = false; ?>
    <?php if($CHARTS_ENABLED): ?>
    <span class="text-gray-300">→</span>
    <button onclick="goStep(4)" id="step-ind-4" class="px-3 py-1 rounded-full bg-gray-200 text-gray-600">4 Chart</button>
    <?php endif; ?>
  </div>

  <?php include __DIR__ . '/../templates/builder_steps.php'; ?>
</div>

<script>
const EDIT_DATA = <?=json_encode($editing)?>;
const CONNECTIONS = <?=json_encode(array_map(fn($c)=>['id'=>$c['id'],'name'=>$c['name'],'fields'=>json_decode($c['detected_fields']?:'[]',true)],$conns))?>;
</script>
<?php endif; ?>
