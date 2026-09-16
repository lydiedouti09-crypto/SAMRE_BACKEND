<?php

namespace App\DataFixtures;

use App\Entity\Etape;
use App\Entity\Mission;
use App\Entity\Notification;
use App\Entity\Participation;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MissionFixture extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $userRepo = $manager->getRepository(User::class);
        $admin = $userRepo->findOneBy(['role' => 'admin']) ?? $userRepo->findOneBy([]);

        // Tags
        $tagNames = ['Mobile', 'Fintech', 'UI/UX', 'Paiement', 'E-Commerce'];
        $tags = [];
        foreach ($tagNames as $name) {
            $tag = new Tag();
            $tag->setNom($name);
            $manager->persist($tag);
            $tags[$name] = $tag;
        }

        // Missions data
        $missionsData = [
            [
                'titre' => 'Wave Mobile Money — Parcours transfert & scan QR',
                'description' => 'Évaluez l\'ergonomie et la rapidité du flux de paiement marchand par scan de QR code et les micro-transferts sans frais.',
                'objectif' => 'Identifier les éventuels ralentissements lors de la validation biométrique et du scan caméra.',
                'image' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=400&q=80',
                'application' => 'Wave CI',
                'versionApplication' => 'v4.12.0',
                'platforme' => 'Android',
                'lienApplication' => 'https://wave.com',
                'dureEstime' => '3 jours',
                'remuneration' => '6500.00',
                'conditions' => 'Avoir un smartphone Android avec appareil photo fonctionnel.',
                'souhaites' => 25,
                'actuels' => 12,
                'statut' => 'ouverte',
                'tags' => ['Mobile', 'Fintech', 'Paiement'],
                'etapes' => [
                    [
                        'titre' => 'Téléchargement et connexion sécurisée',
                        'description' => 'Installer l\'application test, créer un compte sandbox et vérifier l\'authentification par SMS.',
                        'instruction' => 'Prenez une capture d\'écran de l\'écran d\'accueil après première connexion.',
                        'ordre' => 1,
                        'jour' => 1,
                        'resultatAttendu' => 'Capture d\'écran validée',
                        'besoinReference' => false,
                        'dureeEstimee' => '15 min',
                    ],
                    [
                        'titre' => 'Test de transfert d\'argent sandbox',
                        'description' => 'Effectuer un transfert de 500 F vers le numéro test fourni dans le protocole.',
                        'instruction' => 'Validez la transaction et notez le code de référence affiché sur le reçu.',
                        'ordre' => 2,
                        'jour' => 2,
                        'resultatAttendu' => 'Numéro de transaction saisi',
                        'besoinReference' => true,
                        'dureeEstimee' => '20 min',
                    ],
                    [
                        'titre' => 'Paiement marchand via scan QR',
                        'description' => 'Tester le scan du QR code marchand test et évaluer la rapidité de confirmation.',
                        'instruction' => 'Scannez le QR test et confirmez le prélèvement sandbox.',
                        'ordre' => 3,
                        'jour' => 3,
                        'resultatAttendu' => 'Code de confirmation marchand',
                        'besoinReference' => true,
                        'dureeEstimee' => '20 min',
                    ],
                ],
            ],
            [
                'titre' => 'Djamo — Commande et activation de carte Visa',
                'description' => 'Testez le parcours complet de commande de carte virtuelle, recharge via Mobile Money et activation.',
                'objectif' => 'Mesurer la clarté des étapes tarifaires et la facilité de recharge.',
                'image' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=80',
                'application' => 'Djamo App',
                'versionApplication' => 'v3.8.1',
                'platforme' => 'Android',
                'lienApplication' => 'https://djamo.com',
                'dureEstime' => '5 jours',
                'remuneration' => '8000.00',
                'conditions' => 'Disposer d\'une pièce d\'identité valide pour la vérification KYC.',
                'souhaites' => 30,
                'actuels' => 8,
                'statut' => 'ouverte',
                'tags' => ['Fintech', 'UI/UX', 'Paiement'],
                'etapes' => [
                    [
                        'titre' => 'Inscription et KYC d\'identité',
                        'description' => 'Soumettre le formulaire de vérification d\'identité.',
                        'instruction' => 'Compléter les informations de base et attendre la validation.',
                        'ordre' => 1,
                        'jour' => 1,
                        'resultatAttendu' => 'Validation KYC sous 24h',
                        'besoinReference' => false,
                        'dureeEstimee' => '20 min',
                    ],
                    [
                        'titre' => 'Recharge de solde via Orange/MTN Money',
                        'description' => 'Simuler un dépôt virtuel de 2 000 F dans le portefeuille test.',
                        'instruction' => 'Saisir le reçu de transaction généré.',
                        'ordre' => 2,
                        'jour' => 2,
                        'resultatAttendu' => 'Solde crédité avec succès',
                        'besoinReference' => true,
                        'dureeEstimee' => '15 min',
                    ],
                    [
                        'titre' => 'Génération de carte Visa virtuelle',
                        'description' => 'Créer une carte virtuelle et définir le code PIN de sécurité.',
                        'instruction' => 'Confirmez que les 16 chiffres et le CVV sont consultables.',
                        'ordre' => 3,
                        'jour' => 3,
                        'resultatAttendu' => 'Carte activée',
                        'besoinReference' => true,
                        'dureeEstimee' => '25 min',
                    ],
                ],
            ],
            [
                'titre' => 'Orange Bank Africa — Demande de prêt Tik Tak',
                'description' => 'Évaluez la transparence et l\'instantanéité de la souscription au micro-prêt Tik Tak.',
                'objectif' => 'Vérifier la bonne compréhension des taux d\'intérêt et échéanciers.',
                'image' => 'https://images.unsplash.com/photo-1601597111158-2fceff292cdc?w=400&q=80',
                'application' => 'Orange Bank',
                'versionApplication' => 'v2.19.4',
                'platforme' => 'Android',
                'lienApplication' => 'https://orangebank.ci',
                'dureEstime' => '4 jours',
                'remuneration' => '7500.00',
                'conditions' => 'Numéro Orange actif depuis au moins 3 mois.',
                'souhaites' => 20,
                'actuels' => 15,
                'statut' => 'ouverte',
                'tags' => ['Mobile', 'Fintech'],
                'etapes' => [
                    [
                        'titre' => 'Simulation de prêt Tik Tak',
                        'description' => 'Sélectionner un montant fictif de 20 000 F et étudier l\'échéancier.',
                        'instruction' => 'Vérifiez la lisibilité des frais de dossier.',
                        'ordre' => 1,
                        'jour' => 1,
                        'resultatAttendu' => 'Simulation validée',
                        'besoinReference' => false,
                        'dureeEstimee' => '15 min',
                    ],
                    [
                        'titre' => 'Signature du contrat numérique',
                        'description' => 'Valider par code OTP la convention de prêt.',
                        'instruction' => 'Saisissez le code OTP reçu pour clôturer l\'étape.',
                        'ordre' => 2,
                        'jour' => 2,
                        'resultatAttendu' => 'Contrat paraphé',
                        'besoinReference' => true,
                        'dureeEstimee' => '10 min',
                    ],
                ],
            ],
            [
                'titre' => 'Samré Beta Panel — Expérience utilisateur globale',
                'description' => 'Participez à la phase bêta de notre nouvelle application web pour tester les formulaires et le suivi de gains.',
                'objectif' => 'Collecter des retours sur l\'ergonomie globale et les fonctionnalités favorites.',
                'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&q=80',
                'application' => 'Samré App',
                'versionApplication' => 'v1.0.0',
                'platforme' => 'Web & Mobile',
                'lienApplication' => 'https://samre.org',
                'dureEstime' => '2 jours',
                'remuneration' => '5000.00',
                'conditions' => 'Ouvert à tous les testeurs inscrits.',
                'souhaites' => 50,
                'actuels' => 28,
                'statut' => 'ouverte',
                'tags' => ['UI/UX', 'E-Commerce'],
                'etapes' => [
                    [
                        'titre' => 'Découverte du tableau de bord',
                        'description' => 'Explorer la liste des missions disponibles et la vue d\'ensemble.',
                        'instruction' => 'Consulter les détails d\'au moins deux missions.',
                        'ordre' => 1,
                        'jour' => 1,
                        'resultatAttendu' => 'Navigation complétée',
                        'besoinReference' => false,
                        'dureeEstimee' => '10 min',
                    ],
                ],
            ],
        ];

        $createdMissions = [];
        foreach ($missionsData as $mData) {
            $mission = new Mission();
            $mission->setTitre($mData['titre']);
            $mission->setDescription($mData['description']);
            $mission->setObjectif($mData['objectif']);
            $mission->setImage($mData['image']);
            $mission->setApplication($mData['application']);
            $mission->setVersionApplication($mData['versionApplication']);
            $mission->setPlatforme($mData['platforme']);
            $mission->setLienApplication($mData['lienApplication']);
            $mission->setDateDebut(new \DateTime('-2 days'));
            $mission->setDateFin(new \DateTime('+15 days'));
            $mission->setDureEstime($mData['dureEstime']);
            $mission->setRemuneration($mData['remuneration']);
            $mission->setConditionsParticipation($mData['conditions']);
            $mission->setNombreParticipantsSouhaites($mData['souhaites']);
            $mission->setNombreParticipantsActuels($mData['actuels']);
            $mission->setStatut($mData['statut']);
            $mission->setDateCreation(new \DateTime('-3 days'));
            if ($admin) {
                $mission->setResponsable($admin);
            }

            foreach ($mData['tags'] as $tName) {
                if (isset($tags[$tName])) {
                    $mission->addTag($tags[$tName]);
                }
            }

            $manager->persist($mission);

            foreach ($mData['etapes'] as $eData) {
                $etape = new Etape();
                $etape->setTitre($eData['titre']);
                $etape->setDescription($eData['description']);
                $etape->setInstruction($eData['instruction']);
                $etape->setOrdre($eData['ordre']);
                $etape->setJour($eData['jour']);
                $etape->setResultatAttendu($eData['resultatAttendu']);
                $etape->setBesoinReference($eData['besoinReference']);
                $etape->setDureeEstimee($eData['dureeEstimee']);
                $etape->setStatut('actif');
                $etape->setDateCreation(new \DateTime('-2 days'));
                $etape->setMission($mission);
                $manager->persist($etape);
            }

            $createdMissions[] = $mission;
        }

        // Créer des participations uniquement pour les utilisateurs testeurs (rôle chercheur)
        $testerUsers = $userRepo->findBy(['role' => 'chercheur']);
        foreach ($testerUsers as $u) {
            // Participation active sur Wave Mobile Money
            $waveMission = $createdMissions[0];
            $part = new Participation();
            $part->setUser($u);
            $part->setMission($waveMission);
            $part->setStatus('en_cours');
            $part->setContratAccepte(true);
            $part->setDateAcceptation(new \DateTime('-1 day'));
            $part->setDateDebut(new \DateTime('-1 day'));
            $part->setDateFin(new \DateTime('+2 days'));
            $part->setProgression(66);
            $part->setEtapesCompletees(2);
            $part->setEtapesTotal(3);
            $part->setDateCreation(new \DateTime('-1 day'));
            $manager->persist($part);

            // Notifications d'accueil et d'activité
            $n1 = new Notification();
            $n1->setTitre('Bienvenue sur le panel Samré !');
            $n1->setMessage('Votre compte testeur est validé. Vous pouvez dès à présent participer à nos missions rémunérées.');
            $n1->setType('info');
            $n1->setLu(true);
            $n1->setDateCreation(new \DateTime('-1 day'));
            $n1->setUtilisateur($u);
            $manager->persist($n1);

            $n2 = new Notification();
            $n2->setTitre('Étape 2 validée sur Wave');
            $n2->setMessage('Félicitations ! Votre simulation de transfert d\'argent a été validée par notre équipe (+4 300 F crédités).');
            $n2->setType('gain');
            $n2->setLu(false);
            $n2->setDateCreation(new \DateTime('-3 hours'));
            $n2->setUtilisateur($u);
            $manager->persist($n2);

            $n3 = new Notification();
            $n3->setTitre('Nouvelle mission : Djamo Visa Card');
            $n3->setMessage('Une nouvelle mission rémunérée à 8 000 F vient d\'ouvrir 30 places testeurs.');
            $n3->setType('mission');
            $n3->setLu(false);
            $n3->setDateCreation(new \DateTime('-1 hour'));
            $n3->setUtilisateur($u);
            $manager->persist($n3);
        }

        $manager->flush();
    }
}
