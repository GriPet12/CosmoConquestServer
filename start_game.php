<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = DatabaseConnection::getInstance()->getConnection();
    $data = json_decode(file_get_contents('php://input'), true);

    $stmt = $pdo->query('SELECT COUNT(*) FROM players');
    $playerCount = $stmt->fetchColumn();

    if ($playerCount >= 2) {
        $stmt = $pdo->query('SELECT player_id FROM players');
        $activePlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $newGamesCreated = 0;
        foreach ($activePlayers as $player) {
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM games WHERE player_id = :player_id AND status = :status');
            $checkStmt->bindParam(':player_id', $player['player_id'], PDO::PARAM_INT);
            $status = 'in_progress';
            $checkStmt->bindParam(':status', $status, PDO::PARAM_STR);
            $checkStmt->execute();

            // Only create a new game if the player doesn't have an in-progress game
            if ($checkStmt->fetchColumn() == 0) {
                $stmt = $pdo->prepare('INSERT INTO games (player_id, kronus, lyrion, mystara, eclipsia, fiora, score)
                               VALUES (:player_id, 0, 0, 0, 0, 0, 0)');
                $stmt->bindParam(':player_id', $player['player_id'], PDO::PARAM_INT);
                $stmt->execute();
                $newGamesCreated++;
            }
        }

        echo json_encode([
            'status' => 'active',
            'player_count' => $playerCount,
            'new_games_created' => $newGamesCreated
        ]);
    } else {
        echo json_encode([
            'status' => 'not-active',
            'player_count' => $playerCount
        ]);
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}