<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$dossier_id = $_GET['id'] ?? 0;

// Récupération des données existantes
$stmt = $pdo->prepare("SELECT * FROM dossier WHERE id = ?");
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
$responsables = $pdo->query("SELECT id, nom FROM Users WHERE role = 'responsable'")->fetchAll();
$clients = $pdo->query("SELECT id, nom FROM Users WHERE role = 'client'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG - Details Dossier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #7c3aed;
            --success: #10b981;
            --error: #ef4444;
            --background: #f8fafc;
            --text: #1e293b;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        :root {
            --primary: #2563eb;
            --secondary: #7c3aed;
            --success: #10b981;
            --error: #ef4444;
            --background: #f8fafc;
            --text: #1e293b;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: var(--background);
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            color: var(--text);
        }

        .header-anim {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .header-anim::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1));
            animation: rotate 20s linear infinite;
        }

        .dashboard {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.6s var(--transition) forwards;
        }

        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
            opacity: 0;
            transform: translateX(-20px);
            animation: fadeInRight 0.4s forwards;
        }

        .input-group:nth-child(even) {
            animation-delay: 0.1s;
        }

        .floating-label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            transition: var(--transition);
            color: #64748b;
            background: white;
            padding: 0 0.3rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        input:focus ~ .floating-label,
        input:not(:placeholder-shown) ~ .floating-label,
        select:valid ~ .floating-label {
            top: 0;
            font-size: 0.8em;
            color: var(--primary);
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.1);
            opacity: 0;
            transition: var(--transition);
        }

        .submit-btn:hover::after {
            opacity: 1;
        }

        .loader {
            width: 1.2rem;
            height: 1.2rem;
            border: 2px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeInRight {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .flash-message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            animation: slideIn 0.4s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .flash-message.error {
            background: #fee2e2;
            color: var(--error);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 640px) {
            .card {
                padding: 1.5rem;
            }
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }
    </style>
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
            <div class="input-group">
                <input type="text" name="numemro_bl" value="<?= $dossier['numero_bl'] ?>" required>
                <span class="floating-label">Numéro bl</span>
            </div>
            <div class="input-group">
                <input type="text" name="nom" value="<?= $dossier['nom'] ?>" required>
                <span class="floating-label">Nom du Dossier</span>
            </div>
            <div class="input-group">
                <input type="text" name="description" value="<?= $dossier['description'] ?>" required>
                <span class="floating-label">Description</span>
            </div>
            <div class="input-group">
                <input type="date" name="eta" value="<?= $dossier['eta'] ? date('Y-m-d', strtotime($dossier['eta'])) : '' ?>">
                <span class="floating-label">Date d'ETA</span>
            </div>
            <div class="input-group">
                <input type="text" name="type_dossier" value="<?= $dossier['type_dossier'] ?>" required>
                <span class="floating-label">Type de Dossier</span>
            </div>
            <div class="input-group">
                <input type="text" name="mode" value="<?= $dossier['mode'] ?>" required>
                <span class="floating-label">Mode</span>
            </div>
            <div class="input-group">
                <input type="number" name="nombre_conteneur" value="<?= $dossier['nombre_conteneur'] ?>" required>
                <span class="floating-label">Nombre de conteneur</span>
            </div>
            <div class="input-group">
                <input type="text" name="nom_du_navire" value="<?= $dossier['nom_du_navire'] ?>" required>
                <span class="floating-label">Nom du navire</span>   
            </div>
            <div class="input-group">
                <input type="number" name="poids" value="<?= $dossier['poids'] ?>" required>
                <span class="floating-label">Poids</span>
            </div>
            <div class="input-group">
                <select name="responsable_id" required>
                    <option value="">Sélectionner un responsable</option>
                    <?php foreach ($responsables as $responsable): ?>
                        <option value="<?= $responsable['id'] ?>" <?= $dossier['responsable_id'] == $responsable['id'] ? 'selected' : '' ?>>
                            <?= $responsable['nom'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="floating-label">Responsable</span>
            </div>
            <div class="input-group">
                <select name="client_id" required>
                    <option value="">Sélectionner un client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>" <?= $dossier['client_id'] == $client['id'] ? 'selected' : '' ?>>
                            <?= $client['nom'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="floating-label">Client</span>
            </div>
           

            <button type="submit" class="submit-btn">
                <span class="btn-text">Mettre à jour</span>
                <div class="loader"></div>
            </button>
        </form>
    </section>
</body>
</html>