# Implementação Técnica Magento 2: Entidade Própria com Declarative Schema, Repositório e Service Contracts

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do Desafio 14.2 (Entidade própria, do banco ao repositório) da Sprint 7 (Magento 2 / Adobe Commerce). O objetivo central consistiu em projetar, estruturar e homologar uma entidade relacional própria para o gerenciamento de avaliações de produtos (`webjump_avaliacao`), cobrindo desde o esquema declarativo no MariaDB até a camada de abstração de contratos de serviço corporativos (Service Contracts), sem qualquer modificação no diretório `vendor/`.

A implementação apoia-se em cinco pilares fundamentais de engenharia de software corporativa:

- **Modelagem Declarativa e Idempotente (Declarative Schema):** Provisionamento físico da tabela `webjump_avaliacao` via `etc/db_schema.xml` e geração da trava de integridade estrutural `etc/db_schema_whitelist.json`, substituindo scripts procedurais de migração (`InstallSchema`/`UpgradeSchema`).
- **Encapsulamento em Camadas ORM (Model, ResourceModel e Collection):** Segregação estrita entre a entidade em memória (Model), a camada de persistência relacional de baixo nível (ResourceModel) e a orquestração de seleções em lote (Collection).
- **Contratos de Serviço (Service Contracts) e Inversão de Controle:** Exposição de contratos públicos em `Api/` mapeados via `<preference>` no `etc/di.xml`, viabilizando o consumo desacoplado e consultas paginadas/filtradas via `SearchCriteriaInterface`.
- **Programação Defensiva e Tratamento de Exceções:** Implementação de guarda defensiva e captura de falhas através de exceções nativas (`NoSuchEntityException`, `CouldNotSaveException`, `CouldNotDeleteException`), impedindo vazamento de erros fatais do ORM.
- **Ingestão Idempotente via Data Patch (`DataPatchInterface`):** Povoamento automatizado de 5 avaliações de exemplo vinculadas ao produto de catálogo, utilizando a API oficial de repositório e com registro de execução persistido na tabela `patch_list` do banco.

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore completa de artefatos estruturados no módulo `src/app/code/Webjump/Jhonatan/`, demonstrando a separação de responsabilidades entre contratos de serviço, persistência ORM e esquemas de dados:

```text
src/app/code/Webjump/Jhonatan/
├── registration.php                                       # Registro do componente no ComponentRegistrar
├── etc/
│   ├── module.xml                                         # Declaração do módulo e dependências de sequence
│   ├── di.xml                                             # Mapeamento global de preferences (Service Contracts)
│   ├── db_schema.xml                                      # Definição declarativa da tabela webjump_avaliacao
│   ├── db_schema_whitelist.json                           # Whitelist de integridade física da tabela
│   ├── frontend/
│   │   └── di.xml                                         # Interceptors da vitrine pública (Sprint 6)
│   └── adminhtml/
│       ├── events.xml                                     # Observers administrativos (Sprint 6)
│       └── system.xml                                     # Configurações no Stores > Configuration (Sprint 6)
│
├── Api/
│   ├── AvaliacaoRepositoryInterface.php                   # Service Contract oficial de operações do repositório
│   └── Data/
│       ├── AvaliacaoInterface.php                         # Contrato de dados da entidade (getters/setters tipados)
│       └── AvaliacaoSearchResultsInterface.php            # Contrato de retorno paginado de resultados de busca
│
├── Model/
│   ├── Avaliacao.php                                      # Model da entidade (Active Record / Negócio)
│   ├── AvaliacaoRepository.php                            # Implementação concreta do repositório de avaliações
│   ├── AvaliacaoSearchResults.php                         # Implementação concreta dos resultados paginados
│   └── ResourceModel/
│       ├── Avaliacao.php                                  # ResourceModel mapeando tabela e chave primária
│       └── Avaliacao/
│           └── Collection.php                             # Coleção relacional para filtros e seleções em massa
│
├── Setup/
│   └── Patch/
│       └── Data/
│           └── AddSampleAvaliacoes.php                    # Data Patch para povoamento das 5 avaliações de teste
│
├── ViewModel/
│   ├── HomeBlock.php                                      # Lógica do bloco da Home (Sprint 6)
│   └── ProductBadge.php                                   # Resolução do selo sustentável na PDP (Desafio 14.1)
│
└── view/
    └── frontend/
        ├── layout/
        │   ├── cms_index_index.xml                        # Orquestração de layout da Home (Sprint 6)
        │   └── catalog_product_view.xml                   # Injeção declarativa do badge na PDP (Desafio 14.1)
        ├── templates/
        │   ├── home_block.phtml                           # Template institucional da Home
        │   └── product/
        │       └── badge.phtml                            # Template defensivo do selo de produto
        └── web/
            └── css/
                ├── home-block.css                         # Estilização modular da Home
                └── product-badge.css                      # Estilização modular e responsiva do badge
```

