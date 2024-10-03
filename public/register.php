<?php
// Inclure le header
include '../includes/header.php'; // header.php contient <head> et <body>
?>
<link rel="stylesheet" href="../assets/css/inscription.css">

<div class="container">
    <h2>Inscription</h2>
    <form action="register_handler.php" method="POST"> <!-- register_handler.php dans le même dossier public -->
        <label for="username">Nom d'utilisateur :</label>
        <input type="text" name="username" required>

        <label for="email">Email :</label>
        <input type="email" name="email" required>

        <label for="password">Mot de passe :</label>
        <input type="password" name="password" required>

        <button type="submit">S'inscrire</button>
    </form>
    <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous ici</a></p>
</div>

<?php
// Inclure le footer
include '../includes/footer.php';
?>
