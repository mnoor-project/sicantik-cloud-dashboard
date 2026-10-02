<?php
require_once __DIR__ . '/core.php';
session_start();

$page = $_GET['page'] ?? 'home';

// Login page — no auth needed
if ($page === 'login') {
    $error = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $user = login($_POST['username'] ?? '', $_POST['password'] ?? '');
        if ($user) { auditLog('login', 'Login berhasil', $user); header('Location: ?page=home'); exit; }
        $error = 'Username atau password salah';
        auditLog('login_failed', 'Username: ' . ($_POST['username'] ?? ''));
    }
    include __DIR__ . '/pages/login.php';
    exit;
}

if ($page === 'logout') { logout(); header('Location: ?page=login'); exit; }

$user = requireAuth();
$db = getDB();

// Sidebar data
$reports = getAccessibleReports($user);
$menus = getMenus();
// Reports grouped by menu (reports without menu -> default menu)
$defaultMenuId = getDefaultMenuId();
$grouped = [];
foreach ($menus as $m) $grouped[$m['id']] = ['menu'=>$m, 'reports'=>[]];
$orphan = [];
foreach ($reports as $r) {
    $key = $r['menu_id'] ?: $defaultMenuId;
    if (isset($grouped[$key])) $grouped[$key]['reports'][] = $r;
    else $orphan[] = $r;
}

// Allowed pages & role check
$roleMap = [
    'home'=>['admin','editor','viewer'],
    'reports'=>['admin','editor','viewer'],
    'view'=>['admin','editor','viewer'],
    'builder'=>['admin','editor'],
    'connections'=>['admin'],
    'sync'=>['admin','editor'],
    'users'=>['admin'],
    'settings'=>['admin'],
    'audit'=>['admin'],
    'profile'=>['admin','editor','viewer'],
    'donate'=>['admin','editor','viewer'],
];
if (!isset($roleMap[$page])) $page = 'home';
requireRole($user, ...$roleMap[$page]);

$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) $pageFile = __DIR__ . '/pages/home.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(getAppTitle())?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3/dist/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1/Sortable.min.js"></script>
<link rel="stylesheet" href="assets/app.css?v=<?=filemtime(__DIR__.'/assets/app.css')?>">
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Mobile overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-white border-r border-gray-200 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-200">
  <div class="p-4 border-b border-gray-200">
    <h1 class="text-lg font-bold text-gray-800">🏛️ <?=e(getAppTitle())?></h1>
    <p class="text-xs text-gray-500"><?=e(getAppSubtitle())?></p>
  </div>
  <nav class="p-3 overflow-y-auto h-[calc(100%-8rem)]">
    <a href="?page=home" class="nav-item <?=$page==='home'?'active':''?>">📊 Home</a>

    <div class="mt-4 mb-1 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Laporan</div>
    <a href="?page=reports" class="nav-item <?=$page==='reports'?'active':''?>">📋 Semua Laporan</a>
    <?php foreach($grouped as $g): if(empty($g['reports'])) continue; ?>
    <div class="mt-3 mb-1 px-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider"><?=e($g['menu']['name'])?></div>
      <?php foreach($g['reports'] as $r): ?>
      <a href="?page=view&id=<?=$r['id']?>" class="nav-item <?=($page==='view'&&($_GET['id']??'')==$r['id'])?'active':''?>"><?=e($r['icon'])?> <?=e($r['name'])?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <?php foreach($orphan as $r): ?>
    <a href="?page=view&id=<?=$r['id']?>" class="nav-item <?=($page==='view'&&($_GET['id']??'')==$r['id'])?'active':''?>"><?=e($r['icon'])?> <?=e($r['name'])?></a>
    <?php endforeach; ?>
    <?php if(in_array($user['role'],['admin','editor'])): ?>
    <a href="?page=builder" class="nav-item text-blue-600 <?=$page==='builder'?'active':''?>">＋ Buat Laporan</a>
    <?php endif; ?>

    <?php if(in_array($user['role'],['admin','editor'])): ?>
    <div class="mt-4 mb-1 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Sistem</div>
    <?php if($user['role']==='admin'): ?>
    <a href="?page=connections" class="nav-item <?=$page==='connections'?'active':''?>">🔌 Koneksi API</a>
    <?php endif; ?>
    <a href="?page=sync" class="nav-item <?=$page==='sync'?'active':''?>">🔄 Sync Data</a>
    <?php if($user['role']==='admin'): ?>
    <a href="?page=users" class="nav-item <?=$page==='users'?'active':''?>">👥 Users</a>
    <a href="?page=settings" class="nav-item <?=$page==='settings'?'active':''?>">⚙️ Pengaturan</a>
    <a href="?page=audit" class="nav-item <?=$page==='audit'?'active':''?>">📋 Log Aktivitas</a>
    <?php endif; ?>
    <?php endif; ?>
  </nav>
  <div class="absolute bottom-0 w-full p-3 border-t border-gray-200 text-xs text-gray-400">
    v<?=APP_VERSION?> • SQLite
    <a href="?page=donate" class="block mt-1 hover:text-red-500">&#10084;&#65039; Dukung pengembangan</a>
  </div>
</aside>

<!-- Main content -->
<div class="lg:ml-64 min-h-screen">
  <!-- Top bar -->
  <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
    <button onclick="toggleSidebar()" class="lg:hidden p-1 rounded hover:bg-gray-100">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="flex-1"></div>
    <div class="flex items-center gap-3">
      <span class="text-sm text-gray-600">👤 <?=e($user['name'])?></span>
      <span class="text-xs px-2 py-0.5 rounded-full <?=$user['role']==='admin'?'bg-red-100 text-red-700':($user['role']==='editor'?'bg-blue-100 text-blue-700':'bg-gray-100 text-gray-700')?>"><?=e($user['role'])?></span>
      <a href="?page=profile" class="text-xs text-gray-500 hover:text-blue-600" title="Akun Saya">⚙️</a>
      <a href="?page=logout" class="text-xs text-gray-500 hover:text-red-600">Logout</a>
    </div>
  </header>
  <!-- Page content -->
  <main class="p-4 md:p-6">
    <?php include $pageFile; ?>
  </main>
</div>

<script src="assets/app.js?v=<?=filemtime(__DIR__.'/assets/app.js')?>"></script>
</body>
</html>
