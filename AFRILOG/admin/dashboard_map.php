<?php
session_start();
include '../config.php';

$stmt = $pdo->query("
    // Remplacer l'ancienne requête par :
    SELECT 
        p.id,
        p.tracking_number,
        p.sender_name,
        p.receiver_name,
        p.status,
        c.name AS destination_city,
        p.created_at
    FROM packages p
    LEFT JOIN cities c ON p.city_id = c.id
    ORDER BY p.created_at DESC

");
$all_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="dashboard-map" style="height: 600px; width: 100%;"></div>

<script>
function initMap() {
    const map = new google.maps.Map(document.getElementById('dashboard-map'), {
        zoom: 4,
        center: { lat:5.2967076, lng:-4.0035661 } 
    });

    <?php foreach ($all_locations as $loc) : ?>
        new google.maps.Marker({
            position: { lat: <?= $loc['lat'] ?>, lng: <?= $loc['lng'] ?> },
            map: map,
            title: "Colis <?= $loc['tracking_number'] ?> - <?= $loc['updated_at'] ?>"
        });
    <?php endforeach; ?>
}
</script>
<script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDiw_DCMqoSQ5MoxmNqwbMKN_JEy-qQAS0&libraries=places&callback=initMap"
        async defer></script>
