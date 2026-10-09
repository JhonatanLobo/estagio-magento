# Implementação Técnica Magento 2: Mensagem Assombrada no Checkout com Persistência e Exibição no Admin

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do Desafio 17.3 (Mensagem assombrada no checkout) da Sprint 8 (Magento 2 / Adobe Commerce). O objetivo principal consistiu em dotar o checkout da loja de um campo customizado onde o cliente pode escrever uma mensagem que acompanha o pedido, com validação de tamanho máximo, persistência no `quote_address`, cópia automática para `sales_order` no momento da submissão e exibição no painel administrativo — tudo isso sem alterar uma única linha do módulo `Magento_Checkout` ou de qualquer outro arquivo em `vendor/`.

A implementação apoia-se em cinco pilares fundamentais de engenharia de software corporativa:

1. **Provisionamento Declarativo de Colunas (db_schema.xml):** Adição declarativa das colunas `mensagem_assombrada` nas tabelas nativas `quote_address` e `sales_order` via merge com o schema do core, sem escrever SQL manual nem tocar em arquivos do núcleo. A whitelist é regenerada via CLI para travar a integridade estrutural.

2. **Extensão por Extension Attribute (extension_attributes.xml):** O endpoint nativo de shipping (`shipping-information`) faz bind tipado via interface `Magento\Quote\Api\Data\AddressInterface`. Campos que não estão declarados como extension attributes são descartados silenciosamente pelo desserializador do Web API. A solução canônica é declarar `mensagem_assombrada` como extension attribute da interface `AddressInterface` e enviá-lo do checkout dentro do bloco `extension_attributes`, garantindo que o valor chegue ao PHP.

3. **Interceptação Declarativa com Dois Plugins (di.xml):** O primeiro plugin (`LayoutProcessorPlugin`) injeta o campo no array de componentes do checkout via `afterProcess` do `LayoutProcessor`. O segundo (`SaveMensagemAssombradaPlugin`) intercepta o `beforeSaveAddressInformation` do `ShippingInformationManagement` para ler o extension attribute tipado e gravá-lo na coluna customizada do `quote_address`. Nenhum arquivo do `Magento_Checkout` foi alterado.

4. **Ciclo de Vida do Dado via Observer Global (events.xml):** O observer `sales_model_service_quote_submit_before` copia o valor do endereço do quote para o pedido no momento exato da submissão. Ele está no escopo global (`etc/events.xml`), o que garante disparo tanto no fluxo web do checkout quanto em cenários programáticos (CLI, API).

5. **Exibição Defensiva no Admin (ViewModel + Template + Layout):** O bloco "Mensagem Assombrada" é renderizado na tela de visualização do pedido por um ViewModel (`MensagemAssombrada`) que expõe apenas o necessário. O template só renderiza o container quando o método `hasMensagem()` retorna `true`, garantindo zero nós vazios no DOM quando o pedido não possui mensagem preenchida.

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore de artefatos estruturados exclusivamente no módulo `src/app/code/Webjump/Jhonatan/`, evidenciando a separação estrita entre plugins, observers, schema e apresentação:

```text
src/app/code/Webjump/Jhonatan/
├── etc/
│   ├── db_schema.xml                                        # Colunas mensagem_assombrada em quote_address e sales_order
│   ├── db_schema_whitelist.json                             # Whitelist atualizada com as novas colunas
│   ├── di.xml                                               # Preferences (Sprint 7) + 2 plugins do checkout (17.3)
│   ├── events.xml                                           # Observer do produto (Sprint 7) + observer do quote submit
│   └── extension_attributes.xml                             # Declara mensagem_assombrada como extension attribute
│
├── Observer/
│   ├── ProductSaveAfter.php                                 # Observer global do produto (correção da Sprint 7)
│   └── SalesModelServiceQuoteSubmitBefore.php              # Copia mensagem do quote para o order
│
├── Plugin/
│   └── Checkout/
│       ├── LayoutProcessorPlugin.php                       # Adiciona o campo no checkout via afterProcess
│       └── SaveMensagemAssombradaPlugin.php                # Persiste o extension attribute no quote_address
│
├── ViewModel/
│   └── Order/
│       └── MensagemAssombrada.php                          # Resolução defensiva do valor para o admin
│
└── view/
    └── adminhtml/
        ├── layout/
        │   └── sales_order_view.xml                         # Injeção do bloco na tela de visualização do pedido
        └── templates/
            └── order/
                └── view/
                    └── mensagem_assombrada.phtml            # Template defensivo de exibição
```

