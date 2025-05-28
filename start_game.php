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
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM games WHERE player_id = :player_id AND status IN ('in_progress', 'send', 'ok')");
            $stmt->execute(['player_id' => $player['player_id']]);
            $playerHasActiveGame = $stmt->fetchColumn() > 0;
            
            if (!$playerHasActiveGame) {
                $stmt = $pdo->prepare('INSERT INTO games (player_id, kronus, lyrion, mystara, eclipsia, fiora, score, status)
                                       VALUES (:player_id, 0, 0, 0, 0, 0, 0, \'in_progress\')');
                $stmt->execute(['player_id' => $player['player_id']]);
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