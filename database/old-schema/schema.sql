-- =============================================================
-- SIAETEC — Sistema de Intenção Alimentar Escolar
-- Schema do banco de dados
-- Banco: sistema_alimentar
-- Codificação: utf8mb4_unicode_ci
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Tabela: usuarios
-- Responsável pelos dados de autenticação e controle de acesso.
-- Tanto alunos quanto admins possuem um registro aqui.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`            VARCHAR(100)    NOT NULL,
    `login`           VARCHAR(20)     NOT NULL,               -- RM para alunos, identificador para admins
    `senha`           VARCHAR(255)    NOT NULL,               -- Armazenada com password_hash()
    `tipo`            ENUM('aluno', 'admin') NOT NULL,
    `senha_alterada`  TINYINT(1)      NOT NULL DEFAULT 0,     -- 0 = primeiro acesso, 1 = senha já definida
    `ativo`           TINYINT(1)      NOT NULL DEFAULT 1,     -- 0 = inativo, 1 = ativo
    `criado_em`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usuarios_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- Tabela: turmas
-- Organiza os alunos em grupos escolares por ano letivo.
-- Turmas inativas são preservadas para manter histórico.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `turmas` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`        VARCHAR(50)     NOT NULL,                   -- Ex: "1A", "2B Manhã"
    `curso`       VARCHAR(100)    NOT NULL,                   -- Ex: "Técnico em Informática"
    `periodo`     ENUM('manhã', 'tarde', 'noite') NOT NULL,
    `ano_letivo`  YEAR            NOT NULL,
    `ativo`       TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- Tabela: alunos
-- Dados escolares do aluno, separados da autenticação.
-- Vinculados a um usuário e a uma turma.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alunos` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `usuario_id`  INT UNSIGNED    NOT NULL,
    `turma_id`    INT UNSIGNED    NOT NULL,
    `rm`          VARCHAR(20)     NOT NULL,                   -- Registro de Matrícula — único no sistema
    `ano_letivo`  YEAR            NOT NULL,
    `ativo`       TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_alunos_rm` (`rm`),
    CONSTRAINT `fk_alunos_usuario`  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_alunos_turma`    FOREIGN KEY (`turma_id`)   REFERENCES `turmas`   (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- Tabela: refeicoes
-- Cada refeição representa uma enquete para um dia específico.
-- O sistema encerra automaticamente ao atingir o horario_limite.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `refeicoes` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `admin_id`         INT UNSIGNED    NOT NULL,              -- Usuário admin que cadastrou
    `titulo`           VARCHAR(100)    NOT NULL,              -- Ex: "Almoço de terça"
    `descricao`        TEXT            DEFAULT NULL,          -- Descrição do cardápio
    `data_refeicao`    DATE            NOT NULL,
    `horario_limite`   DATETIME        NOT NULL,              -- Após este horário, a enquete é encerrada automaticamente
    `criado_em`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_refeicoes_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- Tabela: intencoes_alimentares
-- Registra a resposta de cada aluno para uma refeição.
-- Um aluno só pode ter uma resposta por refeição (UNIQUE composto).
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `intencoes_alimentares` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `aluno_id`       INT UNSIGNED    NOT NULL,
    `refeicao_id`    INT UNSIGNED    NOT NULL,
    `resposta`       ENUM('sim', 'nao') NOT NULL,
    `respondido_em`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `alterado_em`    DATETIME        DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_intencao_aluno_refeicao` (`aluno_id`, `refeicao_id`),  -- Impede duplicidade
    CONSTRAINT `fk_intencoes_aluno`    FOREIGN KEY (`aluno_id`)    REFERENCES `alunos`    (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_intencoes_refeicao` FOREIGN KEY (`refeicao_id`) REFERENCES `refeicoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- Tabela: resumos_envio
-- Registra os resumos gerados e enviados à cozinha via WhatsApp.
-- Serve como histórico e auditoria dos envios realizados.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resumos_envio` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `refeicao_id`     INT UNSIGNED    NOT NULL,
    `admin_id`        INT UNSIGNED    NOT NULL,               -- Admin que acionou o envio
    `mensagem`        TEXT            NOT NULL,               -- Conteúdo exato enviado ao WhatsApp
    `destinatario`    VARCHAR(100)    NOT NULL,               -- Número ou grupo de destino
    `status`          ENUM('enviado', 'erro') NOT NULL,
    `enviado_em`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_resumos_refeicao` FOREIGN KEY (`refeicao_id`) REFERENCES `refeicoes`  (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_resumos_admin`    FOREIGN KEY (`admin_id`)    REFERENCES `usuarios`   (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================
-- SEED: Usuário Admin padrão
-- Login: admin
-- Senha: admin123  (gerada com password_hash — bcrypt)
-- ⚠️ Altere a senha após o primeiro acesso em produção.
-- =============================================================
INSERT INTO `usuarios` (`nome`, `login`, `senha`, `tipo`, `senha_alterada`, `ativo`)
VALUES (
    'Administrador',
    'admin',
    '$2y$10$LnERw65/nVmpZ8w2EdQ8EOIi1.2kKbcKT4y.kzfMHClkv4EYTKLai',
    'admin',
    1,        -- Admin não passa pelo fluxo de primeiro acesso
    1
);
--4:00
CREATE TABLE IF NOT EXISTS `sugestoes` (
    `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `aluno_id`     INT UNSIGNED    NOT NULL,
    `assunto`      ENUM('cardapio', 'atendimento', 'sistema', 'outro') NOT NULL,
    `mensagem`     TEXT            NOT NULL,
    `enviado_em`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_sugestoes_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 -- 4:40
ALTER TABLE `refeicoes`
    ADD COLUMN `imagem` VARCHAR(100) NULL AFTER `descricao`;