## 3. Rastreabilidade dos Critérios de Aceite

A tabela abaixo correlaciona os requisitos avaliativos da especificação com as soluções técnicas implementadas e suas respectivas evidências comprobatórias de QA:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
|---|---|---|---|
| Criação da Tabela por Schema | Tabela `webjump_avaliacao` definida em `etc/db_schema.xml` provisionada via `bin/magento setup:upgrade`. | Concluído | Evidências 01, 03 e 04 |
| Whitelist Declarativa Versionada | Geração e versionamento de `etc/db_schema_whitelist.json` com mapeamento estrito de colunas e índices. | Concluído | Evidência 02 |
| Service Contracts em Api/ | Modelagem de `AvaliacaoRepositoryInterface`, `AvaliacaoInterface` e `AvaliacaoSearchResultsInterface`. | Concluído | Evidências 07, 08 e 09 |
| Injeção de Dependências (`di.xml`) | Mapeamento declarativo via `<preference>` conectando interfaces às classes concretas no container de DI. | Concluído | Evidência 07 |
| Operações de CRUD no Repositório | Métodos `save()`, `getById()`, `delete()`, `deleteById()` e `getList()` implementados e testados via CLI. | Concluído | Evidências 08, 09 e 10 |
| Busca Estruturada via SearchCriteria | Método `getList()` consumindo `CollectionProcessorInterface` para ordenação, filtros e limites de paginação. | Concluído | Evidência 08 |
| Tratamento Defensivo de Exceções | Captura defensiva disparando `NoSuchEntityException` amigável para registros inexistentes. | Concluído | Evidência 10 |
| Povoamento com 5 Avaliações | Data Patch `AddSampleAvaliacoes` persistindo dados de teste via repositório de forma limpa. | Concluído | Evidências 05 e 06 |
| Idempotência no Banco de Dados | Registro do patch catalogado sob `patch_id` 186 na tabela `patch_list` do MariaDB. | Concluído | Evidência 05 |
| Justificativa Arquitetural | Análise técnica comparativa detalhando a escolha de Tabela Própria frente ao modelo EAV. | Concluído | Seção 6 deste doc |
| Inviolabilidade do Núcleo da Plataforma | Desenvolvimento estritamente isolado no módulo próprio sem nenhuma alteração em `vendor/`. | Concluído | Evidências 11 e 12 |

## 4. Passo a Passo do Processo de Desenvolvimento

Para garantir estabilidade relacional, manutenibilidade e desacoplamento, a esteira de construção da entidade seguiu os passos lógicos abaixo:

### Passo 1: Modelagem Declarativa da Tabela (`etc/db_schema.xml`)

- Estruturou-se a tabela `webjump_avaliacao` com motor de armazenamento `innodb` e conjunto de caracteres UTF-8.
- Declararam-se 7 colunas com tipagem estrita: `avaliacao_id` (`int unsigned auto-increment`), `product_id` (`int unsigned`), `autor` (`varchar 255`), `comentario` (`text`), `nota` (`smallint unsigned default 5`), `aprovado` (`boolean default false`) e `created_at` (`timestamp default current_timestamp`).
- Configurou-se a constraint primária `PRIMARY` na coluna `avaliacao_id` e adicionaram-se dois índices B-Tree (`WEBJUMP_AVALIACAO_PRODUCT_ID` e `WEBJUMP_AVALIACAO_APROVADO`) para otimizar consultas analíticas e moderação no backend.

### Passo 2: Geração da Whitelist de Integridade (`etc/db_schema_whitelist.json`)

