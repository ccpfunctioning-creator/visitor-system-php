<?php
// 🚀 SUPABASE CLOUD DATA NETWORK PIPELINE
define('SUPABASE_URL', 'https://supabase.co');
define('SUPABASE_KEY', 'sb_publishable_3pv8ZKvVnibn91bhWQ0cMt1kM5U');

function querySupabaseCloud($endpoint, $method = 'GET', $payload = null) {
    // Standard routing parser to avoid endpoint double mapping errors
    $cleanUrl = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
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
    
    // Fallback: If network drops or throws a token fault code, return clean empty structures
    if ($httpCode >= 400) {
        return [];
    }
    
    $decodedData = json_decode($response, true);
    
    // Auto-parse array records natively from standard REST endpoints
    if (($method === 'POST' || $method === 'PATCH') && is_array($decodedData) && !empty($decodedData)) {
        return isset($decodedData[0]) ? $decodedData[0] : $decodedData;
    }
    
    return $decodedData;
}
?>
