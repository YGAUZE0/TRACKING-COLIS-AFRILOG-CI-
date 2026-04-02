<?php
session_start();
include '../config.php';

// Vérification de la session admin

// Initialisation des variables
$error = '';
$success = '';
$roles = ['client', 'administrateur'];

// Récupérer les informations de l'administrateur
try {
    $stmt = $pdo->prepare("SELECT nom, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    // Si aucun admin n'est trouvé, définir des valeurs par défaut
    if (!$admin) {
        $admin = ['nom' => 'Administrateur inconnu', 'role' => 'administrateur'];
    }
} catch (PDOException $e) {
    $admin = ['nom' => 'Erreur BD', 'role' => 'administrateur'];
    $error = "Erreur de base de données : " . $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Nettoyage des données
        $nom = htmlspecialchars(trim($_POST['nom']));
        $abbreviation = htmlspecialchars(trim($_POST['abbreviation'] ?? ''));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);
        $role = in_array($_POST['role'], $roles) ? $_POST['role'] : 'client';

        // Validation
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est obligatoire";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if (empty($password) || strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères";

        if (empty($errors)) {
            // Vérifier l'unicité de l'email
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->execute([$email]);
            
            if ($checkEmail->rowCount() === 0) {
                // Hachage du mot de passe
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insertion dans la base
                $sql = "INSERT INTO users 
                        (nom, abbreviation, email, password, role, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nom, $abbreviation, $email, $hashedPassword, $role]);

                $success = "Utilisateur créé avec succès !";
                $_POST = []; // Réinitialiser le formulaire
            } else {
                $error = "Cet email est déjà utilisé";
            }
        } else {
            $error = implode("<br>", $errors);
        }

    } catch (PDOException $e) {
        $error = "Erreur technique : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .form-container:hover {
            transform: translateY(-5px);
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
        }
        
        .password-strength {
            height: 3px;
            background: #ddd;
            margin-top: 5px;
            border-radius: 2px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .animate-input {
            transition: all 0.3s ease;
        }
        
        .animate-input:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container animate__animated animate__fadeInUp">
            <h2 class="text-center mb-4">
                <i class="fas fa-user-plus me-2"></i>Nouvel Utilisateur
                <small class="d-block text-muted mt-2">Administrateur : <?= htmlspecialchars($admin['nom']) ?></small>
            </h2>

            <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__shakeX">
                <?= $error ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <?= $success ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label class="form-label">Nom complet *</label>
                    <input type="text" name="nom" 
                           class="form-control animate-input"
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" 
                           required
                           pattern=".{3,}"
                           title="Minimum 3 caractères">
                    <div class="invalid-feedback">Veuillez saisir un nom valide</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Abréviation</label>
                    <input type="text" name="abbreviation" 
                           class="form-control animate-input"
                           value="<?= htmlspecialchars($_POST['abbreviation'] ?? '') ?>"
                           maxlength="10">
                </div>

                <div class="mb-4">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" 
                           class="form-control animate-input"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                           required>
                    <div class="invalid-feedback">Veuillez saisir un email valide</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="password" 
                           id="password"
                           class="form-control animate-input"
                           required
                           minlength="8">
                    <div class="password-strength"></div>
                    <small class="text-muted">Minimum 8 caractères</small>
                </div>

                <div class="mb-4">
                    <label class="form-label">Rôle *</label>
                    <select name="role" class="form-select animate-input" required>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= $role ?>" <?= ($_POST['role'] ?? '') === $role ? 'selected' : '' ?>>
                            <?= ucfirst($role) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validation en temps réel
    (() => {
        'use strict'

        const forms = document.querySelectorAll('.needs-validation')
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // Indicateur de force du mot de passe
    document.getElementById('password').addEventListener('input', function(e) {
        const password = e.target.value
        const strengthBar = document.querySelector('.password-strength')
        let strength = 0

        if (password.length >= 8) strength += 1
        if (password.match(/[A-Z]/)) strength += 1
        if (password.match(/[0-9]/)) strength += 1
        if (password.match(/[^A-Za-z0-9]/)) strength += 1

        const width = (strength / 4) * 100
        strengthBar.style.width = width + '%'
        strengthBar.style.backgroundColor = 
            width >= 75 ? '#28a745' : 
            width >= 50 ? '#ffc107' : 
            '#dc3545'
    })
    </script>
</body>
</html>