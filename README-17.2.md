# Implementação Técnica Magento 2: Modo Assombrado e Minicart Temático

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do **Desafio 17.2 (Modo Assombrado e Minicart)** da Sprint 8 (Magento 2 / Adobe Commerce). O objetivo central consistiu em introduzir duas frentes de comportamento reativo no tema **Noite Assombrada**:

1. **Modo Assombrado (Dark Mode Profundo):** Um interruptor no cabeçalho que aplica uma versão ainda mais escura do tema, com persistência via `localStorage` do navegador, sobrevivendo à navegação entre páginas.
2. **Minicart com Linguagem da Campanha:** Alteração do componente `Magento_Checkout/js/view/minicart` **via mixin** (não por substituição), adicionando um `ko.computed` cuja mensagem reage à quantidade de itens no carrinho.

A implementação apoia-se em cinco pilares fundamentais de engenharia de software:

1. **Arquitetura de Mixin (extensão não-destrutiva):** O minicart é alterado via `requirejs-config.js → config.mixins`, preservando `this._super()` e todo o comportamento original do componente do core (`Magento_Checkout/js/view/minicart`). Nada de `map` — a escolha evita assumir a manutenção integral do arquivo nativo.
2. **Carregamento Global via `deps`:** O componente `modo-assombrado.js` é declarado na seção `deps` do `requirejs-config.js`, garantindo execução em **toda página** da loja sem exigir `data-mage-init` local.
3. **Persistência Resiliente com Fallback:** O modo assombrado usa `localStorage` com verificação defensiva de disponibilidade (`try/catch`), degradando suavemente em navegadores com armazenamento restrito sem gerar erro.
4. **Reatividade sem Regra Especial:** O `ko.computed` da mensagem do minicart responde **apenas à quantidade de itens** (0 → vazio; N > 0 → "Itens no caldeirão: N"), sem regras para números específicos, garantindo neutralidade institucional e aderência literal ao critério de aceite.
5. **Theming Integrado em Todos os Estados:** Botão do interruptor com estados isolados (`aria-pressed`), modal de remoção do carrinho estilizado em roxo escuro, e minicart totalmente integrado ao Design System do tema.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

```text
src/app/code/Webjump/Jhonatan/
└── view/
    └── frontend/
        ├── layout/
        │   └── default.xml                              # Injeção do interruptor no header.panel
        ├── templates/
        │   └── modo-assombrado.phtml                    # Template acessível do botão
        ├── requirejs-config.js                          # deps + config.mixins
        └── web/
            └── js/
                ├── modo-assombrado.js                   # Componente de persistência e toggle
                └── view/
                    └── minicart-mixin.js                # Mixin reativo do minicart

src/app/design/frontend/Webjump/noite-assombrada/
├── Magento_Checkout/
│   └── web/
│       └── template/
│           └── minicart/
│               └── content.html                       # Cópia do core + 1 linha injetada
└── web/
    └── css/
        └── source/
            └── _extend.less                             # Estado ativo + toggle + minicart + modais
```

**Cadeia de dependência em runtime:**

```text
[ Requisição HTTP ]
       │
       ▼
┌──────────────────────────────────────────────────────┐
│ 1. requirejs-config.js                               │
│    ├── deps: [modo-assombrado]  ── carrega sempre    │
│    └── config.mixins: { minicart → minicart-mixin }  │
└──────────────────────┬───────────────────────────────┘
                       │
       ┌───────────────┴───────────────┐
       ▼                               ▼
┌─────────────────────┐    ┌─────────────────────────┐
│ 2. modo-assombrado  │    │ 3. minicart-mixin       │
│  ├── localStorage   │    │  ├── this._super()      │
│  ├── body class     │    │  └── ko.computed        │
│  └── toggle click   │    │      (mensagem)         │
└─────────────────────┘    └─────────────────────────┘
```

---