## 3. Rastreabilidade dos Critérios de Aceite

A tabela abaixo correlaciona os requisitos avaliativos da especificação do Desafio 17.3 com as soluções técnicas implementadas e as respectivas evidências de QA:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
| --- | --- | --- | --- |
| Campo aparece no checkout no passo de entrega | Injeção declarativa via `LayoutProcessorPlugin` no `shipping-address-fieldset` com componente `textarea` | **Concluído** | Evidências 03 e 04 |
| Validação de tamanho máximo funciona e mostra mensagem ao usuário | Regra `max_text_length: 200` no array do plugin, com aviso vermelho nativo do KO | **Concluído** | Evidência 05 |
| O valor é salvo e aparece na visualização do pedido no admin | Persistência via plugin + extension attribute + observer + ViewModel/Template/Layout no admin | **Concluído** | Evidências 06, 07, 09 e 10 |
| Pedido sem mensagem preenchida é concluído normalmente | Programação defensiva no ViewModel (`hasMensagem()`) e no template (condicional que evita containers vazios) | **Concluído** | Evidência 08 |
| Nenhum arquivo do módulo `Magento_Checkout` foi alterado | Arquitetura por plugins e observer global; `git status` comprova alterações restritas ao módulo | **Concluído** | Evidência 12 |
| README descreve o caminho completo do dado, da tela até o admin | Este documento — Seção 4 (passo a passo) e Seção 6 (estrutura das regras) | **Concluído** | Este README |

## 4. Passo a Passo do Processo de Desenvolvimento

A esteira de construção da funcionalidade seguiu o fluxo arquitetural recomendado pela Adobe, priorizando extensão declarativa a sobrescrita de arquivos do core.

### Passo 1: Provisionamento Declarativo das Colunas (`etc/db_schema.xml`)

- Adicionadas as colunas `mensagem_assombrada` nas tabelas `quote_address` e `sales_order` com tipo `varchar(255)`, `nullable="true"` e comentário descritivo.
- Preservada integralmente a tabela customizada `webjump_avaliacao` da Sprint 7.
- Whitelist regenerada via `bin/magento setup:db-declaration:generate-whitelist` e versionada em `etc/db_schema_whitelist.json`.

### Passo 2: Declaração do Extension Attribute (`etc/extension_attributes.xml`)

- O endpoint nativo `shipping-information` é tipado pela interface `Magento\Quote\Api\Data\AddressInterface`. O desserializador do Web API descarta silenciosamente qualquer campo do JSON que não esteja declarado na interface.
- Declarado o extension attribute `mensagem_assombrada` (tipo `string`) na interface `AddressInterface`. Esse registro permite que o valor chegue ao PHP no bloco `extension_attributes` do payload.

### Passo 3: Injeção do Campo no Checkout (`Plugin/Checkout/LayoutProcessorPlugin.php`)

- Plugin `afterProcess` no `Magento\Checkout\Block\Checkout\LayoutProcessor`.
- Navegação via referência até o fieldset `shipping-address-fieldset` no array do checkout.
- Declaração do campo com `component => Magento_Ui/js/form/element/textarea`, `dataScope => shippingAddress.extension_attributes.mensagem_assombrada` (o prefixo `extension_attributes` é obrigatório) e a regra `max_text_length: 200`.

### Passo 4: Persistência do Extension Attribute no Quote (`Plugin/Checkout/SaveMensagemAssombradaPlugin.php`)

- Plugin `beforeSaveAddressInformation` no `Magento\Checkout\Model\ShippingInformationManagement`.
- Leitura do extension attribute via `$shippingAddress->getExtensionAttributes()->getMensagemAssombrada()`.
- Escrita defensiva na coluna customizada do `quote_address` através do `CartRepositoryInterface`, com try/catch silencioso para nunca bloquear o checkout.

### Passo 5: Cópia para o Pedido no Momento da Submissão (`Observer/SalesModelServiceQuoteSubmitBefore.php`)

- Observer do evento `sales_model_service_quote_submit_before`, registrado no escopo global (`etc/events.xml`).
- O observer valida `quote`, `order` e `shippingAddress` antes de qualquer operação (null safety estrita) e copia o valor apenas se ele não estiver vazio.

### Passo 6: Exibição no Admin (`ViewModel` + `Template` + `Layout`)

