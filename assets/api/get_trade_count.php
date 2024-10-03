<?php

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
    die("Connexion échouée: " . $e->getMessage());
}

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Préparer la requête pour obtenir le nombre de raffineries
$query = $db->prepare("SELECT trade_count FROM user_buildings WHERE user_id = ?");
$query->execute([$user_id]);
$result = $query->fetch(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur a des bâtiments enregistrés
if ($result) {
    $trade_count = $result['trade_count'];
} else {
    $trade_count = 0; // Aucun bâtiment trouvé
}

echo json_encode(['trade_count' => $trade_count]);
?>
