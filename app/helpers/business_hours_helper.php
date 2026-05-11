<?php
/**
 * Helper para verificar status do horário de funcionamento
 */

if (!function_exists('check_business_hours_status')) {
    /**
     * Verifica se a empresa está dentro do horário de funcionamento
     * Retorna array com status e informações do horário atual
     *
     * @param array $hours Array indexado por weekday (1-7) com dados de company_hours
     * @return array ['is_open' => bool, 'label' => string, 'current_time' => string, 'today_hours' => string]
     */
    function check_business_hours_status(array $hours): array
    {
        $tz = new \DateTimeZone('America/Sao_Paulo');
        $now = new \DateTime('now', $tz);
        $weekday = (int) $now->format('N'); // 1=Segunda ... 7=Domingo
        $currentTime = $now->format('H:i:s');
        $currentTimeShort = $now->format('H:i');

        $day = $hours[$weekday] ?? null;

        // Dia não configurado ou fechado
        if (!$day || empty($day['is_open'])) {
            return [
                'is_open' => false,
                'label' => 'Fora do horário',
                'current_time' => $currentTimeShort,
                'today_hours' => 'Fechado hoje',
            ];
        }

        // Montar string do horário de hoje
        $todayParts = [];
        $isInRange = false;

        if (!empty($day['open1']) && !empty($day['close1'])) {
            $o1 = substr((string) $day['open1'], 0, 5);
            $c1 = substr((string) $day['close1'], 0, 5);
            $todayParts[] = "$o1 - $c1";

            if (_bh_time_in_range($currentTime, $day['open1'], $day['close1'])) {
                $isInRange = true;
            }
        }

        if (!empty($day['open2']) && !empty($day['close2'])) {
            $o2 = substr((string) $day['open2'], 0, 5);
            $c2 = substr((string) $day['close2'], 0, 5);
            $todayParts[] = "$o2 - $c2";

            if (_bh_time_in_range($currentTime, $day['open2'], $day['close2'])) {
                $isInRange = true;
            }
        }

        $todayHours = $todayParts ? implode(' / ', $todayParts) : 'Horário não definido';

        return [
            'is_open' => $isInRange,
            'label' => $isInRange ? 'Dentro do horário' : 'Fora do horário',
            'current_time' => $currentTimeShort,
            'today_hours' => $todayHours,
        ];
    }
}

if (!function_exists('_bh_time_in_range')) {
    /**
     * Verifica se o horário atual está no intervalo (suporta virada de meia-noite)
     */
    function _bh_time_in_range(string $current, string $open, string $close): bool
    {
        if ($open <= $close) {
            return ($current >= $open && $current <= $close);
        }
        // Virada de meia-noite (ex: 18:00 -> 02:00)
        return ($current >= $open || $current <= $close);
    }
}
