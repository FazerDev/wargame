<?php

// Activer les erreurs pour déboguer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wargame_flo";

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

session_start();

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];
$trade_price = 2000; // Exemple de prix pour un bâtiment "trade"

// Récupérer les données envoyées en POST
$data = json_decode(file_get_contents('php://input'), true);
$tradeCount = (int)$data['trade_count']; // Gestion du bâtiment "trade"

// Vérifier que l'utilisateur a suffisamment de dollars
$query = $db->prepare("SELECT dollars FROM resources WHERE user_id = ?");
$query->execute([$user_id]);
$userData = $query->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    echo json_encode(['error' => 'Utilisateur introuvable']);
    exit;
}

$currentDollars = $userData['dollars'];
$totalCost = $trade_price * $tradeCount;

if ($currentDollars < $totalCost) {
    echo json_encode(['error' => 'Fonds insuffisants pour construire les bâtiments']);
    exit;
}

// Si le joueur a assez d'argent, déduire les dollars et construire les bâtiments "trade"
$newDollars = $currentDollars - $totalCost;

$updateDollars = $db->prepare("UPDATE resources SET dollars = ? WHERE user_id = ?");
$updateDollars->execute([$newDollars, $user_id]);

// Mettre à jour les bâtiments "trade" dans la table user_buildings
$query = $db->prepare("SELECT * FROM user_buildings WHERE user_id = ?");
$query->execute([$user_id]);
$result = $query->fetch(PDO::FETCH_ASSOC);

if ($result) {
    // Si l'utilisateur possède déjà des bâtiments, met à jour la table
    $update = $db->prepare("UPDATE user_buildings SET trade_count = trade_count + ? WHERE user_id = ?");
    $update->execute([$tradeCount, $user_id]);
} else {
    // Sinon, insère une nouvelle entrée pour l'utilisateur
    $insert = $db->prepare("INSERT INTO user_buildings (user_id, trade_count) VALUES (?, ?)");
    $insert->execute([$user_id, $tradeCount]);
}

// Prix en points pour chaque type de bâtiment
$building_points = 100; // 100 points pour chaque raffinerie

// Calculer les points à ajouter en fonction du nombre de raffineries construites
$points_to_add = $building_points * $tradeCount;

// Mettre à jour les points de l'utilisateur
$updatePoints = $db->prepare("UPDATE resources SET points = points + ? WHERE user_id = ?");
$updatePoints->execute([$points_to_add, $user_id]);

// Retourner une réponse JSON en cas de succès
echo json_encode([
    'success' => true,
    'new_dollars' => $newDollars,
    'trade_count' => $tradeCount,
    'added_points' => $points_to_add,
]);


?>
