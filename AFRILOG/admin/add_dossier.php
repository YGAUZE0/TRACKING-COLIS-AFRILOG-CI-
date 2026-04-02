<?php
session_start();
require '../config.php';

// Récupération des utilisateurs par rôle
try {
    $responsables = $pdo->query("SELECT id, nom FROM users WHERE role = 'administrateur' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    $clients = $pdo->query("SELECT id, nom FROM users WHERE role = 'client' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des utilisateurs : " . $e->getMessage();
    header('Location: gestion-dossiers.php');
    exit();
}

// Fonction pour générer le numéro de dossier
function genererNumeroDossier($pdo, $clientId) {
    // Vérifier d'abord que le client existe
    $stmt = $pdo->prepare("SELECT id, UPPER(SUBSTRING(REPLACE(nom, ' ', ''), 1, 3)) as lettres_client FROM users WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        throw new Exception("Client sélectionné introuvable");
    }
    
    $lettresClient = $client['lettres_client'] ?? 'CLI';
    
    // Récupérer le dernier numéro séquentiel
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(numero_dossier, 5, 4) AS UNSIGNED)) as max_num FROM dossier WHERE numero_dossier LIKE 'M225%'");
    $lastNum = $stmt->fetch(PDO::FETCH_ASSOC)['max_num'] ?? 0;
    $nextNum = $lastNum + 1;
    
    // Formater le numéro
    $numero = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    
    return "M225" . $numero . $lettresClient;
}

