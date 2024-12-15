<?php
include 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM stock_summary");
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($stocks);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
