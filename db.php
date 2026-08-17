<?php
// 🚀 DRIVERLESS CLOUD REST DATA EDGE ROUTER PIPELINE FOR SUPABASE
define('SUPABASE_URL', 'https://icmjvsxjhqjvzvyyolry.supabase.co');

// 💡 EXTREMELY IMPORTANT: Make sure to replace the placeholder key below 
// with your actual long public "anon" public key string starting with "eyJhbG..."
define('SUPABASE_KEY', 'sb_secret_18gPROtxsy8GsxIR4lP8og_tF3hN_Dk');

/**
 * Executes a driverless REST API request straight to your Supabase tables.
 */
function querySupabaseCloud($tableName, $action, $payload = [], $filter = []) {
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . $tableName;
    
    // Append query filter parameters for selective record updates or lookups
    if (!empty($filter)) {
        $queryParams = [];
        foreach ($filter as $key => $val) {
            $queryParams[] = $key . '=' . urlencode($val);
        }
        $url .= '?' . implode('&', $queryParams);
    }

    $ch = curl_init($url);
    
    // Set standard secure cloud authentication headers required by Supabase
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation' // Forces Supabase to return processed rows
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($action === 'INSERT') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    } elseif ($action === 'UPDATE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }

    $response = curl_exec($ch);
    curl_close($ch);

    $decodedData = json_decode($response, true);
    return $decodedData ?? [];
}
?>
