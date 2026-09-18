# Implementação Técnica Magento 2: Campo de Configuração no Admin via system.xml

## 1. Visão Geral do Entregável

Este repositório/PR formaliza a entrega do **Desafio 13.3 (Campo de configuração no admin)** — exercício extra de prioridade **DIF** — da Sprint 6 (Magento 2 / Adobe Commerce). O objetivo principal é dar autonomia ao lojista, permitindo que o texto exibido no bloco da home seja editável diretamente pelo painel administrativo, sem necessidade de alterar código ou realizar deploy.

A solução foi arquitetada estendendo o módulo `Webjump_Jhonatan` criado nos Desafios 13.1 e 13.2, adotando os seguintes pilares de engenharia:

1. **Configuração Declarativa via system.xml:** Definição de uma nova seção (tab `Webjump` → section `Configurações Webjump Jhonatan` → group `Bloco da Home`) com dois campos de texto (`section_title` e `section_subtitle`) exibidos em `Stores > Configuration`.
2. **Valores Padrão Defensivos via config.xml:** Estabelecimento de fallbacks nativos (`<default>`) que garantem renderização estável mesmo se o lojista limpar completamente os campos.
3. **Injeção de Dependência de Serviço de Configuração:** Consumo dos valores via `Magento\Framework\App\Config\ScopeConfigInterface` injetado no construtor do `HomeBlock` (ViewModel), preservando a arquitetura de camadas.
4. **Programação Defensiva no ViewModel:** Dupla camada de fallback — checagem de nulidade/string vazia no PHP e valor padrão declarado em XML — blindando a vitrine contra títulos em branco.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore de artefatos do módulo `Webjump_Jhonatan`, destacando os **3 arquivos novos/modificados** neste desafio (marcados com `★`):

```text
src/app/code/Webjump/Jhonatan/
├── registration.php                                       # Registro do módulo no ComponentRegistrar
├── etc/
│   ├── module.xml                                         # Declaração do módulo e sequência de boot
│   ├── config.xml                                  ★ NEW  # Valores padrão dos campos de configuração
│   ├── frontend/
│   │   └── di.xml                                         # Plugin (Desafio 13.2)
│   └── adminhtml/
│       ├── events.xml                                     # Observer (Desafio 13.2)
│       └── system.xml                              ★ NEW  # Seção e campos em Stores > Configuration
│
├── ViewModel/
│   └── HomeBlock.php                               ★ MOD  # ViewModel com ScopeConfigInterface injetado
│
├── Plugin/
│   └── ProductPlugin.php                                  # Plugin afterGetName (Desafio 13.2)
│
├── Observer/
│   └── ProductSaveAfter.php                               # Observer catalog_product_save_after (Desafio 13.2)
│
└── view/
    └── frontend/
        ├── layout/
        │   └── cms_index_index.xml                        # Injeção do bloco na Home (Desafio 13.1)
        ├── templates/
        │   └── home_block.phtml                           # Template de apresentação (Desafio 13.1)
        └── web/
            └── css/
                └── home-block.css                         # Estilização modular (Desafio 13.1)
```

---

## 3. Rastreabilidade dos Critérios de Aceite

A tabela abaixo correlaciona os requisitos solicitados na especificação do Desafio 13.3 com a solução técnica implementada e a respectiva evidência de QA:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
| --- | --- | --- | --- |
| **Seção em Stores > Configuration** | Nova tab `Webjump` com seção `Configurações Webjump Jhonatan` declarada em `etc/adminhtml/system.xml`. | **Concluído** | Evidências 02 e 03 |
| **Campo de Texto Editável** | Dois campos do tipo `text`: `section_title` e `section_subtitle` no grupo `home_block`. | **Concluído** | Evidências 03 e 04 |
| **Efeito no Site (após limpar cache)** | Injeção de `ScopeConfigInterface` no ViewModel e leitura dos valores via `getValue()`. | **Concluído** | Evidência 05 |
| **Padrão Definido + Fallback Seguro** | Valores padrão em `etc/config.xml` + checagem defensiva `($value !== null && $value !== '')` no PHP. | **Concluído** | Evidência 06.2 |
| **Inviolabilidade do Core** | Nenhum arquivo sob `vendor/` foi alterado ou sobrescrito. | **Concluído** | Evidência 08 |
| **Justificativa Arquitetural** | Documentação da estratégia de autonomia do lojista e camadas de fallback. | **Concluído** | Seção 7 deste doc |

---

## 4. Passo a Passo do Processo de Desenvolvimento

Para garantir escalabilidade e aderência às boas práticas do Magento 2, o desenvolvimento foi orquestrado nas seguintes etapas:

### Passo 1: Declaração da Estrutura de Configuração (`system.xml`)

