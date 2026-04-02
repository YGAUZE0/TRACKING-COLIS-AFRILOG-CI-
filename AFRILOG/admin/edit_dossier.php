<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$dossier_id = $_GET['id'] ?? 0;

// Récupération des données existantes
$stmt = $pdo->prepare("SELECT * FROM Dossiers WHERE id = ?");
$stmt->execute([$dossier_id]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dossier) die("Dossier introuvable");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            ':numero_dossier' => htmlspecialchars($_POST['numero_dossier']),
            ':nom' => htmlspecialchars($_POST['nom']),
            ':description' => htmlspecialchars($_POST['description']),
            ':eta' => $_POST['eta'] ? date('Y-m-d H:i:s', strtotime($_POST['eta'])) : null,
            ':type_dossier' => htmlspecialchars($_POST['type_dossier']),
            ':mode' => htmlspecialchars($_POST['mode']),
            ':nombre_conteneur' => (int)$_POST['nombre_conteneur'],
            ':nom_du_navire' => htmlspecialchars($_POST['nom_du_navire']),
            ':numero_bl' => htmlspecialchars($_POST['numero_bl']),
            ':poids' => (float)$_POST['poids'],
            ':statut' => htmlspecialchars($_POST['statut']),
            ':id' => $dossier_id
        ];

        $stmt = $pdo->prepare("
            UPDATE Dossiers SET
                numero_dossier = :numero_dossier,
                nom = :nom,
                description = :description,
                eta = :eta,
                type_dossier = :type_dossier,
                mode = :mode,
                nombre_conteneur = :nombre_conteneur,
                nom_du_navire = :nom_du_navire,
                numero_bl = :numero_bl,
                poids = :poids,
                statut = :statut
            WHERE id = :id
        ");

        $stmt->execute($data);
        $_SESSION['flash_message'] = "Dossier mis à jour avec succès !";
        header("Location: detail_dossier.php?id=$dossier_id");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Récupération des listes
$responsables = $pdo->query("SELECT id, nom FROM Users WHERE role = 'administrateur'")->fetchAll();
$clients = $pdo->query("SELECT id, nom FROM Users WHERE role = 'client'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    
</head>
<body>
    <header class="header-anim">
        <h1>Édition du dossier <?= $dossier['numero_dossier'] ?></h1>
        <nav>
            <ul>
                <li><a href="detail_dossier.php?id=<?= $dossier_id ?>" class="btn-float"><i class="fas fa-arrow-left"></i> Retour</a></li>
            </ul>
        </nav>
    </header>

    <section class="dashboard">
        <!-- Formulaire similaire à creer-dossier.php avec valeurs pré-remplies -->
        <form method="POST" class="card">
            <div class="input-group">
                <input type="text" name="numero_dossier" value="<?= $dossier['numero_dossier'] ?>" required>
                <span class="floating-label">Numéro dossier</span>
            </div>

            <div class="input-group">
                <select name="statut" required>
                    <option value="En préparation" <?= $dossier['statut'] === 'En préparation' ? 'selected' : '' ?>>En préparation</option>
                    <option value="En cours" <?= $dossier['statut'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Clôturé" <?= $dossier['statut'] === 'Clôturé' ? 'selected' : '' ?>>Clôturé</option>
                </select>
                <span class="floating-label">Statut</span>
            </div>

            <!-- Ajouter tous les autres champs avec leurs valeurs -->

            <button type="submit" class="submit-btn">
                <span class="btn-text">Mettre à jour</span>
                <div class="loader"></div>
            </button>
        </form>
    </section>
</body>
</html>