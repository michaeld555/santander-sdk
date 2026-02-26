# Santander PHP/Laravel SDK

SDK para integracao com APIs do Santander em projetos Laravel e PHP puro.

## Versao

`1.0.0`

## Sumario

- [Escopo](#escopo)
- [Cobertura de Endpoints](#cobertura-de-endpoints)
- [Instalacao](#instalacao)
- [Configuracao](#configuracao)
- [Arquitetura da SDK](#arquitetura-da-sdk)
- [Quickstart Laravel](#quickstart-laravel)
- [Quickstart PHP puro](#quickstart-php-puro)
- [Referencia por Modulo](#referencia-por-modulo)
- [Erros e Tratamento](#erros-e-tratamento)
- [Logs e Observabilidade](#logs-e-observabilidade)
- [Testes](#testes)
- [Notas da versao 1.0.0](#notas-da-versao-110)
- [Documentacao oficial Santander](#documentacao-oficial-santander)
- [Licenca](#licenca)

## Escopo

Esta SDK cobre os fluxos principais das colecoes em `endpoints/`:

- Pagamentos parceiros (`management_payments_partners`)
- Informacoes de contas (`bank_account_information`)
- Comprovantes (`consult_payment_receipts`)
- Pix Automatico (`/api/v1`)
- Autenticacao OAuth2 (`/auth/oauth/v2/token`)

## Cobertura de Endpoints

| Dominio | Base endpoint | Acesso via SDK | Metodos |
| --- | --- | --- | --- |
| Auth | `/auth/oauth/v2/token` | interno (`SantanderAuth`) | renovacao automatica de token |
| Workspaces | `/management_payments_partners/v1/workspaces` | `Santander::workspaces()` | `createWorkspace`, `createWorkspaceValidated`, `listWorkspaces`, `getWorkspace`, `updateWorkspace`, `deleteWorkspace` |
| Pix Payments | `/management_payments_partners/v1/workspaces/:workspaceid/pix_payments` | `Santander::pix()` | `transferPix`, `createPayment`, `confirmPayment`, `getPayment`, `listPayments` |
| Bank Slip Payments | `/management_payments_partners/v1/workspaces/:workspaceid/bank_slip_payments` | `Santander::bankSlips()` | `createPayment`, `confirmPayment`, `getPayment`, `listPayments`, `listAvailableBankSlips` |
| Barcode Payments | `/management_payments_partners/v1/workspaces/:workspaceid/barcode_payments` | `Santander::barcodes()` | `createPayment`, `confirmPayment`, `getPayment`, `listPayments` |
| Vehicle Taxes | `/management_payments_partners/v1/workspaces/:workspaceid/vehicle_taxes_payments` | `Santander::vehicleTaxes()` | `listAvailableVehicleTaxes`, `createPayment`, `confirmPayment`, `getPayment`, `listPayments` |
| Taxes by Fields | `/management_payments_partners/v1/workspaces/:workspaceid/taxes_by_fields_payments` | `Santander::taxesByFields()` | `createPayment`, `confirmPayment`, `getPayment`, `listPayments` |
| Contas | `/bank_account_information/v1/banks/:bank_tax_id` | `Santander::accounts()` | `listAccounts`, `getBalance`, `getStatement` |
| Comprovantes | `/consult_payment_receipts/v1/payment_receipts` | `Santander::receipts()` | `paymentList`, `paymentListIterByPages`, `createReceipt`, `getReceipt`, `receiptCreationHistory` |
| Pix Automatico - Location | `/api/v1/locrec` | `Santander::pixAutomatic()` | `createLocation`, `listLocations`, `getLocation`, `unbindLocation` |
| Pix Automatico - Recorrencia | `/api/v1/rec` | `Santander::pixAutomatic()` | `createRecurrence`, `listRecurrences`, `getRecurrence`, `updateRecurrence`, `cancelRecurrence` |
| Pix Automatico - Solicitacao de confirmacao | `/api/v1/solicrec` | `Santander::pixAutomatic()` | `createConfirmationRequest`, `getConfirmationRequest`, `reviewConfirmationRequest` |
| Pix Automatico - Cobranca recorrente | `/api/v1/cobr` | `Santander::pixAutomatic()` | `createRecurringCharge`, `reviewRecurringCharge`, `getRecurringCharge`, `listRecurringCharges`, `requestRecurringChargeRetry` |
| Pix Automatico - Webhooks | `/api/v1/webhookrec` e `/api/v1/webhookcobr` | `Santander::pixAutomatic()` | `configure*Webhook`, `get*Webhook`, `delete*Webhook` |

## Instalacao

```bash
composer require michaeld555/santander-sdk
```

Publicar configuracao (Laravel):

```bash
php artisan vendor:publish --tag=santander-config
```

## Configuracao

### Variaveis de ambiente

```env
SANTANDER_CLIENT_ID=seu_client_id
SANTANDER_CLIENT_SECRET=seu_client_secret
SANTANDER_CERT=/caminho/para/cert.pem
SANTANDER_BASE_URL=https://trust-open.api.santander.com.br
SANTANDER_WORKSPACE_ID=
SANTANDER_BANK_TAX_ID=90400888000142
SANTANDER_LOG_LEVEL=ERROR
SANTANDER_TIMEOUT=60
```

### Tabela de configuracao

| Variavel | Obrigatoria | Default | Descricao |
| --- | --- | --- | --- |
| `SANTANDER_CLIENT_ID` | Sim | - | Client ID da aplicacao no Santander |
| `SANTANDER_CLIENT_SECRET` | Sim | - | Client secret da aplicacao no Santander |
| `SANTANDER_CERT` | Sim na maioria dos cenarios | - | Certificado client TLS. Pode ser string (caminho) ou array compativel com Guzzle |
| `SANTANDER_BASE_URL` | Sim | - | URL base da API (`trust-open` ou `trust-sandbox`) |
| `SANTANDER_WORKSPACE_ID` | Nao | vazio | Se vazio, SDK tenta descobrir o primeiro workspace `PAYMENTS` ativo |
| `SANTANDER_BANK_TAX_ID` | Nao | `90400888000142` | CNPJ base usado pelos endpoints de contas |
| `SANTANDER_LOG_LEVEL` | Nao | `ERROR` | `ERROR` ou `ALL` |
| `SANTANDER_TIMEOUT` | Nao | `60` | Timeout HTTP em segundos |

### URLs usuais

- Sandbox: `https://trust-sandbox.api.santander.com.br`
- Producao: `https://trust-open.api.santander.com.br`

### Observacoes importantes

- O placeholder de workspace aceita `:workspaceid` e `:workspace_id` internamente.
- Se `SANTANDER_WORKSPACE_ID` nao for informado e nao existir workspace `PAYMENTS` ativo, a SDK lanca `SantanderClientError`.
- Para endpoints de contas, o `bank_tax_id` pode ser definido na config ou sobrescrito por metodo.

## Arquitetura da SDK

### Camadas

- `SantanderAuth`: gerencia token OAuth2 e renovacao
- `SantanderApiClient`: executa requests HTTP, substitui placeholders e centraliza logs/erros
- Modulos de dominio (`Pix`, `PaymentReceipts`, `BankAccounts`, etc): expoem metodos por endpoint
- `SantanderSdk`: facade de alto nivel para acessar todos os modulos

### Fluxo de request

1. O modulo chama `SantanderApiClient` com endpoint + payload
2. O client injeta `Authorization` e `X-Application-Key`
3. O client resolve placeholders de URL
4. O Santander responde
5. A SDK retorna array normalizado ou lanca excecao especifica

## Quickstart Laravel

```php
use Santander\SDK\Facades\Santander;
use Santander\SDK\Exceptions\SantanderError;

try {
    $transfer = Santander::pix()->transferPix(
        pixKey: 'destinatario@email.com',
        value: '50.00',
        description: 'Pagamento pedido #123'
    );

    if (! $transfer['success']) {
        throw new \RuntimeException($transfer['error']);
    }

    $paymentId = $transfer['data']['id'] ?? null;
    $status = $paymentId ? Santander::pix()->getPayment($paymentId) : null;

    // use $status conforme sua regra de negocio
} catch (SantanderError $e) {
    // erro de integracao Santander
} catch (\Throwable $e) {
    // erro generico da aplicacao
}
```

## Quickstart PHP puro

```php
use Illuminate\Http\Client\Factory;
use Santander\SDK\Auth\SantanderAuth;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Client\SantanderClientConfiguration;
use Santander\SDK\PaymentReceipts;
use Santander\SDK\Pix;
use Santander\SDK\SantanderSdk;

$http = new Factory();
$config = new SantanderClientConfiguration(
    clientId: 'seu_client_id',
    clientSecret: 'seu_client_secret',
    cert: '/caminho/para/cert.pem',
    baseUrl: 'https://trust-sandbox.api.santander.com.br',
    workspaceId: '',
    logRequestResponseLevel: 'ERROR',
    logger: null,
    timeout: 60,
    bankTaxId: '90400888000142'
);

$auth = SantanderAuth::fromConfig($http, $config);
$client = new SantanderApiClient($config, $auth, $http);

$sdk = new SantanderSdk(
    $client,
    new Pix($client),
    new PaymentReceipts($client)
);

$workspaces = $sdk->workspaces()->listWorkspaces();
```

## Referencia por Modulo

### 1) Pix (`Santander::pix()`)

#### Fluxo simplificado: `transferPix`

- Entrada:
  - `pixKey`: chave PIX string (CPF/CNPJ/EMAIL/EVP/CELULAR) ou array `beneficiary`
  - `value`: valor numerico
  - `description`: descricao
  - `tags` e `id` opcionais
- Comportamento:
  - cria pagamento
  - aguarda `READY_TO_PAY` quando necessario
  - confirma com status `AUTHORIZED`
  - retorna estrutura padrao:

```php
[
  'success' => true|false,
  'request_id' => '...',
  'data' => [...],
  'error' => ''
]
```

#### Fluxo detalhado (baixo nivel)

```php
$created = Santander::pix()->createPayment([
    'dictCode' => 'destinatario@email.com',
    'dictCodeType' => 'EMAIL',
    'paymentValue' => '10.00',
    'remittanceInformation' => 'Teste',
]);

$confirmed = Santander::pix()->confirmPayment($created['id'], [
    'status' => 'AUTHORIZED',
    'paymentValue' => '10.00',
]);

$one = Santander::pix()->getPayment($created['id']);
$list = Santander::pix()->listPayments(['_limit' => 100, '_offset' => 0]);
```

### 2) Comprovantes (`Santander::receipts()`)

```php
$payments = Santander::receipts()->paymentList([
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
    '_limit' => '100',
]);

$paymentId = $payments[0]['payment']['paymentId'];
$create = Santander::receipts()->createReceipt($paymentId);
$receipt = Santander::receipts()->getReceipt($paymentId, $create['receipt_request_id']);
$history = Santander::receipts()->receiptCreationHistory($paymentId);
```

Paginacao lazy por generator:

```php
foreach (Santander::receipts()->paymentListIterByPages([
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
    '_limit' => '50',
]) as $page) {
    // cada pagina contem paymentsReceipts e links
}
```

### 3) Workspaces (`Santander::workspaces()`)

```php
$created = Santander::workspaces()->createWorkspaceValidated([
    'type' => 'PAYMENTS',
    'description' => 'Workspace API',
    'mainDebitAccount' => [
        'branch' => '1',
        'number' => '130392838',
    ],
    'tags' => ['client:123'],
    'pixPaymentsActive' => true,
    'bankSlipPaymentsActive' => true,
    'bankSlipAvailableActive' => true,
]);

$list = Santander::workspaces()->listWorkspaces();
$one = Santander::workspaces()->getWorkspace('123456');

$updated = Santander::workspaces()->updateWorkspace('123456', [
    'name' => 'Workspace API v2',
]);

$deleted = Santander::workspaces()->deleteWorkspace('123456');
```

### 4) Contas (`Santander::accounts()`)

```php
$accounts = Santander::accounts()->listAccounts(['_limit' => 50]);
$balance = Santander::accounts()->getBalance('0000.000011112222');
$statement = Santander::accounts()->getStatement('0000.000011112222', [
    'initialDate' => '2025-01-01',
    'finalDate' => '2025-01-31',
    '_limit' => 50,
    '_offset' => 0,
]);
```

Sobrescrevendo `bank_tax_id` por chamada:

```php
$accounts = Santander::accounts()->listAccounts(['_limit' => 50], '12345678000195');
```

### 5) Bank Slips (`Santander::bankSlips()`)

```php
$created = Santander::bankSlips()->createPayment($payload);
$confirmed = Santander::bankSlips()->confirmPayment($created['id'], ['status' => 'AUTHORIZED']);
$one = Santander::bankSlips()->getPayment($created['id']);
$list = Santander::bankSlips()->listPayments(['_limit' => 50]);
$dda = Santander::bankSlips()->listAvailableBankSlips([
    'payerDocumentNumber' => '12345678000195',
]);
```

### 6) Barcode (`Santander::barcodes()`)

```php
$created = Santander::barcodes()->createPayment($payload);
$confirmed = Santander::barcodes()->confirmPayment($created['id'], ['status' => 'AUTHORIZED']);
$one = Santander::barcodes()->getPayment($created['id']);
$list = Santander::barcodes()->listPayments(['_limit' => 50]);
```

### 7) Vehicle Taxes (`Santander::vehicleTaxes()`)

```php
$debts = Santander::vehicleTaxes()->listAvailableVehicleTaxes([
    'renavam' => '12345678901',
]);

$created = Santander::vehicleTaxes()->createPayment($payload);
$confirmed = Santander::vehicleTaxes()->confirmPayment($created['id'], ['status' => 'AUTHORIZED']);
$one = Santander::vehicleTaxes()->getPayment($created['id']);
$list = Santander::vehicleTaxes()->listPayments(['_limit' => 50]);
```

### 8) Taxes by Fields (`Santander::taxesByFields()`)

```php
$created = Santander::taxesByFields()->createPayment($payload);
$confirmed = Santander::taxesByFields()->confirmPayment($created['id'], ['status' => 'AUTHORIZED']);
$one = Santander::taxesByFields()->getPayment($created['id']);
$list = Santander::taxesByFields()->listPayments(['_limit' => 50]);
```

### 9) Pix Automatico (`Santander::pixAutomatic()`)

#### Location

```php
$created = Santander::pixAutomatic()->createLocation($payloadLocation);
$list = Santander::pixAutomatic()->listLocations([
    'inicio' => '2025-03-27T00:00:00Z',
    'fim' => '2025-03-28T23:59:59Z',
]);
$one = Santander::pixAutomatic()->getLocation('1001');
$unlink = Santander::pixAutomatic()->unbindLocation('2001', 'idRec');
```

#### Recorrencia

```php
$created = Santander::pixAutomatic()->createRecurrence($payloadRecurrence);
$list = Santander::pixAutomatic()->listRecurrences([
    'inicio' => '2025-03-26T00:00:00Z',
    'fim' => '2025-03-26T23:59:59Z',
]);
$one = Santander::pixAutomatic()->getRecurrence('RR904008882025021000000000001');
$updated = Santander::pixAutomatic()->updateRecurrence('RR904008882025021000000000001', $payloadUpdate);
$cancel = Santander::pixAutomatic()->cancelRecurrence('RR904008882025021000000000001', $payloadCancel);
```

#### Solicitacao de confirmacao

```php
$created = Santander::pixAutomatic()->createConfirmationRequest($payloadSolic);
$one = Santander::pixAutomatic()->getConfirmationRequest('SC904008882025031000000000201');
$review = Santander::pixAutomatic()->reviewConfirmationRequest('SC904008882025031000000000201', $payloadReview);
```

#### Cobranca recorrente

```php
$created = Santander::pixAutomatic()->createRecurringCharge('txid123', $payloadCharge);
$review = Santander::pixAutomatic()->reviewRecurringCharge('txid123', $payloadReview);
$one = Santander::pixAutomatic()->getRecurringCharge('txid123');
$list = Santander::pixAutomatic()->listRecurringCharges([
    'inicio' => '2025-04-25T00:00:00Z',
    'fim' => '2025-04-26T23:59:59Z',
]);
$retry = Santander::pixAutomatic()->requestRecurringChargeRetry('txid123', '2025-04-30');
```

#### Webhooks

```php
$cfgRec = Santander::pixAutomatic()->configureRecurrenceWebhook([
    'webhookUrl' => 'https://sua-api.com/webhook/rec',
]);
$getRec = Santander::pixAutomatic()->getRecurrenceWebhook();
$delRec = Santander::pixAutomatic()->deleteRecurrenceWebhook();

$cfgCobr = Santander::pixAutomatic()->configureRecurringChargeWebhook([
    'webhookUrl' => 'https://sua-api.com/webhook/cobr',
]);
$getCobr = Santander::pixAutomatic()->getRecurringChargeWebhook();
$delCobr = Santander::pixAutomatic()->deleteRecurringChargeWebhook();
```

## Erros e Tratamento

### Excecoes da SDK

- `SantanderRequestError`
  - erro HTTP, timeout de conexao ou resposta nao bem sucedida
  - contem `getStatusCode()` e `getContent()`
- `SantanderClientError`
  - erro de configuracao local (workspace ausente, parametros invalidos, etc)
- `SantanderRejectedError`
  - pagamento rejeitado pelo banco durante create/confirm
- `SantanderStatusTimeoutError`
  - timeout ao aguardar mudanca de status no fluxo de polling

### Exemplo de captura

```php
use Santander\SDK\Exceptions\SantanderRequestError;

try {
    $res = Santander::pix()->createPayment($payload);
} catch (SantanderRequestError $e) {
    logger()->error('Santander request error', [
        'status_code' => $e->getStatusCode(),
        'content' => $e->getContent(),
        'message' => $e->getMessage(),
    ]);
}
```

### Observacao sobre `transferPix`

`transferPix` e um helper de alto nivel. Para fluxos de controle total (idempotencia propria, reprocesso customizado, auditoria por etapa), prefira `createPayment` + `confirmPayment` + `getPayment`.

## Logs e Observabilidade

Configurar `SANTANDER_LOG_LEVEL`:

- `ERROR`: registra apenas falhas
- `ALL`: registra falhas e sucessos

Quando habilitado, o log inclui:

- metodo HTTP
- URL
- request body
- query params
- status code
- response body parseado (quando possivel)
- tipo/mensagem de erro

## Testes

Rodar suite atual:

```bash
vendor/bin/phpunit
```

## Notas da versao 1.0.0

- Adicionados modulos: `WorkspaceManagement`, `BankAccounts`, `BankSlipPayments`, `BarcodePayments`, `VehicleTaxesPayments`, `TaxesByFieldsPayments`, `PixAutomatic`
- `Pix` expandido com `createPayment`, `confirmPayment`, `listPayments`, `getPayment`
- `SantanderApiClient` agora resolve placeholders `:workspaceid` e `:workspace_id`
- Configuracao nova: `bank_tax_id`
- README expandido com referencia completa

## Documentacao oficial Santander

- User guide: <https://developer.santander.com.br/api/user-guide/user-guide-introduction>
- Transferencias e pagamentos: <https://developer.santander.com.br/api/documentacao/transferencias-pix-visao-geral#/>
- Comprovantes: <https://developer.santander.com.br/api/documentacao/comprovantes-visao-geral#/>

## Licenca

MIT