- `ViewModel/Order/MensagemAssombrada.php` implementando `ArgumentInterface` — expõe `getMensagem()` e `hasMensagem()` com guarda de tipagem.
- `sales_order_view.xml` injeta o bloco no container `order_additional_info` da tela de visualização do pedido, passando o ViewModel como argumento.
- `mensagem_assombrada.phtml` renderiza o container apenas se `hasMensagem()` retornar `true`.

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: O valor não chegava ao `quote_address` (extension attribute ausente)

**Sintoma:** Após a primeira compra de teste, o valor em `sales_order.mensagem_assombrada` estava `NULL`. As queries de diagnóstico retornavam vazio em `quote_address` também.

**Causa Raiz:** O endpoint `shipping-information` do Magento é tipado pela interface `Magento\Quote\Api\Data\AddressInterface`. O desserializador do Web API descarta silenciosamente qualquer propriedade do JSON que não esteja declarada na interface como extension attribute. Como `mensagem_assombrada` não fazia parte da interface, o valor morria no caminho antes de chegar ao PHP.

**A Solução:** Foram aplicadas três correções coordenadas:

1. Criação do arquivo `etc/extension_attributes.xml` declarando `mensagem_assombrada` como extension attribute da interface `AddressInterface`.
2. Ajuste do `dataScope` no `LayoutProcessorPlugin` para enviar o valor dentro de `shippingAddress.extension_attributes.mensagem_assombrada` (o prefixo é obrigatório para que o endpoint reconheça o campo).
3. Criação do plugin `SaveMensagemAssombradaPlugin` para ler o extension attribute tipado (`getMensagemAssombrada()`) do `ShippingInformationInterface` e gravá-lo na coluna customizada do `quote_address` via `CartRepositoryInterface`.

### O Problema: Garantir que nenhum arquivo do `Magento_Checkout` fosse alterado

**Causa Raiz:** A primeira tentativa de implementação poderia sugerir sobrescrever templates ou copiar controllers do `Magento_Checkout` para o módulo. Isso violaria a regra de ouro do programa e criaria dívida técnica insustentável.

**A Solução:** Adotou-se estritamente a estratégia de **interceptação declarativa**:

- Os dois plugins (LayoutProcessor e ShippingInformationManager) são registrados apenas em `etc/di.xml` do módulo.
- O observer é registrado em `etc/events.xml` (escopo global).
- Nenhuma linha de `vendor/magento/module-checkout/` ou `app/code/Magento/Checkout` foi tocada.

Isso é comprovado pela Evidência 12, onde o `git status -uall` demonstra que todos os arquivos modificados/criados residem exclusivamente sob `src/app/code/Webjump/Jhonatan/`.

### O Problema: Garantir que pedidos sem mensagem não gerem nós vazios

**Causa Raiz:** Sem programação defensiva, o template renderizaria o `<div class="admin__page-section-item order-mensagem-assombrada">` mesmo quando o valor fosse `NULL`, criando um container visualmente vazio no admin e impactando a experiência do operador.

**A Solução:** O ViewModel `MensagemAssombrada` implementa `hasMensagem()` que retorna `false` quando `getData('mensagem_assombrada')` é `NULL` ou string vazia. O template envolve todo o container em um `if ($viewModel->hasMensagem())`. Resultado: pedidos sem mensagem não renderizam **nenhuma** tag — comprovado na Evidência 08.

## 6. Estrutura das Regras do Módulo

Abaixo estão os trechos centrais da implementação:

### Declaração do Extension Attribute (`etc/extension_attributes.xml`)

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Api/etc/extension_attributes.xsd">
    <extension_attributes for="Magento\Quote\Api\Data\AddressInterface">
        <attribute code="mensagem_assombrada" type="string"/>
    </extension_attributes>
