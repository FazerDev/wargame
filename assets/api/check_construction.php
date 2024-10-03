<?php

// Activer les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

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

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérifier les constructions en cours
$query = $db->prepare("SELECT * FROM building_construction WHERE user_id = ? AND construction_end_time <= NOW()");
$query->execute([$user_id]);
$constructions = $query->fetchAll(PDO::FETCH_ASSOC);

if ($constructions) {
    foreach ($constructions as $construction) {
        // Mise à jour de la table des bâtiments (par exemple, augmenter le nombre de raffineries)
        $building_type = $construction['building_type'];
        $building_count = $construction['construction_count'];
        $updateBuildings = $db->prepare("
            UPDATE user_buildings SET {$building_type}_count = {$building_type}_count + ?
            WHERE user_id = ?
        ");
        $updateBuildings->execute([$building_count, $user_id]);

        // Supprimer la construction terminée
        $deleteConstruction = $db->prepare("DELETE FROM building_construction WHERE id = ?");
        $deleteConstruction->execute([$construction['id']]);
    }
    echo json_encode(['success' => true, 'message' => 'Constructions terminées et mises à jour']);
} else {
    echo json_encode(['success' => false, 'message' => 'Aucune construction terminée']);
}

?>
