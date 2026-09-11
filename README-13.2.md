# Desafio 13.2 — Estendendo o Comportamento do Catálogo

## 1. Visão Geral do Entregável

Este documento e Pull Request formalizam a entrega técnica do **Desafio 13.2 (Estendendo o comportamento do catálogo)** da Sprint 6 (Magento 2 / Adobe Commerce). O objetivo central foi modificar o comportamento e capturar ciclos de vida de entidades nativas do catálogo do Magento sem realizar nenhuma alteração no diretório `vendor/` (núcleo da plataforma).

A implementação apoia-se em quatro pilares arquiteturais de engenharia:

1. **Programação Orientada a Aspectos (AOP via Plugins):** Implementação de um interceptor do tipo `after` sobre o método `getName()` da classe `Magento\Catalog\Model\Product`, adicionando uma chancela de catálogo (` [Webjump Homologado]`) na camada de apresentação pública.
2. **Padrão Publish-Subscribe (Event Observers):** Mapeamento e escuta do evento `catalog_product_save_after` disparado durante o ciclo de persistência de catálogo no backend, registrando métricas estruturadas de telemetria no log da aplicação.
3. **Isolamento de Escopo de Injeção de Dependência:** Segregação estrita das configurações em `etc/frontend/di.xml` e `etc/adminhtml/events.xml`, impedindo efeitos colaterais de persistência indevida e sobrecarga desnecessária de processamento.
4. **Inviolabilidade do Core e Manutenibilidade:** Extensão do ecossistema exclusivamente a partir do módulo `Webjump_Jhonatan`, preservando a compatibilidade integral com futuras atualizações de segurança e patches da Adobe.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore completa de artefatos estruturados no módulo `src/app/code/Webjump/Jhonatan/`, evidenciando a inclusão das camadas de interceptores e observadores:

```text
src/app/code/Webjump/Jhonatan/
├── registration.php                                       # Registro do componente no ComponentRegistrar
├── etc/
│   ├── module.xml                                         # Declaração do módulo e sequência de boot
│   ├── frontend/
│   │   └── di.xml                                         # Injeção do Plugin restrito à vitrine pública
│   └── adminhtml/
│       └── events.xml                                     # Mapeamento do Observer restrito ao admin
│
├── Plugin/
│   └── ProductPlugin.php                                  # Interceptor afterGetName com blindagem defensiva
│
├── Observer/
│   └── ProductSaveAfter.php                               # Observador desacoplado injetando LoggerInterface
│
├── ViewModel/
│   └── HomeBlock.php                                      # Lógica de dados isolada (Desafio 13.1)
│
└── view/
    └── frontend/
        ├── layout/
        │   └── cms_index_index.xml                        # Orquestração de layout da Home
        ├── templates/
        │   └── home_block.phtml                           # Template de apresentação semântica
        └── web/
            └── css/
                └── home-block.css                         # Estilização modular da vitrine
```

---

## 3. Rastreabilidade dos Critérios de Aceite

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
|---|---|---|---|
| Plugin Ativo no Catálogo | Criação de `ProductPlugin` interceptando `afterGetName()` com sufixo institucional. | Concluído | Evidências 01 e 02 |
| Declaração no `di.xml` | Configuração declarada sob `etc/frontend/di.xml` vinculada a `Magento\Catalog\Model\Product`. | Concluído | Evidências 01 e 05 |
| Observer Funcionando | Classe `ProductSaveAfter` vinculada ao evento `catalog_product_save_after`. | Concluído | Evidências 03 e 04 |
| Declaração no `events.xml` | Mapeamento no arquivo `etc/adminhtml/events.xml` escutando salvamentos no admin. | Concluído | Evidências 03 e 04 |
| Telemetria e Log | Injeção de `Psr\Log\LoggerInterface` gerando log formatado em `var/log/system.log`. | Concluído | Evidência 04 |
| Inviolabilidade do Core | Nenhum arquivo ou diretório sob `vendor/` foi editado ou sobrescrito. | Concluído | Evidência 06 |
| Justificativa Arquitetural | Explicação técnica formal comparando as decisões de Plugin vs Observer. | Concluído | Seção 5 deste doc |

---

## 4. Passo a Passo do Processo de Desenvolvimento

O desenvolvimento da extensão seguiu uma esteira lógica para assegurar que a compilação do container de injeção de dependência e os eventos de ciclo de vida fossem integrados com estabilidade:

### Passo 1: Isolamento de Escopo e Declaração do Interceptor (`etc/frontend/di.xml`)

- Criou-se o arquivo de injeção de dependências estritamente no diretório `etc/frontend/`.
- Mapeou-se a classe `Magento\Catalog\Model\Product` adicionando o nó `<plugin>`, definindo o nome único `webjump_jhonatan_product_name_plugin`, apontando para `Webjump\Jhonatan\Plugin\ProductPlugin` com `sortOrder="10"`.

### Passo 2: Implementação Defensiva do Plugin (`Plugin/ProductPlugin.php`)

