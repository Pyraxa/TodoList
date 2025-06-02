<?php
header('Content-Type: application/json');
require_once 'config.php';

// Vérifier l'authentification
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Décoder le token
$tokenData = json_decode(base64_decode($token), true);
$userId = $tokenData['user_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalide']);
    exit;
}

// Récupérer la date de filtrage
$date = $_GET['date'] ?? null;

// Construire la requête
$query = "SELECT id, title, status, created_at, completed_at FROM tasks WHERE user_id = ? AND (status = 'terminée' OR completed_at IS NOT NULL)";
$params = [$userId];

if ($date) {
    $query .= " AND DATE(completed_at) = ?";
    $params[] = $date;
}

$query .= " ORDER BY completed_at DESC, created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($history);
?>