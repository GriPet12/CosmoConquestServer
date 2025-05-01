<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = DatabaseConnection::getInstance()->getConnection();
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['player_id'])) {
        $player_id = $data['player_id'];

        $deleteGamesStmt = $pdo->prepare('DELETE FROM games WHERE player_id = :player_id');
        $deleteGamesStmt->execute(['player_id' => $player_id]);
        $gamesDeleted = $deleteGamesStmt->rowCount();

        $deletePlayerStmt = $pdo->prepare('DELETE FROM players WHERE player_id = :player_id');
        $deletePlayerStmt->execute(['player_id' => $player_id]);
        $playerDeleted = $deletePlayerStmt->rowCount();

        echo json_encode([
            'status' => 'success',
            'message' => 'Player and related games removed successfully',
            'player_deleted' => $playerDeleted > 0,
            'games_deleted' => $gamesDeleted
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'player_id is required']);
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}