# PRD - Wrapper PzPdf (FPDF) no repositório fpdf

## 1. Visão Geral

### 1.1 Contexto
A biblioteca de referência `PzPdf` no projeto `api` (`/home/ljlim/code/puzl/api/app/Library/PzPdf`) entrega geração de PDF com API fluente, apoiada em **FPDF** (pacote `fawno/fpdf`) e em componentes auxiliares (tabelas, listas, fontes TTF, gradientes, transparência, entre outros). Essa implementação **não será alterada**: serve apenas como especificação comportamental e de cobertura de testes.

Este produto é uma **nova biblioteca**, desenvolvida **do zero na raiz do repositório** `/home/ljlim/code/puzl/fpdf/`, com o objetivo de reduzir **complexidade cognitiva e custo de manutenção**, preservando **paridade funcional** e **isolamento entre instâncias** para uso em **jobs concorrentes** e requisições síncronas.

### 1.2 Problema
A arquitetura atual da referência introduz camadas (interfaces, adapter, contratos) que aumentam a carga mental para leitura e evolução, sem agregar requisito de negócio de múltiplos motores de PDF. O time de backend precisa de um **wrapper direto sobre o FPDF** e bibliotecas satélites já adotadas, com código **linear e fácil de navegar**, sem sacrificar recursos nem confiabilidade em produção.

### 1.3 Solução Proposta
Implementar uma biblioteca PHP 8.2 no repositório `fpdf`, composta por uma **API pública coesa** centrada em um wrapper acoplado ao **FPDF** (sem padrão adapter nem interfaces para troca de motor). A funcionalidade deve ser **equivalente à da referência** no que tange operações expostas e comportamento verificado pelos testes. **Todas** as classes, enums e demais tipos públicos da biblioteca, bem como **nomes de arquivos** correspondentes, devem usar o **prefixo `PzPdf`** para identificação imediata no código.

**Decisões complementares:** (8) **Não** usar um único arquivo gigante: uma **fachada pública** (`PzPdf*`) + auxiliares públicos só quando ajudarem (builders, VOs), todos com prefixo `PzPdf`; particionar com `private`/`final` interno, **sem** adapter/interface de motor. (12–13) Portar a suíte de `api/tests/Unit/Library/PzPdf` como base; **obrigatório** testar concorrência; snapshot/hash de PDF **opcional** se estável. Cobertura **≥ referência**; todos os testes portados verdes; novos testes em lacunas (ex.: paralelismo).

---

## 2. Objetivos

### 2.1 Objetivos de Negócio
- Reduzir tempo de onboarding e manutenção da biblioteca de PDF no ecossistema Puzl.
- Manter confiabilidade da geração de PDF em cenários de alta concorrência (filas, APIs).

### 2.2 Objetivos do Usuário
- Desenvolvedores backend conseguem gerar PDFs nos mesmos fluxos atuais (job assíncrono, resposta REST síncrona, gravação em disco) usando a nova biblioteca.
- Código da biblioteca é fácil de ler, estender e depurar, sem obrigatoriedade de navegar por hierarquias de adapter/interface.

### 2.3 Critérios de Sucesso
- Cobertura de testes **equivalente ou superior** à da suíte de referência em `api`, após porte para o repositório `fpdf`.
- **Zero regressões** relativas aos comportamentos cobertos pelos testes portados (todos passando).
- Evidência automatizada de **uso seguro em paralelo** (testes de concorrência/isolamento conforme RF-09).

---

## 3. Requisitos Funcionais

### RF-01: Paridade funcional com a biblioteca de referência
**Descrição**: Capacidades equivalentes à referência `app/Library/PzPdf`: documento fluente, páginas, texto, imagens, vetorial/caminhos se existir, cores, gradiente/alpha, fontes, tabela/lista, template writer, TTF/cache, tipo de imagem, saída, permissões/metadados de exibição se houver, exceções semanticamente alinhadas.

**Critérios de Aceitação**:
- [ ] Auditoria referência × implementação × testes portados concluída sem lacunas não justificadas.

**Prioridade**: Alta

### RF-02: Acoplamento direto ao FPDF
**Descrição**: O motor de renderização deve ser o **FPDF** (distribuição em uso na referência, ex.: `fawno/fpdf`), utilizado **diretamente** pelo wrapper, sem camada de adapter para troca de implementação.

**Critérios de Aceitação**:
- [ ] Não existem interfaces públicas cujo propósito seja permitir “outro motor de PDF” além do FPDF.
- [ ] Dependências satélites necessárias à paridade (ex.: bibliotecas usadas na referência para SVG/caminhos, se mantidas) estão declaradas e versionadas no `composer.json` do projeto `fpdf`.

**Prioridade**: Alta

### RF-03: Simplicidade estrutural (sem adapter pattern exposto)
**Descrição**: Eliminar o padrão **adapter** e **contratos** voltados a múltiplas implementações na superfície pública da biblioteca. A organização interna prioriza legibilidade e poucos saltos indiretos.

