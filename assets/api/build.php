<?php

// Activer les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wargame_flo";

try {
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
$refinery_price = 500; // Exemple de prix d'une raffinerie
$construction_duration = 5 * 60; // Durée de construction en secondes (5 minutes)

// Récupérer les données envoyées en POST
$data = json_decode(file_get_contents('php://input'), true);
$refineryCount = (int)$data['refinery_count'];

// Vérifier que l'utilisateur a suffisamment de dollars
$query = $db->prepare("SELECT dollars FROM resources WHERE user_id = ?");
$query->execute([$user_id]);
$userData = $query->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    echo json_encode(['error' => 'Utilisateur introuvable']);
    exit;
}

$currentDollars = $userData['dollars'];
$totalCost = $refinery_price * $refineryCount;

if ($currentDollars < $totalCost) {
    echo json_encode(['error' => 'Fonds insuffisants pour construire les raffineries']);
    exit;
}

// Si le joueur a assez d'argent, déduire les dollars
$newDollars = $currentDollars - $totalCost;
$updateDollars = $db->prepare("UPDATE resources SET dollars = ? WHERE user_id = ?");
$updateDollars->execute([$newDollars, $user_id]);

// Calculer les horaires de début et de fin de construction
$startTime = date('Y-m-d H:i:s');
$endTime = date('Y-m-d H:i:s', time() + $construction_duration);

// Insérer la construction dans la table `building_construction`
$insert = $db->prepare("
    INSERT INTO building_construction (user_id, building_type, construction_start_time, construction_end_time, construction_count) 
    VALUES (?, 'raffinerie', ?, ?, ?)
");
$insert->execute([$user_id, $startTime, $endTime, $refineryCount]);

// Après l'insertion de la construction dans building_construction, vous pouvez mettre à jour le nombre de raffineries dans la table user_buildings
$updateBuildings = $db->prepare("UPDATE user_buildings SET refinery_count = refinery_count + ? WHERE user_id = ?");
$updateBuildings->execute([$refineryCount, $user_id]);

// Renvoyer la réponse JSON avec les informations de construction
echo json_encode([
    'success' => true,
    'new_dollars' => $newDollars,
    'refinery_count' => $refineryCount,
    'construction_end_time' => $endTime,  // Heure de fin de construction au format ISO
    'construction_duration' => $construction_duration / 60  // Durée en minutes
]);

?>
