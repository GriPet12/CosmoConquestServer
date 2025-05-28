<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = DatabaseConnection::getInstance()->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);
    $requesting_player_id = $data['player_id'] ?? null;

    if (!$requesting_player_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Player ID is required']);
        exit;
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM games WHERE status = 'ok'");
    $completedGamesCount = $stmt->fetchColumn();

    if ($completedGamesCount > 0) {
        $stmt = $pdo->query("SELECT p.player_id, p.username, g.score
                            FROM games g
                            JOIN players p ON g.player_id = p.player_id
                            WHERE g.status = 'ok'
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
            "message" => "Results ready",
            "results" => $formattedResults
        ]);
        exit;
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM players");
    $totalRegisteredPlayers = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT player_id, kronus, lyrion, mystara, eclipsia, fiora FROM games WHERE status = 'send'");
    $submittedPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($totalRegisteredPlayers < 2) {
        echo json_encode(["status" => "waiting", "message" => "Waiting for more players to register"]);
        exit;
    }

    if (count($submittedPlayers) < $totalRegisteredPlayers) {
        $playersWhoSubmitted = count($submittedPlayers);
        echo json_encode([
            "status" => "waiting", 
            "message" => "Waiting for other players to submit moves ($playersWhoSubmitted/$totalRegisteredPlayers submitted)"
        ]);
        exit;
    }

    $players = $submittedPlayers;
    $scores = array_fill_keys(array_column($submittedPlayers, 'player_id'), 0);

    for ($i = 0; $i < count($players); $i++) {
        for ($j = $i + 1; $j < count($players); $j++) {
            $playerA = $players[$i];
            $playerB = $players[$j];

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
        $stmt = $pdo->prepare("UPDATE games SET score = :score, status = 'ok' WHERE player_id = :player_id AND status = 'send'");
        $stmt->execute(['score' => $score, 'player_id' => $player_id]);
    }

    $submittedPlayerIds = array_column($submittedPlayers, 'player_id');
    if (!empty($submittedPlayerIds)) {
        $placeholders = str_repeat('?,', count($submittedPlayerIds) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM games WHERE player_id IN ($placeholders) AND status = 'in_progress'");
        $stmt->execute($submittedPlayerIds);
    }

    $stmt = $pdo->query("SELECT p.player_id, p.username, g.score
                        FROM games g
                        JOIN players p ON g.player_id = p.player_id
                        WHERE g.status = 'ok'
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