## 3. Rastreabilidade dos Critérios de Aceite

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidência |
|---|---|---|---|
| **Modo liga e desliga com troca de classes no HTML** | Classe `modo-assombrado-ativo` aplicada no `<body>` via `$('body').addClass/removeClass()` — sem recarregar página | Concluído | 01, 03 |
| **Escolha persiste entre páginas** | Persistência em `localStorage` sob a chave `modo_assombrado`; leitura no `init()` de toda requisição | Concluído | 02, 04 |
| **Componente carregado em toda página pela seção `deps`** | `requirejs-config.js` na raiz de `view/frontend/`, com `modo-assombrado` na seção `deps` | Concluído | 10 |
| **Minicart alterado por mixin, com `this._super()` preservado** | `config.mixins` mapeia `Magento_Checkout/js/view/minicart` para `minicart-mixin.js`; `_super()` invocado no início do `initialize()` | Concluído | 11 |
| **Mensagem do minicart muda conforme quantidade** | `ko.computed` que reage a `summary_count`: 0 → vazio; N > 0 → "Itens no caldeirão: N" | Concluído | 05, 06, 07, 08 |
| **Carrinho continua funcionando (adicionar, remover, atualizar)** | Mixin estende sem substituir; nenhum método nativo foi sobrescrito | Concluído | 06, 08 |
| **Interruptor acessível e com contraste em todos os estados** | Botão com `aria-pressed` dinâmico, `:focus-visible` para navegação por teclado, estados OFF/ON com paleta consistente | Concluído | 01, 03 |
| **Console limpo em toda navegação** | Zero erros JS em Home, Catálogo, PDP e Carrinho | Concluído | 09 |
| **Inviolabilidade do core** | Nenhuma alteração em `vendor/` ou em `Magento/luma`; somente extensão via tema e módulo próprios | Concluído | 12 |

---

## 4. Passo a Passo do Processo de Desenvolvimento

**Passo 1: Interruptor no Cabeçalho via Layout XML**

Criou-se `view/frontend/layout/default.xml` injetando um bloco `Magento\Framework\View\Element\Template` no container `header.panel`, com o template `Webjump_Jhonatan::modo-assombrado.phtml`, posicionado antes dos links de conta (atributo `before="-"`).

**Passo 2: Template Acessível do Interruptor**

O template `modo-assombrado.phtml` implementa um `<button type="button">` com `aria-pressed` inicial `false` e `title` traduzível. O ícone 🌙 recebe `aria-hidden="true"` para não poluir a árvore de acessibilidade.

**Passo 3: Componente JavaScript do Modo Assombrado**

O arquivo `modo-assombrado.js`:
- Verifica disponibilidade de `localStorage` com `try/catch` (fallback gracioso)
- Aplica/remove a classe `modo-assombrado-ativo` no `<body>` no carregamento
- Registra o clique de forma **delegada** (`$(document).on('click', ...)`), garantindo que o botão funcione mesmo se o header for carregado assincronamente pelo FPC
- Atualiza `aria-pressed` e o label do botão conforme o estado

**Passo 4: Mixin do Minicart**

O arquivo `minicart-mixin.js` recebe o componente original como parâmetro e usa `miniCartOriginal.extend({...})`. No `initialize()`:
- Chama `this._super()` antes de qualquer coisa (garante que todo o minicart nativo é inicializado)
- Declara `mensagemAssombrada = ko.computed(...)` que lê `getCartParam('summary_count')` com `try/catch` defensivo

**Passo 5: Registro no requirejs-config.js**

Seção `deps` para carregar o interruptor globalmente e seção `config.mixins` para vincular o mixin ao componente do minicart.

**Passo 6: Cópia do Template do Minicart**

`content.html` do core foi copiado integralmente para o tema filho. Uma única linha foi acrescentada dentro de `.block-content`:

```html
<div class="mensagem-assombrada"
     data-bind="text: mensagemAssombrada"
     role="status"
     aria-live="polite"></div>
```

**Passo 7: Estilização em `_extend.less`**

Bloco adicional com:
- Estado ativo do `<body>` (fundo `#0d0714`, header e footer mais escuros)
- Botão do interruptor com estados isolados via `aria-pressed` e `:focus-visible`
- Minicart com fundo roxo escuro, tipografia clara e CTA laranja
- Modais e confirm popups com fundo roxo escuro (resolve o texto invisível)

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Cor do Texto se Perdia no Hover do Botão Ativo

**Sintoma:** Após clicar no botão do modo assombrado, ele ficava preso em verde (mesmo sem hover) e, quando o usuário passava o mouse, o texto ficava invisível (cinza esverdeado sobre fundo verde).

**Causa Raiz:** Três problemas combinados:
1. Uso de `&:hover, &:focus` no mesmo bloco — o `:focus` permanecia ativo após o clique (bug clássico do Chromium)
2. `color` verde aplicado globalmente no seletor pai vazava para o `.modo-assombrado__label` e o ícone
3. Ausência de `color: inherit !important` nos filhos do botão

**A Solução:**
1. Substituiu-se `:focus` por `:focus-visible` (só ativa em navegação por teclado)
2. Cada estado (OFF/ON) foi isolado pelo atributo `aria-pressed`
3. Filhos receberam `color: inherit !important`, garantindo que a cor do texto sempre vem do estado do botão pai