- Executou-se o utilitário de CLI `bin/magento setup:db-declaration:generate-whitelist --module-name=Webjump_Jhonatan`.
- O comando inspecionou o XML declarativo e compilou o arquivo JSON de trava estrutural, registrando colunas, constraints e índices existentes para prevenir perdas acidentais de dados em futuras atualizações.

### Passo 3: Contratos de Serviço e Estruturas de Dados (`Api/`)

- Criou-se a interface de dados `Api/Data/AvaliacaoInterface.php`, expondo constantes de colunas e assinaturas tipadas de getters e setters.
- Definiu-se `Api/Data/AvaliacaoSearchResultsInterface.php` herdando de `SearchResultsInterface` para padronizar o encapsulamento de listas de entidades.
- Modelou-se `Api/AvaliacaoRepositoryInterface.php` estabelecendo os contratos de CRUD e definindo as exceções lançadas pela camada de serviço.

### Passo 4: Implementação da Camada de Domínio e Persistência (`Model/`)

- Criou-se a classe `Model/Avaliacao.php` estendendo `AbstractModel` e implementando `AvaliacaoInterface`, vinculando-a ao seu respectivo ResourceModel.
- Criou-se `Model/ResourceModel/Avaliacao.php` estendendo `AbstractDb`, mapeando a tabela física `webjump_avaliacao` e a chave primária `avaliacao_id`.
- Criou-se `Model/ResourceModel/Avaliacao/Collection.php` estendendo `AbstractCollection` para lidar com seleções e filtros relacionais em massa.

### Passo 5: Construção do Repositório e Resolução de Tipagem

- Desenvolveu-se a classe concreta `Model/AvaliacaoSearchResults.php` estendendo `SearchResults` e implementando `AvaliacaoSearchResultsInterface` para atender à tipagem estrita de retorno.
- Implementou-se `Model/AvaliacaoRepository.php` injetando ResourceModel, factories e `CollectionProcessorInterface`, orquestrando as operações de persistência e busca paginada via `SearchCriteria`.

### Passo 6: Injeção de Dependências Global (`etc/di.xml`)

- Declararam-se as diretivas de `<preference>` no container global do Magento (`etc/di.xml`), associando as interfaces `AvaliacaoRepositoryInterface`, `AvaliacaoInterface` e `AvaliacaoSearchResultsInterface` às suas respectivas implementações concretas.

### Passo 7: Ingestão Idempotente via Data Patch (`Setup/Patch/Data/AddSampleAvaliacoes.php`)

- Implementou-se o patch sob o contrato `DataPatchInterface`.
- Injetou-se o `AvaliacaoRepositoryInterface` no construtor para realizar a gravação dos dados via Service Contract, evitando comandos SQL manuais.
- Foram persistidas 5 avaliações completas vinculadas ao produto ID 1 (`Mouse Gamer Pro Wireless`), intercalando notas (4 e 5) e status de aprovação (3 aprovadas e 2 pendentes).

### Passo 8: Pipeline de Execução e Compilação

- No terminal do WSL2, executou-se `bin/magento setup:upgrade` para criar a tabela no banco MariaDB e processar o Data Patch.
- Executou-se `bin/magento setup:di:compile` para compilar o container de injeção de dependências, gerar proxies e validar a integridade de todas as classes e factories.

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Incompatibilidade de Retorno Estrito em `getList()` (`TypeError`)

Durante os testes de integração do repositório via linha de comando, a execução do método `getList()` retornou uma exceção fatal de tipagem estrita do PHP:

```text
Fatal error: Uncaught TypeError: Webjump\Jhonatan\Model\AvaliacaoRepository::getList(): 
Return value must be of type Webjump\Jhonatan\Api\Data\AvaliacaoSearchResultsInterface, 
Magento\Framework\Api\SearchResults returned in /var/www/html/.../AvaliacaoRepository.php:95
```

### Causa Raiz

A classe genérica `Magento\Framework\Api\SearchResults` do core implementa `Magento\Framework\Api\SearchResultsInterface`, porém não implementa a interface tipada do módulo `Webjump\Jhonatan\Api\Data\AvaliacaoSearchResultsInterface`. Como a assinatura do método no contrato exige retorno estrito de `AvaliacaoSearchResultsInterface`, o PHP bloqueou a execução.

