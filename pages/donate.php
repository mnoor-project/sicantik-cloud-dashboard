<?php
$waMsg = rawurlencode("Halo Pak Noor, saya sudah berdonasi untuk pengembangan Dashboard Mandiri Sicantik Cloud. Terima kasih atas aplikasinya \u{1F64F}\n\nNama   : \nNominal: ");
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">&#10084;&#65039; Dukung Dashboard Mandiri Sicantik Cloud</h2>

<div class="max-w-2xl space-y-4">
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-sm text-gray-600 leading-relaxed">
    <p class="mb-3">Dashboard Mandiri Sicantik Cloud adalah aplikasi pendamping yang dikembangkan secara mandiri untuk memudahkan pemantauan data perizinan dari Sicantik Cloud: laporan interaktif, sinkronisasi data otomatis, filter dan ekspor data, serta pengaturan akses per pengguna.</p>
    <p>Aplikasi ini bukan produk resmi Sicantik Cloud dan dijalankan di server masing-masing instansi, sehingga tidak ada biaya langganan. Dukungan Anda membantu waktu dan tenaga untuk perbaikan, pengembangan fitur baru, dan dokumentasi.</p>
  </div>

  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
    <div class="text-xs text-gray-400 mb-2">&#127974; Transfer Bank Jago</div>
    <div class="flex items-center justify-between gap-3">
      <div>
        <div class="font-mono text-xl text-gray-800" id="donate-rek">100891675874</div>
        <div class="text-sm text-gray-500 mt-1">a.n. Muhammad Noor</div>
      </div>
      <button type="button" onclick="copyDonate('donate-rek', this)" class="text-xs bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100">Salin</button>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
    <div class="text-xs text-gray-400 mb-2">&#128172; WhatsApp</div>
    <div class="flex items-center justify-between gap-3">
      <div class="font-mono text-xl text-gray-800">085752735703</div>
      <a href="https://wa.me/6285752735703" target="_blank" rel="noopener" class="text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded-lg hover:bg-green-100">Chat</a>
    </div>
    <p class="text-sm text-gray-500 mt-3">Sudah berdonasi? Kabari saya agar bisa saya ucapkan terima kasih.</p>
    <a href="https://wa.me/6285752735703?text=<?=$waMsg?>" target="_blank" rel="noopener" class="mt-3 inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">&#9989; Konfirmasi Donasi</a>
  </div>

  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex items-center justify-between gap-3">
    <div><div class="text-xs text-gray-400 mb-1">&#127760; Website</div><div class="text-gray-800">muhammadnoor.com</div></div>
    <a href="https://muhammadnoor.com" target="_blank" rel="noopener" class="text-xs bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100">Kunjungi</a>
  </div>

  <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-500 leading-relaxed">
    <strong class="text-gray-600">&#8505;&#65039; Catatan.</strong> Donasi bersifat sukarela dan tidak wajib. Seluruh fitur aplikasi tetap dapat digunakan sepenuhnya tanpa donasi. Dukungan ini bersifat pribadi kepada pengembang dan tidak berkaitan dengan layanan, pungutan, atau kewenangan instansi mana pun.
  </div>

  <p class="text-sm text-gray-500">Terima kasih atas dukungan dan kepercayaannya &#128591;</p>
</div>

<script>
function copyDonate(id, btn) {
  const t = document.getElementById(id).textContent.trim();
  const done = () => { const o = btn.textContent; btn.textContent = 'Tersalin \u2713'; setTimeout(() => btn.textContent = o, 1500); };
  if (navigator.clipboard) navigator.clipboard.writeText(t).then(done, () => prompt('Salin nomor:', t));
  else prompt('Salin nomor:', t);
}
</script>
