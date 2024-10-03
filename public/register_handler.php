<?php
include '../config/db.php'; // Connexion à la base de données

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $checkUser = "SELECT id FROM users WHERE email = ? OR username = ?";
    $stmt = $conn->prepare($checkUser);
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Cet utilisateur existe déjà.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // Insère les ressources initiales
            $stmt = $conn->prepare("INSERT INTO resources (user_id, or_ressource, petrole, munitions, diamants, diesel, essence) VALUES (?, 10000, 10000, 10000, 10000, 10000, 10000)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Insère les bâtiments par défaut
            $default_refinery_count = 1;
            $default_mine_count = 1;
            $default_ammo_factory_count = 0;
            $default_trade_count = 0;

            $stmt = $conn->prepare("INSERT INTO user_buildings (user_id, refinery_count, mine_count, ammo_factory_count, trade_count) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiii", $user_id, $default_refinery_count, $default_mine_count, $default_ammo_factory_count, $default_trade_count);
            $stmt->execute();

            header("Location: login.php"); // Redirige vers la page de connexion après inscription
            exit();
        } else {
            echo "Erreur lors de l'inscription.";
        }
    }

    $stmt->close();
    $conn->close();
}
?>
