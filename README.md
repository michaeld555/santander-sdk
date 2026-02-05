# Santander PHP/Laravel SDK

SDK para integração com as APIs do Santander, com foco em uso em projetos Laravel e também em PHP puro.

**Criador:** `michaeld555`

## Visão geral

O pacote simplifica fluxos comuns de pagamentos do Santander:

* Autenticação com **renovação automática de token**
* Descoberta de **Workspace PAYMENTS** (quando não informado)
* Transferências **PIX** com validação e acompanhamento de status
* **Comprovantes**: listagem de pagamentos, solicitação e obtenção do comprovante
* Tratamento consistente de erros e logging configurável

## Requisitos

* PHP `^8.1`
* Laravel `^10` ou `^11`
* Extensões PHP padrão para HTTP/TLS

## Instalação

```bash
composer require michaeld555/santander-sdk

```

Publique a configuração:

```bash
php artisan vendor:publish --tag=santander-config

```

## Configuração

No seu `.env`:

```env
SANTANDER_CLIENT_ID=seu_client_id
SANTANDER_CLIENT_SECRET=seu_client_secret
SANTANDER_CERT=/caminho/para/cert.pem
SANTANDER_BASE_URL=https://trust-open.api.santander.com.br
SANTANDER_WORKSPACE_ID=
SANTANDER_LOG_LEVEL=ERROR
SANTANDER_TIMEOUT=60

```

### Parâmetros

* `SANTANDER_CLIENT_ID`: client id da aplicação
* `SANTANDER_CLIENT_SECRET`: client secret da aplicação
* `SANTANDER_CERT`: caminho para o certificado (string) ou configuração compatível com Guzzle
* `SANTANDER_BASE_URL`: base URL da API
* `SANTANDER_WORKSPACE_ID`: opcional; se vazio, o SDK busca o primeiro workspace **PAYMENTS** ativo
* `SANTANDER_LOG_LEVEL`: `ERROR` ou `ALL`
* `SANTANDER_TIMEOUT`: timeout em segundos

## Uso rápido (Laravel)

```php
use Santander\SDK\Facades\Santander;

$transfer = Santander::pix()->transferPix(
    pixKey: 'recipient@email.com',
    value: '50.00',
    description: 'Pagamento do almoço'
);

$status = Santander::pix()->getTransfer($transfer['data']['id']);

```

## Transferência PIX por chave

```php
use Santander\SDK\Facades\Santander;

$transfer = Santander::pix()->transferPix(
    pixKey: 'recipient@email.com',
    value: '100.00',
    description: 'Pagamento de serviços'
);

```

## Transferência PIX para conta bancária

```php
use Santander\SDK\Facades\Santander;

$beneficiario = [
    'name' => 'John Doe',
    'bankCode' => '404',
    'branch' => '2424',
    'number' => '123456789',
    'type' => 'CONTA_CORRENTE',
    'documentType' => 'CPF',
    'documentNumber' => '12345678909',
];

$transfer = Santander::pix()->transferPix(
    pixKey: $beneficiario,
    value: '100.00',
    description: 'Pagamento do aluguel'
);

```

## Listar pagamentos

```php
use Santander\SDK\Facades\Santander;

$pagamentos = Santander::receipts()->paymentList([
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-02',
]);

$paymentId = $pagamentos[0]['payment']['paymentId'];

```

### Iterar por páginas

```php
use Santander\SDK\Facades\Santander;

foreach (Santander::receipts()->paymentListIterByPages([
    'start_date' => '2025-02-01',
    'end_date' => '2025-02-28',
    '_limit' => '2',
]) as $page) {
    // $page contém 'paymentsReceipts' e 'links'
}

```

## Solicitar comprovante

```php
use Santander\SDK\Facades\Santander;

$createResponse = Santander::receipts()->createReceipt($paymentId);

```

## Obter comprovante

```php
use Santander\SDK\Facades\Santander;

$receiptInfo = Santander::receipts()->getReceipt(
    $paymentId,
    $createResponse['receipt_request_id']
);

echo $receiptInfo['status'];
echo $receiptInfo['location'];

```

## Uso em PHP puro (sem Laravel)

```php
use Illuminate\Http\Client\Factory;
use Santander\SDK\Auth\SantanderAuth;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Client\SantanderClientConfiguration;
use Santander\SDK\Pix;
use Santander\SDK\PaymentReceipts;

$http = new Factory();
$config = new SantanderClientConfiguration(
    clientId: 'seu_client_id',
    clientSecret: 'seu_client_secret',
    cert: '/caminho/para/cert.pem',
    baseUrl: 'https://trust-open.api.santander.com.br'
);

$auth = SantanderAuth::fromConfig($http, $config);
$client = new SantanderApiClient($config, $auth, $http);

$pix = new Pix($client);
$receipts = new PaymentReceipts($client);

```

## Tratamento de erros

O SDK lança exceções específicas:

* `SantanderRequestError`: falhas HTTP ou resposta inválida
* `SantanderClientError`: problemas de configuração
* `SantanderRejectedError`: pagamento rejeitado pelo banco
* `SantanderStatusTimeoutError`: timeout ao aguardar atualização de status

## Logs

Configure `SANTANDER_LOG_LEVEL`:

* `ERROR`: registra apenas falhas
* `ALL`: registra falhas e sucessos

## Testes

```bash
vendor/bin/phpunit

```

## Contribuição

* Abra issues para bugs e sugestões
* Envie PRs com testes quando possível

## Documentação oficial Santander

* User guide: [https://developer.santander.com.br/api/user-guide/user-guide-introduction](https://developer.santander.com.br/api/user-guide/user-guide-introduction)
* PIX/Boletos/Transferências: [https://developer.santander.com.br/api/documentacao/transferencias-pix-visao-geral#/](https://developer.santander.com.br/api/documentacao/transferencias-pix-visao-geral#/)
* Comprovantes: [https://developer.santander.com.br/api/documentacao/comprovantes-visao-geral#/](https://developer.santander.com.br/api/documentacao/comprovantes-visao-geral#/)

## Licença

MIT