### A Solução: Classe Concreta Especializada e Injeção de Factory Tipada

Para solucionar o gap arquitetural mantendo total adesão às boas práticas de Service Contracts:

- Criação da Classe Concreta (`Model/AvaliacaoSearchResults.php`):

```php
<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model;

use Magento\Framework\Api\SearchResults;
use Webjump\Jhonatan\Api\Data\AvaliacaoSearchResultsInterface;

class AvaliacaoSearchResults extends SearchResults implements AvaliacaoSearchResultsInterface
{
}
```

- Mapeamento Declarativo no `etc/di.xml`:

```xml
<preference for="Webjump\Jhonatan\Api\Data\AvaliacaoSearchResultsInterface"
            type="Webjump\Jhonatan\Model\AvaliacaoSearchResults"/>
```

- Injeção da Factory no Repositório: O construtor do `AvaliacaoRepository` foi refatorado para receber `AvaliacaoSearchResultsInterfaceFactory`, permitindo instanciar o objeto tipado em tempo de execução e satisfazer o contrato sem erro de tipo (Comprovado na Evidência 08).

### Prevenção de Exclusão Acidental de Colunas com Whitelist Declarativa

No Declarative Schema, alterações no `db_schema.xml` podem disparar exclusões físicas de dados caso o framework não identifique a coluna em sua lista autorizada.

- **Solução:** A execução obrigatória de `bin/magento setup:db-declaration:generate-whitelist --module-name=Webjump_Jhonatan` gerou o manifesto `db_schema_whitelist.json`, travando os metadados de colunas e índices no controle de versão (Auditado na Evidência 02).

### Programação Defensiva contra Retornos Fantasmas (`NoSuchEntityException`)

Consultas individuais por ID (`getById`) que não encontram registros poderiam retornar instâncias de Model vazias com ID nulo.

- **Solução:** O método `getById()` valida explicitamente se `$avaliacao->getId()` foi preenchido após o `load()` do ResourceModel. Em caso negativo, dispara uma `NoSuchEntityException` amigável (`Avaliacao com o ID "X" nao foi encontrada.`), garantindo estabilidade para integrações externas (Auditado na Evidência 10).

## 6. Modelagem de Dados no Magento 2: Por que Tabela Própria e não EAV neste caso?

A decisão entre modelar uma nova entidade através do padrão EAV (Entity-Attribute-Value) ou através de uma Tabela Própria (Flat Relacional) é um dos divisores de águas em arquitetura Adobe Commerce / Magento 2.

| Critério Arquitetural | Tabela Própria (Flat Relacional) | Modelo EAV (Entity-Attribute-Value) |
|---|---|---|
| Natureza dos Atributos | Atributos fixos, homogêneos e universais em todos os registros. | Atributos altamente heterogêneos e customizáveis por tipo de item. |
| Topologia Relacional | Tabela única plana com colunas primitivas estritas. | Dados dispersos verticalmente em dezenas de tabelas auxiliares (`_int`, `_varchar`, etc.). |
| Performance de Consulta | Leituras diretas e simples sem necessidade de junções pesadas. | Leituras brutas lentas exigindo dezenas de JOINs para montar o objeto. |
| Dependência de Indexadores | Atualização em tempo real; dispensa processamento em lote via indexadores. | Dependência obrigatória de indexação (`indexer:reindex`) para alimentar tabelas flat. |
| Agregações Numéricas | Cálculos nativos ultrarrápidos (`SELECT AVG(nota), COUNT(*)`). | Cálculos agregados lentos devido à fragmentação dos valores inteiros/decimais. |
| Manutenção Estrutural | Declarada diretamente no `db_schema.xml` versionado. | Metadados persistidos dinamicamente nas tabelas `eav_attribute`. |

### Racional Técnico para a Entidade de Avaliações (`webjump_avaliacao`)

Para gerenciar as avaliações de clientes, adotou-se estritamente Tabela Própria pelos seguintes pilares de engenharia:

1. **Homogeneidade Estrutural Absoluta:**
   O modelo EAV foi concebido para entidades cujo conjunto de atributos varia drasticamente entre instâncias (como o catálogo de produtos, onde um vestuário demanda atributos como tamanho e cor, enquanto um eletrônico demanda voltagem e conectividade). Uma avaliação de produto não possui essa variabilidade: ela sempre terá autor, produto avaliado, comentário, nota numérica, status de aprovação e data de criação. O uso de EAV aqui adicionaria uma complexidade relacional desnecessária.

