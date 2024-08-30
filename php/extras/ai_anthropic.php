<?php
/*
    // Usage example
    --note: add your anthropic API Key to anthropic_apikey in config.xml first
    loadExtras('ai_anthropic');
    $question = "Return a csv list all the cities in France, their current population, and the average cost a home in each city.";
    $answer = ai_anthropicAsk($question);
    echo $answer;

 */
function ai_anthropicAPIKey(){
    global $CONFIG;
    if(!isset($CONFIG['anthropic_apikey'])){
        echo "no anthropic_apikey set in config.xml";
        exit;
    }
    return $CONFIG['anthropic_apikey'];
}
function ai_anthropicAsk($question,$max_tokens=1000) {
    $url = 'https://api.anthropic.com/v1/messages';

    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . ai_anthropicAPIKey(),
        'anthropic-version: 2023-06-01'
    ];

    $data = [
        'model' => 'claude-3-sonnet-20240229',
        'max_tokens' => $max_tokens,
        'messages' => [
            ['role' => 'user', 'content' => $question]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return 'Error: ' . curl_error($ch);
    }
    curl_close($ch);
    $result = json_decode($response, true);
    if(isset($result['content'][0]['text'])){
        return $result['content'][0]['text'];
    }
    return 'Error: ' . printValue($result);
}