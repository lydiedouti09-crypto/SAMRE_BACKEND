# Samre SDK — intégration rapide

Ce document décrit le flux de base pour intégrer le SDK Samre dans une application mobile ou web tiers.

## 1) Rôle du backend

Le backend Samre est la seule source de vérité pour :
- la création du code du jour
- le secret HMAC de l’application
- la validation du code soumis
- le contrôle des validations frauduleuses

Le SDK ne reçoit jamais le secret HMAC. Il ne reçoit que :
- appId
- apiKey
- sdkToken
- URL de validation

## 2) Flux fonctionnel

1. L’application cliente charge le SDK
2. Le SDK appelle le backend pour obtenir la localisation de l’écran
3. L’écran Samre est affiché dans l’application
4. Le testeur remplit le code du jour
5. Le SDK envoie le code au backend avec :
   - appId
   - apiKey
   - sdkToken
   - testerId
   - deviceId
   - code
6. Le backend recalcule le code attendu via HMAC
7. Le backend vérifie le token SDK + anti-rejeu + validité métier
8. Le backend renvoie le résultat

## 3) Endpoints backend

### Obtenir la localisation de l’écran
GET /api/v1/sdk/screen-location

Réponse :
```json
{
  "success": true,
  "data": {
    "screen": "daily-code-screen",
    "route": "/daily-code"
  },
  "message": "Emplacement écran retourné.",
  "errors": []
}
```

### Vérifier le code du jour
POST /api/v1/sdk/verify-code

Headers requis :
- X-App-Key: <apiKey>
- X-SDK-Token: <sdkToken>
- X-Device-Id: <deviceId>

Body :
```json
{
  "app_id": 1,
  "panelisteUid": "TST-7A8B9C",
  "tester_id": 42,
  "deviceId": "device-123",
  "code": "7K9P-4MX2"
}
```

> **Note :** Le testeur peut renseigner soit son `panelisteUid` (ex: `TST-7A8B9C` affiché sur son espace Samré), soit son `tester_id`. Le code est insensible à la casse et aux tirets (ex: `7k9p4mx2` ou `7K9P-4MX2`).

Réponse réussie :
```json
{
  "success": true,
  "data": {
    "valide": true,
    "alreadyValidated": false,
    "jourValide": 1,
    "progression": 8,
    "totalJours": 12,
    "terminee": false
  },
  "message": "Félicitations ! Jour 1 validé avec succès.",
  "errors": []
}
```

## 4) Credentials SDK

L’administrateur Samre génère pour chaque application :
- appId
- apiKey
- sdkToken

Le secret HMAC reste côté backend.

## 5) Snippet JS / mobile léger

```js
const APP_ID = 1;
const API_KEY = 'sk_app_xxx';
const SDK_TOKEN = 'sdk_xxx';
const BASE_URL = 'https://votre-backend.example.com';

async function getScreenLocation() {
  const response = await fetch(`${BASE_URL}/api/v1/sdk/screen-location`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });

  return response.json();
}

async function verifyCode({ testerId, deviceId, code }) {
  const response = await fetch(`${BASE_URL}/api/v1/sdk/verify-code`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-App-Key': API_KEY,
      'X-SDK-Token': SDK_TOKEN,
      'X-Device-Id': deviceId,
    },
    body: JSON.stringify({
      app_id: APP_ID,
      tester_id: testerId,
      deviceId,
      code,
    }),
  });

  return response.json();
}
```

## 6) Sécurité recommandée

- jamais exposer le secret HMAC
- garder le token SDK unique par application
- enregistrer deviceId + IP
- empêcher les validations répétées depuis le même device/IP
- limiter les tentatives de code par testeur
- ne jamais accepter un code sans appId / testerId valide

## 7) Intégration côté développeur

Le développeur ne fait que :
- injecter le SDK dans son app
- configurer appId + apiKey + sdkToken
- afficher l’écran Samre
- transmettre le code à l’API Samre

Le backend gère le calcul et la sécurité.
