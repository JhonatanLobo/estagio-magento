# Provisionamento de Infraestrutura Magento 2.4.8-p1: Arquitetura Conteinerizada com Docker e WSL2

## 1. Visão Geral do Entregável

Este repositório/PR formaliza a entrega técnica do **Desafio 12.1 (Ambiente Magento no ar)**. O objetivo principal foi arquitetar, provisionar e homologar um ambiente local completo do **Magento Open Source 2.4.8-p1** sob uma infraestrutura conteinerizada de alta performance, garantindo paridade com ambientes produtivos e mitigando gargalos de I/O típicos do desenvolvimento em Windows.

A solução foi implementada utilizando a stack `docker-magento` integrada ao subsistema **WSL2 (Ubuntu 22.04 LTS)** e orquestrada pelo Docker Desktop, contemplando os seguintes pilares de engenharia:

1. **Isolamento e Arquitetura de Microsserviços:** Provisionamento de 9 contêineres dedicados para cada camada da aplicação (PHP 8.4 FPM, Nginx 1.28, MariaDB 11.4, OpenSearch 3.0, Redis 8.1, RabbitMQ 4.2 e Mailcatcher).
2. **Otimização de Hardware e Kernel:** Calibragem de recursos de memória e processamento via `.wslconfig`, combinada à retenção do código-fonte exclusivamente no filesystem nativo do Linux (`ext4`), eliminando o overhead de tradução do driver 9P em `/mnt/c/`.
3. **Resolução de Rede e Roteamento Local:** Configuração de terminação TLS/SSL automatizada com certificados locais e sincronização de mapeamento DNS bidirecional (`magento.test`) entre WSL2 e Windows Host.
4. **Governança de Acessos e Modo Operacional:** Provisionamento de credenciais administrativas individualizadas (`jhonatan_admin`) via Magento CLI, refatoração de dependências de segurança (bypass de 2FA para desenvolvimento local) e travamento estrito da aplicação em modo `developer`.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore de diretórios do ambiente local (`~/Sites/magento`), evidenciando a separação entre os utilitários de orquestração do contêiner, os manifestos declarativos de infraestrutura e o núcleo da aplicação:

```text
~/Sites/magento/
├── bin/                                                   # Scripts de orquestração do ambiente Docker
│   ├── download                                           # Wrapper para download e resolução de pacotes
│   ├── setup                                              # Automação de setup:install, DI e SSL
│   ├── start                                              # Inicializador dos serviços da stack
│   ├── stop                                               # Finalizador seguro dos contêineres
│   ├── magento                                            # Proxy de execução direta da CLI do Magento
│   └── detect-versions                                    # Resolutor matricial de imagens e versões
│
├── compose.yaml                                           # Declaração base dos serviços conteinerizados
├── compose.versions.yaml                                  # Fixação declarativa das versões das imagens (2.4.8-p1)
│
├── env/                                                   # Variáveis de ambiente dos serviços da stack
│   ├── db.env
│   ├── opensearch.env
│   ├── rabbitmq.env
│   └── redis.env
│
├── src/                                                   # Raiz da aplicação Magento (Filesystem Linux)
│   ├── app/etc/env.php                                    # Configurações de conexão (DB, Cache, Search, Crypt)
│   ├── bin/magento                                        # Executável CLI nativo da aplicação
│   ├── composer.json                                      # Manifesto de dependências do Magento 2.4.8-p1
│   └── pub/                                               # Diretório público exposto pelo Nginx
│
└── README.md                                              # Documentação técnica e governança da entrega

```

---

## 3. Rastreabilidade dos Critérios de Aceite

A tabela a seguir correlaciona os requisitos avaliados com as implementações técnicas realizadas e suas respectivas comprovações:

| Requisito Avaliado | Solução Técnica Implementada | Status | Evidências de QA |
| --- | --- | --- | --- |
| **Stack Magento 2.4.8-p1** | Download e compilação das dependências via Composer apontando para o repositório oficial da Adobe. | **Concluído** | Evidências 01 e 04 |
| **Orquestração Conteinerizada** | Provisionamento isolado de PHP 8.4, MariaDB 11.4, OpenSearch 3.0 e Redis 8.1 saudáveis. | **Concluído** | Evidência 04 |
| **Roteamento DNS & SSL** | Mapeamento do host `magento.test` com emissão de certificado TLS local via `mkcert`. | **Concluído** | Evidências 01, 02 e 05 |
| **Acesso Administrativo** | Criação de usuário próprio (`jhonatan_admin`) e desativação cirúrgica de 2FA para o ambiente dev. | **Concluído** | Evidências 02 e 06 |
| **Modo Developer Ativo** | Configuração do deploy mode via CLI (`bin/magento deploy:mode:set developer`). | **Concluído** | Evidência 03 |
| **Troubleshooting Documentado** | Relato de resolução de conflitos de portas (3306 e 8080) e resolução de DNS no host Windows. | **Concluído** | Seção 5 deste documento |

---

## 4. Passo a Passo do Processo de Desenvolvimento

