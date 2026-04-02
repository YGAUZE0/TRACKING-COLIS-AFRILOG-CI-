<?php
// admin/edit_carrier.php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: manage_carriers.php');
    exit();
}

$carrier_id = $_GET['id'];

// Récupérer les informations du transporteur
$stmt = $pdo->prepare("SELECT * FROM carriers WHERE id = ?");
$stmt->execute([$carrier_id]);
$carrier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$carrier) {
    header('Location: manage_carriers.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $vehicle_number = $_POST['vehicle_number'];

    $stmt = $pdo->prepare("UPDATE carriers SET name = ?, phone = ?, vehicle_number = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $vehicle_number, $carrier_id]);

    header('Location: manage_carriers.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG - Modifier un transporteur</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Animation d'entrée */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animation au survol */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .animated-element {
            opacity: 0;
            animation: slideIn 0.6s ease-out forwards;
        }

        form input, form button {
            transition: all 0.3s ease;
        }

        form input:focus {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        form button:hover {
            animation: pulse 1s infinite;
            cursor: pointer;
        }

        .success-message {
            animation: slideIn 0.6s ease-out;
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            display: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>AFRILOG - Modifier un transporteur</h1>
        <nav>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <section class="dashboard">
        <h2>Modifier le transporteur</h2>
        <form method="POST">
            <label for="name">Nom :</label>
            <input type="text" id="name" name="name" value="<?= $carrier['name'] ?>" required>

            <label for="phone">Téléphone :</label>
            <input type="text" id="phone" name="phone" value="<?= $carrier['phone'] ?>" required>

            <label for="vehicle_number">Numéro de véhicule :</label>
            <input type="text" id="vehicle_number" name="vehicle_number" value="<?= $carrier['vehicle_number'] ?>" required>

            <button type="submit">Enregistrer</button>
        </form>
    </section>
</body>
</html>