</config>
```

### Plugin de Injeção no Checkout (`LayoutProcessorPlugin.php`)

```php
$caminho['mensagem_assombrada'] = [
    'component'  => 'Magento_Ui/js/form/element/textarea',
    'config'     => [
        'customScope' => 'shippingAddress',
        'template'    => 'ui/form/field',
        'elementTmpl' => 'ui/form/element/textarea',
        'tooltip'     => ['description' => __('Escreva uma mensagem que ira junto com o seu pedido.')],
    ],
    'dataScope'  => 'shippingAddress.extension_attributes.mensagem_assombrada',
    'label'      => __('Mensagem assombrada no pacote'),
    'provider'   => 'checkoutProvider',
    'sortOrder'  => 250,
    'validation' => ['max_text_length' => 200],
];
```

### Plugin de Persistência (`SaveMensagemAssombradaPlugin.php`)

```php
public function beforeSaveAddressInformation(
    ShippingInformationManagement $subject,
    $cartId,
    ShippingInformationInterface $addressInformation
): array {
    $shippingAddress = $addressInformation->getShippingAddress();
    if (!$shippingAddress) {
        return [$cartId, $addressInformation];
    }

    $extensionAttributes = $shippingAddress->getExtensionAttributes();
    if (!$extensionAttributes) {
        return [$cartId, $addressInformation];
    }

    $mensagem = $extensionAttributes->getMensagemAssombrada();
    if ($mensagem === null || $mensagem === '') {
        return [$cartId, $addressInformation];
    }

    try {
        $quote = $this->quoteRepository->getActive($cartId);
        $quoteShippingAddress = $quote->getShippingAddress();
        if ($quoteShippingAddress) {
            $quoteShippingAddress->setData('mensagem_assombrada', (string) $mensagem);
            $this->quoteRepository->save($quote);
        }
    } catch (\Exception $e) {
        // Silencioso para nao bloquear checkout
    }

    return [$cartId, $addressInformation];
}
```

### Observer de Cópia (`SalesModelServiceQuoteSubmitBefore.php`)

```php
public function execute(Observer $observer): void
{
    $quote = $observer->getEvent()->getQuote();
    $order = $observer->getEvent()->getOrder();

    if (!$quote || !$order) {
        return;
    }

    $shippingAddress = $quote->getShippingAddress();
    if (!$shippingAddress) {
        return;
    }

    $mensagem = $shippingAddress->getData('mensagem_assombrada');
    if ($mensagem !== null && $mensagem !== '') {
        $order->setData('mensagem_assombrada', (string) $mensagem);
    }
}
```

### ViewModel Defensivo (`MensagemAssombrada.php`)

```php
public function getMensagem(): ?string
{
    $order = $this->getOrder();
    if (!$order) {
        return null;
    }

    $mensagem = $order->getData('mensagem_assombrada');
    return ($mensagem !== null && $mensagem !== '') ? (string) $mensagem : null;
}

public function hasMensagem(): bool
{
    return $this->getMensagem() !== null;
}
```

## 7. Decisões de Engenharia Frontend: Extensão vs. Sobrescrita

**Por que usar Extension Attribute em vez de tentar adicionar o campo como atributo customizado direto?**
O endpoint `shipping-information` recebe um payload tipado por `ShippingInformationInterface` e `AddressInterface`. Sem a declaração formal do extension attribute, o desserializador do Web API descarta o campo silenciosamente (comportamento documentado no próprio core do Magento 2). A alternativa "adicionar direto no `setData`" não funciona porque o `getData` só é populado após a desserialização tipada.

**Por que dois plugins e não um só?**
O `LayoutProcessorPlugin` atua na camada de apresentação (injeção do campo no array visual do checkout). O `SaveMensagemAssombradaPlugin` atua na camada de persistência (gravação no `quote_address` após o cliente finalizar o passo). São responsabilidades distintas — separar em dois plugins mantém o código com responsabilidade única (SRP) e permite desativar um sem afetar o outro.

**Por que observar `sales_model_service_quote_submit_before` e não `checkout_submit_all_after`?**
O `sales_model_service_quote_submit_before` é disparado no ponto exato onde o quote se transforma em order — o objeto `$order` já existe em memória mas ainda não foi persistido. Isso permite setar o dado antes do `save()`, evitando uma segunda query de UPDATE. O `checkout_submit_all_after` dispararia após o save, exigindo um segundo `save()` e criando uma janela de inconsistência.

**Por que o observer está em `etc/events.xml` (raiz) e não em `etc/frontend/events.xml`?**
Seguindo a diretriz consolidada da Sprint anterior, observers devem residir no escopo global quando o disparo precisa cobrir múltiplos contextos (frontend, CLI, API). Como o desafio prevê uso futuro do módulo em cenários de API e integração, a escolha pelo escopo global é a mais segura e previne regressões.

## 8. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as 12 evidências formais de homologação, cobrindo o schema, o registro de plugins e observer, a renderização do campo no checkout, a validação client-side, a persistência no banco, a exibição no admin e a integridade do core.

### Evidência 01 — Provisionamento Declarativo das Colunas (CLI)

Consulta estrutural nas tabelas `quote_address` e `sales_order` via MariaDB, comprovando a criação da coluna `mensagem_assombrada` com tipo `varchar(255) YES NULL` nas duas tabelas nativas.

![Evidência 01](https://github.com/user-attachments/assets/ec20f75c-7af9-4372-8fe8-afa688f3d906)

### Evidência 02 — Registro do Plugin e Observer (CLI)

Leitura do `etc/di.xml` (com as preferences da Sprint 7 + os dois plugins do checkout) e do `etc/events.xml` (com o observer do produto + o observer do quote submit) via terminal, comprovando o registro declarativo em escopo global.

![Evidência 02](https://github.com/user-attachments/assets/4ce3e077-7e92-48fa-b341-40857d4a761c)

### Evidência 03 — Campo de Mensagem Vazio no Checkout

Tela do checkout (`checkout/#shipping`) exibindo o campo "Mensagem assombrada no pacote" vazio, com textarea e tooltip, posicionado entre o Phone Number e os Shipping Methods.