- Em `etc/adminhtml/system.xml`, criou-se uma **tab nova** (`webjump`) para agrupar todas as futuras configurações do módulo, isolando-as visualmente das seções nativas do Magento.
- Dentro dessa tab, foi declarada a **section** `webjump_jhonatan` com a diretiva `<resource>Webjump_Jhonatan::config</resource>`, integrando-a ao sistema de ACL (Access Control List) nativo da plataforma.
- O grupo `home_block` e os dois campos `type="text"` foram configurados com `showInDefault`, `showInWebsite` e `showInStore` ativos, permitindo edição em qualquer escopo.

### Passo 2: Estabelecimento de Valores Padrão (`config.xml`)

- Em `etc/config.xml`, foi definido o nó `<default>` mapeando os valores iniciais dos campos.
- Este arquivo é a **primeira linha de defesa** contra renderização vazia: mesmo que o lojista nunca abra o admin, a home exibirá um conteúdo significativo.

### Passo 3: Refatoração do ViewModel (`HomeBlock.php`)

- Injetou-se `Magento\Framework\App\Config\ScopeConfigInterface` via construtor.
- Dois novos métodos (`getSectionTitle()` e `getSectionSubtitle()`) foram adicionados para ler os valores via `$this->scopeConfig->getValue(self::XML_PATH_SECTION_TITLE)`.
- Aplicou-se a **segunda camada de defesa**: verificação `($value !== null && $value !== '')` antes de retornar, com fallback em constantes privadas (`DEFAULT_TITLE` e `DEFAULT_SUBTITLE`).

### Passo 4: Ciclo de Build e Invalidação de Cache

- `bin/magento setup:upgrade` para registrar os novos arquivos XML no banco de dados (tabela `core_config_data`).
- `bin/magento setup:di:compile` para gerar os interceptores do construtor do `HomeBlock` com a nova dependência.
- `bin/magento cache:clean config layout block_html full_page` para invalidar os caches de configuração e do Full Page Cache (Redis).

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### Decisão 1: Por que `system.xml` e não um campo em `core_config_data` direto?

Embora seja possível inserir registros manualmente na tabela `core_config_data` via SQL, o padrão sênior é declarar tudo via `system.xml`. Isso garante:
- **Rastreabilidade:** o campo aparece na UI do admin de forma consistente em qualquer ambiente (dev, staging, produção).
- **ACL nativa:** a diretiva `<resource>` integra automaticamente o campo ao sistema de permissões de usuários administrativos.
- **Versionamento:** o schema fica no Git, sobrevivendo a `bin/magento setup:upgrade` e rollbacks.

### Decisão 2: Dupla camada de fallback (XML + PHP)

Foi identificado que um campo de texto vazio no admin pode gerar dois cenários indesejados:
- **Cenário A:** lojista limpa o campo e salva → `<h2>` vazio no DOM, quebrando o layout visual.
- **Cenário B:** banco de dados indisponível durante leitura → `null` retornado pelo `ScopeConfigInterface`.

A solução foi implementar fallback em duas camadas independentes:
1. **XML (`config.xml`):** valor padrão retornado automaticamente quando o campo nunca foi tocado.
2. **PHP (`HomeBlock.php`):** checagem `($value !== null && $value !== '')` com constante privada como último recurso.

Esta abordagem blinda a vitrine contra qualquer dos cenários, garantindo zero downtime visual.

### Decisão 3: Uso de constantes privadas `private const`

Optou-se por `private const` em vez de propriedades públicas ou strings literais nos métodos. Vantagens:
- **Performance:** constantes são resolvidas em compile-time.
- **Manutenibilidade:** alterar o valor padrão exige mudança em um único ponto.
- **Imutabilidade:** previne mutações acidentais em tempo de execução.

### Decisão 4: Escopo `showInDefault/Website/Store` ativo em todos os campos

O Magento permite configurar parâmetros em três escopos hierárquicos: **Default** (global), **Website** e **Store View**. Ao habilitar os três, damos flexibilidade total ao lojista:
- Um cliente B2B pode ter um título diferente do B2C se operarem em Websites distintos.
- Uma loja multilíngue pode traduzir o título por Store View sem duplicar a lógica do ViewModel.

---

## 6. Estrutura Técnica do Código

