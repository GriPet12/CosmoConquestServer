<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = DatabaseConnection::getInstance()->getConnection();

    $stmt = $pdo->query("SELECT COUNT(*) FROM games WHERE status != 'send'");
    $nonSendGamesCount = $stmt->fetchColumn();

    if ($nonSendGamesCount > 0) {
        echo json_encode(["status" => "wait"]);
        exit;
    }

    $stmt = $pdo->query("SELECT player_id, kronus, lyrion, mystara, eclipsia, fiora FROM games");
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $scores = array_fill_keys(array_column($players, 'player_id'), 0);

    foreach ($players as $playerA) {
        foreach ($players as $playerB) {
            if ($playerA['player_id'] === $playerB['player_id']) continue;

            $scoreA = 0;
            $scoreB = 0;

            foreach (['kronus', 'lyrion', 'mystara', 'eclipsia', 'fiora'] as $planet) {
                if ($playerA[$planet] > $playerB[$planet]) {
                    $scoreA += 2;
                } elseif ($playerA[$planet] === $playerB[$planet]) {
                    $scoreA += 1;
                    $scoreB += 1;
                } else {
                    $scoreB += 2;
                }
            }

            if ($scoreA > $scoreB) {
                $scores[$playerA['player_id']] += 2;
            } elseif ($scoreA === $scoreB) {
                $scores[$playerA['player_id']] += 1;
                $scores[$playerB['player_id']] += 1;
            } else {
                $scores[$playerB['player_id']] += 2;
            }
        }
    }

    foreach ($scores as $player_id => $score) {
        $stmt = $pdo->prepare("UPDATE games SET score = :score, status = 'ok' WHERE player_id = :player_id");
        $stmt->execute(['score' => $score, 'player_id' => $player_id]);
    }

    $stmt = $pdo->query("SELECT p.player_id, p.username, g.score
                    FROM games g
                    JOIN players p ON g.player_id = p.player_id
                    ORDER BY g.score DESC");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedResults = [];
    foreach ($results as $result) {
        $formattedResults[] = [
            'username' => $result['username'],
            'score' => $result['score']
        ];
    }

    echo json_encode([
        "status" => "ok",
        "message" => "Results calculated successfully",
        "results" => $formattedResults
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>