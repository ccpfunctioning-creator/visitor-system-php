<?php
// 🚀 PRODUCTION SUPABASE CLOUD NETWORK PIPELINE INTEGRATION
define('SUPABASE_URL', 'https://stjcymykqpqkurrlezdq.supabase.co'); 
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN0amN5bXlrcXBxY3VybGV6ZHEiLCJyb2xlIjoiYW5vbiIsImlhdCI6MTc4NTE1Njk0NywiZXhwIjoyMTAwNzMyOTQ3fQ.vNnQ9YJgV0nE_f7nZ29K-G9WnK9M5U_vNnQ9YJgV0nE');

function querySupabaseCloud($endpoint, $method = 'GET', $payload = null) {
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
    
    if ($httpCode >= 400) {
        return [];
    }
    
    $decodedData = json_decode($response, true);
    
    // Auto-parse array records natively from standard REST endpoints
    if (($method === 'POST' || $method === 'PATCH') && is_array($decodedData) && !empty($decodedData)) {
        // Return first item of array if database returns collection envelope
        return isset($decodedData[0]) ? $decodedData[0] : $decodedData;
    }
    
    return $decodedData;
}
?>
