<?php
require_once '../config/cors.php';
require_once '../config/db.php';

try {
    // Get query parameters
    $city = isset($_GET['city']) ? trim($_GET['city']) : null;
    $activeOnly = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;

    // Build query
    $sql = "SELECT
                id,
                name,
                city,
                address,
                zip_code,
                contact_person,
                phone,
                email,
                is_active,
                created_at
            FROM turkish_centers
            WHERE 1=1";

    $params = [];

    // Filter by city if provided
    if ($city && $city !== '') {
        $sql .= " AND city = ?";
        $params[] = $city;
    }

    // Filter by active status
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }

    $sql .= " ORDER BY city ASC, name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $centers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_active to boolean for frontend
    foreach ($centers as &$center) {
        $center['id'] = (int)$center['id'];
        $center['is_active'] = (bool)$center['is_active'];
    }

    // Get unique cities for filtering
    $citiesStmt = $pdo->query("
        SELECT DISTINCT city
        FROM turkish_centers
        WHERE is_active = 1
        ORDER BY city ASC
    ");
    $cities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data' => [
            'centers' => $centers,
            'cities' => $cities,
            'total' => count($centers)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in centers/list.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'debug' => [
            'message' => $e->getMessage()
        ]
    ]);
} catch (Exception $e) {
    error_log("General error in centers/list.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred'
    ]);
}
