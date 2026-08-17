-- =============================================================
-- Migração 003 — E-mail e status de envio de credenciais
-- Banco: sistema_alimentar
-- Executar no phpMyAdmin (aba SQL) com o banco selecionado.
-- =============================================================

ALTER TABLE `usuarios`
    ADD COLUMN `email` VARCHAR(150) DEFAULT NULL AFTER `login`,
    ADD COLUMN `credenciais_enviadas` TINYINT(1) NOT NULL DEFAULT 0 AFTER `senha_alterada`,
    ADD COLUMN `credenciais_enviadas_em` DATETIME DEFAULT NULL AFTER `credenciais_enviadas`;

-- Observação: `email` fica NULL para usuários existentes (ex.: admin e
-- alunos cadastrados antes desta migração). O sistema trata e-mail ausente
-- como "não configurado" — nunca como erro de envio.
