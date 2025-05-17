<?php

    function generateHabits($description) {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=$key";
            
            // Preparar os dados para a requisição
            $data = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "text" => "Gere uma lista de 5 hábitos diários precisos e específicos para o seguinte objetivo:
                                
                                Instruções:
                                - Crie hábitos práticos e alcançáveis
                                - Cada hábito deve ser específico e mensurável
                                - Foque em ações concretas que levem ao objetivo
                                
                                Formato de resposta:
                                [
                                    {
                                        'habit_name': 'Nome curto do hábito',
                                        'habit_description': 'Descrição motivadora e específica'
                                    }
                                ]
                                
                                Objetivo: $description"
                            ]
                        ]
                    ]
                ],
                "generationConfig" => [
                    "temperature" => 2,
                    "maxOutputTokens" => 1000
                ]
            ];

            // Inicializar cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            // Executar a requisição
            $response = curl_exec($ch);

            // Verificar erros de cURL
            if(curl_errno($ch)){
                error_log('Erro cURL: ' . curl_error($ch));
                curl_close($ch);
                return null;
            }

            // Fechar conexão cURL
            curl_close($ch);

            // Decodificar a resposta
            $responseData = json_decode($response, true);

            // Extrair o texto da resposta
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $habitsText = $responseData['candidates'][0]['content']['parts'][0]['text'];
                
                // Tentar parsear o JSON
                $habits = json_decode($habitsText, true);
                
                // Validar os hábitos
                if (is_array($habits)) {
                    return $habits;
                } else {
                    // Se o JSON falhar, tentar parsear manualmente
                    $habits = parseHabitsManually($habitsText);
                    return $habits;
                }
            }

            return null;
        } catch (Exception $e) {
            error_log('Erro ao gerar hábitos: ' . $e->getMessage());
            return null;
        }
    }

    function parseHabitsManually($text) {
        $habits = [];
        
        // Expressão regular para encontrar padrões de hábitos
        preg_match_all('/(?:\'|")habit_name(?:\'|"):\s*(?:\'|")([^\'"]*)(?:\'|"),\s*(?:\'|")habit_description(?:\'|"):\s*(?:\'|")([^\'"]*)(?:\'|")/i', $text, $matches);
        
        if (!empty($matches[1]) && !empty($matches[2])) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $habits[] = [
                    'habit_name' => trim($matches[1][$i]),
                    'habit_description' => trim($matches[2][$i])
                ];
            }
        }
        
        return $habits ?: null;
    }
?>