- Modelou-se a classe `ProductPlugin` contendo o método público `afterGetName(Product $subject, ?string $result): ?string`.
- Aplicou-se programação defensiva com tipagem estrita (`declare(strict_types=1);`):
  - **Null Safety:** Verificação de retorno vazio/nulo antes da concatenação.
  - **Idempotência:** Validação via `str_contains` para certificar que, caso o método seja chamado repetidas vezes durante a renderização da mesma página, o sufixo não seja duplicado em cascata.

### Passo 3: Mapeamento de Eventos Administrativos (`etc/adminhtml/events.xml`)

- Mapeou-se o evento de ciclo de vida `catalog_product_save_after` em `etc/adminhtml/events.xml`.
- Declarou-se o observer `webjump_jhonatan_product_save_after_observer` associado à classe `Webjump\Jhonatan\Observer\ProductSaveAfter`.

### Passo 4: Implementação do Observador com Injeção de Dependência (`Observer/ProductSaveAfter.php`)

- Criou-se a classe `ProductSaveAfter` implementando a interface contratual `Magento\Framework\Event\ObserverInterface`.
- Injetou-se no construtor o serviço `Psr\Log\LoggerInterface`, aderindo aos padrões PSR-3 de logging sem acoplamento rígido com o framework.
- No método `execute(Observer $observer)`, extraíram-se com segurança o ID numérico, SKU e Nome do produto salvo via `$observer->getEvent()->getProduct()`, gravando o log com nível INFO.

### Passo 5: Compilação de Interceptors e Purga de Cache

- No terminal do WSL2, executou-se `bin/magento setup:di:compile` para varrer os manifestos XML e gerar as classes de interceptação na pasta `generated/code/`.
- Executou-se `bin/magento cache:clean config full_page block_html` para sincronizar os esquemas e disponibilizar o novo comportamento na vitrine.

---

## 5. Decisões Arquiteturais: Por que Plugin em um caso e Observer no outro?

A escolha entre Plugins, Observers e Preferences é um dos critérios avaliativos fundamentais do Magento 2. A tabela e justificativas abaixo detalham a fundamentação técnica das decisões adotadas:

| Critério de Engenharia | Plugin (`ProductPlugin`) | Observer (`ProductSaveAfter`) |
|---|---|---|
| Intenção Primária | Modificar o resultado de uma operação em andamento. | Reagir a uma ação de negócio que já foi finalizada. |
| Acesso ao Retorno | Possui acesso direto ao argumento original e ao valor retornado. | Não intercepta o retorno; recebe apenas o payload do evento. |
| Impacto no Fluxo | Síncrono e direto na árvore de execução do método `getName()`. | Desacoplado; apenas captura a telemetria pós-persistência. |
| Acoplamento | Conectado ao contrato da classe de Produto. | 100% desacoplado via barramento de eventos do framework. |

### Justificativa da Escolha do Plugin para Alteração do Nome

Para adicionar a chancela ` [Webjump Homologado]` na vitrine, a necessidade de negócio exigia alterar o valor de retorno de um método público (`getName()`) sem reescrever a classe inteira.

- **Por que não Preference?** Uma preference no `di.xml` forçaria a substituição completa de `Magento\Catalog\Model\Product` por uma classe própria. Isso configuraria uma prática anti-padrão violando o princípio Open/Closed do SOLID, gerando incompatibilidade instantânea com outros módulos de terceiros que também precisassem interagir com o produto.
- **Por que Plugin `after`?** O método `after` foi escolhido pois não tínhamos interesse em alterar parâmetros de entrada (o que exigiria `before`) nem abortar a execução do core (o que exigiria `around`). O `after` é o interceptor mais limpo e com o menor overhead de performance da plataforma.

### Justificativa da Escolha do Observer para Telemetria de Salvamento

Para registrar que um produto foi salvo, o requisito exigia disparar uma ação lateral (auditoria em arquivo de log) sem nenhuma intenção de alterar o fluxo de gravação ou o retorno do método `save()`.

**Por que o Observer é superior neste cenário?** O evento `catalog_product_save_after` é emitido pelo Magento assim que a transação no banco MariaDB é concluída. O Observer atua como um ouvinte assíncrono conceitual: ele é executado, colhe os dados da entidade instanciada e delega à `LoggerInterface`. Se o log falhasse ou sofresse lentidão de disco, a gravação do produto no banco de dados já estaria preservada, garantindo alta coesão e baixo acoplamento.

---

## 6. Decisões de Engenharia e Troubleshooting (Prevenção de Falhas)

### 1. Prevenção de Poluição do Banco de Dados via Escopo `frontend/di.xml`

**Cenário de Risco:** Declarar o plugin no arquivo global `etc/di.xml` faria com que o interceptor rodasse em todas as áreas da plataforma (CLI, Admin e Cron). No momento em que um usuário salvasse o produto no Admin, o método `getName()` poderia ser invocado pelo processador do formulário, gravando o nome com o sufixo no MariaDB. Na próxima edição, o produto conteria o sufixo duas vezes, corrompendo a base.

