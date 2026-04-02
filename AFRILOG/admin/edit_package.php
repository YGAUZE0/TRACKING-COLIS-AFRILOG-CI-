<?php
// admin/edit_package.php
session_start();
include '../config.php';

// Vérification de l'authentification admin


$error = '';
$success = '';
$package_id = $_GET['id'] ?? 0;

// Récupération des données du colis
$package = $pdo->prepare("
    SELECT p.*, c.name AS city_name, cr.name AS carrier_name 
    FROM packages p
    LEFT JOIN cities c ON p.city_id = c.id
    LEFT JOIN carriers cr ON p.carrier_id = cr.id
    WHERE p.id = ?
");
$package->execute([$package_id]);
$package_data = $package->fetch(PDO::FETCH_ASSOC);

if (!$package_data) {
    header('Location: dashboard.php');
    exit();
}

// Récupération des positions
$positions = $pdo->prepare("
    SELECT * FROM positions 
    WHERE package_id = ? 
    ORDER BY timestamp DESC
");
$positions->execute([$package_id]);

// Récupération de la dernière position
$last_position = $pdo->prepare("
    SELECT * FROM positions 
    WHERE package_id = ? 
    ORDER BY timestamp DESC 
    LIMIT 1
");
$last_position->execute([$package_id]);
$last_position_data = $last_position->fetch(PDO::FETCH_ASSOC);

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Modification unique du statut
    if (isset($_POST['update_status'])) {
        $status = trim($_POST['status']);
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE packages 
                SET status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$status, $package_id]);
            
            $pdo->commit();
            $success = 'Statut mis à jour avec succès!';
            header("Refresh:0");
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
    // Requête corrigée
// Dans la partie de mise à jour du colis
if (isset($_POST['update_package'])) {
   
$stmt = $pdo->prepare("
UPDATE packages 
SET status = :status, 
    carrier_id = :carrier_id, 
    city_id = :city_id 
WHERE id = :id
");

$stmt->execute([
':status' => $status,
':carrier_id' => $carrier_id,
':city_id' => $city_id,
':id' => $package_id
]);
$stmt = $pdo->prepare("
    UPDATE packages 
    SET status = :status, 
        carrier_id = :carrier_id, 
        city_id = :city_id 
    WHERE id = :id
");

$stmt->execute([
    ':status' => $status,
    ':carrier_id' => $carrier_id,
    ':city_id' => $city_id,
    ':id' => $package_id
]);
}
    
    // Rafraîchir les données
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = :id");
    $stmt->execute([':id' => $package_id]);
    $package_data = $stmt->fetch();
    
    $success = 'Informations mises à jour avec succès!';


    
    // Ajout d'une nouvelle position
    if (isset($_POST['add_position'])) {
        $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
        $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
        $address = trim($_POST['address']);

        if ($latitude && $longitude && !empty($address)) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO positions 
                    (package_id, latitude, longitude, address, timestamp)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$package_id, $latitude, $longitude, $address]);

                if ($package_data['status'] !== 'en transit') {
                    $pdo->exec("UPDATE packages SET status = 'en transit' WHERE id = $package_id");
                }

                $pdo->commit();
                $success = 'Position ajoutée avec succès!';
                header("Refresh:0");
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Erreur lors de l'ajout : " . $e->getMessage();
            }
        } else {
            $error = 'Veuillez remplir tous les champs de position correctement';
        }
    }

    // Mise à jour des positions existantes
    if (isset($_POST['update_positions'])) {
        try {
            $pdo->beginTransaction();
            foreach ($_POST['positions'] as $position_id => $position_data) {
                $stmt = $pdo->prepare("
                    UPDATE positions 
                    SET latitude = ?, longitude = ?, address = ?, timestamp = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $position_data['latitude'],
                    $position_data['longitude'],
                    $position_data['address'],
                    $position_data['timestamp'],
                    $position_id
                ]);
            }
            $pdo->commit();
            $success = 'Positions mises à jour avec succès!';
            header("Refresh:0");
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }


