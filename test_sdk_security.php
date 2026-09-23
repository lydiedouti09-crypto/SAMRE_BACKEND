<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\Application;
use App\Entity\Mission;
use App\Entity\Participation;
use App\Entity\Etape;
use App\Entity\Reference;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/.env');

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? true);
$kernel = new Kernel($env, $debug);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$sdkController = $container->get('App\\Controller\\Api\\SdkController');
$dailyCodeController = $container->get('App\\Controller\\Api\\DailyCodeController');
$dailyCodeService = $container->get('App\\Service\\DailyCodeService');

echo "=================================================================\n";
echo "🧪 TEST DE SÉCURITÉ ET DE VALIDATION DU NOUVEAU SYSTÈME HMAC\n";
echo "=================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $title, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $title\n";
        if ($detail) echo "         -> $detail\n";
        $passCount++;
    } else {
        echo "  [FAIL] $title\n";
        if ($detail) echo "         -> $detail\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// SECTION A : TEST UNITAIRE DU SERVICE HMAC (DailyCodeService)
// -------------------------------------------------------------
echo "--- Section A : Test Unitaire de DailyCodeService ---\n";

$secret = 'test_secret_key_64_characters_hex_0123456789abcdef0123456789abcdef';
$codeDay1 = $dailyCodeService->generateToken($secret, 1, 2, 3, 1);
$codeDay2 = $dailyCodeService->generateToken($secret, 1, 2, 3, 2);

assertTest(
    "Génération code J1 format valide (8 cars Base32 avec tiret : XXXX-XXXX)",
    preg_match('/^[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4}$/', $codeDay1) === 1,
    "Code généré J1 : $codeDay1"
);

assertTest(
    "Codes J1 et J2 distincts pour des jours différents",
    $codeDay1 !== $codeDay2,
    "J1: $codeDay1 vs J2: $codeDay2"
);

assertTest(
    "Vérification insensible à la casse et aux tirets",
    $dailyCodeService->verifyCode(strtolower(str_replace('-', '', $codeDay1)), $secret, 1, 2, 3, 1),
    "Saisie minuscule sans tiret acceptée"
);

assertTest(
    "Rejet d'un code faux",
    !$dailyCodeService->verifyCode('FAUX-CODE', $secret, 1, 2, 3, 1),
    "Code bidon rejeté"
);

// -------------------------------------------------------------
// SECTION B : TESTS D'INTÉGRATION ENDPOINTS
// -------------------------------------------------------------
echo "\n--- Section B : Configuration des Entités de Test ---\n";

$testApp = new Application();
$testApp->setNom('HMAC Test App');
$testApp->setPlateforme('android');
$testApp->setVersion('1.0.0');
$testApp->setStatut('actif');
$testApp->setApiKey('sk_app_test_' . bin2hex(random_bytes(16)));
$testApp->setSdkToken('sdk_test_' . bin2hex(random_bytes(16)));
$testApp->setSecretKey(bin2hex(random_bytes(32)));
$testApp->setTokenIntegration('tok_test_' . bin2hex(random_bytes(16)));
$testApp->setDureeJoursDefaut(12);
$testApp->setNbMaxPanelistes(12);
$em->persist($testApp);

$testUser = new User();
$testUser->setEmail('tester_hmac_' . bin2hex(random_bytes(4)) . '@example.com');
$testUser->setNom('Testeur');
$testUser->setPrenom('HMAC');
$testUser->setRoles(['ROLE_TESTEUR']);
$testUser->setPassword('password123');
$em->persist($testUser);

$testMission = new Mission();
$testMission->setTitre('Mission Test HMAC');
$testMission->setDescription('Mission pour test de validation');
$testMission->setApplicationEntity($testApp);
$testMission->setApplication('HMAC Test App');
$testMission->setPlatforme('Android');
$testMission->setNombreParticipantsActuels(1);
$testMission->setNombreParticipantsSouhaites(12);
$testMission->setDateCreation(new \DateTime());
$testMission->setStatut('ouverte');
$testMission->setDureEstime('12 jours');
$testMission->setDateDebut(new \DateTime());
$testMission->setDateFin((new \DateTime())->modify('+12 days'));
$em->persist($testMission);

$testPart = new Participation();
$testPart->setMission($testMission);
$testPart->setUser($testUser);
$testPart->setStatus('en_cours');
$testPart->setPanelisteUid('TST-HMAC-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)));
$testPart->setDateDebut(new \DateTime());
$testPart->setProgression(0);
$testPart->setEtapesCompletees(0);
$testPart->setEtapesTotal(12);
$testPart->setJoursValides([]);
$testPart->setDateCreation(new \DateTime());
$em->persist($testPart);

$em->flush();

$apiKey = $testApp->getApiKey();
$sdkToken = $testApp->getSdkToken();
$secretKey = $testApp->getSecretKey();
$panelisteUid = $testPart->getPanelisteUid();
$testerId = $testUser->getId();
$missionId = $testMission->getId();
$appId = $testApp->getId();

echo "App ID: $appId | Tester ID: $testerId | Paneliste: $panelisteUid\n\n";

try {
    // --- Test 1 : Rejet si mauvais code ---
    echo "--- Test 1 : Rejet si code faux sur /api/v1/sdk/verify-code ---\n";
    $reqBad = Request::create('/api/v1/sdk/verify-code', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_APP_KEY' => $apiKey,
        'HTTP_X_SDK_TOKEN' => $sdkToken,
    ], json_encode([
        'testerId' => $testerId,
        'code' => '9999-9999',
        'deviceId' => 'device-phone-xyz',
    ]));
    $resBad = $dailyCodeController->verifyCodeFromSdk($reqBad);
    $dataBad = json_decode($resBad->getContent(), true);
    assertTest(
        "Rejet 422 avec code incorrect",
        $resBad->getStatusCode() === 422 && $dataBad['success'] === false,
        "Status: " . $resBad->getStatusCode()
    );

    // --- Test 2 : Validation Jour 1 avec vrai code HMAC ---
    echo "\n--- Test 2 : Validation réussie du Jour 1 ---\n";
    $codeJ1 = $dailyCodeService->generateToken($secretKey, $missionId, $appId, $testerId, 1);
    $reqJ1 = Request::create('/api/v1/sdk/verify-code', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_APP_KEY' => $apiKey,
        'HTTP_X_SDK_TOKEN' => $sdkToken,
    ], json_encode([
        'panelisteUid' => $panelisteUid,
        'code' => $codeJ1,
        'deviceId' => 'device-phone-xyz',
    ]));
    $resJ1 = $dailyCodeController->verifyCodeFromSdk($reqJ1);
    $dataJ1 = json_decode($resJ1->getContent(), true);

    assertTest(
        "Validation Jour 1 réussie (200 OK)",
        $resJ1->getStatusCode() === 200 && ($dataJ1['success'] ?? false) === true && ($dataJ1['data']['jourValide'] ?? 0) === 1,
        "Message: " . ($dataJ1['message'] ?? '') . " | Jour validé: " . ($dataJ1['data']['jourValide'] ?? '')
    );

    // --- Test 3 : Re-soumission du Jour 1 (Anti-Replay) ---
    echo "\n--- Test 3 : Détection de rejeu sur Jour 1 déjà validé ---\n";
    $resJ1Replay = $dailyCodeController->verifyCodeFromSdk($reqJ1);
    $dataJ1Replay = json_decode($resJ1Replay->getContent(), true);

    assertTest(
        "Jour 1 détecté comme déjà validé (alreadyValidated = true)",
        $resJ1Replay->getStatusCode() === 200 && ($dataJ1Replay['data']['alreadyValidated'] ?? false) === true,
        "Message: " . ($dataJ1Replay['message'] ?? '')
    );

    // --- Test 4 : Validation Jour 2 sur le MÊME deviceId (Vérification du correctif de bug !) ---
    echo "\n--- Test 4 : Validation réussie du Jour 2 sur le MÊME deviceId ---\n";
    $codeJ2 = $dailyCodeService->generateToken($secretKey, $missionId, $appId, $testerId, 2);
    $reqJ2 = Request::create('/api/v1/sdk/verify-code', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_APP_KEY' => $apiKey,
        'HTTP_X_SDK_TOKEN' => $sdkToken,
    ], json_encode([
        'panelisteUid' => $panelisteUid,
        'code' => $codeJ2,
        'deviceId' => 'device-phone-xyz', // MÊME smartphone que J1 !
    ]));
    $resJ2 = $dailyCodeController->verifyCodeFromSdk($reqJ2);
    $dataJ2 = json_decode($resJ2->getContent(), true);

    assertTest(
        "Le même smartphone peut valider le Jour 2 sans être bloqué par l'anti-replay",
        $resJ2->getStatusCode() === 200 && ($dataJ2['data']['jourValide'] ?? 0) === 2 && ($dataJ2['data']['progression'] ?? 0) > 0,
        "Progression calculée : " . ($dataJ2['data']['progression'] ?? '') . "% | Étapes : 2/12"
    );

} finally {
    echo "\nNettoyage des entités de test...\n";
    $em->remove($testPart);
    $em->remove($testMission);
    $em->remove($testUser);
    $em->remove($testApp);
    $em->flush();
}

echo "\n=================================================================\n";
echo "RÉSULTAT GLOBAL DU TEST : $passCount / " . ($passCount + $failCount) . " TESTS RÉUSSIS\n";
echo "=================================================================\n";
