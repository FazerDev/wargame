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
    echo "Erreur de connexion à la base de données: " . $e->getMessage();
    exit;
}

// Récupérer les constructions en cours dont la fin est atteinte
$query = $db->prepare("SELECT * FROM building_construction WHERE construction_end_time <= NOW() AND status = 'en construction'");
$query->execute();
$constructions = $query->fetchAll(PDO::FETCH_ASSOC);

// Mettre à jour chaque construction terminée
foreach ($constructions as $construction) {
    $user_id = $construction['user_id'];
    $refinery_count = $construction['construction_count'];

    // Mettre à jour le nombre de raffineries pour l'utilisateur
    $update = $db->prepare("UPDATE user_buildings SET refinery_count = refinery_count + ? WHERE user_id = ?");
    $update->execute([$refinery_count, $user_id]);

    // Marquer la construction comme terminée
    $updateConstruction = $db->prepare("UPDATE building_construction SET status = 'terminé' WHERE id = ?");
    $updateConstruction->execute([$construction['id']]);
}

echo "Mise à jour des constructions terminées effectuée.";
?>
