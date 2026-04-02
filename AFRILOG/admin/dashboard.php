<?php
session_start();
require '../config.php';

$current_tab = $_GET['tab'] ?? 'dashboard';
$current_user = [];
$stats = [];
$recent_deliveries = [];
$vehicles = [];
$dossiers = [];
$usersClients = [];
$carriers = [];
$cities = [];

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Récupération utilisateur connecté
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Statistiques
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM packages) AS total_packages,
            (SELECT COUNT(*) FROM packages WHERE status = 'livré') AS delivered,
            (SELECT COUNT(*) FROM carriers) AS total_carriers,
            (SELECT COUNT(*) FROM users WHERE role = 'client') AS total_clients,
            (SELECT COUNT(*) FROM cities) AS total_cities,
            (SELECT COUNT(*) FROM vehicules) AS total_vehicles,
            (SELECT COUNT(*) FROM dossier WHERE statut != 'termine') AS dossiers_actifs
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    // Dernières données
    $recent_deliveries = $pdo->query("SELECT * FROM packages ORDER BY id DESC LIMIT 5")->fetchAll();
    $vehicles = $pdo->query("SELECT * FROM vehicules ORDER BY vehicule_id DESC LIMIT 5")->fetchAll();
    $dossiers = $pdo->query("SELECT * FROM dossier ORDER BY id DESC LIMIT 5")->fetchAll();
    $usersClients = $pdo->query("SELECT * FROM users WHERE role = 'client' ORDER BY id DESC LIMIT 5")->fetchAll();
    $carriers = $pdo->query("SELECT * FROM carriers ORDER BY id DESC LIMIT 5")->fetchAll();
    $cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();

} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG CI - Administration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .action-buttons .btn {
            padding: 3px 8px;
            margin: 0 2px;
        }
        .badge-status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .map-container {
            height: 400px;
            background-color: #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        .map-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #6b7280;
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
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
                    <div class="font-medium"><?= sanitize($current_user['nom'] ?? 'Admin') ?></div>
                    <div class="text-xs text-blue-200"><?= sanitize($current_user['role'] ?? 'Administrateur') ?></div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto">
                <div class="p-2">
                    <!-- Boutons d'action -->
                    <div class="mb-4 space-y-2 mx-2">
                        <a href="add_user.php?role=client" class="flex items-center px-3 py-2 text-white bg-green-600 hover:bg-green-700 rounded">
                            <i class="fas fa-user-plus mr-3"></i>
                            <span class="nav-text">Nouveau Client</span>
                        </a>
                        <a href="add_dossier.php" class="flex items-center px-3 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded">
                            <i class="fas fa-folder-plus mr-3"></i>
                            <span class="nav-text">Nouveau Dossier</span>
                        </a>
                        <a href="add_package.php" class="flex items-center px-3 py-2 text-white bg-cyan-600 hover:bg-cyan-700 rounded">
                            <i class="fas fa-box mr-3"></i>
                            <span class="nav-text">Nouveau Colis</span>
                        </a>
                        <a href="add_vehicle.php" class="flex items-center px-3 py-2 text-white bg-yellow-600 hover:bg-yellow-700 rounded">
                            <i class="fas fa-truck mr-3"></i>
                            <span class="nav-text">Nouveau Véhicule</span>
                        </a>
                    </div>
                    
                    <!-- Menu principal -->
                    <div class="mb-1 nav-text text-xs uppercase text-blue-300 font-bold px-3 py-2">Menu Principal</div>
                    
                    <a href="?tab=dashboard" class="flex items-center px-3 py-3 text-white <?= ($current_tab === 'dashboard') ? 'bg-blue-700' : 'hover:bg-blue-700' ?> rounded mx-2 mb-1">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        <span class="nav-text">Tableau de bord</span>
                    </a>
                    
                    <div class="mb-1 nav-text text-xs uppercase text-blue-300 font-bold px-3 py-2">Gestion</div>
                    
                    <a href="?tab=clients" class="flex items-center px-3 py-3 text-white <?= ($current_tab === 'clients') ? 'bg-blue-700' : 'hover:bg-blue-700' ?> rounded mx-2 mb-1">
                        <i class="fas fa-users mr-3"></i>
                        <span class="nav-text">Clients</span>
                    </a>
                    
                    <a href="?tab=dossiers" class="flex items-center px-3 py-3 text-white <?= ($current_tab === 'dossiers') ? 'bg-blue-700' : 'hover:bg-blue-700' ?> rounded mx-2 mb-1">
                        <i class="fas fa-folder-open mr-3"></i>
                        <span class="nav-text">Dossiers</span>
                    </a>
                    
                    <a href="?tab=packages" class="flex items-center px-3 py-3 text-white <?= ($current_tab === 'packages') ? 'bg-blue-700' : 'hover:bg-blue-700' ?> rounded mx-2 mb-1">
                        <i class="fas fa-boxes mr-3"></i>
                        <span class="nav-text">Colis</span>
                    </a>
                    
                    <a href="?tab=vehicules" class="flex items-center px-3 py-3 text-white <?= ($current_tab === 'vehicules') ? 'bg-blue-700' : 'hover:bg-blue-700' ?> rounded mx-2 mb-1">
                        <i class="fas fa-truck mr-3"></i>
                        <span class="nav-text">Véhicules</span>
                    </a>
                    
                    <a href="?tab=villes" class="flex items-center px-3 py-3 text-white <?= ($current_tab === 'villes') ? 'bg-blue-700' : 'hover:bg-blue-700' ?> rounded mx-2 mb-1">
                        <i class="fas fa-city mr-3"></i>
                        <span class="nav-text">Villes</span>
                    </a>
                    
                    <a href="?tab=localisation" class="flex items-center px-3 py-3 text-white <?= ($current_tab === 'localisation') ? 'bg-blue-700' : 'hover:bg-blue-700' ?> rounded mx-2 mb-1">
                        <i class="fas fa-map-marker-alt mr-3"></i>
                        <span class="nav-text">Localisation</span>
                    </a>
                </div>
            </nav>
            
            <!-- Bottom Menu -->
            <div class="p-4 border-t border-blue-700">
                <a href="#" class="flex items-center px-3 py-2 text-white hover:bg-blue-700 rounded">
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
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4 flex items-center justify-between">
                    <h1 class="text-xl font-semibold text-gray-800">
                        <?= match($current_tab) {
                            'dashboard' => 'Tableau de bord',
                            'clients' => 'Gestion des Clients',
                            'dossiers' => 'Dossiers Actifs',
                            'packages' => 'Gestion des Colis',
                            'vehicules' => 'Gestion des Véhicules',
                            'villes' => 'Gestion des Villes',
                            'localisation' => 'Localisation en temps réel',
                            default => 'Tableau de bord'
                        } ?>
                    </h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" placeholder="Rechercher..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <div class="relative">
                            <button class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php switch($current_tab): 
                    case 'dashboard': ?>
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                            <div class="stat-card bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                        <i class="fas fa-users text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-gray-500">Clients</h3>
                                        <p class="text-2xl font-bold"><?= $stats['total_clients'] ?? 0 ?></p>
                                        <p class="text-blue-500 text-sm">Gestion des clients</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                                        <i class="fas fa-box text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-gray-500">Colis</h3>
                                        <p class="text-2xl font-bold"><?= $stats['total_packages'] ?? 0 ?></p>
                                        <p class="text-green-500 text-sm"><?= $stats['delivered'] ?? 0 ?> livrés</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                                        <i class="fas fa-folder-open text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-gray-500">Dossiers</h3>
                                        <p class="text-2xl font-bold"><?= $stats['dossiers_actifs'] ?? 0 ?></p>
                                        <p class="text-orange-500 text-sm">Actifs</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                        <i class="fas fa-truck text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-gray-500">Véhicules</h3>
                                        <p class="text-2xl font-bold"><?= $stats['total_vehicles'] ?? 0 ?></p>
                                        <p class="text-purple-500 text-sm">Disponibles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Localisation des véhicules -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                            <div class="bg-white rounded-lg shadow">
                                <div class="p-4 border-b">
                                    <h2 class="text-lg font-semibold">Localisation des véhicules</h2>
                                </div>
                                <div class="p-4">
                                    <div class="map-container rounded-lg mb-4">
                                        <div class="map-placeholder">
                                            <i class="fas fa-map-marked-alt text-5xl mb-2 text-blue-500 animate-pulse"></i>
                                            <p>Carte des véhicules en temps réel</p>
                                            <p class="text-sm mt-2">Intégration avec Google Maps ou OpenStreetMap</p>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <?php foreach (array_slice($vehicles, 0, 4) as $vehicle): ?>
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 rounded-full bg-green-500 mr-2"></div>
                                            <div class="flex-1">
                                                <div class="flex justify-between">
                                                    <span class="font-medium"><?= sanitize($vehicle['type']) ?> - <?= sanitize($vehicle['immatriculation']) ?></span>
                                                    <span class="text-sm text-gray-500"><?= rand(0, 1) ? 'En mouvement' : 'Arrêté' ?></span>
                                                </div>
                                                <div class="text-sm text-gray-500"><?= 
                                                    $cities ? sanitize($cities[array_rand($cities)]['name']) : 'Abidjan'
                                                ?> à <?= 
                                                    $cities ? sanitize($cities[array_rand($cities)]['name']) : 'Bouaké'
                                                ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dernières activités -->
                            <div class="bg-white rounded-lg shadow">
                                <div class="p-4 border-b">
                                    <h2 class="text-lg font-semibold">Dernières activités</h2>
                                </div>
                                <div class="p-4">
                                    <div class="space-y-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0 mr-3">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                                    <i class="fas fa-info-circle"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between">
                                                    <span class="font-medium">Bienvenue sur le tableau de bord</span>
                                                    <span class="text-sm text-gray-500">Maintenant</span>
                                                </div>
                                                <p class="text-sm text-gray-500">Vous êtes connecté en tant que <?= sanitize($current_user['role'] ?? 'administrateur') ?></p>
                                            </div>
                                        </div>
                                        <?php if (!empty($recent_deliveries)): ?>
                                        <div class="flex">
                                            <div class="flex-shrink-0 mr-3">
                                                <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between">
                                                    <span class="font-medium">Nouveau colis enregistré</span>
                                                    <span class="text-sm text-gray-500">Aujourd'hui</span>
                                                </div>
                                                <p class="text-sm text-gray-500"><?= sanitize($recent_deliveries[0]['tracking_number']) ?> pour <?= sanitize($recent_deliveries[0]['destination']) ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($vehicles)): ?>
                                        <div class="flex">
                                            <div class="flex-shrink-0 mr-3">
                                                <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center">
                                                    <i class="fas fa-truck"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between">
                                                    <span class="font-medium">Nouveau véhicule ajouté</span>
                                                    <span class="text-sm text-gray-500">Aujourd'hui</span>
                                                </div>
                                                <p class="text-sm text-gray-500"><?= sanitize($vehicles[0]['type']) ?> - <?= sanitize($vehicles[0]['immatriculation']) ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>

                    
                    <?php case 'clients': ?>
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h2 class="text-lg font-semibold">Liste des Clients</h2>
                                <a href="add_user.php?role=client" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Ajouter
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($usersClients as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($user['nom']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($user['email']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge-status bg-<?= $user['role'] === 'admin' ? 'red' : 'blue' ?>-100 text-<?= $user['role'] === 'admin' ? 'red' : 'blue' ?>-800">
                                                    <?= sanitize($user['role']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2"><i class="fas fa-edit"></i></a>
                                                <?php if($user['role'] !== 'admin'): ?>
                                                    <a href="delete_user.php?id=<?= $user['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Confirmer la suppression ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t flex justify-between items-center">
                                <div class="text-sm text-gray-500">Affichage de 1 à <?= count($usersClients) ?> sur <?= $stats['total_clients'] ?? 0 ?> clients</div>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-left"></i></button>
                                    <button class="px-3 py-1 border rounded bg-blue-600 text-white">1</button>
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>
                        

                    
                  <?php case 'dossiers': ?>
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h2 class="text-lg font-semibold">Dossiers Actifs</h2>
                                <a href="add_dossier.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Ajouter
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($dossiers as $dossier): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($dossier['reference']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($dossier['client_id']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge-status bg-<?= $dossier['statut'] === 'en cours' ? 'yellow' : 'green' ?>-100 text-<?= $dossier['statut'] === 'en cours' ? 'yellow' : 'green' ?>-800">
                                                    <?= sanitize($dossier['statut']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="edit_dossier.php?id=<?= $dossier['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2"><i class="fas fa-edit"></i></a>
                                                <a href="delete_dossier.php?id=<?= $dossier['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Confirmer la suppression ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t flex justify-between items-center">
                                <div class="text-sm text-gray-500">Affichage de 1 à <?= count($dossiers) ?> sur <?= $stats['dossiers_actifs'] ?? 0 ?> dossiers actifs</div>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-left"></i></button>
                                    <button class="px-3 py-1 border rounded bg-blue-600 text-white">1</button>
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>

                        

                    <?php case 'packages': ?>
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h2 class="text-lg font-semibold">Gestion des Colis</h2>
                                <a href="add_package.php" class="px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-700 flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Ajouter
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Suivi</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_deliveries as $package): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($package['tracking_number']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($package['destination']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge-status bg-<?= 
                                                    match($package['status']) {
                                                        'livré' => 'green',
                                                        'en transit' => 'yellow',
                                                        default => 'gray'
                                                    }
                                                ?>-100 text-<?= 
                                                    match($package['status']) {
                                                        'livré' => 'green',
                                                        'en transit' => 'yellow',
                                                        default => 'gray'
                                                    }
                                                ?>-800">
                                                    <?= sanitize($package['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="edit_package.php?id=<?= $package['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2"><i class="fas fa-edit"></i></a>
                                                <a href="delete_package.php?id=<?= $package['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Confirmer la suppression ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t flex justify-between items-center">
                                <div class="text-sm text-gray-500">Affichage de 1 à <?= count($recent_deliveries) ?> sur <?= $stats['total_packages'] ?? 0 ?> colis</div>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-left"></i></button>
                                    <button class="px-3 py-1 border rounded bg-blue-600 text-white">1</button>
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case 'vehicules': ?>
                         <div class="bg-white rounded-lg shadow">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h2 class="text-lg font-semibold">Gestion des Véhicules</h2>
                                <a href="add_vehicle.php" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Ajouter
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Immatriculation</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacité</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($vehicles as $vehicle): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($vehicle['immatriculation']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($vehicle['type']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($vehicle['capacite']) ?> kg</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="edit_vehicle.php?id=<?= $vehicle['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2"><i class="fas fa-edit"></i></a>
                                                <a href="delete_vehicle.php?id=<?= $vehicle['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Confirmer la suppression ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t flex justify-between items-center">
                                <div class="text-sm text-gray-500">Affichage de 1 à <?= count($vehicles) ?> sur <?= $stats['total_vehicles'] ?? 0 ?> véhicules</div>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-left"></i></button>
                                    <button class="px-3 py-1 border rounded bg-blue-600 text-white">1</button>
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case 'villes': ?>
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h2 class="text-lg font-semibold">Gestion des Villes</h2>
                                <a href="add_city.php" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Ajouter
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pays</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code Postal</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($cities as $city): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($city['name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($city['country'] ?? 'Côte d\'Ivoire') ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= sanitize($city['postal_code'] ?? 'N/A') ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="edit_city.php?id=<?= $city['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2"><i class="fas fa-edit"></i></a>
                                                <a href="delete_city.php?id=<?= $city['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Confirmer la suppression ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t flex justify-between items-center">
                                <div class="text-sm text-gray-500">Affichage de 1 à <?= count($cities) ?> sur <?= $stats['total_cities'] ?? count($cities) ?> villes</div>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-left"></i></button>
                                    <button class="px-3 py-1 border rounded bg-blue-600 text-white">1</button>
                                    <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case 'localisation': ?>
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 border-b">
                                <h2 class="text-lg font-semibold">Localisation en temps réel</h2>
                            </div>
                            <div class="p-4">
                                <div class="map-container rounded-lg mb-4">
                                    <div class="map-placeholder">
                                        <i class="fas fa-map-marked-alt text-5xl mb-2 text-blue-500 animate-pulse"></i>
                                        <p>Carte des véhicules en temps réel</p>
                                        <p class="text-sm mt-2">Intégration avec Google Maps ou OpenStreetMap</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h3 class="font-medium mb-2">Véhicules en mouvement</h3>
                                        <div class="space-y-3">
                                            <?php foreach (array_slice($vehicles, 0, 3) as $vehicle): ?>
                                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                                <div class="w-2 h-2 rounded-full bg-green-500 mr-3"></div>
                                                <div class="flex-1">
                                                    <div class="font-medium"><?= sanitize($vehicle['type']) ?> (<?= sanitize($vehicle['immatriculation']) ?>)</div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= $cities ? sanitize($cities[array_rand($cities)]['name']) : 'Abidjan' ?> → 
                                                        <?= $cities ? sanitize($cities[array_rand($cities)]['name']) : 'Bouaké' ?>
                                                    </div>
                                                </div>
                                                <div class="text-sm text-gray-500"><?= rand(50, 90) ?> km/h</div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="font-medium mb-2">Véhicules à l'arrêt</h3>
                                        <div class="space-y-3">
                                            <?php foreach (array_slice($vehicles, 3, 2) as $vehicle): ?>
                                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                                <div class="w-2 h-2 rounded-full bg-yellow-500 mr-3"></div>
                                                <div class="flex-1">
                                                    <div class="font-medium"><?= sanitize($vehicle['type']) ?> (<?= sanitize($vehicle['immatriculation']) ?>)</div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= $cities ? sanitize($cities[array_rand($cities)]['name']) : 'Abidjan' ?>
                                                    </div>
                                                </div>
                                                <div class="text-sm text-gray-500">Arrêté</div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>

                <?php endswitch; ?>
            </main>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Ici vous pourriez ajouter l'intégration de Google Maps ou OpenStreetMap
        // Exemple avec Google Maps (nécessite une clé API)
        /*
        function initMap() {
            const map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 7.5399, lng: -5.5471 }, // Centre sur la Côte d'Ivoire
                zoom: 7,
            });
            
            // Ajouter des marqueurs pour chaque véhicule
            <?php foreach ($vehicles as $vehicle): ?>
            new google.maps.Marker({
                position: { lat: <?= rand(4, 10) ?>, lng: <?= rand(-8, -3) ?> },
                map,
                title: "<?= sanitize($vehicle['immatriculation']) ?>",
            });
            <?php endforeach; ?>
        }
        */
    </script>
    <!-- 
    <script src="https://maps.googleapis.com/maps/api/js?key=VOTRE_CLE_API&callback=initMap&libraries=&v=weekly" async></script>
    -->
</body>
</html>