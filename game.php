<?php
// Inclure le fichier de connexion à la base de données
include 'config/db.php'; 

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];

// Préparer la requête pour obtenir les ressources de l'utilisateur et le nombre de raffineries
$stmt = $conn->prepare("
    SELECT petrole, or_ressource, munitions, diamants, diesel, essence, points, dollars, refinery_count, ammo_factory_count 
    FROM resources 
    LEFT JOIN user_buildings ON resources.user_id = user_buildings.user_id 
    WHERE resources.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($petrole, $or_ressource, $munitions, $diamants, $diesel, $essence, $points, $dollars, $refinery_count, $ammo_factory_count);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base Militaire Interactive</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<!------------------------------------------------------------------------------------>
<!------------------------- Gestion de l'affichage de la MAP ------------------------->

<div id="base-outer" style="height: 2036px; width: 2036px; 
     transition-timing-function: cubic-bezier(0.1, 0.57, 0.1, 1); 
     transition-duration: 0ms; 
     transform: translate(-29px, -541px) translateZ(0px);">
    <div class="base-background one"></div>
    <div class="base-background two"></div>
    <div class="base-background three"></div>
    <div class="base-background four"></div>
    <div class="bank"></div>
    <div class="headquarter"></div>
    <div class="headquarter_background"></div>
    <div class="airport"></div>
    <div class="airport_background"></div>
    <div class="ammunition_factory"></div>
    <div class="ammunition_factory_background"></div>
    <div class="refinery"></div>
    <div class="refinery_background"></div>
    <div class="houses_1"></div>
    <div class="houses_background"></div>
    <div class="university"></div>
    <div class="university_background"></div>
    <div class="barracks"></div>
    <div class="barracks_background"></div>
    <div class="market"></div>
    <div class="market_background"></div>
    <div class="trade"></div>
    <div class="trade_2"></div>
    <div class="trade_1_background"></div>
    <div class="weapon_factory"></div>
    <div class="weapon_factory_background"></div>
    <div class="mine"></div>
    <div class="pentagon"></div>
</div>
<!------------------------------------------------------------------------------------>
<!--------------------------- RAFFINERIE-------------------------------------->
<div id="menu-refinery" class="menu">
    <button class="close-btn">&times;</button>
    <h3>Raffinerie</h3>
    <div id="construction-timer"></div>
    <p>Vous possédez actuellement : <span id="owned-refinery-count"><?php echo htmlspecialchars($refinery_count); ?></span> raffineries.</p>
    <p>Vous pouvez construire : <span id="max-refinery-count">0</span> raffineries.</p>
    <p>Combien de raffineries voulez-vous construire ?</p>
    <input type="number" id="refinery-count" min="1" value="1">
    <br>
    <button id="build-refinery">Construire</button>
</div>

<!------------------------------------------------------------------------------------>
<!----------------------- USINE DE MUNITIONS------------------------------------>
<div id="menu-ammunition_factory" class="menu">
    <button class="close-btn">&times;</button>
    <h3>Usine de Munitions</h3>
    <p>Vous possédez actuellement : <span id="owned-ammunition-count"><?php echo htmlspecialchars($ammo_factory_count); ?></span> usines de munitions.</p>
    <p>Vous pouvez construire : <span id="max-ammunition-count">0</span> usines de munitions.</p>
    <p>Combien d'usines de munitions voulez-vous construire ?</p>
    <input type="number" id="ammunition-count" min="1" value="1">
    <br>
    <button id="build-ammunition">Construire</button>
</div>
<!------------------------------------------------------------------------------------>
<!----------------------- MINE DOR------------------------------------>
<div id="menu-mine" class="menu">
    <button class="close-btn">&times;</button>
    <h3>Mine d'Or</h3>
    <p>Vous possédez actuellement : <span id="owned-mine-count"><?php echo htmlspecialchars($mine_count); ?></span> mines d'or.</p>
    <p>Vous pouvez construire : <span id="max-mine-count">0</span> mines d'or.</p>
    <p>Combien de mines d'or voulez-vous construire ?</p>
    <input type="number" id="mine-count" min="1" value="1">
    <br>
    <button id="build-mine">Construire</button>
</div>
<!------------------------------------------------------------------------------------>
<!----------------------- COMMERCE------------------------------------>
<div id="menu-trade" class="menu">
    <button class="close-btn">&times;</button>
    <h3>Gratte-ciel</h3>
    <p>Vous possédez actuellement : <span id="owned-trade-count"><?php echo htmlspecialchars($trade_count); ?></span> gratte-ciels.</p>
    <p>Vous pouvez construire : <span id="max-trade-count">0</span> gratte-ciels.</p>
    <p>Combien de gratte-ciels voulez-vous construire ?</p>
    <input type="number" id="trade-count" min="1" value="1">
    <br>
    <button id="build-trade">Construire</button>
</div>


<!----------------------- NOTIFICATIONS------------------------------------>
<div id="notification" class="notification" style="display: none;">
    <h4 id="notification-title"></h4>
    <p id="notification-message"></p>
</div>

<div id="notification1" class="notification1" style="display: none;">
    <h4 id="notification1-title"></h4>
    <p id="notification1-message"></p>
</div>
<!------------------------------------------------------------------------------------>
<!------------------------------ Gestion des ressources ------------------------------>

<div class="resources-bar">
    <div class="resource-item">
        <img src="assets/images/oil.gif" alt="Pétrole" class="resource-icon">
        <span class="resource-value" id="petrol"><?php echo htmlspecialchars($petrole); ?></span>
    </div>
    <div class="resource-item">
        <img src="assets/images/gazole.gif" alt="Diesel" class="resource-icon">
        <span class="resource-value" id="diesel"><?php echo htmlspecialchars($diesel); ?></span>
    </div>
    <div class="resource-item">
        <img src="assets/images/essence.gif" alt="Essence" class="resource-icon">
        <span class="resource-value" id="essence"><?php echo htmlspecialchars($essence); ?></span>
    </div>
    <div class="resource-item">
        <img src="assets/images/gold.gif" alt="Or" class="resource-icon">
        <span class="resource-value" id="gold"><?php echo htmlspecialchars($or_ressource); ?></span>
    </div>
    <div class="resource-item">
        <img src="assets/images/ammo.gif" alt="Munitions" class="resource-icon">
        <span class="resource-value" id="ammo"><?php echo htmlspecialchars($munitions); ?></span>
    </div>
    <div class="resource-item">
        <img src="assets/images/dollars.png" alt="Dollars" class="resource-icon">
        <span class="resource-value" id="dollars"><?php echo htmlspecialchars($dollars); ?></span>
    </div>
    <div class="resource-item">
        <img src="assets/images/diamond.gif" alt="Diamants" class="resource-icon">
        <span class="resource-value" id="diamonds"><?php echo htmlspecialchars($diamants); ?></span>
    </div>
    <div class="resource-item">
        <img src="assets/images/point.png" alt="Points" class="resource-icon">
        <span class="resource-value" id="points"><?php echo htmlspecialchars($points); ?></span>
    </div>
</div>

<!------------------------------------------------------------------------------------>
<!-------------------------- Fin Gestion des ressources ------------------------------>

<!------------------------------------------------------------------------------------>
<!------------------------------ Bouton bas de pages -- ------------------------------>

<div class="bottom-panel">
    <a href="#" class="panel-link" id="alliance-link">
        <img src="assets/images/alliance.png" alt="Page d'Alliance" class="panel-icon">
    </a>
    <a href="#classement" class="panel-link">
        <img src="assets/images/classement.png" alt="Classement" class="panel-icon">
    </a>
</div>

<!-- Ajout du bouton de déconnexion -->
<div class="logout-button">
    <form action="public/logout.php" method="POST">
        <button type="submit">Se déconnecter</button>
    </form>
</div>
<!-- Fin du bouton de déconnexion -->

<script src="assets/js/scripts.js"></script>
</body>
</html>