**Critérios de Aceitação**:
- [ ] Superfície pública não expõe `*Adapter*` nem interfaces de “motor PDF”.
- [ ] Composição interna (métodos privados, classes `final` auxiliares) é permitida para evitar arquivos monolíticos inviáveis.

**Prioridade**: Alta

### RF-04: Convenção de nomenclatura `PzPdf` em arquivos e tipos
**Descrição**: Todo arquivo PHP da biblioteca e todo nome de classe, enum, exceção e value object **públicos** deve iniciar com o prefixo **`PzPdf`** (ex.: `PzPdf.php`, `PzPdfTableBuilder.php`, `PzPdfOutputMode.php`). O nome do arquivo deve refletir o nome da classe principal que contém, salvo convenção PSR-4 do projeto.

**Critérios de Aceitação**:
- [ ] Revisão automatizada ou checklist de PR garante aderência ao prefixo em novos arquivos.
- [ ] Não há tipos públicos da biblioteca sem o prefixo `PzPdf`.

**Prioridade**: Alta

### RF-05: Fábrica ou construtor de instâncias isoladas
**Descrição**: Deve existir forma clara de criar **nova instância** do documento/wrapper por uso (análogo ao papel da factory na referência), sem estado compartilhado indevido entre gerações.

**Critérios de Aceitação**:
- [ ] Documentação de uso (mínima: README ou docblocks da entrada pública) descreve como obter instâncias isoladas.
- [ ] Testes demonstram múltiplas instâncias independentes.

**Prioridade**: Alta

### RF-06: Paridade dos componentes e serviços de suporte
**Descrição**: Tabelas/colunas, listas, template writer, TTF, imagem e demais serviços da referência com comportamento validado pelos testes portados.

**Critérios de Aceitação**:
- [ ] Cenários portados equivalentes; exceções/mensagens compatíveis onde os testes exigirem.

**Prioridade**: Alta

### RF-07: Fluxos de saída e integração com aplicação
**Descrição**: Deve ser possível obter o PDF nos mesmos modos previstos pela referência (ex.: string na memória, envio HTTP, escrita em disco), preservando as funções necessárias aos fluxos: job assíncrono, resposta REST síncrona e persistência em arquivo.

**Critérios de Aceitação**:
- [ ] Cada modo de saída usado na referência possui teste ou exemplo coberto na nova suíte.
- [ ] Não há dependência de estado global da biblioteca para completar uma geração.

**Prioridade**: Alta

### RF-08: Concorrência e isolamento
**Descrição**: Vários workers ou requisições podem usar a biblioteca **simultaneamente**. Instâncias distintas não podem corromper saída umas das outras por estado compartilhado mutável (incluindo cache de fontes ou resolvers, se existirem).

**Critérios de Aceitação**:
- [ ] Existem testes automatizados que exercitam geração **concorrente** ou **paralela** (ex.: `pcntl`/processos ou paralelização suportada pelo runner), com validação de integridade das saídas.
- [ ] Documentação de limitações, se houver (ex.: diretório de cache compartilhado com locking), está na seção de riscos ou dependências.

**Prioridade**: Alta

### RF-09: Localização e autonomia do repositório `fpdf`
**Descrição**: Todo o código da nova biblioteca e seus testes residem sob `/home/ljlim/code/puzl/fpdf/`, com autoload e dependências próprias. **Nenhum** arquivo em `/home/ljlim/code/puzl/api/` é modificado por este trabalho.

**Critérios de Aceitação**:
- [ ] Estrutura de pastas e `composer.json` no `fpdf` permitem executar a suíte de testes localmente.
- [ ] Não há alterações em `api` atribuíveis a este entregável.

**Prioridade**: Alta

### RF-10: Entrega big bang
**Descrição**: A entrega é única: biblioteca completa no `fpdf`, sem fase intermediária de convivência obrigatória com código antigo dentro do mesmo pacote (o legado permanece intocado no `api`).

**Critérios de Aceitação**:
- [ ] Checklist de paridade e testes fechado em uma única baseline de release interna da biblioteca `fpdf`.

**Prioridade**: Média

---

## 4. Requisitos Não-Funcionais

### RNF-01: Manutenibilidade e legibilidade
**Descrição**: Preferir módulos pequenos e nomes explícitos; evitar indireções desnecessárias. Código aderente ao **PSR-12** e compatível com **PHP 8.2**.

### RNF-02: Testabilidade
**Descrição**: A biblioteca deve ser testável com PHPUnit (ou ferramenta adotada no `fpdf`), com suíte espelhando a referência e extensões para concorrência.

### RNF-03: Desempenho
**Descrição**: Sem regressão material vs. referência onde houver benchmark; caso contrário, evitar I/O síncrono desnecessário em caminhos quentes.

### RNF-04: Qualidade do PDF gerado
**Descrição**: Manter legibilidade/encoding/fontes no nível da referência. Sem UI de produto neste PRD.

---

## 5. Casos de Uso

### 5.1 Caso de Uso Principal
**Ator**: Desenvolvedor backend

**Fluxo Principal**:
1. Instancia o wrapper `PzPdf*` (ou fábrica equivalente) para um novo documento.
2. Configura página, fontes e conteúdo com a API fluente.
3. Obtém bytes ou arquivo PDF conforme o fluxo (job, HTTP ou disco).