$carriers = $pdo->query("SELECT id, name FROM carriers")->fetchAll();
$cities = $pdo->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le colis - AFRILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .map-container { height: 300px; border-radius: 10px; overflow: hidden; margin: 20px 0; }
        .timeline { position: relative; padding-left: 30px; margin: 20px 0; }
        .timeline::before { content: ''; position: absolute; left: 10px; width: 2px; background: #3498db; top: 0; bottom: 0; }
        .timeline-item { position: relative; margin-bottom: 20px; padding-left: 20px; }
        .timeline-item::before { content: '➤'; position: absolute; left: -8px; color: #3498db; font-size: 1.2em; }
        .table-hover tbody tr:hover { background-color: rgba(52, 152, 219, 0.05); }
        .osm-iframe { border: 1px solid #ddd; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Gestion du colis #<?= $package_data['tracking_number'] ?></h1>
        
        <?php if ($error) : ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if ($success) : ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <!-- Carte de la dernière position -->
        <div class="card mb-4 shadow">
            <div class="card-header bg-warning text-dark">
                Dernière position enregistrée
            </div>
            <div class="card-body">
                <?php if ($last_position_data) : ?>
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Date/heure</dt>
                                <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($last_position_data['timestamp'])) ?></dd>
                                <dt class="col-sm-4">Coordonnées</dt>
                                <dd class="col-sm-8"><?= $last_position_data['latitude'] ?>, <?= $last_position_data['longitude'] ?></dd>
                                <dt class="col-sm-4">Adresse</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($last_position_data['address']) ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <iframe class="osm-iframe" 
                                width="100%" 
                                height="250"
                                src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $last_position_data['longitude']-0.01 ?>%2C<?= $last_position_data['latitude']-0.01 ?>%2C<?= $last_position_data['longitude']+0.01 ?>%2C<?= $last_position_data['latitude']+0.01 ?>&marker=<?= $last_position_data['latitude'] ?>%2C<?= $last_position_data['longitude'] ?>">
                            </iframe>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="alert alert-info mb-0">Aucune position enregistrée</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulaire de modification du colis -->
        
    <div class="card-header bg-primary text-white">
        Modification du statut
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-12">
                    <select name="status" class="form-select">
                        <option value="en attente" <?= $package_data['status'] === 'en attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="en transit" <?= $package_data['status'] === 'en transit' ? 'selected' : '' ?>>En transit</option>
                        <option value="livré" <?= $package_data['status'] === 'livré' ? 'selected' : '' ?>>Livré</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="update_status" class="btn btn-primary mt-3">
                Mettre à jour le statut
            </button>
        </form>
    </div>
</div>
      <button type="submit" name="update_package" class="btn btn-primary mt-3">Mettre à jour</button>
                </form>
            </div>
        </div>

        <!-- Modification des positions existantes -->
        <div class="card mb-4 shadow">
            <div class="card-header bg-info text-white">
                Modification des positions
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date/Heure</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>Adresse</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($positions as $position) : ?>
                                <tr>
                                    <td>
                                        <input type="datetime-local" 
                                            name="positions[<?= $position['id'] ?>][timestamp]" 
                                            value="<?= date('Y-m-d\TH:i', strtotime($position['timestamp'])) ?>" 
                                            class="form-control">
                                    </td>
                                    <td>
                                        <input type="number" step="any" 
                                            name="positions[<?= $position['id'] ?>][latitude]" 
                                            value="<?= $position['latitude'] ?>" 
                                            class="form-control">
                                    </td>
                                    <td>
                                        <input type="number" step="any" 
                                            name="positions[<?= $position['id'] ?>][longitude]" 
                                            value="<?= $position['longitude'] ?>" 
                                            class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" 
                                            name="positions[<?= $position['id'] ?>][address]" 
                                            value="<?= htmlspecialchars($position['address']) ?>" 
                                            class="form-control">
                                    </td>
                                    <td>
                                        <a href="delete_position.php?id=<?= $position['id'] ?>&package_id=<?= $package_id ?>" 
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Supprimer cette position ?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" name="update_positions" class="btn btn-primary">
                        Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>

        <!-- Ajout d'une nouvelle position -->
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                Ajouter une nouvelle position
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="number" step="any" name="latitude" 
                                class="form-control" placeholder="Latitude" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="any" name="longitude" 
                                class="form-control" placeholder="Longitude" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="address" 
                                class="form-control" placeholder="Adresse complète" required>
                        </div>
                    </div>
                    <button type="submit" name="add_position" class="btn btn-success mt-3">
                        <i class="bi bi-plus-circle"></i> Ajouter
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>