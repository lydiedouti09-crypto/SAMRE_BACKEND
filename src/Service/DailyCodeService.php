<?php

namespace App\Service;

class DailyCodeService
{
    /**
     * Alphabet Base32 sans caractères ambigus (évite 0, O, 1, I, L)
     */
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * Génère un code quotidien prédictible et vérifiable par HMAC.
     * Le code est dérivé de : secretKey + missionId + appId + testerId + dayNumber.
     */
    public function generateToken(
        string $secretKey,
        int $missionId,
        int $appId,
        int $testerId,
        int|\DateTimeInterface $dayOrDate
    ): string {
        $dayIdentifier = $dayOrDate instanceof \DateTimeInterface
            ? $dayOrDate->format('Y-m-d')
            : (string) $dayOrDate;

        $payload = sprintf('%d:%d:%d:%s', $missionId, $appId, $testerId, $dayIdentifier);
        $rawHash = hash_hmac('sha256', $payload, $secretKey, true);

        return $this->formatCode($rawHash, 8);
    }

    /**
     * Vérifie le code soumis par le SDK / testeur.
     */
    public function verifyCode(
        string $submittedCode,
        string $secretKey,
        int $missionId,
        int $appId,
        int $testerId,
        int|\DateTimeInterface $dayOrDate
    ): bool {
        $cleanSubmitted = $this->cleanCode($submittedCode);
        if (empty($cleanSubmitted)) {
            return false;
        }

        $expected = $this->generateToken($secretKey, $missionId, $appId, $testerId, $dayOrDate);
        $cleanExpected = $this->cleanCode($expected);

        return hash_equals($cleanExpected, $cleanSubmitted);
    }

    /**
     * Nettoie le code en supprimant les espaces, tirets et en le passant en majuscules.
     */
    public function cleanCode(string $code): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', trim($code)) ?? '');
    }

    /**
     * Formate les octets bruts en une chaîne Base32 lisible au format XXXX-XXXX.
     */
    private function formatCode(string $bytes, int $length = 8): string
    {
        $alphabet = self::ALPHABET;
        $alphabetLen = strlen($alphabet); // 32
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $byteVal = ord($bytes[$i] ?? chr(0));
            $result .= $alphabet[$byteVal % $alphabetLen];
        }

        if ($length === 8) {
            return substr($result, 0, 4) . '-' . substr($result, 4, 4);
        }

        return $result;
    }
}
