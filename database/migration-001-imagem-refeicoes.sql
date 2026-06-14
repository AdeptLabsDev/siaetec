-- =============================================================
-- Migração 001 — Coluna de imagem nas refeições
-- Banco: sistema_alimentar
-- Executar no phpMyAdmin (aba SQL) com o banco selecionado.
-- =============================================================

ALTER TABLE `refeicoes`
    ADD COLUMN `imagem` VARCHAR(100) NULL AFTER `descricao`;

-- Observação: a coluna guarda apenas o NOME do arquivo .webp
-- (ex.: 'feijoada.webp'), armazenado em /assets/img/.
-- Quando vazia, o sistema usa /assets/img/prato-padrao.webp como fallback.
