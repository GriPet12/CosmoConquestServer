<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = DatabaseConnection::getInstance()->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username is required']);
        exit;
    }

    $username = $data['username'];

    $stmt = $pdo->query('SELECT COUNT(*) FROM players');
    $playerCount = $stmt->fetchColumn();
    if ($playerCount >= 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Maximum number of players reached']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO players (username) VALUES (:username) RETURNING player_id');
    $stmt->execute(['username' => $username]);

    $playerId = $stmt->fetchColumn();

    echo json_encode(['player_id' => $playerId]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}