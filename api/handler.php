<?php
require_once __DIR__ . '/../core.php';
session_start();

header('Content-Type: application/json');
$user = getCurrentUser();
if (!$user) { http_response_code(401); echo '{"error":"Unauthorized"}'; exit; }

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'save_connection':
        requireRole($user, 'admin');
        auditLog('save_connection', 'Koneksi: '.($input['name']??''), $user);
        $id = intval($input['id'] ?? 0);
        $data = [
            $input['name']??'', $input['endpoint_uuid']??'',
            $input['api_key_path']??'', $input['bearer_token']??'',
            $input['apikey_header']??'', $input['salt_key']??'',
            $input['query_params']??'{}', $input['sql_reference']??''
        ];
        if ($id) {
            $db->prepare("UPDATE connections SET name=?,endpoint_uuid=?,api_key_path=?,bearer_token=?,apikey_header=?,salt_key=?,query_params=?,sql_reference=?,updated_at=datetime('now') WHERE id=?")->execute([...$data, $id]);
        } else {
            $db->prepare("INSERT INTO connections(name,endpoint_uuid,api_key_path,bearer_token,apikey_header,salt_key,query_params,sql_reference) VALUES(?,?,?,?,?,?,?,?)")->execute($data);
            $id = $db->lastInsertId();
        }
        jsonResponse(['ok'=>true,'id'=>$id,'redirect'=>'?page=connections']);
        break;

    case 'delete_connection':
        requireRole($user, 'admin');
        auditLog('delete_connection', 'ID: '.($input['id']??''), $user);
        $db->prepare("DELETE FROM connections WHERE id=?")->execute([$input['id']]);
        $db->prepare("DELETE FROM data_cache WHERE connection_id=?")->execute([$input['id']]);
        $db->prepare("DELETE FROM sync_log WHERE connection_id=?")->execute([$input['id']]);
        jsonResponse(['ok'=>true]);
        break;

    case 'test_connection':
        requireRole($user, 'admin');
        $conn = $input;
        $result = callSicantikAPI($conn);
        $fields = $result['success'] ? detectFields($result['data']) : [];
        jsonResponse([
            'ok'=>$result['success'],
            'http_status'=>$result['http_status'],
            'duration'=>$result['duration'],
            'record_count'=>count($result['data']),
            'fields'=>$fields,
            'error'=>$result['error'],
            'raw'=>substr($result['raw'] ?? '', 0, 2000),
        ]);
        break;

    case 'sync':
        requireRole($user, 'admin', 'editor', 'viewer');
        auditLog('sync', 'Conn ID: '.($input['connection_id']??''), $user);
        $result = syncConnection(intval($input['connection_id']));
        jsonResponse(['ok'=>$result['success'],'record_count'=>count($result['data']),'duration'=>$result['duration'],'error'=>$result['error']]);
        break;

    case 'sync_all':
        requireRole($user, 'admin', 'editor');
        auditLog('sync_all', '', $user);
        $conns = $db->query("SELECT id FROM connections")->fetchAll();
        $results = [];
        foreach ($conns as $c) $results[] = syncConnection($c['id']);
        jsonResponse(['ok'=>true,'results'=>$results]);
        break;

    case 'save_report':
        requireRole($user, 'admin', 'editor');
        auditLog('save_report', 'Laporan: '.($input['name']??''), $user);
        $id = intval($input['id'] ?? 0);
        $menuId = !empty($input['menu_id']) ? intval($input['menu_id']) : getDefaultMenuId();
        $d = [$input['name'],$input['description']??'',$input['icon']??'📋',intval($input['connection_id']),json_encode($input['fields']??[]),json_encode($input['filters']??[]),json_encode($input['charts']??[]),$input['override_params']??'',intval($input['sort_order']??0),$menuId];
        if ($id) {
            $db->prepare("UPDATE reports SET name=?,description=?,icon=?,connection_id=?,config_fields=?,config_filters=?,config_charts=?,override_params=?,sort_order=?,menu_id=?,updated_at=datetime('now') WHERE id=?")->execute([...$d,$id]);
        } else {
            $db->prepare("INSERT INTO reports(name,description,icon,connection_id,config_fields,config_filters,config_charts,override_params,sort_order,menu_id,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)")->execute([...$d,$user['id']]);
            $id = $db->lastInsertId();
        }
        jsonResponse(['ok'=>true,'id'=>$id,'redirect'=>'?page=view&id='.$id]);
        break;

    case 'reorder_reports':
        requireRole($user, 'admin');
        $st = $db->prepare("UPDATE reports SET sort_order=? WHERE id=?");
        foreach ($input['items'] ?? [] as $item) {
            $st->execute([intval($item['sort_order']), intval($item['id'])]);
        }
        jsonResponse(['ok'=>true]);
        break;

    case 'save_menu':
        requireRole($user, 'admin');
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $sort = intval($input['sort_order'] ?? 0);
        if ($name === '') { jsonResponse(['ok'=>false,'error'=>'Nama menu wajib diisi']); break; }
        if ($id) {
            $db->prepare("UPDATE menus SET name=?,sort_order=? WHERE id=?")->execute([$name,$sort,$id]);
        } else {
            $db->prepare("INSERT INTO menus(name,sort_order) VALUES(?,?)")->execute([$name,$sort]);
            $id = $db->lastInsertId();
        }
        jsonResponse(['ok'=>true,'id'=>$id]);
        break;

    case 'delete_menu':
        requireRole($user, 'admin');
        $id = intval($input['id'] ?? 0);
        $default = getDefaultMenuId();
        if ($id === $default) { jsonResponse(['ok'=>false,'error'=>'Menu default tidak bisa dihapus']); break; }
        $db->prepare("DELETE FROM menus WHERE id=?")->execute([$id]);
        $db->prepare("UPDATE reports SET menu_id=? WHERE menu_id=?")->execute([$default,$id]);
        jsonResponse(['ok'=>true]);
        break;

    case 'reorder_menus':
        requireRole($user, 'admin');
        $st = $db->prepare("UPDATE menus SET sort_order=? WHERE id=?");
        foreach ($input['items'] ?? [] as $item) {
            $st->execute([intval($item['sort_order']), intval($item['id'])]);
        }
        jsonResponse(['ok'=>true]);
        break;

    case 'delete_report':
        requireRole($user, 'admin', 'editor');
        auditLog('delete_report', 'ID: '.($input['id']??''), $user);
        $db->prepare("DELETE FROM reports WHERE id=?")->execute([$input['id']]);
        jsonResponse(['ok'=>true]);
        break;

    case 'save_user':
        requireRole($user, 'admin');
        auditLog('save_user', 'User: '.($input['username']??''), $user);
        $id = intval($input['id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE users SET username=?,name=?,role=? WHERE id=?")->execute([$input['username'],$input['name'],$input['role'],$id]);
            if (!empty($input['password'])) {
                $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($input['password'],PASSWORD_DEFAULT),$id]);
            }
        } else {
            $db->prepare("INSERT INTO users(username,password_hash,name,role) VALUES(?,?,?,?)")->execute([$input['username'],password_hash($input['password'],PASSWORD_DEFAULT),$input['name'],$input['role']]);
            $id = $db->lastInsertId();
        }
        // Save report access (admin skips, viewer/editor uses it)
        $role = $input['role'] ?? 'viewer';
        if ($role !== 'admin') {
            $accessMode = $input['access_mode'] ?? 'all';
            if ($accessMode === 'selected' && !empty($input['report_access'])) {
                saveUserReportAccess($id, $input['report_access']);
            } else {
                saveUserReportAccess($id, null); // null = clear = all access
            }
        } else {
            saveUserReportAccess($id, null); // admin always all
        }
        jsonResponse(['ok'=>true,'redirect'=>'?page=users']);
        break;

    case 'update_profile':
        auditLog('update_profile', '', $user);
        // Self-service: update own name and/or password
        $changed = false;
        if (!empty($input['name'])) {
            $db->prepare("UPDATE users SET name=? WHERE id=?")->execute([trim($input['name']), $user['id']]);
            $changed = true;
        }
        if (!empty($input['new_password'])) {
            if (empty($input['old_password'])) {
                jsonResponse(['ok'=>false,'error'=>'Password lama wajib diisi']);
                break;
            }
            $st = $db->prepare("SELECT password_hash FROM users WHERE id=?");
            $st->execute([$user['id']]);
            $current = $st->fetchColumn();
            if (!password_verify($input['old_password'], $current)) {
                jsonResponse(['ok'=>false,'error'=>'Password lama salah']);
                break;
            }
            $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($input['new_password'],PASSWORD_DEFAULT), $user['id']]);
            $changed = true;
        }
        jsonResponse(['ok'=>$changed]);
        break;

    case 'toggle_user':
        requireRole($user, 'admin');
        auditLog('toggle_user', 'ID: '.($input['id']??''), $user);
        $db->prepare("UPDATE users SET is_active=NOT is_active WHERE id=? AND id!=?")->execute([$input['id'],$user['id']]);
        jsonResponse(['ok'=>true]);
        break;

    case 'delete_user':
        requireRole($user, 'admin');
        auditLog('delete_user', 'ID: '.($input['id']??''), $user);
        $db->prepare("DELETE FROM users WHERE id=? AND id!=?")->execute([$input['id'],$user['id']]);
        $db->prepare("DELETE FROM sessions WHERE user_id=?")->execute([$input['id']]);
        jsonResponse(['ok'=>true]);
        break;

    case 'save_settings':
        requireRole($user, 'admin');
        auditLog('save_settings', implode(', ', array_keys($input['settings']??[])), $user);
        foreach ($input['settings'] ?? [] as $k => $v) {
            saveSetting($k, $v);
        }
        jsonResponse(['ok'=>true]);
        break;

    case 'get_cached_data':
        $connId = intval($input['connection_id'] ?? 0);
        jsonResponse(['data' => getCachedData($connId)]);
        break;

    case 'clear_audit_log':
        requireRole($user, 'admin');
        auditLog('clear_audit_log', '', $user);
        $db->exec("DELETE FROM audit_log");
        jsonResponse(['ok'=>true]);
        break;

    case 'log_export':
        $format = $input['format'] ?? 'unknown';
        $report = $input['report_name'] ?? '';
        auditLog('export_' . $format, 'Laporan: ' . $report, $user);
        jsonResponse(['ok'=>true]);
        break;

    case 'generate_api_token':
        requireRole($user, 'admin');
        auditLog('generate_api_token', '', $user);
        $token = generateApiToken();
        jsonResponse(['ok'=>true, 'token'=>$token]);
        break;

    default:
        jsonResponse(['error'=>'Unknown action']);
}
