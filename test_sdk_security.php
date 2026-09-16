<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\Application;
use App\Entity\Mission;
use App\Entity\Participation;
use App\Entity\Etape;
use App\Entity\Reference;
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

echo "=================================================================\n";
echo "🧪 TEST DE SÉCURITÉ DE L'ENDPOINT SDK : POST /api/sdk/verify-day\n";
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

// 1. SETUP : Création d'une application de test avec sa vraie clé API
$testApp = new Application();
$testApp->setNom('Sécurité Test App');
$testApp->setPlateforme('android');
$testApp->setVersion('1.0.0');
$testApp->setStatut('actif');
$testApp->setApiKey('sk_app_test_' . bin2hex(random_bytes(16)));
$testApp->setTokenIntegration('tok_test_' . bin2hex(random_bytes(16)));
$testApp->setDureeJoursDefaut(12);
$testApp->setNbMaxPanelistes(12);
$em->persist($testApp);

// 2. SETUP : Création d'une mission liée
$testMission = new Mission();
$testMission->setTitre('Mission Sécurité Test');
$testMission->setApplicationEntity($testApp);
$testMission->setApplication('Sécurité Test App');
$testMission->setStatut('ouverte');
$testMission->setNombreParticipantsSouhaites(12);
$testMission->setDureEstime('12 jours');
$testMission->setRemuneration('5000');
$testMission->setDateDebut(new \DateTime());
$testMission->setDateFin((new \DateTime())->modify('+12 days'));
$em->persist($testMission);

// 3. SETUP : Création d'une étape pour le Jour 1
$testEtape = new Etape();
$testEtape->setMission($testMission);
$testEtape->setJour(1);
$testEtape->setOrdre(1);
$testEtape->setTitre('Jour 1 : Test Initial');
$testEtape->setStatut('actif');
$testEtape->setBesoinReference(true);
$em->persist($testEtape);

