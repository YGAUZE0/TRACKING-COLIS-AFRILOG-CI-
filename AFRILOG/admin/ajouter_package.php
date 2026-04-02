<?php
// admin/add_package.php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Récupération des données nécessaires
$carriers = $pdo->query("SELECT carrier_id, name FROM Carrier")->fetchAll(PDO::FETCH_ASSOC);
$cities = $pdo->query("SELECT city_id, city_name FROM City")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT user_id, nom FROM User")->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération et validation des données
        $requiredFields = [
            'tracking_number' => 'Numéro de suivi',
            'destination_city_id' => 'Ville de destination',
            'destination_address' => 'Adresse de livraison',
            'carrier_id' => 'Transporteur',
            'user_id' => 'Client',
            'weight' => 'Poids'
        ];

        $data = [];
        foreach ($requiredFields as $field => $name) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ $name est obligatoire");
            }
            $data[$field] = secureData($_POST[$field]);
        }

        // Validation spécifique
        if (!preg_match('/^[A-Z]{3}\d{9}$/', $data['tracking_number'])) {
            throw new Exception("Format de suivi invalide (ex: AFR123456789)");
        }

        if ($pdo->query("SELECT package_id FROM Package WHERE tracking_number = '".$data['tracking_number']."'")->rowCount() > 0) {
            throw new Exception("Ce numéro de suivi existe déjà");
        }

        // Conversion des données
        $data['weight'] = (float)str_replace(',', '.', $data['weight']);
        $data['dimensions'] = !empty($_POST['dimensions']) ? secureData($_POST['dimensions']) : null;
        $data['estimated_delivery'] = !empty($_POST['estimated_delivery']) ? $_POST['estimated_delivery'] : null;

        // Récupération des coordonnées de la ville
        $cityStmt = $pdo->prepare("SELECT latitude, longitude FROM City WHERE city_id = ?");
        $cityStmt->execute([$data['destination_city_id']]);
        $cityData = $cityStmt->fetch(PDO::FETCH_ASSOC);

        if (!$cityData) {
            throw new Exception("Ville de destination invalide");
        }

        // Insertion dans la base
        $stmt = $pdo->prepare("
            INSERT INTO Package (
                tracking_number,
                destination_city_id,
                destination_address,
                destination_zipcode,
                current_position,
                status,
                carrier_id,
                user_id,
                weight,
                dimensions,
                estimated_delivery
            ) VALUES (
                :tracking_number,
                :destination_city_id,
                :destination_address,
                :destination_zipcode,
                ST_GeomFromText(CONCAT('POINT(', ?, ', ', ?, ')')),
                :status,
                :carrier_id,
                :user_id,
                :weight,
                :dimensions,
                :estimated_delivery
            )
        ");

        $params = [
            ':tracking_number' => $data['tracking_number'],
            ':destination_city_id' => $data['destination_city_id'],
            ':destination_address' => $data['destination_address'],
            ':destination_zipcode' => $_POST['destination_zipcode'] ?? null,
            ':status' => 'En préparation',
            ':carrier_id' => $data['carrier_id'],
            ':user_id' => $data['user_id'],
            ':weight' => $data['weight'],
            ':dimensions' => $data['dimensions'],
            ':estimated_delivery' => $data['estimated_delivery']
        ];

        $stmt->execute(array_merge($params, [$cityData['latitude'], $cityData['longitude']]));

        header('Location: dashboard.php?success=1');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function secureData($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
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
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">
                <!-- Colonne gauche -->
                <div class="col-md-6">
                    <div class="mb-3 form-group">
                        <label class="form-label">Numéro de suivi*</label>
                        <input type="text" name="tracking_number" class="form-control" 
                               pattern="[A-Z]{3}\d{9}" 
                               title="Format: 3 lettres majuscules suivies de 9 chiffres (ex: AFR123456789)" 
                               required>
                    </div>

                    <div class="mb-3 form-group">
                        <label class="form-label">Client*</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Sélectionnez un client</option>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?= $user['user_id'] ?>">
                                    <?= secureData($user['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 form-group">
                        <label class="form-label">Transporteur*</label>
                        <select name="carrier_id" class="form-select" required>
                            <option value="">Sélectionnez un transporteur</option>
                            <?php foreach ($carriers as $carrier) : ?>
                                <option value="<?= $carrier['carrier_id'] ?>">
                                    <?= secureData($carrier['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 form-group">
                        <label class="form-label">Poids (kg)*</label>
                        <input type="number" name="weight" step="0.01" class="form-control" required>
                    </div>
                </div>

                <!-- Colonne droite -->
                <div class="col-md-6">
                    <div class="mb-3 form-group">
                        <label class="form-label">Ville de destination*</label>
                        <select name="destination_city_id" class="form-select" required>
                            <option value="">Sélectionnez une ville</option>
                            <?php foreach ($cities as $city) : ?>
                                <option value="<?= $city['city_id'] ?>">
                                    <?= secureData($city['city_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 form-group">
                        <label class="form-label">Adresse complète*</label>
                        <textarea name="destination_address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="mb-3 form-group">
                        <label class="form-label">Code postal</label>
                        <input type="text" name="destination_zipcode" class="form-control">
                    </div>

                    <div class="mb-3 form-group">
                        <label class="form-label">Dimensions (LxHxP en cm)</label>
                        <input type="text" name="dimensions" class="form-control" 
                               pattern="\d+x\d+x\d+"
                               title="Format: 30x20x15">
                    </div>

                    <div class="mb-3 form-group">
                        <label class="form-label">Date de livraison estimée</label>
                        <input type="date" name="estimated_delivery" class="form-control" 
                               min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-box-open me-2"></i>Créer le colis
                </button>
            </div>
        </form>
    </section>

    /* Animations clés */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Styles généraux */
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

        /* Styles du formulaire */
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52,152,219,0.2);
            transform: scale(1.02);
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
        }

        /* Animations des éléments du formulaire */
        .form-group {
            opacity: 0;
            animation: slideUp 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.2s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.6s; }
        .form-group:nth-child(4) { animation-delay: 0.8s; }
        .form-group:nth-child(5) { animation-delay: 1s; }
        .btn { animation-delay: 1.2s; }

        /* Animation d'erreur */
        .alert-danger {
            animation: shake 0.4s ease-in-out;
            border: 2px solid #e74c3c;
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            header h1 {
                font-size: 1.5rem;
            }
        }
    </style>

        <footer class="fade-in">
            <p>&copy; 2023 AFRILOG. Tous droits réservés.</p>
        </footer>
</body>
</html>