CREATE DATABASE IF NOT EXISTS gerenciador_events
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gerenciador_events;

CREATE TABLE IF NOT EXISTS eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    nome_evento VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    vagas INT NOT NULL,
    data_evento DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evento_turnos (
    id_evento INT NOT NULL,
    turno ENUM('matutino', 'vespertino', 'noturno') NOT NULL,
    PRIMARY KEY (id_evento, turno),
    CONSTRAINT fk_evento_turnos_evento
        FOREIGN KEY (id_evento) REFERENCES eventos (id_evento)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS participantes (
    id_participante INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    cpf CHAR(11) NOT NULL,
    telefone VARCHAR(20) NULL DEFAULT '',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_participantes_email (email),
    UNIQUE KEY uk_participantes_cpf (cpf)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscricoes (
    id_inscricao INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_participante INT NOT NULL,
    turno ENUM('matutino', 'vespertino', 'noturno') NOT NULL,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_inscricao_unica (id_evento, id_participante, turno),
    CONSTRAINT fk_inscricoes_evento
        FOREIGN KEY (id_evento) REFERENCES eventos (id_evento)
        ON DELETE CASCADE,
    CONSTRAINT fk_inscricoes_participante
        FOREIGN KEY (id_participante) REFERENCES participantes (id_participante)
        ON DELETE CASCADE
) ENGINE=InnoDB;
