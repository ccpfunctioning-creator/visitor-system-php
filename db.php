<?php
// 🚀 SUPABASE CLOUD DATA NETWORK PIPELINE
define('SUPABASE_URL', 'https://stjcymykqpqkurrlezdq.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_3pv8ZKvVnibn91bhWQ0cMA_Aw5xjf51'); // Placeholder: You will need to replace this string with your long "anon public" key if needed

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
    
    // Fallback: If network drops or throws a token fault code, return clean empty structures
    if ($httpCode >= 400) {
        return [];
    }
    
    return json_decode($response, true);
}
?>
