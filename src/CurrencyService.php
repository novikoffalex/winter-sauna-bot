<?php
/**
 * Простой сервис конвертации валют для THB -> USD
 * Использует публичный API exchangerate.host без ключей, с резервом на фиксированный курс.
 */

class CurrencyService
{
    /**
     * Конвертирует сумму в батах в доллары США.
     * Возвращает число с 2 знаками после запятой.
     */
    public function convertThbToUsd(float $amountThb): float
    {
        $rate = $this->fetchThbUsdRate();
        $usd = $amountThb * $rate; // rate = USD per 1 THB
        return round($usd, 2);
    }

    /**
     * Получить курс USD за 1 THB.
     * Пытаемся через exchangerate.host; если не удалось — используем резервный курс 0.027.
     */
    private function fetchThbUsdRate(): float
    {
        try {
            $url = 'https://api.exchangerate.host/latest?base=THB&symbols=USD';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http >= 400 || $resp === false) {
                throw new \Exception('FX HTTP error');
            }
            $json = json_decode($resp, true);
            if (!isset($json['rates']['USD'])) {
                throw new \Exception('FX parse error');
            }
            // base=THB → USD value показывает USD за 1 THB
            return (float)$json['rates']['USD'];
        } catch (\Throwable $e) {
            error_log('CurrencyService fallback rate used: ' . $e->getMessage());
            return 0.027; // ~1 THB ≈ 0.027 USD (примерный резервный курс)
        }
    }
}