// Générer un numéro par défaut si c'est une nouvelle création
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_SESSION['form_data'])) {
    try {
        $defaultClientId = $clients[0]['id'] ?? 0;
        if ($defaultClientId) {
            $_SESSION['pre_generated_numero'] = genererNumeroDossier($pdo, $defaultClientId);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}
// Dans la partie traitement du formulaire (remplacez votre section POST actuelle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Validation renforcée
        $requiredFields = [
            'nom' => 'Nom du dossier',
            'client' => 'Client',
            'type_dossier' => 'Type de dossier',
            'mode' => 'Mode de transport',
            'statut' => 'Statut',
            'poids' => 'Poids'
        ];

        $errors = [];
        foreach ($requiredFields as $field => $name) {
            if (empty($_POST[$field])) {
                $errors[] = "Le champ $name est obligatoire";
            }
        }

        // Validation spécifique pour le numéro de dossier
        if (empty($_POST['numero_dossier']) || !preg_match('/^M225\d{4}[A-Z]{3}$/', $_POST['numero_dossier'])) {
            $errors[] = "Numéro de dossier invalide - le format doit être M225XXXXYYY";
        }

        if (!empty($errors)) {
            throw new Exception(implode("<br>", $errors));
        }

        // 2. Préparation des données avec vérification
        $clientId = (int)$_POST['client'];
        $responsableId = !empty($_POST['responsable']) ? (int)$_POST['responsable'] : null;

        // Vérification que le client existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'client' LIMIT 1");
        $stmt->execute([$clientId]);
        if (!$stmt->fetch()) {
            throw new Exception("Le client sélectionné n'existe pas");
        }

        // Vérification que le responsable existe (si spécifié)
        if ($responsableId) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'administrateur' LIMIT 1");
            $stmt->execute([$responsableId]);
            if (!$stmt->fetch()) {
                throw new Exception("Le responsable sélectionné n'existe pas");
            }
        }

        // 3. Nettoyage et formatage des données
        $data = [
            ':numero_dossier' => $_POST['numero_dossier'],
            ':nom' => htmlspecialchars(trim($_POST['nom'])),
            ':description' => !empty($_POST['description']) ? htmlspecialchars(trim($_POST['description'])) : null,
            ':client_id' => $clientId,
            ':responsable_id' => $responsableId,
            ':eta' => !empty($_POST['eta']) ? date('Y-m-d H:i:s', strtotime($_POST['eta'])) : null,
            ':type_dossier' => htmlspecialchars(trim($_POST['type_dossier'])),
            ':mode' => htmlspecialchars(trim($_POST['mode'])),
            ':nombre_conteneur' => !empty($_POST['nombre_conteneur']) ? (int)$_POST['nombre_conteneur'] : 0,
            ':nom_du_navire' => !empty($_POST['nom_du_navire']) ? htmlspecialchars(trim($_POST['nom_du_navire'])) : null,
            ':numero_bl' => !empty($_POST['numero_bl']) ? htmlspecialchars(trim($_POST['numero_bl'])) : null,
            ':poids' => (float)$_POST['poids'],
            ':statut' => htmlspecialchars(trim($_POST['statut']))
        ];

        // 4. Requête SQL corrigée avec les bons noms de colonnes
        $sql = "INSERT INTO dossier (
            numero_dossier, nom, description, 
            client_id, responsable_id, 
            eta, type_dossier, mode, 
            nombre_conteneur, nom_du_navire,
            numero_bl, poids, statut
        ) VALUES (
            :numero_dossier, :nom, :description, 
            :client_id, :responsable_id, 
            :eta, :type_dossier, :mode, 
            :nombre_conteneur, :nom_du_navire,
            :numero_bl, :poids, :statut
        )";

        $stmt = $pdo->prepare($sql);
        
        // 5. Exécution avec gestion d'erreur détaillée
        if (!$stmt->execute($data)) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Erreur SQL [{$errorInfo[0]}]: {$errorInfo[2]}");
        }

        // 6. Succès - redirection avec message
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => "Dossier créé avec succès ! Numéro: " . $_POST['numero_dossier']
        ];
        
        unset($_SESSION['pre_generated_numero']);
        header('Location: gestion-dossiers.php');
        exit();

    } catch (PDOException $e) {
        $errorMessage = "Erreur base de données : " . $e->getMessage();
        
        // Erreur de clé étrangère
        if ($e->errorInfo[1] == 1452) {
            $errorMessage = "Erreur de référence : Le client ou responsable sélectionné n'existe pas";
        }
        
        $_SESSION['error_message'] = $errorMessage;
        $_SESSION['form_data'] = $_POST;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST;
    }
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG - Création de dossier</title>
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
       :root {
            --primary: #2c3e50;
            --primary-light: #3d566e;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .admin-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-nav {
            display: flex;
            gap: 15px;
            margin-top: 1rem;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            padding: 8px 16px;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
        }
        
        .admin-nav a:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .form-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-title {
            font-family: 'Poppins', sans-serif;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f9f9f9;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
            background-color: white;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
        }
        
        .form-icon {
            position: absolute;
            right: 15px;
            top: 40px;
            color: var(--gray);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.85rem 1.75rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(52, 152, 219, 0.3);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        
        .btn-outline:hover {
            background-color: rgba(52, 152, 219, 0.08);
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-nav {
                flex-direction: column;
                gap: 8px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1><i class="fas fa-folder-plus"></i> Création de dossier</h1>
            <nav class="admin-nav">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                <a href="gestion-dossiers.php"><i class="fas fa-folder-open"></i> Gestion des dossiers</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </header>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($_SESSION['error_message']) ?></div>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <main>
            <form method="POST" id="dossierForm" class="form-card">
                <h2 class="form-title"><i class="fas fa-file-alt"></i> Informations du dossier</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="numero_dossier" class="form-label">Numéro de dossier *</label>
                        <input type="text" id="numero_dossier" class="form-control" readonly
                               value="<?= htmlspecialchars($_SESSION['form_data']['numero_dossier'] ?? $_SESSION['pre_generated_numero'] ?? '') ?>">
                        <input type="hidden" name="numero_dossier" value="<?= htmlspecialchars($_SESSION['form_data']['numero_dossier'] ?? $_SESSION['pre_generated_numero'] ?? '') ?>">
                        <small class="form-text" style="color: var(--gray); font-size: 0.85rem;">Format: M225 + numéro séquentiel + 3 lettres client</small>
                        <i class="fas fa-hashtag form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="nom" class="form-label">Nom du dossier *</label>
                        <input type="text" id="nom" name="nom" class="form-control" required
                               value="<?= htmlspecialchars($_SESSION['form_data']['nom'] ?? '') ?>">
                        <i class="fas fa-file-signature form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="client" class="form-label">Client *</label>
                        <select id="client" name="client" class="form-control" required>
                            <option value="">Sélectionnez un client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>" 
                                    <?= ($_SESSION['form_data']['client'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($client['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-user-tie form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="responsable" class="form-label">Responsable *</label>
                        <select id="responsable" name="responsable" class="form-control" required>
                            <option value="">Sélectionnez un responsable</option>
                            <?php foreach ($responsables as $responsable): ?>
                                <option value="<?= $responsable['id'] ?>" 
                                    <?= ($_SESSION['form_data']['responsable'] ?? '') == $responsable['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($responsable['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-user-shield form-icon"></i>
                    </div>
                </div>
                
                 
                <div class="form-grid">
                    <div class="form-group">
                        <label for="type_dossier" class="form-label">Type de dossier *</label>
                        <select id="type_dossier" name="type_dossier" class="form-control" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="Import" <?= ($_SESSION['form_data']['type_dossier'] ?? '') == 'Import' ? 'selected' : '' ?>>Import</option>
                            <option value="Export" <?= ($_SESSION['form_data']['type_dossier'] ?? '') == 'Export' ? 'selected' : '' ?>>Export</option>
                            <option value="Transit" <?= ($_SESSION['form_data']['type_dossier'] ?? '') == 'Transit' ? 'selected' : '' ?>>Transit</option>
                        </select>
                        <i class="fas fa-folder-open form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="mode" class="form-label">Mode de transport *</label>
                        <select id="mode" name="mode" class="form-control" required>
                            <option value="">Sélectionnez un mode</option>
                            <option value="Maritime" <?= ($_SESSION['form_data']['mode'] ?? '') == 'Maritime' ? 'selected' : '' ?>>Maritime</option>
                            <option value="Aérien" <?= ($_SESSION['form_data']['mode'] ?? '') == 'Aérien' ? 'selected' : '' ?>>Aérien</option>
                            <option value="Terrestre" <?= ($_SESSION['form_data']['mode'] ?? '') == 'Terrestre' ? 'selected' : '' ?>>Terrestre</option>
                        </select>
                        <i class="fas fa-shipping-fast form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="statut" class="form-label">Statut *</label>
                        <select id="statut" name="statut" class="form-control" required>
                            <option value="">Sélectionnez un statut</option>
                            <option value="En préparation" <?= ($_SESSION['form_data']['statut'] ?? '') == 'En préparation' ? 'selected' : '' ?>>En préparation</option>
                            <option value="En cours" <?= ($_SESSION['form_data']['statut'] ?? '') == 'En cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="Clôturé" <?= ($_SESSION['form_data']['statut'] ?? '') == 'Clôturé' ? 'selected' : '' ?>>Clôturé</option>
                        </select>
                        <i class="fas fa-tasks form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="eta" class="form-label">Date estimée d'arrivée (ETA)</label>
                        <input type="datetime-local" id="eta" name="eta" class="form-control"
                               value="<?= htmlspecialchars($_SESSION['form_data']['eta'] ?? '') ?>">
                        <i class="fas fa-calendar-alt form-icon"></i>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre_conteneur" class="form-label">Nombre de conteneurs</label>
                        <input type="number" id="nombre_conteneur" name="nombre_conteneur" class="form-control" min="0"
                               value="<?= htmlspecialchars($_SESSION['form_data']['nombre_conteneur'] ?? 0) ?>">
                        <i class="fas fa-boxes form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="poids" class="form-label">Poids (tonnes) *</label>
                        <input type="number" id="poids" name="poids" class="form-control" step="0.01" required
                               value="<?= htmlspecialchars($_SESSION['form_data']['poids'] ?? '') ?>">
                        <i class="fas fa-weight-hanging form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="nom_du_navire" class="form-label">Nom du navire</label>
                        <input type="text" id="nom_du_navire" name="nom_du_navire" class="form-control"
                               value="<?= htmlspecialchars($_SESSION['form_data']['nom_du_navire'] ?? '') ?>">
                        <i class="fas fa-ship form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_bl" class="form-label">Numéro BL</label>
                        <input type="text" id="numero_bl" name="numero_bl" class="form-control"
                               value="<?= htmlspecialchars($_SESSION['form_data']['numero_bl'] ?? '') ?>">
                        <i class="fas fa-barcode form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($_SESSION['form_data']['description'] ?? '') ?></textarea>
                    <i class="fas fa-align-left form-icon"></i>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer le dossier
                    </button>
                    <a href="gestion-dossiers.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clientSelect = document.getElementById('client');
            const numeroDossierField = document.getElementById('numero_dossier');
            const hiddenNumeroDossier = document.querySelector('input[name="numero_dossier"]');
            
            // Fonction pour mettre à jour le numéro de dossier via AJAX
            async function updateNumeroDossier(clientId) {
                if (!clientId) {
                    numeroDossierField.value = '';
                    hiddenNumeroDossier.value = '';
                    return;
                }
                
                try {
                    numeroDossierField.value = 'Génération en cours...';
                    
                    const response = await fetch('generate_dossier_number.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `client_id=${clientId}`
                    });
                    
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.numero_dossier) {
                        numeroDossierField.value = data.numero_dossier;
                        hiddenNumeroDossier.value = data.numero_dossier;
                    } else {
                        throw new Error(data.error || 'Erreur lors de la génération du numéro');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    numeroDossierField.value = 'Erreur de génération';
                    hiddenNumeroDossier.value = '';
                    alert('Erreur lors de la génération du numéro: ' + error.message);
                }
            }
            
            // Écouter les changements de client
            clientSelect.addEventListener('change', function() {
                updateNumeroDossier(this.value);
            });
            
            // Initialiser au chargement si client déjà sélectionné
            if (clientSelect.value) {
                updateNumeroDossier(clientSelect.value);
            }
            
            // Validatbion du formulaire
            const form = document.getElementById('dossierForm');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#e74c3c';
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs obligatoires');
                }
            });
        });
    </script>
</body>
</html>

<?php 
unset($_SESSION['form_data']); 
unset($_SESSION['pre_generated_numero']);
?>
