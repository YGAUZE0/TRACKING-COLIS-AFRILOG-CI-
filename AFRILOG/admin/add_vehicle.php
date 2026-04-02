<?php
session_start();
require '../config.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $immatriculation = $_POST['immatriculation'];
        $type = $_POST['type'];
        $capacite = $_POST['capacite'];
        $statut = $_POST['statut'];
        $marque = $_POST['marque'];
        $modele = $_POST['modele'];
        $annee = $_POST['annee'];
        $proprietaire = $_POST['proprietaire']; // Champ texte pour le propriétaire

        $stmt = $pdo->prepare("INSERT INTO vehicules 
                              (immatriculation, type, capacite, statut, marque, modele, annee, proprietaire) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $immatriculation, $type, $capacite, $statut, $marque, $modele, $annee, $proprietaire
        ]);

        // Message de succès avec animation
        $_SESSION['success_message'] = "Véhicule ajouté avec succès!";
        header('Location: vehicles.php');
        exit();
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'ajout du véhicule: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG - Ajouter un véhicule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        .animate-slide-up {
            animation: slideUp 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        .input-focus-effect:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }
        .sidebar {
            transition: all 0.3s ease;
            background: #1e40af;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar.collapsed .nav-text {
            display: none;
        }
        .sidebar.collapsed .logo-text {
            display: none;
        }
        .main-content {
            transition: all 0.3s ease;
        }
        .main-content.expanded {
            margin-left: 70px;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar bg-blue-800 text-white w-64 flex flex-col">
            <!-- Logo -->
            <div class="p-4 flex items-center justify-between border-b border-blue-700">
                <div class="flex items-center">
                    <i class="fas fa-truck-moving text-2xl mr-3"></i>
                    <span class="logo-text text-xl font-bold">AFRILOG CI</span>
                </div>
                <button id="toggleSidebar" class="text-white focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- User Profile -->
            <div class="p-4 flex items-center border-b border-blue-700">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user"></i>
                </div>
                <div class="ml-3 nav-text">
                    <div class="font-medium"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
                    <div class="text-xs text-blue-200"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Administrateur') ?></div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto">
                <div class="p-2">
                    <div class="mb-1 nav-text text-xs uppercase text-blue-300 font-bold px-3 py-2">Menu Principal</div>
                    
                    <a href="dashboard.php" class="flex items-center px-3 py-3 text-white hover:bg-blue-700 rounded mx-2 mb-1">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        <span class="nav-text">Tableau de bord</span>
                    </a>
                    
                    <div class="mb-1 nav-text text-xs uppercase text-blue-300 font-bold px-3 py-2">Logistique</div>
                    
                    <a href="vehicles.php" class="flex items-center px-3 py-3 text-white bg-blue-700 rounded mx-2 mb-1">
                        <i class="fas fa-truck mr-3"></i>
                        <span class="nav-text">Véhicules</span>
                    </a>
                    
                    <a href="packages.php" class="flex items-center px-3 py-3 text-white hover:bg-blue-700 rounded mx-2 mb-1">
                        <i class="fas fa-box mr-3"></i>
                        <span class="nav-text">Colis</span>
                    </a>
                    
                    <a href="dossiers.php" class="flex items-center px-3 py-3 text-white hover:bg-blue-700 rounded mx-2 mb-1">
                        <i class="fas fa-folder-open mr-3"></i>
                        <span class="nav-text">Dossiers</span>
                    </a>
                    
                    <div class="mb-1 nav-text text-xs uppercase text-blue-300 font-bold px-3 py-2">Gestion</div>
                    
                    <a href="clients.php" class="flex items-center px-3 py-3 text-white hover:bg-blue-700 rounded mx-2 mb-1">
                        <i class="fas fa-users mr-3"></i>
                        <span class="nav-text">Clients</span>
                    </a>
                    
                    <a href="localisation.php" class="flex items-center px-3 py-3 text-white hover:bg-blue-700 rounded mx-2 mb-1">
                        <i class="fas fa-map-marker-alt mr-3"></i>
                        <span class="nav-text">Localisation</span>
                    </a>
                    
                    <a href="villes.php" class="flex items-center px-3 py-3 text-white hover:bg-blue-700 rounded mx-2 mb-1">
                        <i class="fas fa-city mr-3"></i>
                        <span class="nav-text">Villes</span>
                    </a>
                </div>
            </nav>
            
            <!-- Bottom Menu -->
            <div class="p-4 border-t border-blue-700">
                <a href="settings.php" class="flex items-center px-3 py-2 text-white hover:bg-blue-700 rounded">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="nav-text">Paramètres</span>
                </a>
                <a href="logout.php" class="flex items-center px-3 py-2 text-white hover:bg-blue-700 rounded">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span class="nav-text">Déconnexion</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <div class="max-w-4xl mx-auto px-4 py-8">
                <!-- Header avec animation -->
                <div class="animate-slide-up mb-8">
                    <div class="flex items-center justify-between">
                        <h1 class="text-3xl font-bold text-gray-800">
                            <i class="fas fa-truck-moving text-indigo-600 mr-3"></i>
                            Ajouter un nouveau véhicule
                        </h1>
                        <a href="vehicles.php" class="flex items-center text-indigo-600 hover:text-indigo-800 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i> Retour
                        </a>
                    </div>
                    <div class="w-20 h-1 bg-indigo-500 mt-2 rounded-full"></div>
                </div>

                <!-- Carte du formulaire avec animation -->
                <div class="animate-fade-in bg-white rounded-xl shadow-lg overflow-hidden">
                    <!-- Progress Bar animée -->
                    <div class="h-1 bg-gray-200">
                        <div id="progress-bar" class="h-full bg-indigo-600 transition-all duration-500" style="width: 0%"></div>
                    </div>

                    <!-- Formulaire -->
                    <form id="vehicle-form" method="POST" class="p-6 space-y-6">
                        <?php if (isset($error_message)): ?>
                        <div class="animate-fade-in bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                            <p><?php echo $error_message; ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Section 1 - Information de base -->
                        <fieldset class="space-y-6 border-b pb-6">
                            <legend class="text-lg font-semibold text-indigo-700 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i> Informations de base
                            </legend>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Immatriculation -->
                                <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                    <label for="immatriculation" class="block text-sm font-medium text-gray-700">Immatriculation *</label>
                                    <input type="text" id="immatriculation" name="immatriculation" required
                                           class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all"
                                           placeholder="AB-123-CD">
                                </div>
                                
                                <!-- Type de véhicule -->
                                <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                    <label for="type" class="block text-sm font-medium text-gray-700">Type de véhicule *</label>
                                    <select id="type" name="type" required
                                            class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all">
                                        <option value="" disabled selected>Sélectionnez un type</option>
                                        <option value="Camion 10T">Camion 10T</option>
                                        <option value="Camion 12T">Camion 12T</option>
                                        <option value="Camion 18T">Camion 18T</option>
                                        <option value="Camion frigorifique">Camion frigorifique</option>
                                        <option value="Fourgonnette">Fourgonnette</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Capacité -->
                                <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                    <label for="capacite" class="block text-sm font-medium text-gray-700">Capacité (kg) *</label>
                                    <input type="number" id="capacite" name="capacite" required
                                           class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all"
                                           placeholder="5000">
                                </div>
                                
                                <!-- Statut -->
                                <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                    <label for="statut" class="block text-sm font-medium text-gray-700">Statut *</label>
                                    <select id="statut" name="statut" required
                                            class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all">
                                        <option value="Disponible">Disponible</option>
                                        <option value="En mission">En mission</option>
                                        <option value="En maintenance">En maintenance</option>
                                    </select>
                                </div>
                                
                                <!-- Année -->
                                <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                    <label for="annee" class="block text-sm font-medium text-gray-700">Année</label>
                                    <input type="number" id="annee" name="annee"
                                           class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all"
                                           placeholder="2020" min="2000" max="<?= date('Y') ?>">
                                </div>
                            </div>
                        </fieldset>

                        <!-- Section 2 - Détails techniques -->
                        <fieldset class="space-y-6 border-b pb-6">
                            <legend class="text-lg font-semibold text-indigo-700 flex items-center">
                                <i class="fas fa-cogs mr-2"></i> Détails techniques
                            </legend>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Marque -->
                                <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                    <label for="marque" class="block text-sm font-medium text-gray-700">Marque</label>
                                    <input type="text" id="marque" name="marque"
                                           class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all"
                                           placeholder="Ex: Volvo, Mercedes...">
                                </div>
                                
                                <!-- Modèle -->
                                <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                    <label for="modele" class="block text-sm font-medium text-gray-700">Modèle</label>
                                    <input type="text" id="modele" name="modele"
                                           class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all"
                                           placeholder="Ex: FH16, Actros...">
                                </div>
                            </div>
                            
                            <!-- Propriétaire (champ texte) -->
                            <div class="space-y-1 transform transition-all hover:scale-[1.01]">
                                <label for="proprietaire" class="block text-sm font-medium text-gray-700">Propriétaire *</label>
                                <input type="text" id="proprietaire" name="proprietaire" required
                                       class="input-focus-effect w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all"
                                       placeholder="Nom complet du propriétaire">
                            </div>
                            
                            <!-- Photo (optionnel) -->
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Photo du véhicule</label>
                                <div class="mt-1 flex items-center">
                                    <div class="relative group">
                                        <div class="w-32 h-32 rounded-lg bg-gray-200 flex items-center justify-center overflow-hidden">
                                            <i class="fas fa-truck text-4xl text-gray-400 group-hover:text-indigo-500 transition-colors"></i>
                                        </div>
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 flex items-center justify-center transition-all cursor-pointer">
                                            <span class="text-white opacity-0 group-hover:opacity-100 transition-opacity text-sm font-medium">
                                                <i class="fas fa-camera mr-1"></i> Changer
                                            </span>
                                        </div>
                                    </div>
                                    <button type="button" class="ml-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-colors">
                                        <i class="fas fa-upload mr-2"></i> Télécharger
                                    </button>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Boutons de soumission -->
                        <div class="flex justify-end space-x-4 pt-4 border-t">
                            <button type="reset" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors flex items-center">
                                <i class="fas fa-undo mr-2"></i> Réinitialiser
                            </button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-white transition-colors flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-transform">
                                <i class="fas fa-save mr-2"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animation de la progress bar en fonction du remplissage
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('vehicle-form');
            const progressBar = document.getElementById('progress-bar');
            const requiredFields = form.querySelectorAll('[required]');
            
            function updateProgressBar() {
                let filledFields = 0;
                requiredFields.forEach(field => {
                    if (field.value.trim() !== '') filledFields++;
                });
                
                const progress = (filledFields / requiredFields.length) * 100;
                progressBar.style.width = `${progress}%`;
                
                // Changement de couleur en fonction du progrès
                if (progress < 30) {
                    progressBar.classList.remove('bg-indigo-600', 'bg-green-500');
                    progressBar.classList.add('bg-red-500');
                } else if (progress < 70) {
                    progressBar.classList.remove('bg-red-500', 'bg-green-500');
                    progressBar.classList.add('bg-indigo-600');
                } else {
                    progressBar.classList.remove('bg-red-500', 'bg-indigo-600');
                    progressBar.classList.add('bg-green-500');
                }
            }
            
            // Écouter les changements sur tous les champs requis
            requiredFields.forEach(field => {
                field.addEventListener('input', updateProgressBar);
                field.addEventListener('change', updateProgressBar);
            });
            
            // Animation au survol des boutons
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', () => {
                    button.classList.add('transition-all', 'duration-200');
                });
            });
            
            // Initialiser la progress bar
            updateProgressBar();

            // Toggle sidebar
            document.getElementById('toggleSidebar').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('collapsed');
                document.querySelector('.main-content').classList.toggle('expanded');
            });
        });
    </script>
</body>
</html>