### O Problema: Modal de Remoção Ficava com Fundo Branco e Texto Invisível

**Sintoma:** Ao remover um item do carrinho, o confirm popup nativo do Magento (`Magento_Ui`) exibia fundo branco com texto claro — ilegível.

**Causa Raiz:** O tema sobrescreve `@text__color` para branco gelo (`#EDE7F6`) globalmente. O modal do core tem `background-color: #fff` embutido, resultando em texto claro sobre fundo claro.

**A Solução:** Bloco LESS dedicado estilizando `.modal-popup` e `.modal-slide` com fundo roxo noite, bordas sutis, título em laranja, botões primário e secundário padronizados.

### O Problema: Novo Mixin Não Refletia no Navegador (Cache do RequireJS)

**Sintoma:** Após editar `minicart-mixin.js`, o comportamento antigo continuava aparecendo no navegador mesmo com hard refresh.

**Causa Raiz:** O `requirejs-config.js` gera uma URL versionada (`minicart-mixin.js?v=xxx`) baseada em hash. Como o `requirejs-config.js` não mudou, o navegador continuou servindo a versão antiga do arquivo do cache HTTP.

**A Solução:**
1. Purga total do `pub/static` + recompilação via `setup:static-content:deploy`
2. `localStorage.clear()` no console para limpar o cache interno do RequireJS
3. Nova janela anônima + **Disable cache** ativado no DevTools

### Decisão de UX: Mensagem do Minicart Estritamente Proporcional

Durante o desenvolvimento, avaliou-se criar uma mensagem especial ao atingir 13 itens como referência temática ao número supersticioso. A ideia foi descartada por dois motivos:

1. **Aderência literal ao critério:** O desafio pede apenas que "a mensagem mude conforme a quantidade", sem exigir regras de borda específicas.
2. **Neutralidade institucional:** Em ambiente corporativo brasileiro, o número 13 carrega associações externas ao contexto da campanha. Optou-se por manter a mensagem estritamente proporcional, evitando qualquer interpretação fora do escopo temático.

A lógica final é:

```javascript
if (qtd === 0) {
    return $t('Seu caldeirao esta vazio');
}
return $t('Itens no caldeirao: ') + qtd;
```

---

## 6. Engenharia Frontend: Mixin vs. Map, e Gestão de Estado

**Por que mixin e não map?** O `map` do RequireJS substitui a implementação inteira: o componente original deixa de existir. Com mixin, o core continua rodando e apenas interceptamos o que precisa ser alterado. É o equivalente exato ao `preference` vs `plugin` do PHP — a recomendação é sempre interceptar antes de substituir.

**Por que `deps` no requirejs-config em vez de `data-mage-init` no template?** O `data-mage-init` é escopado a um elemento específico. Como o interruptor do modo assombrado precisa estar disponível em **toda página** (Home, PLP, PDP, Checkout, Carrinho), a seção `deps` é a abordagem correta — o componente é carregado incondicionalmente assim que o RequireJS é inicializado.

**Por que `localStorage` e não cookie?** `localStorage` não é enviado em cada requisição HTTP (economia de banda), tem API síncrona simples e escopo por origem — ideal para preferências de UI. Cookies seriam apropriados para preferências que o servidor precisa ler (o que não é o caso: o tema escuro é decidido no cliente).

---

## 7. Relatório Detalhado de Quality Assurance (QA)

### Evidência 01 — Modo Assombrado Ativo na Home

**Análise Visual:** Home Page com o **modo assombrado ligado** — fundo quase preto (`#0d0714`), botão "MODO NORMAL" em laranja sólido, cabeçalho com borda verde-bruxa e tipografia Creepster no título. Comprova que a classe `modo-assombrado-ativo` foi aplicada no `<body>`.

