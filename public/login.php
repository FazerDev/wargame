<?php
// Inclure le header
include '../includes/header.php'; // Assurez-vous que le chemin est correct pour inclure le header
?>
<link rel="stylesheet" href="../assets/css/connexion.css">

<div class="container">
    <h2>Connexion</h2>
    <form action="auth.php" method="POST">
        <label for="email">Email :</label>
        <input type="email" name="email" required>

        <label for="password">Mot de passe :</label>
        <input type="password" name="password" required>

        <button type="submit">Se connecter</button>
    </form>
    <p>Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous ici</a></p>
</div>

<?php
// Inclure le footer
include '../includes/footer.php'; // Assurez-vous que le chemin est correct pour inclure le footer
?>
