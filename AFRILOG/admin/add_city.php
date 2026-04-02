<?php
session_start();
include '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placeName = trim($_POST['place_name']);
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;

    if (empty($placeName)) {
        $error = 'Le nom du lieu est requis';
    } elseif (empty($lat) || empty($lng)) {
        $error = 'Veuillez sélectionner un lieu valide sur la carte';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM cities WHERE name = ?");
            $stmt->execute([$placeName]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Cette ville est déjà enregistrée';
            } else {
                $stmt = $pdo->prepare("INSERT INTO cities (name, lat, lng) VALUES (?, ?, ?)");
                if ($stmt->execute([$placeName, $lat, $lng])) {
                    $success = 'Ville enregistrée avec succès';
                    $placeName = $lat = $lng = '';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur de base de données : ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFRILOG - Ajouter une ville</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 30px;
        }
        #map {
            height: 500px;
            width: 100%;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #ddd;
        }
        .search-container {
            position: relative;
        }
        #searchResults {
            position: absolute;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: none;
        }
        .search-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .search-item:hover {
            background-color: #f5f5f5;
        }
        .coordinate-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .leaflet-popup-content-wrapper {
            border-radius: 8px;
        }
        #mapControls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        .loading-spinner {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4"><i class="fas fa-city me-2"></i>Ajouter une nouvelle ville</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="cityForm">
            <div class="mb-3">
                <label for="place_name" class="form-label">Nom de la ville</label>
                <input type="text" class="form-control" id="place_name" name="place_name" 
                       value="<?= htmlspecialchars($placeName ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Localisation sur la carte</label>
                <div id="map"></div>
                <div id="mapControls">
                    <button type="button" id="locateBtn" class="btn btn-sm btn-primary mb-2">
                        <i class="fas fa-location-arrow"></i> Ma position
                    </button>
                    <button type="button" id="searchBtn" class="btn btn-sm btn-secondary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </div>

            <div class="coordinate-box">
                <div class="row">
                    <div class="col-md-6">
                        <label for="lat" class="form-label">Latitude</label>
                        <input type="number" step="any" class="form-control" id="lat" name="lat" 
                               value="<?= htmlspecialchars($lat ?? '') ?>" required readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="lng" class="form-label">Longitude</label>
                        <input type="number" step="any" class="form-control" id="lng" name="lng" 
                               value="<?= htmlspecialchars($lng ?? '') ?>" required readonly>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="list_cities.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Retour
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser la carte centrée sur la Côte d'Ivoire
            const map = L.map('map').setView([7.5399, -5.5471], 7);
            
            // Ajouter le fond de carte OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            // Créer un marqueur (invisible au départ)
            const marker = L.marker([0, 0], {
                draggable: true,
                opacity: 0
            }).addTo(map);

            // Gestionnaire d'événement pour les clics sur la carte
            map.on('click', function(e) {
                updateMarkerPosition(e.latlng);
            });

            // Mettre à jour la position du marqueur
            function updateMarkerPosition(latlng) {
                marker.setLatLng(latlng);
                marker.setOpacity(1);
                
                // Mettre à jour les champs du formulaire
                document.getElementById('lat').value = latlng.lat.toFixed(6);
                document.getElementById('lng').value = latlng.lng.toFixed(6);
                
                // Centrer la carte sur la nouvelle position
                map.setView(latlng, 12);
                
                // Ajouter un popup
                marker.bindPopup(`<b>Position sélectionnée</b><br>Lat: ${latlng.lat.toFixed(6)}<br>Lng: ${latlng.lng.toFixed(6)}`).openPopup();
            }

            // Bouton de localisation
            document.getElementById('locateBtn').addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const latlng = L.latLng(position.coords.latitude, position.coords.longitude);
                        updateMarkerPosition(latlng);
                    }, function(error) {
                        alert('Impossible d\'obtenir votre position : ' + error.message);
                    });
                } else {
                    alert('La géolocalisation n\'est pas supportée par votre navigateur');
                }
            });

            // Bouton de recherche
            document.getElementById('searchBtn').addEventListener('click', function() {
                const cityName = document.getElementById('place_name').value.trim();
                
                if (cityName.length < 2) {
                    alert('Veuillez entrer un nom de ville valide');
                    return;
                }
                
                // Utiliser Nominatim pour la recherche
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(cityName)}&limit=1`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            const result = data[0];
                            const latlng = L.latLng(result.lat, result.lon);
                            updateMarkerPosition(latlng);
                            
                            // Mettre à jour le nom de la ville avec le résultat exact
                            document.getElementById('place_name').value = result.display_name.split(',')[0];
                        } else {
                            alert('Aucun résultat trouvé pour cette ville');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur de recherche:', error);
                        alert('Erreur lors de la recherche de la ville');
                    });
            });

            // Si des coordonnées existent déjà (après validation échouée)
            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            
            if (latInput.value && lngInput.value) {
                const latlng = L.latLng(parseFloat(latInput.value), parseFloat(lngInput.value));
                updateMarkerPosition(latlng);
            }
        });
    </script>
</body>
</html>