![Evidência 01](https://github.com/user-attachments/assets/c1717bbe-dc94-4e96-b17c-03c4e65e14d8)

### Evidência 02 — Persistência entre Páginas (Catálogo)

**Análise Visual:** Página `/perifericos.html` com o **modo assombrado mantido** após a navegação. Prova que o `localStorage` está sendo lido corretamente na inicialização de cada nova requisição e que o componente carregado via `deps` executa em qualquer rota.

![Evidência 02](https://github.com/user-attachments/assets/4a6b474e-45cc-4739-b2ae-9808b00d36e9)

### Evidência 03 — Modo Assombrado Desativado

**Análise Visual:** Mesma página com o modo **desligado** — botão "MODO ASSOMBRADO" com borda laranja (não mais fundo sólido) e fundo da loja retornando ao roxo noite padrão (`#2A1B3D`). Comprova o ciclo completo do toggle.

![Evidência 03](https://github.com/user-attachments/assets/880ebd4f-e66d-45ef-8af2-070a547c925f)

### Evidência 04 — LocalStorage Inspecionado no DevTools

**Análise Visual:** DevTools na aba **Application** → **Local Storage → https://magento.test**, exibindo a chave `modo_assombrado` com valor `1` (modo ativo). Comprova a persistência real do estado no navegador.

![Evidência 04](https://github.com/user-attachments/assets/ad8bb919-6ad1-4e7c-854a-ae37e9af735a)

### Evidência 05 — Minicart Vazio

**Análise Visual:** Minicart com fundo roxo escuro e a mensagem **"Seu caldeirão está vazio"** em verde-bruxa. Comprova o estado `qtd === 0` do `ko.computed`.

![Evidência 05](https://github.com/user-attachments/assets/c320759b-08e7-4eff-a4fe-0bb66cb291f8)

### Evidência 06 — Binding do Knockout Resolvido no DOM (DevTools Elements)

**Análise Visual:** Painel **Elements** do DevTools destacando o elemento `<div class="mensagem-assombrada" data-bind="text: mensagemAssombrada" role="status" aria-live="polite">Itens no caldeirão: 1</div>` expandido dentro de `.block-content` do minicart. Comprova que o mixin injetou a marcação no template do tema filho e que o Knockout processou o binding, injetando o texto resolvido. Os atributos `role="status"` e `aria-live="polite"` garantem que leitores de tela anunciem a mudança da mensagem.

![Evidência 06](https://github.com/user-attachments/assets/3432152a-93d9-4360-a8cc-7a2a4becfaf0)

### Evidência 07 — Binding via Console (`innerText`)

**Análise Visual:** Console do DevTools com o comando `document.querySelector('.mensagem-assombrada').innerText` retornando `'Itens no caldeirão: 1'`. Prova **programática** (não apenas visual) de que o elemento foi injetado no DOM, o binding foi processado e o `ko.computed` retornou a string formatada corretamente. O painel lateral do Console confirma `No errors` / `No warnings`.

![Evidência 07](https://github.com/user-attachments/assets/cec21d4a-29af-4105-a7ab-26df7442e7e7)

### Evidência 08 — Minicart com 5 Itens

**Análise Visual:** Mensagem **"Itens no caldeirão: 5"** + subtotal de US$ 1.499,50. Comprova a reatividade em quantidade arbitrária, sem regra especial — confirma que o `ko.computed` reage a qualquer valor de `summary_count`.

![Evidência 08](https://github.com/user-attachments/assets/87a6cbb7-ddbb-47ce-92a2-00dfd0fd15c3)

### Evidência 09 — Console Sem Erros Após Navegação Completa

**Análise Visual:** DevTools aberto na aba Console com filtro `-error` aplicado, indicando **`No errors`** no painel lateral, após navegação por Home → Catálogo → PDP → Carrinho. Os `7 warnings` em amarelo são referentes a `preload` de assets do próprio Magento (tolerados pela Adobe).

![Evidência 09](https://github.com/user-attachments/assets/4baa2d54-086e-466a-a13e-e9852c2fbf63)

### Evidência 10 — Código do requirejs-config.js (CLI)

**Análise Visual:** Terminal exibindo o `requirejs-config.js` com as seções `deps` (carregamento global do `modo-assombrado`) e `config.mixins` (vínculo do mixin ao minicart). Evidência documental das duas decisões arquiteturais centrais.

![Evidência 10](https://github.com/user-attachments/assets/8ee11e66-a563-448b-97d5-2da99db637c1)

### Evidência 11 — Código do minicart-mixin.js (CLI)

**Análise Visual:** Terminal exibindo o mixin com `miniCartOriginal.extend()`, `this._super()` preservado e o `ko.computed` da mensagem assombrada. Comprova a estratégia de extensão não-destrutiva.

![Evidência 11](https://github.com/user-attachments/assets/541028f8-7a06-40c0-ab6c-06eb96c68cb5)

### Evidência 12 — Compilação de DI e Integridade do Core (CLI)

**Análise Visual:** Terminal exibindo `setup:di:compile` finalizado em 100% (34s) e o `git status -uall` com: 1 arquivo modificado (`_extend.less`) e 6 arquivos novos sob `src/app/`. Nenhuma alteração em `vendor/`, cumprindo a regra de ouro do programa.

![Evidência 12](https://github.com/user-attachments/assets/b3222ae5-6b6f-49b1-80bf-41caffa8d76c)
