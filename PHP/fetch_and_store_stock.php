<?php
// Include database connection
include 'db.php';

// Get the stock symbol from the GET request
$symbol = strtoupper($_GET['symbol'] ?? ''); // Convert to uppercase
if (empty($symbol)) {
    echo json_encode(['error' => 'No stock ticker provided.']);
    exit;
}

// Check if the stock exists in the database
$stmt = $pdo->prepare("SELECT * FROM stock_summary WHERE symbol = :symbol");
$stmt->execute(['symbol' => $symbol]);
$stock = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch from MarketStack API if stock doesn't exist or is older than 1 day
if (!$stock || (strtotime($stock['last_updated']) < strtotime('-1 day'))) {
    $apiKey = 'b72e36a7fcfc2bcd8c5bce45c6ee75e2'; 
    $apiUrl = "http://api.marketstack.com/v1/eod/latest?access_key=$apiKey&symbols=$symbol";

    try {
        // Fetch data using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $apiResponse = curl_exec($ch);
        curl_close($ch);

        // Check if the API response is valid
        if (!$apiResponse) {
            echo json_encode(['error' => 'Failed to fetch data from MarketStack API.']);
            exit;
        }

        $stockData = json_decode($apiResponse, true);
    

        // Validate the API response
        if (empty($stockData['data']) || count($stockData['data']) === 0) {
            echo json_encode(['error' => 'No data found for the entered stock.']);
            exit;
        }
      
        // Extract relevant data
        $latestData = $stockData['data'][0];
        $data = [
            'symbol' => $latestData['symbol'],
            'current_price' => (float) $latestData['close'],
            'day_high' => (float) $latestData['high'],
            'day_low' => (float) $latestData['low'],
            'open' => (float) $latestData['open'],
            'last_updated' => date('Y-m-d H:i:s') 
        ];

        // Store or update data in the database
        $stmt = $pdo->prepare("
            INSERT INTO stock_summary (symbol, current_price, day_high, day_low, open, last_updated)
            VALUES (:symbol, :current_price, :day_high, :day_low, :open, :last_updated)
            ON DUPLICATE KEY UPDATE 
            current_price = :current_price, 
            day_high = :day_high, 
            day_low = :day_low, 
            open = :open,
            last_updated = :last_updated"
        );
        if (!$stmt->execute($data)) {
            echo json_encode(['error' => 'Failed to update database.']);
            exit;
        }

        // Return the fetched data
        echo json_encode($data);
    } catch (Exception $e) {
        echo json_encode(['error' => 'An error occurred while processing the request.']);
    }
} else {
    // Return data from the database
    echo json_encode($stock);
}
