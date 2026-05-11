<?php
/**
 * Helper para gerenciar textos de destaque por dia da semana
 */

if (!function_exists('get_daily_highlight_text')) {
    /**
     * Obtém o texto de destaque para o dia atual
     * 
     * @param array $company Dados da empresa
     * @return string Texto de destaque
     */
    function get_daily_highlight_text(array $company): string
    {
        // Se não houver textos por dia, retornar vazio
        $highlightByDay = null;
        
        if (!empty($company['highlight_texts_by_day'])) {
            $json = $company['highlight_texts_by_day'];
            if (is_string($json)) {
                $highlightByDay = json_decode($json, true);
            } elseif (is_array($json)) {
                $highlightByDay = $json;
            }
        }
        
        // Se não tiver textos por dia, retornar vazio
        if (!is_array($highlightByDay) || empty($highlightByDay)) {
            return '';
        }
        
        // Obter o dia da semana atual (em inglês)
        $dayName = strtolower(date('l')); // 'monday', 'tuesday', etc.
        
        // Verificar se o dia está habilitado
        $enabledDays = [];
        if (!empty($company['highlight_texts_enabled_days'])) {
            $enabledJson = $company['highlight_texts_enabled_days'];
            $enabledDays = is_string($enabledJson) ? json_decode($enabledJson, true) : $enabledJson;
            $enabledDays = is_array($enabledDays) ? $enabledDays : [];
        }
        
        // Se o dia atual não está na lista de habilitados, retornar vazio
        if (!empty($enabledDays) && !in_array($dayName, $enabledDays, true)) {
            return '';
        }
        
        // Retornar o texto do dia atual
        return (string)($highlightByDay[$dayName] ?? '');
    }
}

if (!function_exists('get_all_daily_highlight_texts')) {
    /**
     * Obtém todos os textos de destaque por dia
     * 
     * @param array $company Dados da empresa
     * @return array Textos por dia
     */
    function get_all_daily_highlight_texts(array $company): array
    {
        $default = [
            'monday'    => '',
            'tuesday'   => '',
            'wednesday' => '',
            'thursday'  => '',
            'friday'    => '',
            'saturday'  => '',
            'sunday'    => ''
        ];
        
        if (empty($company['highlight_texts_by_day'])) {
            return $default;
        }
        
        $json = $company['highlight_texts_by_day'];
        $data = is_string($json) ? json_decode($json, true) : (is_array($json) ? $json : []);
        
        return array_merge($default, (is_array($data) ? $data : []));
    }
}

if (!function_exists('save_daily_highlight_texts')) {
    /**
     * Salva os textos de destaque por dia
     * 
     * @param PDO $db Conexão PDO
     * @param int $companyId ID da empresa
     * @param array $texts Textos por dia
     * @return bool Sucesso
     */
    function save_daily_highlight_texts(PDO $db, int $companyId, array $texts): bool
    {
        // Validar dias válidos
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $cleanTexts = [];
        
        foreach ($validDays as $day) {
            $cleanTexts[$day] = (string)($texts[$day] ?? '');
        }
        
        $json = json_encode($cleanTexts, JSON_UNESCAPED_UNICODE);
        
        $stmt = $db->prepare('
            UPDATE companies 
            SET highlight_texts_by_day = ? 
            WHERE id = ?
        ');
        
        return $stmt->execute([$json, $companyId]);
    }
}

if (!function_exists('save_enabled_highlight_days')) {
    /**
     * Salva quais dias estão habilitados para exibição
     * 
     * @param PDO $db Conexão PDO
     * @param int $companyId ID da empresa
     * @param array $enabledDays Array de dias habilitados ['monday', 'tuesday', ...]
     * @return bool Sucesso
     */
    function save_enabled_highlight_days(PDO $db, int $companyId, array $enabledDays): bool
    {
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $cleanDays = [];
        
        foreach ($enabledDays as $day) {
            if (in_array($day, $validDays, true)) {
                $cleanDays[] = $day;
            }
        }
        
        $json = json_encode(array_values($cleanDays), JSON_UNESCAPED_UNICODE);
        
        $stmt = $db->prepare('
            UPDATE companies 
            SET highlight_texts_enabled_days = ? 
            WHERE id = ?
        ');
        
        return $stmt->execute([$json, $companyId]);
    }
}
