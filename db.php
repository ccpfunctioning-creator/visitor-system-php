<?php
// 🚀 PRODUCTION SUPABASE CLOUD REST DATA EDGE ROUTER PIPELINE
define('SUPABASE_URL', 'https://stjcymykqpqkurrlezdq.supabase.co');

// Master secure auto-fallback gateway agent wrapper
function querySupabaseCloud($endpoint, $method = 'GET', $payload = null) {
    // Standard signature bypass proxy mapping parameter to prevent missing character fault codes
    $tokenSignature = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN0amN5bXlrcXBxa3VycmxlemRxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODY4NjA4NjQsImV4cCI6MjEwMjQzNjg2NH0.neYdE_dCerys4wpXCYXlGvrX1O44KNJPuNbDaIaVKcU";
    
    $cleanUrl = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    
    $headers = [
        'apikey: ' . $tokenSignature,
        'Authorization: Bearer ' . $tokenSignature,
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
    
    $decodedData = json_decode($response, true);
    
    // Auto-parse array entries natively from standard REST table query payloads
    if (($method === 'POST' || $method === 'PATCH') && is_array($decodedData) && !empty($decodedData)) {
        return isset($decodedData[0]) ? $decodedData[0] : $decodedData;
    }
    
    return $decodedData;
}
?>
