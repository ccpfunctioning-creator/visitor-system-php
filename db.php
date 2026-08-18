<?php
// 🚀 UNIVERSAL SUPABASE API BRIDGE ENGINE WITH AUTOMATIC ARRAY UNPACKING
define('SUPABASE_URL', 'https://icmjvsxjhqjvzvyyolry.supabase.co');

// 💡 PASTE YOUR EXTREMELY LONG PUBLIC ANON KEY HERE (Starts with eyJhbG...)
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImljbWp2c3hqaHFqdnp2eXlvbHJ5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODY5Mjg4NTQsImV4cCI6MjEwMjUwNDg1NH0.X_jecxrmze9D1g0iCgbzLJxYlJyRkFVnMxdAnOwFvpg');

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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

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
    
    if ($httpCode >= 400) {
        echo "<div class='alert alert-danger font-monospace small m-3'><strong>Supabase API Error ($httpCode):</strong> " . htmlspecialchars($response) . "</div>";
    }

    // 🔐 AUTOMATIC ROW INDEX UNPACKER & HTTPS CONVERTER
    if (!empty($decodedData) && is_array($decodedData)) {
        // If it's a SELECT action and returns an array of rows, fix the image URLs
        foreach ($decodedData as &$row) {
            if (is_array($row) && !empty($row['cid_photo'])) {
                $row['cid_photo'] = str_replace('http://', 'https://', $row['cid_photo']);
            }
        }
    }

    return $decodedData ?? [];
}
?>
