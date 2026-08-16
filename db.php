<?php
// 🚀 SUPABASE CLOUD DATA NETWORK PIPELINE
define('SUPABASE_URL', 'https://stjcymykqpqkurrlezdq.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN0amN5bXlrcXBxY3VybGV6ZHEiLCJyb2xlIjoiYW5vbiIsImlhdCI6MTc4NTE1Njk0NywiZXhwIjoyMTAwNzMyOTQ3fQ.xxxx'); // Placeholder: You will need to replace this string with your long "anon public" key if needed

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
    curl_close($ch);
    
    return json_decode($response, true);
}
?>