**Fluxo Alternativo**:
- Reutilização de builders de tabela/lista para montar blocos repetitivos.

**Fluxo de Exceção**:
- Entradas inválidas ou falhas de IO disparam exceções da família `PzPdf*`, tratáveis pela aplicação.

### 5.2 Caso de Uso Secundário
**Ator**: Sistema de filas (múltiplos workers)

**Fluxo Principal**:
1. Cada job cria sua própria instância do wrapper.
2. Gera o PDF em paralelo com outros jobs.
3. Persiste ou envia o resultado sem corrupção cruzada.

**Fluxo de Exceção**:
- Falha em recurso compartilhado (ex.: diretório de cache): deve ser tratada com erro claro e, quando possível, isolada ao job.

---

## 6. Regras de Negócio

### RN-01: Paridade como fonte da verdade
O comportamento esperado é o da biblioteca de referência no `api` na data da implementação, validado pelos testes portados.

### RN-02: Prefixo `PzPdf` obrigatório
Todo tipo e arquivo público da biblioteca no repositório `fpdf` deve usar o prefixo `PzPdf` no nome.

### RN-03: Motor fixo FPDF
A troca do FPDF por outra biblioteca de PDF está **fora do escopo**; uma eventual migração futura seria novo projeto.

### RN-04: Isolamento entre gerações
Uma instância do wrapper não pode depender de estado mutável compartilhado com outra instância para produzir saída correta.

---

## 7. Interface e Experiência do Usuário

### 7.1 Wireframes/Mockups
Não aplicável (biblioteca backend).

### 7.2 Fluxo de Navegação
Não aplicável.

### 7.3 Diretrizes de UX
- API previsível e autocompletável (métodos fluentes onde fizer sentido).
- Mensagens de exceção acionáveis para o desenvolvedor.
- Exemplos mínimos de uso no repositório `fpdf` (sem exigência de documentação de usuário final de produto).

---

## 8. Integrações e Dependências

### 8.1 APIs Externas
Não há integração obrigatória com APIs HTTP externas para o núcleo da biblioteca, salvo o que a aplicação consumidora fizer com o PDF gerado.

### 8.2 Dependências Internas
Nenhuma dependência de código em `api` para compilar ou testar a biblioteca no `fpdf`; apenas referência conceitual e de testes a portar.

### 8.3 Dependências Técnicas
- PHP **8.2+**
- **FPDF** (`fawno/fpdf` ou equivalente adotado na referência)
- Demais pacotes Composer **efetivamente utilizados** pela referência para manter paridade (ex.: biblioteca de dados de caminho SVG, se ainda necessária), auditados no `composer.json` do `fpdf` no momento da implementação

---

## 9. Restrições e Limitações

### 9.1 Restrições Técnicas
- Não modificar arquivos sob `/home/ljlim/code/puzl/api/`.
- Não introduzir adapter/interfaces públicas para troca de motor PDF.
- Manter compatibilidade com execução em ambientes típicos de CLI (jobs) e FPM (HTTP).

### 9.2 Restrições de Negócio
- Entrega alinhada ao uso interno por desenvolvedores backend Puzl; publicação como pacote Composer open-source não é requisito deste PRD.

---

## 10. Fora do Escopo

**O que NÃO será implementado nesta versão**:
- ❌ Substituição do **FPDF** por outra biblioteca de PDF
- ❌ Alterações na biblioteca legada dentro de `api`
- ❌ Redesign de layouts de documentos de negócio específicos (apenas paridade de capacidades da lib de referência)
- ❌ Interfaces e adapters para múltiplos motores de renderização

---

## 11. Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| Diferenças sutis de renderização FPDF entre ambientes | Médio | Média | Priorizar asserções estáveis; evitar snapshots frágeis; alinhar versões fixas no Composer |
| Regressão em concorrência por cache global de fontes/arquivos | Alto | Média | Testes paralelos explícitos; cache com escopo por instância ou isolamento de diretório por worker quando necessário |
| Escopo “paridade total” subestimado | Alto | Baixa | Checklist cruzado referência × testes portados; revisão incremental por componente |

---

## 12. Anexos

### 12.1 Referências
- Biblioteca de referência (somente leitura): `/home/ljlim/code/puzl/api/app/Library/PzPdf`
- Testes de referência (porte): `/home/ljlim/code/puzl/api/tests/Unit/Library/PzPdf`
- Repositório alvo: `/home/ljlim/code/puzl/fpdf/`

### 12.2 Glossário
- **Paridade funcional**: equivalência de comportamento validada pela suíte portada.
- **Wrapper**: API de alto nível sobre o FPDF.
- **Isolamento**: instâncias não compartilham estado mutável que afete a saída.

---

## Histórico de Versões

| Versão | Data | Autor | Descrição |
|--------|------|-------|-----------|
| 1.0 | 2026-03-22 | Produto/Tech | Versão inicial após esclarecimentos e decisões complementares (perguntas 8, 12 e 13) |
