<?php
session_start();
require 'config.php';

// Activation des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification de session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userNom = htmlspecialchars($_SESSION['nom'] ?? '');
$userAbrev = htmlspecialchars($_SESSION['abreviation'] ?? '');
$trackingInfo = [];
$error = '';

try {
    // Récupération des statistiques
    $stmtStats = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM packages WHERE id = ? AND status = 'en attente') AS en_attente,
            (SELECT COUNT(*) FROM packages WHERE id = ? AND status = 'en transit') AS en_transit,
            (SELECT COUNT(*) FROM packages WHERE id = ? AND status = 'livré') AS livres,
            (SELECT COUNT(*) FROM dossier WHERE id = ?) AS dossiers_actifs
    ");
    $stmtStats->execute([$userId, $userId, $userId, $userId]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    // Récupération des colis récents
    $stmtColis = $pdo->prepare("
        SELECT p.*, c.name AS carrier_name 
        FROM packages p
        LEFT JOIN carriers c ON p.carrier_id = c.id
        WHERE p.id = ?
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    $stmtColis->execute([$userId]);
    $recentColis = $stmtColis->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Client - Afrilogci</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { transition: all 0.3s; }
        .package-card:hover { transform: translateY(-3px); }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .animate-pulse { animation: pulse 2s infinite; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="bg-blue-800 text-white w-64 flex-shrink-0 p-6">
            <div class="flex items-center space-x-3 mb-8">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center">
                    <span class="text-blue-800 font-bold"><?= substr($userAbrev, 0, 2) ?></span>
                </div>
                <div>
                    <h2 class="font-bold"><?= $userNom ?></h2>
                    <p class="text-xs text-blue-200"><?= $_SESSION['role'] ?? 'Client' ?></p>
                </div>
            </div>
            
            <nav class="space-y-2">
                <a href="Acceuil.php" class="block py-2 px-4 rounded bg-blue-900">
                    <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                </a>
                <a href="tracking.php" class="block py-2 px-4 rounded hover:bg-blue-700">
                    <i class="fas fa-map-marker-alt mr-2"></i>Suivre les colis
                </a>
                <a href="mes_colis.php" class="block py-2 px-4 rounded hover:bg-blue-700">
                    <i class="fas fa-box-open mr-2"></i>Mes colis
                </a>
                <a href="mes_dossiers.php" class="block py-2 px-4 rounded hover:bg-blue-700">
                    <i class="fas fa-folder mr-2"></i>Mes dossiers
                </a>
            </nav>
        </div>

        <!-- Contenu principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm p-4 flex justify-between items-center">
                <h1 class="text-xl font-bold text-blue-800">Espace Client - <?= $userNom ?></h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <span class="font-medium"><?= $userNom ?></span>
                    </div>
                </div>
            </header>

            <!-- Contenu -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Cartes de statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-500">En attente</p>
                                <p class="text-2xl font-bold text-blue-600"><?= $stats['en_attente'] ?? 0 ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-500">En transit</p>
                                <p class="text-2xl font-bold text-yellow-600"><?= $stats['en_transit'] ?? 0 ?></p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-truck-moving text-yellow-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-500">Livrés</p>
                                <p class="text-2xl font-bold text-green-600"><?= $stats['livres'] ?? 0 ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-500">Dossiers</p>
                                <p class="text-2xl font-bold text-purple-600"><?= $stats['dossiers_actifs'] ?? 0 ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-folder-open text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de suivi -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="font-bold text-lg mb-4">Suivi rapide de colis</h3>
                    <form method="POST" class="flex gap-4">
                        <input type="text" name="tracking_number" 
                               class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Exemple : AFR123456">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </form>
                    <?php if ($error): ?>
                        <p class="text-red-500 mt-2"><?= $error ?></p>
                    <?php endif; ?>
                </div>

                <!-- Résultats du suivi -->
                <?php if (!empty($trackingInfo)): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Suivi du colis <?= $trackingInfo['info']['tracking_number'] ?></h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <?php if ($trackingInfo['info']['latitude']): ?>
                                <div class="h-64 rounded-lg overflow-hidden">
                                    <iframe class="w-full h-full" 
                                        src="https://www.openstreetmap.org/export/embed.html?bbox=<?= 
                                            $trackingInfo['info']['longitude']-0.02 ?>,<?= 
                                            $trackingInfo['info']['latitude']-0.02 ?>,<?= 
                                            $trackingInfo['info']['longitude']+0.02 ?>,<?= 
                                            $trackingInfo['info']['latitude']+0.02 ?>&marker=<?= 
                                            $trackingInfo['info']['latitude'] ?>,<?= 
                                            $trackingInfo['info']['longitude'] ?>">
                                    </iframe>
                                </div>
                            <?php else: ?>
                                <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <p class="text-gray-500">Localisation non disponible</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-4">
                            <?php foreach ($trackingInfo['history'] as $event): ?>
                            <div class="flex items-start pl-4 border-l-2 border-blue-200">
                                <div class="flex-1">
                                    <p class="font-medium">
                                        <?= match($event['status']) {
                                            'en attente' => 'Prise en charge',
                                            'en transit' => 'En transit',
                                            'livré' => 'Livré',
                                            default => 'Mise à jour'
                                        } ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= date('d/m/Y H:i', strtotime($event['timestamp'])) ?>
                                    </p>
                                    <?php if (!empty($event['address'])): ?>
                                    <p class="text-sm text-gray-500"><?= $event['address'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Derniers colis -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold">Dernières expéditions</h3>
                        <a href="mes_colis.php" class="text-blue-600 hover:text-blue-800">Voir tout →</a>
                    </div>
                    
                    <div class="grid gap-4">
                        <?php foreach ($recentColis as $colis): ?>
                        <div class="package-card bg-gray-50 p-4 rounded-lg hover:bg-white transition-all">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-medium"><?= $colis['tracking_number'] ?></h4>
                                    <p class="text-sm text-gray-500">
                                        <?= $colis['sender_name'] ?> → <?= $colis['receiver_name'] ?>
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-sm 
                                    <?= match($colis['status']) {
                                        'livré' => 'bg-green-100 text-green-800',
                                        'en transit' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    } ?>">
                                    <?= ucfirst($colis['status']) ?>
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Transporteur</p>
                                    <p><?= $colis['carrier_name'] ?? 'Non attribué' ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Date</p>
                                    <p><?= date('d/m/Y', strtotime($colis['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>