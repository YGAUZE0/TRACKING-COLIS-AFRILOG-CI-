<?php
// admin/add_package.php
session_start();
include '../config.php';

function genererNumeroSuivi($nomClient) {
    $prefix = substr(strtoupper($nomClient), 0, 3);
    $prefix = str_pad($prefix, 3, 'X', STR_PAD_RIGHT);
    $numero = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
    return $prefix . $numero;
}

// Vérification de la session admin
// if (!isset($_SESSION['admin_id'])) {
//     header('Location: index.php');
//     exit();
// }

$error = '';
$tracking_preview = '';

// Récupération des données
try {
    $clients = $pdo->query(
        "SELECT id, nom 
        FROM users 
        WHERE role = 'client' 
        ORDER BY nom ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $carriers = $pdo->query("SELECT id, name FROM carriers")->fetchAll(PDO::FETCH_ASSOC);
    $cities = $pdo->query("SELECT id, name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_name = trim($_POST['sender_name']);
    $receiver_name = trim($_POST['receiver_name']);
    $status = trim($_POST['status']);
    $carrier_id = trim($_POST['carrier_id']);
    $city_id = trim($_POST['city_id']);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $vehicle_type = trim($_POST['vehicle_type']);
    
    // Génération du numéro de suivi
    $tracking_number = genererNumeroSuivi($receiver_name);
    
    // Vérification unicité
    $stmt = $pdo->prepare("SELECT id FROM packages WHERE tracking_number = ?");
    $stmt->execute([$tracking_number]);
    if($stmt->fetch()) {
        $tracking_number = genererNumeroSuivi($receiver_name . mt_rand(0,9));
    }

    // Données de position
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
    $address = trim($_POST['address']);

    // Validation
    $requiredFields = [
        $sender_name => "Nom de l'expéditeur",
        $receiver_name => "Destinataire",
        $status => "Statut",
        $carrier_id => "Transporteur",
        $city_id => "Ville",
        $latitude => "Latitude",
        $longitude => "Longitude",
        $address => "Adresse"
    ];

    foreach ($requiredFields as $value => $field) {
        if (empty($value)) {
            $error = "Le champ '$field' est requis";
            break;
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO packages 
                (tracking_number, sender_name, receiver_name, status, carrier_id, city_id, weight, vehicle_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $stmt->execute([
                $tracking_number, 
                $sender_name, 
                $receiver_name, 
                $status, 
                $carrier_id,
                $city_id,
                $weight,
                $vehicle_type
            ]);
            $package_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO positions 
                (package_id, latitude, longitude, address, timestamp)
                VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$package_id, $latitude, $longitude, $address]);

            $pdo->commit();
            
            header('Location: dashboard.php?success=1');
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur base de données : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG - Ajouter un colis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="fade-in">
        <h1>AFRILOG - Ajouter un colis</h1>
        <nav>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <section class="dashboard fade-in">
        <h2>Ajouter un colis</h2>
        
        <?php if ($error) : ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="tracking_preview" class="form-label">Numéro de suivi généré :</label>
                <input type="text" id="tracking_preview" class="form-control" readonly>
                <small class="text-muted">Ce numéro est généré automatiquement</small>
            </div>

            <div class="mb-3">
                <label for="sender_name" class="form-label">Nom de l'expéditeur :</label>
                <input type="text" id="sender_name" name="sender_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="receiver_name" class="form-label">Destinataire :</label>
                <select id="receiver_name" name="receiver_name" class="form-select" required>
                    <option value="">Choisir un client</option>
                    <?php foreach ($clients as $client) : ?>
                        <option value="<?= htmlspecialchars($client['nom']) ?>">
                            <?= htmlspecialchars($client['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Statut :</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="en attente">En attente</option>
                    <option value="en transit">En transit</option>
                    <option value="livré">Livré</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="carrier_id" class="form-label">Transporteur :</label>
                <select id="carrier_id" name="carrier_id" class="form-select" required>
                    <option value="">Choisir un transporteur</option>
                    <?php foreach ($carriers as $carrier) : ?>
                        <option value="<?= $carrier['id'] ?>">
                            <?= htmlspecialchars($carrier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="city_id" class="form-label">Ville de destination :</label>
                <select id="city_id" name="city_id" class="form-select" required>
                    <option value="">Choisir une ville</option>
                    <?php foreach ($cities as $city) : ?>
                        <option value="<?= $city['id'] ?>">
                            <?= htmlspecialchars($city['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
<div class="mb-3">
    <label for="weight" class="form-label">Poids (kg) :</label>
    <input type="number" step="0.1" id="weight" name="weight" class="form-control" required>
</div>

<div class="mb-3">
    <label for="vehicle_type" class="form-label">Type de véhicule :</label>
    <select id="vehicle_type" name="vehicle_type" class="form-select" required>
        <option value="pick-up">Pick-up</option>
        <option value="10T">Camion 10T</option>
        <option value="Plateau">Plateau</option>
        <option value="porte char">Porte-char</option>
        <option value="autres">Autres</option>
    </select>
</div>  
            

            <div class="card mt-4 mb-3 shadow">
                <div class="card-header bg-info text-white">
                    Position initiale
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" step="any" name="latitude" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" step="any" name="longitude" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-seam"></i> Ajouter le colis
            </button>
        </form>
    </section>

    <script>
        // Génération du numéro de suivi en temps réel
        document.getElementById('receiver_name').addEventListener('change', function() {
            const nom = this.value.toUpperCase();
            let prefix = nom.substring(0, 3);
            
            // Compléter avec X si nécessaire
            if(prefix.length < 3) {
                prefix = prefix.padEnd(3, 'X');
            }
            
            // Générer chiffres aléatoires
            const chiffres = Math.floor(Math.random() * 1000)
                .toString()
                .padStart(3, '0');
            
            document.getElementById('tracking_preview').value = prefix + chiffres;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bs5-toast@1.0.0/dist/bs5-toast.min.js"></script>

</body><style>
        .card-header.bg-info {
            background: linear-gradient(45deg, #17a2b8, #138496);
        }
        
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        input[type="number"] {
            -moz-appearance: textfield;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        header {
            background: linear-gradient(45deg, #2c3e50, #3498db);
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: fadeIn 0.8s ease-out;
        }

        .dashboard {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: slideUp 0.6s ease-out;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52,152,219,0.2);
        }

        .btn-primary {
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .dashboard {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</html>