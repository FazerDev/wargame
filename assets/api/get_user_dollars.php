<?php
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

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Préparer la requête pour obtenir les dollars depuis la table ressources
$query = $db->prepare("SELECT dollars FROM resources WHERE user_id = ?");
$query->execute([$user_id]);
$result = $query->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $dollars = $result['dollars'];
    echo json_encode(['success' => true, 'dollars' => $dollars]);
} else {
    echo json_encode(['error' => 'Utilisateur introuvable']);
}
?>