![Evidência 03](https://github.com/user-attachments/assets/e181cbb2-6f7a-4465-b409-63130b062abc)

### Evidência 04 — Campo Preenchido com Texto Válido

Checkout com a mensagem "Entregar antes das 18h. Cuidado com o gato preto." digitada no campo, sem qualquer aviso de validação (dentro do limite de 200 caracteres).

![Evidência 04](https://github.com/user-attachments/assets/39ec472e-f1c7-458f-b307-09fcc14789d2)

### Evidência 05 — Validação de Tamanho Máximo

Checkout com texto longo ultrapassando 200 caracteres, exibindo a mensagem de erro em vermelho `Please enter less or equal than 200 symbols.` abaixo do campo, com borda vermelha de estado inválido.

![Evidência 05](https://github.com/user-attachments/assets/ab82260a-789d-46d6-87f8-7972212d816e)

### Evidência 06 — Pedido Finalizado com Sucesso

Tela de sucesso do checkout (`checkout/onepage/success/`) exibindo o número do pedido `#000000005` após finalizar o fluxo com a mensagem preenchida e válida.

![Evidência 06](https://github.com/user-attachments/assets/05587b35-8ae0-40f0-ae34-196d62206c33)

### Evidência 07 — Bloco "Mensagem Assombrada" no Admin

Tela de visualização do pedido `#000000005` no admin (`Sales > Orders > View`) exibindo o bloco "Mensagem Assombrada" com o texto gravado, na seção de informações adicionais do pedido.

![Evidência 07](https://github.com/user-attachments/assets/ae17034f-1334-493e-a5f0-ebf0b2749590)

### Evidência 08 — Programação Defensiva: Pedido Sem Mensagem

Tela de visualização do pedido `#000000003` (com `mensagem_assombrada` nulo no banco) exibindo a ausência completa do bloco "Mensagem Assombrada", comprovando que o ViewModel + template evitam a renderização de containers vazios no DOM.

![Evidência 08](https://github.com/user-attachments/assets/2c1fbee9-7000-4583-84ca-a3df7cb8d6cd)

### Evidência 09 — Valor Persistido em `quote_address`

Consulta SQL comprovando o valor gravado na coluna customizada do endereço do quote (`address_id 21, address_type shipping, mensagem_assombrada 'Teste final 17.3. Cuidado com o gato preto.'`).

![Evidência 09](https://github.com/user-attachments/assets/f2015fed-6190-4a9f-bfc2-2a80341ae5dc)

### Evidência 10 — Valor Copiado para `sales_order`

Consulta SQL comprovando que o observer copiou o valor do `quote_address` para o `sales_order` no momento da submissão (`entity_id 4, increment_id 000000004, mensagem_assombrada 'Teste final 17.3...'`).

![Evidência 10](https://github.com/user-attachments/assets/4cbf810c-da0e-41ec-8671-06013e48cef6)

### Evidência 11 — Compilação de Injeção de Dependência 100%

Execução de `bin/magento setup:di:compile` finalizada em 100% (`Plugin list generation... 9/9 [100%]`, `Generated code and dependency injection configuration successfully.`), atestando ausência de erros de sintaxe, tipagem ou conflitos de DI nos plugins e no observer.

![Evidência 11](https://github.com/user-attachments/assets/920b4088-f63f-4203-bbad-6ca4d3e29bae)

### Evidência 12 — Inviolabilidade do Core

Retorno de `git status -uall` na branch `exercicio/17.3-mensagem-checkout-assombrada`, comprovando que todas as alterações estão estritamente contidas em `src/app/code/Webjump/Jhonatan/` (4 arquivos modificados + 7 novos), com `vendor/` e todos os módulos do core intocados.

![Evidência 12](https://github.com/user-attachments/assets/c80cd8fc-3ce0-46a4-8d02-397a09223648)