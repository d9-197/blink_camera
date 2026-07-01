<?php
/* This file is part of Jeedom.
 *
 * Webhook endpoint receiving detection notifications from an external bridge
 * (Blink does not push directly to plugins; a relay must POST here).
 *
 * Security: shared token, regenerable from the plugin configuration.
 *
 * Expected request:
 *   POST /plugins/blink_camera/core/php/notification.php?token=<shared-token>
 *   Content-Type: application/json
 *   { "network_id": "...", "camera_id": "...", "source": "pir|button_press", "timestamp": "YYYY-MM-DD_HHMMSS" }
 *
 * Token can also be passed via the X-Blink-Token header instead of the URL.
 */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../class/blink_camera.class.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$expected = blink_camera::getWebhookToken();
$provided = isset($_GET['token']) ? (string)$_GET['token'] : '';
if ($provided === '' && isset($_SERVER['HTTP_X_BLINK_TOKEN'])) {
    $provided = (string)$_SERVER['HTTP_X_BLINK_TOKEN'];
}
if ($provided === '' || !hash_equals($expected, $provided)) {
    blink_camera::logwarn('notification.php: invalid or missing token');
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

try {
    $matched = blink_camera::handleDetectionNotification($payload);
    if (!$matched) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'no_matching_camera']);
        exit;
    }
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    blink_camera::logerror('notification.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal_error']);
}
