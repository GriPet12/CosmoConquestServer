<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');

try {
    $pdo = DatabaseConnection::getInstance()->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    $requiredFields = ['player_id', 'kronus', 'lyrion', 'mystara', 'eclipsia', 'fiora'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            exit;
        }
    }

    if ($data['kronus'] + $data['lyrion'] + $data['mystara'] + $data['eclipsia'] + $data['fiora'] !== 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Incorrect drone distribution']);
        exit;
    }

    $checkStmt = $pdo->prepare('SELECT player_id FROM games WHERE player_id = :player_id');
    $checkStmt->execute(['player_id' => $data['player_id']]);

    if ($checkStmt->rowCount() === 0) {
        $stmt = $pdo->prepare('
            INSERT INTO games (player_id, kronus, lyrion, mystara, eclipsia, fiora, status)
            VALUES (:player_id, :kronus, :lyrion, :mystara, :eclipsia, :fiora, \'send\')
        ');
        $stmt->execute([
            'player_id' => $data['player_id'],
            'kronus' => $data['kronus'],
            'lyrion' => $data['lyrion'],
            'mystara' => $data['mystara'],
            'eclipsia' => $data['eclipsia'],
            'fiora' => $data['fiora']
        ]);

        echo json_encode(['status' => 'success', 'move_id' => $pdo->lastInsertId()]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE games
            SET kronus = :kronus, lyrion = :lyrion, mystara = :mystara, eclipsia = :eclipsia, fiora = :fiora, status = \'send\'
            WHERE player_id = :player_id
        ');
        $stmt->execute([
            'player_id' => $data['player_id'],
            'kronus' => $data['kronus'],
            'lyrion' => $data['lyrion'],
            'mystara' => $data['mystara'],
            'eclipsia' => $data['eclipsia'],
            'fiora' => $data['fiora']
        ]);

        echo json_encode(['status' => 'success', 'player_id' => $data['player_id']]);
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>