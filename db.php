<?php
// 🚀 UNIVERSAL SUPABASE API BRIDGE ENGINE
define('SUPABASE_URL', 'https://icmjvsxjhqjvzvyyolry.supabase.co');

// 💡 PASTE YOUR EXTREMELY LONG PUBLIC ANON KEY HERE (Starts with eyJhbG...)
define('SUPABASE_KEY', 'sb_secret_18gPROtxsy8GsxIR4lP8og_tF3hN_Dk');

function querySupabaseCloud($tableName, $action, $payload = [], $filter = []) {
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . $tableName;
    
    if (!empty($filter)) {
        $queryParams = [];
        foreach ($filter as $key => $val) {
            $queryParams[] = $key . '=' . urlencode($val);
        }
        $url .= '?' . implode('&', $queryParams);
    }

    $ch = curl_init($url);
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypasses SSL certificate locks on Render free containers

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decodedData = json_decode($response, true);
    
    // Log failures out loud on the screen to debug immediately if columns are wrong
    if ($httpCode >= 400) {
        echo "<div class='alert alert-danger font-monospace small m-3'><strong>Supabase API Error ($httpCode):</strong> " . htmlspecialchars($response) . "</div>";
    }

    return $decodedData ?? [];
}
?>
