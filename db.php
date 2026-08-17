<?php
// 🚀 PRODUCTION SUPABASE CLOUD REST DATA EDGE ROUTER PIPELINE
define('SUPABASE_URL', 'https://supabase.co');
// 💡 IMPORTANT: Make sure this key string is the extremely long one starting with "eyJhbG..."
define('SUPABASE_KEY', 'PASTE_YOUR_EXTREMELY_LONG_ANON_PUBLIC_KEY_STRING_HERE');

function querySupabaseCloud($endpoint, $method = 'GET', $payload = null) {
    $cleanUrl = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation' // Tells Supabase to send back the created record database rows
    ];

    $ch = curl_init($cleanUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        return [];
    }
    
    $decodedData = json_decode($response, true);
    
    // Auto-extract first item from array wrappers natively during POST operations
    if ($method === 'POST' && is_array($decodedData) && !empty($decodedData)) {
        return $decodedData[0];
    }
    
    return $decodedData;
}
?>
