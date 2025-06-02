<?php
header('Content-Type: application/json');
require_once 'config.php';

// Récupération du token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization header missing']);
    exit;
}

// Décodage simplifié du token (en production, utilisez JWT)
$tokenParts = explode('.', $token);
if (count($tokenParts) !== 3) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token format']);
    exit;
}

$tokenData = json_decode(base64_decode($tokenParts[1]), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($tokenData['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token data']);
    exit;
}

$userId = $tokenData['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo->beginTransaction();

    if ($method === 'GET') {
        // Récupérer les tâches
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ?");
        $stmt->execute([$userId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
    }
    elseif ($method === 'POST') {
        // Ajouter une tâche
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title'])) {
            throw new Exception('Title is required', 400);
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $input['title']]);
        
        $taskId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode($task);
    }
    elseif ($method === 'DELETE') {
        // Supprimer une tâche
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            throw new Exception('Task ID is required', 400);
        }

        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['id'], $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Task not found or not authorized', 404);
        }

        echo json_encode(['success' => true]);
    }
    else {
        throw new Exception('Method not allowed', 405);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>