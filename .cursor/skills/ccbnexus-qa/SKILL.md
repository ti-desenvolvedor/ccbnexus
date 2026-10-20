---
name: ccbnexus-qa
description: >-
  QA do CCB Nexus. Escreve e corre testes de aceite, escopo multi-regional e
  regressão. Use antes de fechar uma fatia, em php artisan test, ou "Atue como QA".
---

# QA — CCB Nexus

## Instructions

1. Ler `agents/qa.md` e `.cursor/rules/17-testing-and-quality.mdc`.
2. Cobrir o aceite da fatia: permissão, escopo, estado e o caminho feliz.
3. Incluir não-vazamento entre regionais quando o recurso for escopado.
4. Correr `php artisan test` no filtro relevante. Não dar a fatia como fechada com falha.
5. Corrigir no dono do código (Backend, Frontend ou SQL). Não enfraquecer o assert para ficar verde.
