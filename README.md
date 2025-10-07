# Saque PIX 

Plataforma de conta digital  (Hyperf 3 + Swoole) para **saque PIX** imediato e **agendado** via  **cron** e **notificação por e‑mail** (Mailpit). Adicionalmente, está implementado **idempotência** para evitar saques duplicados a partir de um mesmo **request** e o envio de email do forma assíncrona (Redis) para evitar falha no **response** mesmo após o saque ter sido efetivado no banco de dados (Mysql 8).

## Containers Docker (versão e porta)
- **hyperf-skeleton-service**: `hyperf/hyperf:8.3-alpine-v3.22-swoole` (porta 9501)
- **hyperf-skeleton--mysql**: `mysql:8` (porta 3306)
- **hyperf-skeleton-redis**: `redis:latest` (porta 6379)
- **hyperf-skeleton-smtp**: `axllent/mailpit:latest` (porta SMTP 1025, e interface do usuário 8025)

A versão Hyperf 3.x (swoole + alpine + php 8.3) proporciona um desenvolvimento rápido, enxuto e pronto para uso do Hyperf.


## Regras de negócio implementadas

√ A operação do saque deve ser registrado no banco de dados, usando as `tabelas account_withdraw` e `account_withdraw_pix`.
√ O saque sem agendamento deve realizar o saque de imediato.
√ O saque com agendamento deve ser processado somente via cron.
√ O saque deve deduzir o saldo da conta na tabela `account` .
√ Atualmente só existe a opção de saque via PIX, podendo ser somente para chaves do tipo email, possibilitando uma fácil expansão de outras formas de saque no futuro.
√ Não é permitido sacar um valor maior do que o disponível no saldo da conta digital.
√ O saldo da conta não pode ficar negativo.
√ Para saque agendado, não é permitido agendar para um momento no passado.
√ Para saque agendado, não é permitido agendar para uma data maior que 7 dias no futuro. 

## Envio de email de notificação

Após realizar o saque, será enviado um email para o email do PIX, informando que o saque foi efetuado. contendo a data e hora do saque, o valor sacado e os dados do pix informado.

√ Envio de email de saque imediato através de Job (evita latencia/erro na resposta da API por falha no serviço SMTP)

## Regras de negócio adicionais (opcional)

√ Controle de atomicidade por **Idempotência** (tabela `request_idempotency`) 
√ No método de **Cancelamento** permite apenas de saques **agendados** pendentes.

## Idempotência

É a propriedade de segurança que garante que uma operação, se repetida várias vezes, terá sempre o mesmo efeito final, evitando duplicações indesejadas, onde neste caso a primeira tentativa válida debita ou agenda o saque do saldo; tentativas subsequentes não tem efeiro e respondem com o mesmo valores que foram retornados na primeira requisição válida, caso esse retorno tenha sido perdido (droped-packet) e um nova requisição foi solicitada com a mesma chave (`Indepontency-Key`) no header. 

### Listener da API

- http://127.0.0.1:9501/

### Listener da Interface Web do MailPit

- http://127.0.0.1:8025/

## Endpoint do Case
- `POST /account/{accountId}/balance/withdraw`

## Endpoints adicional ao Case (para efetuar o cancelamento de agendamento de saques)

- `GET /account/{accountId}/cancel/{withdrawalId}`

## Endpoints adicionais ao Case (uso no ambiente de desenvolvimento)
- `GET /account/{accountId}/balance`
- `GET /account/{accountId}/withdrawals`

### Exemplos 

#### Saque (imediato)
```bash
curl --request POST \
  --url http://127.0.0.1:9501/account/{accountId}/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{"method": "PIX", "pix": {"type": "email", "key": "test@test.com"}, "amount": 100.00, "schedule": null}'
```

#### Saque (imediato com idempotência)
```bash
curl --request POST \
  --url http://127.0.0.1:9501/account/3{accountId}/balance/withdraw \
  --header 'Content-Type: application/json' \
  --header 'Idempotency-Key: acct-b5c478a9-4b1f-4c8d-8e4d-61f2f8a9e4d1' \
  --data '{"method": "PIX", "pix": {"type": "email", "key": "test@test.com"}, "amount": 100.00, "schedule": null}'
```

