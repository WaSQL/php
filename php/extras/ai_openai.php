<?php
/*
    // Usage example
    --note: add your anthropic API Key to anthropic_apikey in config.xml first
    loadExtras('ai_openai');
    $question = "Return a csv list all the cities in France, their current population, and the average cost a home in each city.";
    $answer = ai_openapiAsk($question);
    echo $answer;

 */
function ai_openapiAPIKey(){
    global $CONFIG;
    if(!isset($CONFIG['openai_apikey'])){
        echo "no openai_apikey set in config.xml";
        exit;
    }
    return $CONFIG['openai_apikey'];
}
function ai_openapiAsk($question) {
    $url = 'https://api.openai.com/v1/chat/completions';

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . ai_openapiAPIKey()
    ];

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => $question]
        ],
        'temperature' => 0.7
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
    if(isset($result['choices'][0]['message']['content'])){
        return $result['choices'][0]['message']['content'];
    }
    return 'Error: ' . printValue($result);
}