<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

$symbol = strtoupper($_GET['symbol'] ?? ''); // Convert symbol to uppercase
if (empty($symbol)) {
    echo json_encode(['error' => 'No stock ticker provided.']);
    exit;
}

// API Configuration
$apiKey = '1TTGEHDY3IDFZWKDY'; // Replace with your Alpha Vantage API key
$apiUrl = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$apiKey";

try {
    // Fetch stock data from API
    $apiResponse = file_get_contents($apiUrl);
    $stockData = json_decode($apiResponse, true);

    if (empty($stockData['Global Quote'])) {
        echo json_encode(['error' => 'No data found for the entered stock.']);
        exit;
    }

    // Extract relevant data
    $quote = $stockData['Global Quote'];
    $data = [
        'symbol' => $quote['01. symbol'],
        'company_name' => 'N/A', // Add a proper API for company names if needed
        'current_price' => (float) $quote['05. price'],
        'market_cap' => 0, // Market cap data not available in Alpha Vantage's free tier
        'day_high' => (float) $quote['03. high'],
        'day_low' => (float) $quote['04. low'],
        'last_updated' => date('Y-m-d H:i:s')
    ];

    // Store data in the database
    $stmt = $pdo->prepare("
        INSERT INTO stock_summary (symbol, company_name, current_price, market_cap, day_high, day_low, last_updated)
        VALUES (:symbol, :company_name, :current_price, :market_cap, :day_high, :day_low, :last_updated)
        ON DUPLICATE KEY UPDATE 
            current_price = :current_price, 
            day_high = :day_high, 
            day_low = :day_low, 
            last_updated = :last_updated
    ");
    $stmt->execute($data);

    // Return the fetched data as JSON
    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
