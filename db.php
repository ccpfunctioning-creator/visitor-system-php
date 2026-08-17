<?php
// 🚀 SUPABASE CLOUD REST DATA EDGE ROUTER PIPELINE
define('SUPABASE_URL', 'https://supabase.co');

function querySupabaseCloud($endpoint, $method = 'GET', $payload = null) {
    // ⚡ MASTER SERVICE API TOKEN MATCHING YOUR REPOSITORY
    $tokenSignature = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN0amN5bXlrcXBxY3VybGV6ZHEiLCJyb2xlIjoiYW5vbiIsImlhdCI6MTc4NTE1Njk0NywiZXhwIjoyMTAwNzMyOTQ3fQ.vNnQ9YJgV0nE_f7nZ29K-G9WnK9M5U_vNnQ9YJgV0nE";
    
    $cleanUrl = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    
    $headers = [
        'apikey: ' . $tokenSignature,
        'Authorization: Bearer ' . $tokenSignature,
        'Content-Type: application/json',
        'Prefer: return=representation' // Demands record data back from cloud table layers
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
    
    // 🚀 UNWRAP SUPABASE COLLECTION ARRAYS IMMEDIATELY
    if (($method === 'POST' || $method === 'PATCH') && is_array($decodedData) && !empty($decodedData)) {
        if (isset($decodedData[0])) {
            return $decodedData[0]; // Extract the flat record object out of the initial index envelope
        }
    }
    
    return $decodedData;
}
?>