// 4. SETUP : Création d'une participation panéliste
$testPart = new Participation();
$testPart->setMission($testMission);
$testPart->setStatus('en_cours');
$testPart->setPanelisteUid('TST-SECURE-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)));
$testPart->setDateDebut(new \DateTime());
$testPart->setProgression(0);
$testPart->setEtapesCompletees(0);
$testPart->setEtapesTotal(12);
$em->persist($testPart);

// 5. SETUP : Création de la référence du jour
$validDailyCode = 'SECU-J01-K9X2';
$testRef = new Reference();
$testRef->setMission($testMission);
$testRef->setParticipation($testPart);
$testRef->setEtape($testEtape);
$testRef->setReference($validDailyCode);
$testRef->setDateGeneration(new \DateTime());
$testRef->setDateExpiration((new \DateTime())->modify('+48 hours'));
$testRef->setStatut('generee');
$em->persist($testRef);

$em->flush();

$validApiKey = $testApp->getApiKey();
$validPanelisteUid = $testPart->getPanelisteUid();

echo "Environnement de test préparé :\n";
echo "  - Clé API valide générée : " . substr($validApiKey, 0, 15) . "...\n";
echo "  - Panéliste UID généré   : $validPanelisteUid\n";
echo "  - Code du jour généré    : $validDailyCode\n\n";

try {
    // --- CAS 1 : Aucune clé API transmise ---
    echo "--- Test 1 : Rejet si aucune clé API transmise ---\n";
    $req1 = Request::create('/api/sdk/verify-day', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'panelisteUid' => $validPanelisteUid,
        'code' => $validDailyCode,
    ]));
    $res1 = $sdkController->verifyDay($req1);
    $data1 = json_decode($res1->getContent(), true);
    assertTest(
        "Rejet 400 quand clé API absente",
        $res1->getStatusCode() === 400 && $data1['success'] === false,
        "Status: " . $res1->getStatusCode() . " | Message: " . ($data1['error'] ?? '')
    );

    // --- CAS 2 : Clé API invalide / bidon (Tentative d'usurpation) ---
    echo "\n--- Test 2 : Rejet si clé API invalide (ex: sk_app_pirate_12345) ---\n";
    $req2 = Request::create('/api/sdk/verify-day', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_APP_KEY' => 'sk_app_pirate_invalid_key_99999',
    ], json_encode([
        'panelisteUid' => $validPanelisteUid,
        'code' => $validDailyCode,
    ]));
    $res2 = $sdkController->verifyDay($req2);
    $data2 = json_decode($res2->getContent(), true);
    assertTest(
        "Rejet 401 Unauthorized quand clé API fausse",
        $res2->getStatusCode() === 401 && $data2['success'] === false,
        "Status: " . $res2->getStatusCode() . " | Message: " . ($data2['error'] ?? '')
    );

    // --- CAS 3 : Clé API valide mais Panéliste introuvable ---
    echo "\n--- Test 3 : Rejet si panéliste inexistant ---\n";
    $req3 = Request::create('/api/sdk/verify-day', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_APP_KEY' => $validApiKey,
    ], json_encode([
        'panelisteUid' => 'TST-INEXISTANT-999',
        'code' => $validDailyCode,
    ]));
    $res3 = $sdkController->verifyDay($req3);
    $data3 = json_decode($res3->getContent(), true);
    assertTest(
        "Rejet 404 quand panéliste non reconnu",
        $res3->getStatusCode() === 404 && $data3['success'] === false,
        "Status: " . $res3->getStatusCode() . " | Message: " . ($data3['error'] ?? '')
    );

    // --- CAS 4 : Clé API valide, Panéliste valide, mais code faux ---
    echo "\n--- Test 4 : Rejet si le code quotidien est erroné ---\n";
    $req4 = Request::create('/api/sdk/verify-day', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_APP_KEY' => $validApiKey,
    ], json_encode([
        'panelisteUid' => $validPanelisteUid,
        'code' => 'CODE-COMPLETEMENT-FAUX',
    ]));
    $res4 = $sdkController->verifyDay($req4);
    $data4 = json_decode($res4->getContent(), true);
    assertTest(
        "Rejet 422 quand code incorrect",
        $res4->getStatusCode() === 422 && $data4['success'] === false,
        "Status: " . $res4->getStatusCode() . " | Message: " . ($data4['error'] ?? '')
    );

    // --- CAS 5 : Clé API valide, Panéliste valide ET Vrai code quotidien ---
    echo "\n--- Test 5 : Acceptation et Validation avec Clé API valide + Panéliste + Code exact ---\n";
    $req5 = Request::create('/api/sdk/verify-day', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_APP_KEY' => $validApiKey,
    ], json_encode([
        'panelisteUid' => $validPanelisteUid,
        'code' => $validDailyCode,
    ]));
    $res5 = $sdkController->verifyDay($req5);
    $data5 = json_decode($res5->getContent(), true);
    assertTest(
        "Succès 200 et validation de la journée",
        $res5->getStatusCode() === 200 && $data5['success'] === true,
        "Status: " . $res5->getStatusCode() . " | Message: " . ($data5['message'] ?? '') . " | Jour: " . ($data5['jour'] ?? '')
    );

    // Vérification de la persistance en base
    $em->refresh($testRef);
    $em->refresh($testPart);
    $em->refresh($testEtape);
    assertTest(
        "Persistance DB : Référence marquée 'validee' avec date de validation",
        $testRef->getStatut() === 'validee' && $testRef->getDateValidation() !== null,
        "Statut: " . $testRef->getStatut() . " | Date: " . $testRef->getDateValidation()->format('Y-m-d H:i:s')
    );
    assertTest(
        "Persistance DB : Étape marquée 'validee'",
        $testEtape->getStatut() === 'validee',
        "Statut Étape: " . $testEtape->getStatut()
    );

    // --- CAS 6 : Re-soumission du même code déjà validé ---
    echo "\n--- Test 6 : Idempotence si code re-soumis ---\n";
    $res6 = $sdkController->verifyDay($req5);
    $data6 = json_decode($res6->getContent(), true);
    assertTest(
        "Succès 200 avec flag alreadyValidated = true",
        $res6->getStatusCode() === 200 && ($data6['alreadyValidated'] ?? false) === true,
        "Message: " . ($data6['message'] ?? '')
    );

} finally {
    // Nettoyage des fixtures de test
    echo "\nNettoyage des fixtures de test...\n";
    $em->remove($testRef);
    $em->remove($testPart);
    $em->remove($testEtape);
    $em->remove($testMission);
    $em->remove($testApp);
    $em->flush();
}

echo "\n=================================================================\n";
echo "RÉSULTAT GLOBAL DU TEST : $passCount / " . ($passCount + $failCount) . " TESTS RÉUSSIS\n";
echo "=================================================================\n";
