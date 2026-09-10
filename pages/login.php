<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — <?=e(getAppTitle())?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-sm">
  <div class="text-center mb-6">
    <div class="text-4xl mb-2">🏛️</div>
    <h1 class="text-xl font-bold text-gray-800"><?=e(getAppTitle())?></h1>
    <p class="text-sm text-gray-500"><?=e(getAppSubtitle())?></p>
  </div>
  <?php if(!empty($error)): ?>
  <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"><?=e($error)?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
      <input type="text" name="username" required autofocus class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
      <div class="relative">
        <input type="password" name="password" required class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <button type="button" onclick="togglePw(this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" tabindex="-1" title="Tampilkan/sembunyikan password">
          <svg class="pw-eye-off w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5 1.66 0 3.23-.394 4.62-1.09M9.878 9.878a3 3 0 1 0 4.243 4.243M9.878 9.878 3 3m6.878 6.878L21 21M1 1l22 22" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774" /></svg>
          <svg class="pw-eye w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1.012 1.012 0 0 1 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        </button>
      </div>
    </div>
    <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700 transition">🔐 Masuk</button>
  </form>
</div>
<script>
function togglePw(btn) {
  const input = btn.parentElement.querySelector('input');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.querySelector('.pw-eye-off').classList.toggle('hidden', show);
  btn.querySelector('.pw-eye').classList.toggle('hidden', !show);
}
</script>
</body>
</html>