O provisionamento da infraestrutura foi executado em etapas lógicas, assegurando estabilidade operacional e consistência de dados:

### Passo 1: Calibragem do Subsistema WSL2 (`.wslconfig`)

Para impedir o esgotamento de recursos do Windows 11 durante as operações de compilação da injeção de dependência e reindexação, foi criado o arquivo `C:\Users\Jhonatan\.wslconfig`:

* **Memória:** Fixada em `8GB` para garantir folga operacional aos contêineres pesados (OpenSearch e MariaDB).
* **Processadores:** Limitados a `6` núcleos lógicos, balanceando compilação multithread com fluidez do sistema host.
* **Swap:** Estabelecida em `2GB` como margem de segurança contra *Out Of Memory (OOM)*.

### Passo 2: Resolução Matricial e Download da Base de Código

* A execução do utilitário `bin/download community 2.4.8-p1` disparou o script `bin/detect-versions`, mapeando a matriz de compatibilidade do Magento 2.4.8-p1.
* O script gerou o arquivo de sobreposição `compose.versions.yaml`, travando as versões de PHP 8.4 e MariaDB 11.4.
* Autenticação das chaves de API da Adobe (`auth.json`) permitiu ao Composer baixar os pacotes de dependências diretamente na pasta do projeto.

### Passo 3: Provisionamento Automatizado e Setup

* Executou-se `bin/setup magento.test`, responsável por aplicar a DDL das tabelas no MariaDB, conectar o mecanismo de catálogo ao OpenSearch e instanciar os nós de cache de sessão e objeto no Redis.
* O utilitário gerou o certificado SSL assinado pela CA local (`rootCA.pem`), vinculando os nós ao Nginx e forçando o deploy inicial de arquivos estáticos das áreas `frontend` e `adminhtml`.

### Passo 4: Governança de Acessos e Bypass de 2FA

* Criação do usuário administrativo via CLI com credenciais seguras e personalizadas (`jhonatan_admin`), atendendo às diretrizes de auditoria.
* Identificação da dependência acoplada entre `Magento_TwoFactorAuth` e `Magento_AdminAdobeImsTwoFactorAuth`, executando a desativação coordenada dos dois módulos para permitir o login direto no painel local.

### Passo 5: Alinhamento do Modo Operacional

* O ambiente foi alternado para `developer` através do comando `bin/magento deploy:mode:set developer`, ativando a renderização dinâmica de assets em `pub/static` e habilitando o detalhamento de stack traces de erros.

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

Durante a montagem da stack, foram identificados e solucionados cenários críticos de bloqueio de infraestrutura:

### Problema 1: Conflito de Porta `0.0.0.0:3306` (MariaDB)

* **Sintoma:** O contêiner `magento-db-1` falhou ao inicializar, retornando erro de bind na porta 3306.
* **Causa Raiz:** O serviço nativo do Windows `MySQL80` já estava em execução, consumindo a porta padrão de banco de dados.
* **Solução:** Interrupção do serviço via PowerShell administrativo (`Stop-Service -Name MySQL80`) e reconfiguração do tipo de inicialização para `Manual`, liberando a interface para o Docker.

### Problema 2: Conflito de Porta `0.0.0.0:8080` (phpMyAdmin)

* **Sintoma:** Erro `Error response from daemon: ports are not available: exposing port TCP 0.0.0.0:8080` ao executar `bin/start`.
* **Causa Raiz:** Um processo residente no host Windows mantinha um socket ativo em modo `LISTENING` na porta 8080.
* **Solução:** Identificação do PID via `netstat -ano | findstr :8080` e encerramento forçado da tarefa via `taskkill /F /PID <PID>`.

### Problema 3: Parâmetros Incorretos no Script `bin/download`

* **Sintoma:** Composer falhava com a mensagem `Could not find package magento/project-2.4.8-p1-edition`.
* **Causa Raiz:** A assinatura do script `bin/download` define `$1` como a edição (`EDITION`) e `$2` como a versão (`VERSION`). Ao passar apenas `2.4.8-p1`, o script interpretou o valor como o nome da edição do projeto.
* **Solução:** Execução ajustada para `bin/download community 2.4.8-p1`, garantindo a resolução correta para `magento/project-community-edition=2.4.8-p1`.

### Problema 4: Trava de Dependência Circular na Desativação de 2FA

* **Sintoma:** O comando `bin/magento module:disable Magento_TwoFactorAuth` retornou restrição de constraints e abortou a alteração.
* **Causa Raiz:** O módulo `Magento_AdminAdobeImsTwoFactorAuth` possui dependência declarada em relação ao módulo principal de 2FA.
* **Solução:** Desativação simultânea via linha de comando: `bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth Magento_TwoFactorAuth`, seguida de `bin/magento cache:flush`.

### Problema 5: Erro `DNS_PROBE_FINISHED_NXDOMAIN` no Host Windows