### 6.1. Declaração em `etc/adminhtml/system.xml`

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <tab id="webjump" translate="label" sortOrder="100">
            <label>Webjump</label>
        </tab>
        <section id="webjump_jhonatan" translate="label" sortOrder="10"
                 showInDefault="1" showInWebsite="1" showInStore="1">
            <label>Configurações Webjump Jhonatan</label>
            <tab>webjump</tab>
            <resource>Webjump_Jhonatan::config</resource>
            <group id="home_block" translate="label" sortOrder="10"
                   showInDefault="1" showInWebsite="1" showInStore="1">
                <label>Bloco da Home</label>
                <field id="section_title" translate="label" type="text" sortOrder="10"
                       showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Título da Seção</label>
                    <comment>Texto exibido como título no bloco de compromissos da home.</comment>
                </field>
                <field id="section_subtitle" translate="label" type="text" sortOrder="20"
                       showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Subtítulo da Seção</label>
                    <comment>Texto exibido como subtítulo no bloco de compromissos da home.</comment>
                </field>
            </group>
        </section>
    </system>
</config>
```

### 6.2. Valores Padrão em `etc/config.xml`

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/config.xsd">
    <default>
        <webjump_jhonatan>
            <home_block>
                <section_title>Compromissos de Entrega e Atendimento</section_title>
                <section_subtitle>Padrões de serviço aplicados diretamente à sua experiência de compra</section_subtitle>
            </home_block>
        </webjump_jhonatan>
    </default>
</config>
```

### 6.3. Trecho-Chave do `ViewModel/HomeBlock.php`

```php
private const XML_PATH_SECTION_TITLE = 'webjump_jhonatan/home_block/section_title';
private const XML_PATH_SECTION_SUBTITLE = 'webjump_jhonatan/home_block/section_subtitle';
private const DEFAULT_TITLE = 'Compromissos de Entrega e Atendimento';
private const DEFAULT_SUBTITLE = 'Padrões de serviço aplicados diretamente à sua experiência de compra';

public function __construct(ScopeConfigInterface $scopeConfig)
{
    $this->scopeConfig = $scopeConfig;
}

public function getSectionTitle(): string
{
    $value = $this->scopeConfig->getValue(self::XML_PATH_SECTION_TITLE);
    return ($value !== null && $value !== '') ? (string)$value : self::DEFAULT_TITLE;
}
```

---

## 7. Decisões de Modelagem: Autonomia do Lojista com Fallback

Em cumprimento ao requisito analítico do desafio, seguem as justificativas de governança adotadas:

- **Por que expor a configuração via admin em vez de hardcoded no PHP?**
- Hardcodar textos em código viola o princípio de **autonomia do lojista** — toda mudança exige um desenvolvedor, uma branch e um deploy. Ao expor via `Stores > Configuration`, o lojista ajusta o conteúdo em segundos, sem acionar equipe técnica. É o mesmo padrão dos *settings* da section no Shopify e dos *dialogs* do AEM.

- **Por que manter valores padrão em `config.xml` em vez de confiar apenas no PHP?**
- O `config.xml` é a fonte declarativa da verdade: se o lojista nunca acessou o admin, o Magento resolve o valor padrão na camada de banco sem executar código PHP extra. Isso reduz carga de processamento e mantém a intenção de negócio registrada no Git.

- **Por que dupla camada de fallback (XML + PHP)?**
- A defesa em profundidade é uma prática de engenharia sênior. O `config.xml` cobre o caso comum (valor nunca definido); o `HomeBlock.php` cobre o caso extremo (lojista limpou o campo ou banco indisponível). Juntas, elas garantem que a vitrine **nunca** exiba um título em branco, independentemente do estado do admin.

---

## 8. Relatório Detalhado de Quality Assurance (QA)

Para comprovar o funcionamento de ponta a ponta da configuração customizada e o efeito na vitrine, apresentam-se abaixo **9 evidências formais de homologação**, auditando desde a estrutura do código até o reflexo visual na home pública.

### Evidência 01 — Estrutura de Arquivos do Módulo (VS Code)

* **Análise Visual/Técnica:** Árvore de arquivos do módulo `Webjump_Jhonatan` no VS Code exibindo os 2 arquivos novos do 13.3 (`etc/config.xml` e `etc/adminhtml/system.xml`) e a estrutura herdada dos Desafios 13.1 e 13.2 (Observer, Plugin, view, registration.php). Os arquivos-chave do desafio estão todos visíveis simultaneamente.

<img width="40%" alt="evidencia-01-estrutura-modulo" src="https://github.com/user-attachments/assets/8f28b420-b3a7-4469-8296-c9660e6e6c09" />

---

### Evidência 02 — Nova Tab Webjump em Stores > Configuration

* **Análise Visual/Técnica:** Menu lateral esquerdo da tela de configuração exibindo a nova aba `WEBJUMP` entre `GENERAL` e `SECURITY`, comprovando o registro bem-sucedido da tab via `system.xml`. URL do admin visível na barra de endereços.

<img width="100%" alt="evidencia-02-tab-webjump-admin" src="https://github.com/user-attachments/assets/13531c54-48f5-4795-8748-695f4c02904f" />