#### Saque (agendado)
```bash
curl --request POST \
  --url http://127.0.0.1:9501/account/{accountId}/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{"method": "PIX", "pix": {"type": "email", "key": "test@test.com"}, "amount": 500.00, "schedule": "2025-10-06 10:00:00"}'
```

#### Listar saques (imediatos/agendados/erros)
```bash
curl --request GET \
  --url 'http://127.0.0.1:9501/account/{accountId}/withdrawals?status='
  ```
- `status` pode ser `done`, `scheduled`, `error` ou não informar para todos registros

#### Cancelar saques agendado
```bash
curl --request GET \
  --url http://127.0.0.1:9501/account/{accountId}/cancel/{withdrawId}
```
## Execução


Após clonar o projeto (ou baixar e descopactar o arquivo `pix_case.zip` fornecido pelo github) para uma pasta local, dentro dessa pasta, crie um arquivo de configuração `.env` a partir do exemplo `.env.example` (`cp .env.example .env`).

Criar e subir os containers do projeto com o comando `docker compose up -d`. 

Instalar todas as dependências utilizadas neste projeto, para isso execute `docker container exec -it hyperf-skeleton-service composer install -o`.

Iniciar o banco de dados com as tabelas deste Case execute `docker container exec -it hyperf-skeleton-service php bin/hyperf.php migrate --seed`

## Depuracao, Observacibilidade e Segurança

Utilize o comando `docker compose logs -f` para observar as mensagens de logs enviadas para o log do console dos containeres, por exemplo:

```log
hyperf-skeleton-service  | [DEBUG] Current microtime: 1759817280 0.01628500. Crontab dispatcher sleep 59.984s.
hyperf-skeleton-service  | [2025-10-07 14:08:00] hyperf.INFO: ProcessDue  {"started_at":"2025-10-07 03:08:00"} []
hyperf-skeleton-service  | [DEBUG] Event Hyperf\Framework\Event\OnPipeMessage handled by Hyperf\Crontab\Listener\OnPipeMessageListener listener.
hyperf-skeleton-service  | [2025-10-07 14:08:00] sql.INFO: [18.14] select * from `account_withdraw` where `scheduled` = '1' and `done` = '' and `scheduled_for` <= '2025-10-07 03:08:00' order by `scheduled_for` asc limit 100 for update skip locked [] []
hyperf-skeleton-service  | [DEBUG] Event Hyperf\Database\Events\QueryExecuted handled by App\Listener\DbQueryExecutedListener listener.
hyperf-skeleton-service  | [2025-10-07 14:08:00] hyperf.INFO: ProcessDue  {"finished_at":"2025-10-07 03:08:00","processed":0} []
hyperf-skeleton-service  | [INFO] Crontab task [finish-pending-withdrawals] executed successfully at 2025-10-07 14:08:00.
```

No qual permite visualizar a execução da cron a cada minuto para verificar os saques agendados pendentes de termino, os SQLs efetuados no banco de dados, o monitoramento do enfileramento/envio de e-mail para o serviço de smtp, etc.

Se desejar ativar o `watcher` (que atualiza em tempo real as alterações realizadas na pasta local, durante o desenvolvimento do projeto) altere o endpoint no arquivo docker-composer.yml para `entrypoint: ["php", "bin/hyperf.php", "server:watch"]`.

### Observações

As rotas `/account/{accountId}/balance` e `/account/{accountId}/withdrawals` foram criadas para facilitar a depuracão em desenvolvimento, evitanto a necessidade de executar comandos SQL diretamente no servidor de banco de dados.

O arquivo `Insomnia.yaml` na pasta do projeto contém os requests para os endpoint desse projeto para utilizar no Insomnia (veja em `https://insomnia.rest`).

O arquivo `swagger.yaml` na pasta do projeto contém os requests para os endpoint deste projeto para utilizar no Swagger (veja em `https://editor.swagger.io`).

Como o objetivo deste case é demonstrar a facilidade da criação de APIs utilizando mapeadmento de endpoints por anotações (não utiliza routes.php), processamento assíncrono de tarefas, verificação de  solicitaçoes agendadas via cron, entre outras funcionalidades do Hyperf, não foi utilizada a arquitetura de desenvolvimento direcionada a domínio (Domain-Driven Design, ou DDD), a qual explora no foca de domínio do negócio.