* **Sintoma:** O navegador no Windows não conseguia carregar `https://magento.test`, mesmo com a rota configurada no `/etc/hosts` do WSL2.
* **Causa Raiz:** O subsistema WSL2 possui stack de rede isolada; o `/etc/hosts` do Linux não propaga registros para o resolvedor DNS nativo do Windows. Além disso, navegadores modernos ativam DNS-over-HTTPS (DoH), ignorando o arquivo de hosts do sistema.
* **Solução:** Injeção manual do apontamento no arquivo `C:\Windows\System32\drivers\etc\hosts` via PowerShell (`Add-Content`), execução de `ipconfig /flushdns` e desativação temporária do DNS Seguro no navegador.

---

## 6. Decisões de Engenharia: Performance e Segurança Local

* **Filesystem Nativo Linux vs `/mnt/c/`:** A execução integral sob `~/Sites/magento` (sistema de arquivos `ext4` do WSL2) foi estritamente adotada. Projetos montados via `/mnt/c/` sofrem degradação drástica em I/O devido à conversão de chamadas POSIX para o protocolo 9P do Windows, tornando requisições do Magento lentas.
* **Sanitização de Quebras de Linha (Unix LF):** Configuração global do repositório para `core.autocrlf false`, evitando a conversão de quebras de linha para CRLF (`\r\n`), que frequentemente quebra a execução de scripts em `bin/` com erros de interpretador.
* **Bypass Estratégico de 2FA em Desenvolvimento:** Em ambientes locais sem gateway de e-mail corporativo ativo, o 2FA nativo do Magento interrompe a produtividade exigindo tokens que caem em caixas de mensagem virtuais. A desativação controlada preserva o fluxo ágil de desenvolvimento sem degradar a segurança do código que irá para staging/produção.

---

## 7. Relatório Detalhado de Quality Assurance (QA)

Abaixo estão apresentadas as **6 evidências formais de homologação**, comprovando o provisionamento estável, a conformidade da infraestrutura e a liberação dos pontos de acesso do sistema.

### Evidência 01 — Vitrine da Loja (Frontend Home)

* **Análise Visual/Técnica:** Valida a renderização completa do tema padrão do Magento através do endpoint seguro `https://magento.test`. A barra de navegação exibe o handshake HTTPS resolvido com o certificado emitido localmente, confirmando que Nginx, PHP-FPM e MariaDB estão operando sincronizados.

![Evidência 01 — Vitrine da Loja](https://github.com/user-attachments/assets/234a16a3-101a-43d0-8f4e-f0e707cdad8c)

---

### Evidência 02 — Painel Administrativo Autenticado (Dashboard)

* **Análise Visual/Técnica:** Comprova a autenticação bem-sucedida no backend administrativo (`https://magento.test/admin`) com a conta `jhonatan_admin`. A interface carrega o menu lateral completo (Sales, Catalog, Customers, Marketing, Content, Reports e Stores), atestando a integridade das tabelas de autorização e controle de sessão via Redis.

![Evidência 02 — Painel Administrativo](https://github.com/user-attachments/assets/569a869a-f8cf-4c17-b651-8ec6a2785850)

---

### Evidência 03 — Validação do Modo de Operação (CLI Deploy Mode)

* **Análise Visual/Técnica:** Demonstra a consulta via linha de comando `bin/magento deploy:mode:show`, evidenciando a string de resposta `Current application mode: developer.`. Isso assegura que o ambiente está com compilação dinâmica e depuração em tempo real ativas.

![Evidência 03 — CLI Deploy Mode](https://github.com/user-attachments/assets/7cde8809-dc6d-491b-bf1a-ecd5053a41ad)

---

### Evidência 04 — Integridade e Saúde da Suíte de Contêineres

* **Análise Visual/Técnica:** Terminal evidenciando a inicialização controlada dos contêineres Docker, demonstrando que todos os serviços da orquestração subiram com status `Started` ou `Healthy`. A imagem ratifica a coexistência estável das portas mapeadas (80, 443, 3306, 8080, 9200, 5672).

![Evidência 04 — Status dos Contêineres](https://github.com/user-attachments/assets/fc045873-d008-4e6c-a8ac-66b7c29b1870)

---

### Evidência 05 — Resolução de Rede e Roteamento Local (DNS Ping)

* **Análise Visual/Técnica:** Execução de `ping magento.test` no PowerShell do Windows, comprovando 0% de perda de pacotes e tempo de resposta `<1ms` apontando para a interface de loopback `127.0.0.1`. A captura valida a mitigação bem-sucedida da falha de resolução `NXDOMAIN`.

![Evidência 05 — Resolução DNS Ping](https://github.com/user-attachments/assets/fb6633a0-1f58-4cc2-90d0-b350611af013)

---

### Evidência 06 — Desativação Coordenada dos Módulos de 2FA

* **Análise Visual/Técnica:** Saída do comando `bin/magento module:status` no terminal do Ubuntu, validando que os módulos `Magento_TwoFactorAuth` e `Magento_AdminAdobeImsTwoFactorAuth` constam como desativados.

![Evidência 06 — Módulos 2FA Desativados](https://github.com/user-attachments/assets/03a40e26-a081-4d20-be35-4e67e9923a8f)