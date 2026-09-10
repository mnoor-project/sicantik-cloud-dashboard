<?php
require_once __DIR__ . '/../core.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (!validateApiToken()) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or missing API token']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'api_list_reports':
        $reports = $db->query("SELECT r.id, r.name, r.description, r.icon, r.sort_order, c.name as connection_name FROM reports r LEFT JOIN connections c ON r.connection_id=c.id ORDER BY r.sort_order, r.name")->fetchAll();
        $out = [];
        foreach ($reports as $r) {
            $last = getLastSync($r['id']);
            $out[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'description' => $r['description'],
                'icon' => $r['icon'],
                'connection' => $r['connection_name'],
                'last_sync' => $last['synced_at'] ?? null,
            ];
        }
        echo json_encode(['ok' => true, 'reports' => $out]);
        break;

    case 'api_report_data':
        $reportId = intval($_GET['report_id'] ?? 0);
        if (!$reportId) {
            http_response_code(400);
            echo json_encode(['error' => 'report_id required']);
            exit;
        }
        $st = $db->prepare("SELECT r.*, c.name as connection_name FROM reports r LEFT JOIN connections c ON r.connection_id=c.id WHERE r.id=?");
        $st->execute([$reportId]);
        $report = $st->fetch();
        if (!$report) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found']);
            exit;
        }
        $data = getCachedData($report['connection_id']);
        $fields = json_decode($report['config_fields'] ?: '[]', true);
        echo json_encode([
            'ok' => true,
            'report' => [
                'id' => $report['id'],
                'name' => $report['name'],
                'description' => $report['description'],
                'connection' => $report['connection_name'],
            ],
            'fields' => $fields,
            'data' => $data,
            'record_count' => count($data),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action. Use: api_list_reports, api_report_data']);
}
