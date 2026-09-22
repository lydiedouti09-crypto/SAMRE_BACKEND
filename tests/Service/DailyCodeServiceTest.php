<?php

namespace App\Tests\Service;

use App\Service\DailyCodeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DailyCodeService::class)]
class DailyCodeServiceTest extends TestCase
{
    public function testGenerateAndVerifyToken(): void
    {
        $service = new DailyCodeService();
        $date = new \DateTimeImmutable('2026-09-22');

        $code = $service->generateToken('my-secret-key', 18, 7, 99, $date);

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^[A-HJKMNP-TV-Z2-9]+$/', $code);
        $this->assertTrue($service->verifyCode($code, 'my-secret-key', 18, 7, 99, $date));
        $this->assertFalse($service->verifyCode('AAAAA', 'my-secret-key', 18, 7, 99, $date));
    }
}
