<?php
// Self-service profile page — all roles can access
?>
<h2 class="text-2xl font-bold text-gray-800 mb-6">👤 Akun Saya</h2>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg mb-6">
  <h3 class="font-semibold text-gray-700 mb-4">📝 Profil</h3>
  <div class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
      <input type="text" value="<?=e($user['username'])?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
      <p class="text-xs text-gray-400 mt-1">Username hanya bisa diubah oleh admin.</p>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
      <input type="text" id="prof-name" value="<?=e($user['name'])?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
      <input type="text" value="<?=e($user['role'])?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
    </div>
  </div>
  <button onclick="saveProfile()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">💾 Simpan Nama</button>
  <span id="profile-msg" class="ml-3 text-sm hidden"></span>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg">
  <h3 class="font-semibold text-gray-700 mb-4">🔑 Ganti Password</h3>
  <div class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label>
      <div class="relative">
        <input type="password" id="prof-oldpw" required class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <button type="button" onclick="togglePw(this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" tabindex="-1" title="Tampilkan/sembunyikan password">
          <svg class="pw-eye-off w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5 1.66 0 3.23-.394 4.62-1.09M9.878 9.878a3 3 0 1 0 4.243 4.243M9.878 9.878 3 3m6.878 6.878L21 21M1 1l22 22" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774" /></svg>
          <svg class="pw-eye w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1.012 1.012 0 0 1 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        </button>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
      <div class="relative">
        <input type="password" id="prof-newpw" required minlength="6" class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <button type="button" onclick="togglePw(this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" tabindex="-1" title="Tampilkan/sembunyikan password">
          <svg class="pw-eye-off w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5 1.66 0 3.23-.394 4.62-1.09M9.878 9.878a3 3 0 1 0 4.243 4.243M9.878 9.878 3 3m6.878 6.878L21 21M1 1l22 22" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774" /></svg>
          <svg class="pw-eye w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1.012 1.012 0 0 1 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        </button>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
      <div class="relative">
        <input type="password" id="prof-newpw2" required minlength="6" class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <button type="button" onclick="togglePw(this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" tabindex="-1" title="Tampilkan/sembunyikan password">
          <svg class="pw-eye-off w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5 1.66 0 3.23-.394 4.62-1.09M9.878 9.878a3 3 0 1 0 4.243 4.243M9.878 9.878 3 3m6.878 6.878L21 21M1 1l22 22" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774" /></svg>
          <svg class="pw-eye w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1.012 1.012 0 0 1 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        </button>
      </div>
    </div>
  </div>
  <button onclick="changeMyPassword()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm">🔑 Ubah Password</button>
  <span id="pw-msg" class="ml-3 text-sm hidden"></span>
</div>

<script>
async function saveProfile() {
  const name = document.getElementById('prof-name').value.trim();
  if (!name) { alert('Nama tidak boleh kosong'); return; }
  const res = await api('update_profile', { name });
  const msg = document.getElementById('profile-msg');
  msg.classList.remove('hidden');
  if (res.ok) {
    msg.className = 'ml-3 text-sm text-green-600';
    msg.textContent = '✅ Tersimpan';
    setTimeout(() => location.reload(), 600);
  } else {
    msg.className = 'ml-3 text-sm text-red-600';
    msg.textContent = '❌ ' + (res.error || 'Gagal');
  }
}

async function changeMyPassword() {
  const oldPw = document.getElementById('prof-oldpw').value;
  const newPw = document.getElementById('prof-newpw').value;
  const newPw2 = document.getElementById('prof-newpw2').value;
  if (!oldPw || !newPw) { alert('Semua field wajib diisi'); return; }
  if (newPw.length < 6) { alert('Password baru minimal 6 karakter'); return; }
  if (newPw !== newPw2) { alert('Konfirmasi password tidak cocok'); return; }
  const res = await api('update_profile', { old_password: oldPw, new_password: newPw });
  const msg = document.getElementById('pw-msg');
  msg.classList.remove('hidden');
  if (res.ok) {
    msg.className = 'ml-3 text-sm text-green-600';
    msg.textContent = '✅ Password berhasil diubah';
    document.getElementById('prof-oldpw').value = '';
    document.getElementById('prof-newpw').value = '';
    document.getElementById('prof-newpw2').value = '';
  } else {
    msg.className = 'ml-3 text-sm text-red-600';
    msg.textContent = '❌ ' + (res.error || 'Gagal');
  }
}
</script>