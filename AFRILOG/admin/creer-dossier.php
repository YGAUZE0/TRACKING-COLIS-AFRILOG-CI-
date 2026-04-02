<?php
// admin/creer-dossier.php
session_start();
include '../config.php';

// Récupération des utilisateurs par rôle
$responsables = $pdo->query("SELECT id, nom FROM users WHERE role = 'administrateur'")->fetchAll(PDO::FETCH_ASSOC);
$clients = $pdo->query("SELECT id, nom FROM users WHERE role = 'client'")->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $requiredFields = [
            'numero_dossier', 'nom', 'client', 
            'type_dossier', 'mode', 'statut', 'poids'
        ];

        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ $field est obligatoire");
            }
        }

        // Nettoyage des données
        $data = [
            ':numero_dossier' => htmlspecialchars($_POST['numero_dossier']),
            ':nom' => htmlspecialchars($_POST['nom']),
            ':description' => htmlspecialchars($_POST['description'] ?? null),
            ':client' => (int)$_POST['client'],
            ':responsable' => !empty($_POST['responsable']) ? (int)$_POST['responsable'] : null,
            ':eta' => !empty($_POST['eta']) ? date('Y-m-d H:i:s', strtotime($_POST['eta'])) : null,
            ':type_dossier' => htmlspecialchars($_POST['type_dossier']),
            ':mode' => htmlspecialchars($_POST['mode']),
            ':nombre_conteneur' => (int)($_POST['nombre_conteneur'] ?? 0),
            ':nom_du_navire' => htmlspecialchars($_POST['nom_du_navire'] ?? null),
            ':numero_bl' => htmlspecialchars($_POST['numero_bl'] ?? null),
            ':poids' => (float)$_POST['poids'],
            ':statut' => htmlspecialchars($_POST['statut'])
        ];

        // Insertion dans la base
        $stmt = $pdo->prepare("
            INSERT INTO Dossier (
                numero_dossier, nom, description, 
                client, responsable, 
                eta, type_dossier, mode, 
                nombre_conteneur, nom_du_navire,
                numero_bl, poids, statut
            ) VALUES (
                :numero_dossier, :nom, :description, 
                :client, :responsable, 
                :eta, :type_dossier, :mode, 
                :nombre_conteneur, :nom_du_navire,
                :numero_bl, :poids, :statut
            )
        ");

        $stmt->execute($data);

        $_SESSION['flash_message'] = "Dossier créé avec succès !";
        header('Location: gestion-dossiers.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG - Créer un dossier</title>
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
    <h1>AFRILOG - Créer un nouveau dossier</h1>
        <nav>
            <ul style="display: flex; gap: 1rem; list-style: none; padding: 0;">
                <li><a href="dashboard.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-home"></i> Tableau de bord</a></li>
                <li><a href="logout.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <section class="dashboard">
    <?php if(isset($_SESSION['error_message'])): ?>
            <div class="flash-message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form method="POST" id="dossierForm" class="card">
            <div class="grid-container">
           
                <!-- Colonne 1 -->
                <div class="input-group">
                    <input type="text" name="numero_dossier" placeholder=" " required 
                           pattern="DOS-\d{4}-\d{3}" title="Format: DOS-AAAA-NNN">
                    <span class="floating-label">Numéro dossier</span>
                    <i class="fas fa-hashtag input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="text" name="nom" placeholder=" " required>
                    <span class="floating-label">Nom du dossier</span>
                    <i class="fas fa-file-signature input-icon"></i>
                </div>

                <!-- Modifications des champs client/responsable -->
                <div class="input-group">
                    <select name="client" required>
                        <option value="">Sélectionner un client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="floating-label">Client</span>
                    <i class="fas fa-users input-icon"></i>
                </div>

                <div class="input-group">
                    <select name="responsable" required>
                        <option value="">Sélectionner un responsable</option>
                        <?php foreach ($responsables as $responsable): ?>
                            <option value="<?= $responsable['id'] ?>"><?= htmlspecialchars($responsable['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="floating-label">Responsable</span>
                    <i class="fas fa-user-tie input-icon"></i>
                </div>

                
            </div>
            <div class="input-group">
                    <input type="datetime-local" name="eta">
                    <span class="floating-label">ETA (Date/heure estimée)</span>
                    <i class="fas fa-clock input-icon"></i>
                </div>

                <div class="input-group">
                    <select name="type_dossier" required>
                        <option value="">Type de dossier</option>
                        <option value="Import">Import</option>
                        <option value="Export">Export</option>
                        <option value="Transit">Transit</option>
                    </select>
                    <span class="floating-label">Type de dossier</span>
                    <i class="fas fa-folder-open input-icon"></i>
                </div>

                <div class="input-group">
                    <select name="mode" required>
                        <option value="">Mode de transport</option>
                        <option value="Maritime">Maritime</option>
                        <option value="Aérien">Aérien</option>
                        <option value="Terrestre">Terrestre</option>
                    </select>
                    <span class="floating-label">Mode</span>
                    <i class="fas fa-shipping-fast input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="number" name="nombre_conteneur" min="0" value="0" required>
                    <span class="floating-label">Nombre de conteneurs</span>
                    <i class="fas fa-boxes input-icon"></i>
                </div>
                 <!-- Colonne 3 -->
                 <div class="input-group">
                    <input type="text" name="nom_du_navire" placeholder=" ">
                    <span class="floating-label">Nom du navire</span>
                    <i class="fas fa-ship input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="text" name="numero_bl" placeholder=" ">
                    <span class="floating-label">Numéro BL</span>
                    <i class="fas fa-barcode input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="number" name="poids" step="0.01" required>
                    <span class="floating-label">Poids (tonnes)</span>
                    <i class="fas fa-weight-hanging input-icon"></i>
                </div>

                <div class="input-group">
                    <select name="statut" required>
                        <option value="">Statut du dossier</option>
                        <option value="En préparation">En préparation</option>
                        <option value="En cours">En cours</option>
                        <option value="Clôturé">Clôturé</option>
                    </select>
                    <span class="floating-label">Statut</span>
                    <i class="fas fa-tasks input-icon"></i>
                </div>
            </div>

            <div class="input-group">
                <textarea name="description" placeholder=" " rows="4"></textarea>
                <span class="floating-label">Description détaillée</span>
                <i class="fas fa-align-left input-icon"></i>
            </div>

            <button type="submit" class="submit-btn">
                <span class="btn-text">Créer le dossier</span>
                <div class="loader"></div>
            </button>
            <!-- Bouton de soumission -->
        </form>
    </section>

    <script>
       document.addEventListener('DOMContentLoaded', () => {
            // Animation des éléments
            document.querySelectorAll('.input-group').forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });

            // Gestion du formulaire
            const form = document.getElementById('dossierForm');
            const submitBtn = form.querySelector('.submit-btn');
            const loader = submitBtn.querySelector('.loader');

            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs requis');
                    return;
                }

                submitBtn.disabled = true;
                loader.style.display = 'block';
                submitBtn.querySelector('.btn-text').style.opacity = '0.5';
            });

            // Effet de vague sur le header
            const header = document.querySelector('.header-anim');
            header.addEventListener('mousemove', (e) => {
                const x = e.clientX / window.innerWidth;
                const y = e.clientY / window.innerHeight;
                header.style.backgroundPosition = `${x * 100}% ${y * 100}%`;
            });

            // Animation au scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.card, .input-group').forEach(el => observer.observe(el));
        });
    </script>
    
</body>
</html>