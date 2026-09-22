<?php

namespace App\Service;

class DailyCodeService
{
    public function generateToken(string $secretKey, int $missionId, int $appId, int $testerId, \DateTimeImmutable $date): string
    {
        $payload = sprintf('%d:%d:%d:%s', $missionId, $appId, $testerId, $date->format('Y-m-d'));
        $hmac = hash_hmac('sha256', $payload, $secretKey);

        return $this->toBase32Readable(substr($hmac, 0, 10));
    }

    public function verifyCode(string $submittedCode, string $secretKey, int $missionId, int $appId, int $testerId, \DateTimeImmutable $date): bool
    {
        $expected = $this->generateToken($secretKey, $missionId, $appId, $testerId, $date);

        return hash_equals($expected, strtoupper(trim($submittedCode)));
    }

    private function toBase32Readable(string $hex): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $clean = strtolower($hex);
        $value = hexdec($clean);

        $chars = '';
        $base = strlen($alphabet);

        do {
            $chars = $alphabet[$value % $base] . $chars;
            $value = intdiv($value, $base);
        } while ($value > 0);

        return substr(str_pad($chars, 10, 'A', STR_PAD_LEFT), 0, 10);
    }
}