**Solução Adotada:** Declaração exclusiva em `src/app/code/Webjump/Jhonatan/etc/frontend/di.xml`. O plugin é registrado unicamente na árvore de injeção da loja pública, garantindo que o Admin e o banco de dados permaneçam com a string pura original.

### 2. Tratamento Defensivo contra Duplicação em Memória (Idempotência)

**Cenário de Risco:** Em listagens complexas, breadcrumbs, menus e blocos de busca, o método `getName()` de uma mesma instância de produto pode ser executado múltiplas vezes no mesmo ciclo de renderização.

**Solução Adotada:** Implementação da guarda `if (str_contains($result, self::SUFFIX))` antes de retornar o valor concatenado. O interceptor garante que, independentemente de quantas vezes seja invocado na mesma requisição, o sufixo seja injetado apenas uma vez.

### 3. Conflitos de Portas no Host Windows após Reboot

**Cenário de Risco:** Após o reboot da máquina de desenvolvimento, serviços residentes do Windows tomaram a porta 8080 (PID 5032), impedindo a inicialização saudável do contêiner `magento-phpmyadmin-1`.

**Solução Adotada:** Interrupção preventiva das tarefas via PowerShell administrativo (`taskkill /F /PID 5032`) antes da subida da stack via `bin/start`, restabelecendo os 9 contêineres em estado íntegro.

---

## 7. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as 6 evidências formais de homologação do Desafio 13.2, auditando a renderização na vitrine, a interceptação do método, o registro no log da aplicação e o pipeline de build:

### Evidência 01 — Efeito do Plugin na Página de Detalhes do Produto (PDP)
* **Análise Visual/Técnica:** Vitrine pública acessada em `https://magento.test/mouse-gamer-pro-wireless.html`. Comprova o método `afterGetName` em plena operação: o título principal em `<h1>` e o breadcrumb estrutural renderizam a string com o sufixo ` [Webjump Homologado]`, preservando o preço, estoque e imagem intactos.

<img width="100%" alt="01-pdp-plugin-applied" src="https://github.com/user-attachments/assets/d668cdb1-b763-4d17-82cb-b3d6b2d7cd4a" />

---

### Evidência 02 — Efeito do Plugin na Página de Categoria (PLP / Listagem)
* **Análise Visual/Técnica:** Vitrine pública em `https://magento.test/perifericos.html`. Comprova que o interceptor estende o comportamento visual na listagem de produtos da categoria em grade/lista sem acionar erros de tipagem estrita.

<img width="100%" alt="02-plp-plugin-rendered" src="https://github.com/user-attachments/assets/e1c44f42-5170-490c-a47e-01070df3ccea" />

---

### Evidência 03 — Ação de Salvamento da Entidade no Admin
* **Análise Visual/Técnica:** Painel administrativo acessado em `https://magento.test/admin` no menu Catalog > Products. Ficha do produto Mouse Gamer Pro Wireless com a notificação verde de sucesso ("You saved the product."), demonstrando que o formulário de backend mantém o nome original sem contaminação pelo plugin.

<img width="100%" alt="03-admin-product-save" src="https://github.com/user-attachments/assets/a8089e87-2bdb-4d9a-89e6-48ad273a9a42" />

---

### Evidência 04 — Telemetria Registrada no Log do Sistema (Terminal CLI)
* **Análise Visual/Técnica:** Terminal do Ubuntu (WSL2) executando `grep "Webjump_Jhonatan" src/var/log/system.log`. Comprova o disparo imediato do observer `catalog_product_save_after`, registrando a mensagem estruturada contendo o timestamp, o ID da entidade (1), SKU (`MOUSE-PRO-01`) e o Nome exato do produto.

<img width="100%" alt="04-observer-log-telemetry" src="https://github.com/user-attachments/assets/b9951cb7-4f6e-4b95-9d31-160fd1ca43b7" />

---

### Evidência 05 — Integridade do Pipeline de Build (`setup:di:compile`)
* **Análise Visual/Técnica:** Terminal evidenciando o retorno com 100% de sucesso do compilador de injeção de dependências (`bin/magento setup:di:compile`). Atesta conformidade sintática, ausência de falhas nos manifestos XML e correta resolução do construtor da classe `ProductSaveAfter`.

<img width="100%" alt="05-cli-compile-success" src="https://github.com/user-attachments/assets/a6ca9f29-9880-46cc-8462-427aad00b441" />

---

### Evidência 06 — Inviolabilidade do Núcleo da Plataforma (`vendor/` Intacto)
* **Análise Visual/Técnica:** Execução de `git status` no terminal comprovando que o diretório `vendor/` permaneceu estritamente intocado, satisfazendo a regra de ouro de engenharia de software da plataforma.

<img width="100%" alt="06-vendor-untouched" src="https://github.com/user-attachments/assets/08045d92-5bea-4b68-b590-bcda6fa71d6d" />
