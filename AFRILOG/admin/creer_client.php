<?php
session_start();
include '../config.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['admin_id'])) {
    die("Accès refusé.");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"]);
    $abreviation = strtoupper(trim($_POST["abreviation"])); // Toujours en majuscule
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $role = ($_POST["role"] === "admin") ? "admin" : "client";

    // Vérifier que tous les champs sont remplis
    if (empty($nom) || empty($abreviation) || empty($email) || empty($password)) {
        $error = "Tous les champs sont requis.";
    } else {
        // Vérifier si l'email ou l'abréviation existe déjà
        $stmt = $pdo->prepare("SELECT id FROM Clients WHERE email = ? OR abreviation = ?");
        $stmt->execute([$email, $abreviation]);

        if ($stmt->rowCount() > 0) {
            $error = "Un client avec cet email ou cette abréviation existe déjà.";
        } else {
            // Hasher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insérer le client
            $stmt = $pdo->prepare("INSERT INTO Clients (nom, abreviation, email, password, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nom, $abreviation, $email, $hashed_password, $role])) {
                $success = "Client ajouté avec succès.";
            } else {
                $error = "Erreur lors de l'ajout du client.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Client</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container py-5">
        <h2>Ajouter un Client</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom du client</label>
                <input type="text" id="nom" name="nom" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="abreviation" class="form-label">Abréviation</label>
                <input type="text" id="abreviation" name="abreviation" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rôle</label>
                <select id="role" name="role" class="form-control">
                    <option value="client">Client</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
</body>
</html>
