= 400) {
        return [];
    }
    
    $decodedData = json_decode($response, true);
    
    // 🚀 THE ULTIMATE FIX: If Supabase returns a collection list array wrapper, peel it off instantly!
    if (is_array($decodedData) && isset($decodedData[0]) && is_array($decodedData[0])) {
        return $decodedData[0]; // Extracted flat data object out of the list layout envelope [{...}] -> {...}
    }
    
    return $decodedData;
}
?>