2. **Alta Performance em Cálculos Analíticos e Filtros:**
   Em uma loja com centenas de milhares de avaliações, operações comuns como calcular a média de estrelas de um produto (`AVG(nota)`) ou contar o volume de opiniões aprovadas (`COUNT(*)`) são resolvidas pelo MariaDB em milissegundos através de uma consulta simples com índice composto B-Tree (`product_id + aprovado`). No EAV, essa operação exigiria varreduras verticais custosas em tabelas de inteiros (`catalog_product_entity_int`), provocando bloqueio de tabelas e lentidão de I/O.

3. **Independência Operacional e Moderação em Tempo Real:**
   Entidades EAV dependem de ciclos de reindexação (`indexer:reindex`) para planificar suas tabelas de leitura e sincronizar o motor OpenSearch. Avaliações de produtos exigem imediatismo transacional: quando um operador de atendimento aprova uma avaliação no painel administrativo, ela deve constar como aprovada no mesmo segundo, sem exigir a execução de indexadores pesados de catálogo.

## 7. Decisões de Engenharia Backend: Contratos de Serviço e SearchCriteria

### Por que Service Contracts (`Api/`) em vez de chamar o Model diretamente?

Consumir o Model diretamente em controllers ou módulos integradores gera acoplamento rígido ao ORM (`AbstractModel`). Ao estabelecer a `AvaliacaoRepositoryInterface`, qualquer módulo consome apenas a abstração de contrato. A implementação concreta de persistência no banco pode ser reescrita no futuro sem quebrar nenhum consumidor externo.

### Por que utilizar `SearchCriteriaInterface` e `CollectionProcessorInterface`?

O `SearchCriteria` padroniza a forma como o Magento realiza buscas filtradas, paginação e ordenação de forma agnóstica ao banco de dados. Em vez de concatenar cláusulas SQL (`WHERE`, `LIMIT`) no repositório, delegamos o processamento para o `CollectionProcessor`, mantendo o código seguro contra injeções SQL e compatível com as APIs REST e GraphQL nativas do Magento.

## 8. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as 12 evidências formais de homologação do Desafio 14.2, cobrindo todo o ciclo de schema declarativo, persistência física no MariaDB, injeção de dependências e testes de busca estruturada:

### Evidência 01 — Declarative Schema XML (`etc/db_schema.xml`)

**Análise Visual/Técnica:** Leitura limpa do manifesto `db_schema.xml` via terminal. Comprova a modelagem da tabela `webjump_avaliacao` com 7 colunas tipadas, chave primária auto-incremento e os índices B-Tree nas colunas `product_id` e `aprovado`.

