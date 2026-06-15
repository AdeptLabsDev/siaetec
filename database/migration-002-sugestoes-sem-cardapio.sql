-- =============================================================
-- Migração 002 — Remove a opção 'cardapio' das sugestões
-- Banco: sistema_alimentar
-- Executar no phpMyAdmin (aba SQL) com o banco selecionado.
-- =============================================================

-- 1. (Opcional) Verifique se há sugestões usando 'cardapio':
--    SELECT COUNT(*) FROM `sugestoes` WHERE `assunto` = 'cardapio';

-- 2. Reatribua eventuais registros antigos para 'outro' (evita perda na conversão do ENUM):
UPDATE `sugestoes` SET `assunto` = 'outro' WHERE `assunto` = 'cardapio';

-- 3. Redefine o ENUM sem a opção 'cardapio':
ALTER TABLE `sugestoes`
    MODIFY `assunto` ENUM('atendimento', 'sistema', 'outro') NOT NULL;
