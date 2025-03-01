<?php
function generateHabits($descricao) {
    $apiKey = "AIzaSyA8GvLhXyDxjv-zeBknJjLVFbOMaMflBWs"; 
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=$apiKey";

    $data = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => "Crie uma lista de 5 hábitos diários no seguinte formato (sem números ou marcadores):\n\nNome: [Título do hábito]\nDescrição: [Descrição breve do hábito]\n\nGere hábitos para: " . $descricao
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return "Erro ao conectar à API: " . curl_error($ch);
    }
    curl_close($ch);

    // Debug: Exibe a resposta bruta da API
    echo "<pre>";
    print_r($response);
    echo "</pre>";

    $result = json_decode($response, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $result['candidates'][0]['content']['parts'][0]['text'];

        // Extrair hábitos separando "Nome:" e "Descrição:"
        $habits = [];
        preg_match_all('/Nome:\s*(.*?)\nDescrição:\s*(.*?)(?=\nNome:|\z)/s', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $habits[] = [
                'habit_name' => trim($match[1]),
                'habit_description' => trim($match[2])
            ];
        }

        return $habits;
    } else {
        return "Erro ao gerar hábitos.";
    }
}

?>