![Evidência 01](https://github.com/user-attachments/assets/d5844d6b-5252-42d1-917e-9c7df734844f)

### Evidência 02 — Whitelist Declarativa Gerada (`etc/db_schema_whitelist.json`)

**Análise Visual/Técnica:** Retorno do arquivo JSON compilado via CLI (`generate-whitelist`). Evidencia a catalogação das colunas, índices e constraint `PRIMARY`, provando a trava de segurança contra deleções de dados em deploys.

![Evidência 02](https://github.com/user-attachments/assets/d4f43a8d-0bc1-45ea-a0b8-d732e5cc863c)

### Evidência 03 — DDL da Tabela Criada no MariaDB (`DESCRIBE`)

**Análise Visual/Técnica:** Inspeção relacional no banco MariaDB (`DESCRIBE webjump_avaliacao;`). Atesta a criação física da tabela plana contendo colunas unsigned, chave primária (`PRI`), suporte a nulos em `comentario` e `auto_increment` ativo.

![Evidência 03](https://github.com/user-attachments/assets/d1f1ab27-8ab6-44b7-8f97-b6f40462d834)

### Evidência 04 — Índices de Busca e Performance no Banco (`SHOW INDEX`)

**Análise Visual/Técnica:** Consulta estrutural de índices (`SHOW INDEX FROM webjump_avaliacao;`). Comprova os índices B-Tree `PRIMARY`, `WEBJUMP_AVALIACAO_PRODUCT_ID` e `WEBJUMP_AVALIACAO_APROVADO`, garantindo alta velocidade de leitura.

![Evidência 04](https://github.com/user-attachments/assets/486f35cd-0509-4e01-913a-ae46aee4a806)

### Evidência 05 — Idempotência e Registro do Data Patch (`patch_list`)

**Análise Visual/Técnica:** Consulta na tabela de controle de patches do Magento (`patch_list`). Evidencia o patch registrado sob o ID 186 com o namespace completo da classe `AddSampleAvaliacoes`, garantindo que não rodará duplicado.

![Evidência 05](https://github.com/user-attachments/assets/8731ad74-8562-4270-b7f3-d5abb0ef9411)

### Evidência 06 — 5 Avaliações de Exemplo Persistidas no Banco

**Análise Visual/Técnica:** Consulta `SELECT` na tabela `webjump_avaliacao`. Comprova os 5 registros inseridos pelo patch, com autores reais, notas variadas (4 e 5), vinculados ao produto ID 1 e divididos entre aprovados (`1`) e pendentes (`0`).

![Evidência 06](https://github.com/user-attachments/assets/0e53dd23-f3aa-4bf8-bb93-4ed18c52a8fb)

### Evidência 07 — Mapeamento de Preferences no `etc/di.xml`

**Análise Visual/Técnica:** Leitura do arquivo `etc/di.xml` via terminal. Demonstra a configuração do container de DI mapeando os contratos `AvaliacaoRepositoryInterface`, `AvaliacaoInterface` e `AvaliacaoSearchResultsInterface` para suas respectivas classes concretas.

![Evidência 07](https://github.com/user-attachments/assets/1c9a5b67-f6ec-4c29-b6c5-b9b6cab34882)

### Evidência 08 — Service Contract: Busca Filtrada com Paginação (`getList`)

**Análise Visual/Técnica:** Script PHP CLI executando `getList()` com `SearchCriteriaBuilder`. Comprova o repositório filtrando pelo status `aprovado = 1` (3 registros no total do banco) e limitando a página em `pageSize = 2` (retornando exatamente Lucas e Mariana).

![Evidência 08](https://github.com/user-attachments/assets/ffea0cc6-5737-4b72-bf09-d327e5634f97)

### Evidência 09 — Resolução de Entidade Individual (`getById`)

**Análise Visual/Técnica:** Script PHP CLI executando `getById(1)` através do contrato `AvaliacaoRepositoryInterface`. Comprova o carregamento correto da entidade em memória através do ResourceModel, exibindo os dados completos da avaliação.

![Evidência 09](https://github.com/user-attachments/assets/b8eab081-59d3-4701-9cce-850ee1bcb0cf)

### Evidência 10 — Programação Defensiva: Tratamento de Exceção (`NoSuchEntityException`)

**Análise Visual/Técnica:** Script PHP CLI executando `getById(999)` para um ID inexistente. Comprova o tratamento defensivo do repositório capturando a exceção formal `NoSuchEntityException` com a mensagem amigável `Avaliacao com o ID "999" nao foi encontrada.`.

![Evidência 10](https://github.com/user-attachments/assets/0b496daa-4bb5-4cc6-89a7-8329c3138604)

### Evidência 11 — Integridade de Compilação do Backend (`setup:di:compile`)

**Análise Visual/Técnica:** Retorno com 100% de sucesso do compilador de injeção de dependências (`bin/magento setup:di:compile`). Valida conformidade sintática, tipagem estrita e resolução correta das factories sob `generated/code/`.

![Evidência 11](https://github.com/user-attachments/assets/e904719f-14f9-447e-b982-90337bfc7ee6)

### Evidência 12 — Inviolabilidade do Core e Status Git

**Análise Visual/Técnica:** Execução de `git status` no terminal na branch `exercicio/14.2-entidade-propria-banco-repositorio`. Comprova que todas as alterações estão restritas a `src/app/code/Webjump/Jhonatan/`, atestando que o diretório `vendor/` permaneceu estritamente intocado.

![Evidência 12](https://github.com/user-attachments/assets/20571cc9-e718-4f6a-83fb-a897ae39f923)