---

### Evidência 03 — Campos de Texto com Valores Padrão

* **Análise Visual/Técnica:** Seção `Configurações Webjump Jhonatan > Bloco da Home` exibindo os dois campos (`Título da Seção` e `Subtítulo da Seção`) preenchidos com os valores padrão herdados do `etc/config.xml`: `Compromissos de Entrega e Atendimento` e `Padrões de serviço aplicados diretamente à sua experiência de compra`. URL contendo `/section/webjump_jhonatan/`.

<img width="100%" alt="evidencia-03-campos-padrao-admin" src="https://github.com/user-attachments/assets/15e63ae3-9dec-462d-92b6-74b8836e9028" />

---

### Evidência 04 — Campos Preenchidos com Valores Personalizados e Salvos

* **Análise Visual/Técnica:** Mesma tela da Evidência 03 após alterar os campos para `Nossos Compromissos` e `Qualidade e confiança em cada compra`. A notificação verde `You saved the configuration.` comprova o salvamento no banco `core_config_data`.

<img width="100%" alt="evidencia-04-campos-salvos-admin" src="https://github.com/user-attachments/assets/72735712-67fb-4b29-b332-ea28cf3883f6" />

---

### Evidência 05 — Reflexo dos Valores na Home Pública

* **Análise Visual/Técnica:** Vitrine acessada em `https://magento.test` exibindo o bloco de compromissos com o título `Nossos Compromissos` e subtítulo `Qualidade e confiança em cada compra`, validando a leitura do `ScopeConfigInterface` em tempo de renderização. Os 4 cards com ícones permanecem intactos.

<img width="100%" alt="evidencia-05-home-valores-personalizados" src="https://github.com/user-attachments/assets/4f8af48a-e815-4692-921a-ac5697083843" />

---

### Evidência 06.1 — Fallback: Campo de Título Esvaziado no Admin

* **Análise Visual/Técnica:** Painel administrativo com o campo **Título da Seção** propositalmente esvaziado e salvo. A notificação verde `You saved the configuration.` comprova a persistência do valor vazio no banco, acionando o fallback nas camadas inferiores.

<img width="100%" alt="evidencia-06-1-fallback-admin-campo-vazio" src="https://github.com/user-attachments/assets/58ef09fd-a8c0-44a3-906b-a81179faf213" />

---

### Evidência 06.2 — Fallback: Valor Padrão Restaurado na Home Pública

* **Análise Visual/Técnica:** Vitrine acessada em `https://magento.test` após a limpeza de cache via `bin/magento cache:clean config full_page`. O bloco exibe o título `Compromissos de Entrega e Atendimento`, comprovando que o fallback declarado em `etc/config.xml` (camada XML) e a constante `DEFAULT_TITLE` do `HomeBlock.php` (camada PHP) atuaram corretamente, garantindo que a vitrine nunca renderize um título em branco.

<img width="100%" alt="evidencia-06-2-fallback-home-valor-padrao" src="https://github.com/user-attachments/assets/d22f4773-d45c-4e9c-9080-be536b4ae4c4" />

---

### Evidência 07 — Pipeline de Compilação e Invalidação de Cache via CLI

* **Análise Visual/Técnica:** Terminal Ubuntu (WSL2) comprovando a execução sequencial dos três comandos essenciais do ciclo de build:
  1. **`bin/magento setup:upgrade`** — final da execução evidenciando `Module 'Webjump_Jhonatan':` nas listas de data-post-updates.
  2. **`bin/magento setup:di:compile`** — com `Plugin list generation... 9/9 [100%]` e a mensagem `Generated code and dependency injection configuration successfully.`.
  3. **`bin/magento cache:clean config layout block_html full_page`** — limpeza dos 4 tipos de cache essenciais.

<img width="100%" alt="evidencia-07-pipeline-cli" src="https://github.com/user-attachments/assets/86942ff2-8f1d-45f8-bf7a-737eda732ecc" />

---

### Evidência 08 — Inviolabilidade do Núcleo (`vendor/` Intacto)

* **Análise Visual/Técnica:** Saída de `git status` no terminal comprovando que apenas os 3 arquivos do módulo `Webjump_Jhonatan` foram modificados/criados (`ViewModel/HomeBlock.php` modificado, `etc/adminhtml/system.xml` e `etc/config.xml` novos). Nenhuma menção a arquivos sob `vendor/` ou `src/vendor/`, atestando que o núcleo permaneceu 100% intacto.

<img width="100%" alt="evidencia-08-vendor-untouched" src="https://github.com/user-attachments/assets/96bbe960-8006-47fb-b3cc-45aea076b74d" />