CREATE TABLE contato (
    `#` INT(5) NOT NULL AUTO_INCREMENT,
    nomeConc VARCHAR(70) NOT NULL,
    emailConc VARCHAR(100) NOT NULL,
    AssuntoConc VARCHAR(500) NOT NULL,
    PRIMARY KEY (`#`)
);

CREATE TABLE empresa (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nome_empresa VARCHAR(255) NOT NULL,
    email_empresa VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255),
    PRIMARY KEY (id)
);

CREATE TABLE funcionario (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_empresa INT(11) NOT NULL,
    senha VARCHAR(255),
    nome VARCHAR(50) NOT NULL,
    cpf VARCHAR(15) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    profissao VARCHAR(50) NOT NULL,
    registroProfissional VARCHAR(50) NOT NULL,
    instituicao VARCHAR(50) NOT NULL,
    tempo VARCHAR(50) NOT NULL,
    telefone VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    INDEX (id_empresa),
    FOREIGN KEY (id_empresa)
    REFERENCES empresa(id) 
);

CREATE TABLE dose_mensal (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_funcionario INT(11) NOT NULL,
    nivel_radiacao_dossimetro DECIMAL(10,3) NOT NULL,
    data_dossimetro DATE NOT NULL,
    PRIMARY KEY (id),
    INDEX (id_funcionario),
    FOREIGN KEY (id_funcionario)
    REFERENCES funcionario(id) 
);

CREATE TABLE exame_preventivo (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_funcionario INT(11) NOT NULL,
    tipo_exame VARCHAR(255) NOT NULL,
    data_exame DATE NOT NULL,
    resultado_exame TEXT NOT NULL,
    PRIMARY KEY (id),
    INDEX (id_funcionario),
    FOREIGN KEY (id_funcionario)
    REFERENCES funcionario(id) 
);

CREATE TABLE ferias (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_funcionario INT(11) NOT NULL,
    data_inicio DATE NOT NULL,
    data_termino DATE NOT NULL,
    PRIMARY KEY (id),
    INDEX (id_funcionario),
    FOREIGN KEY (id_funcionario)
    REFERENCES funcionario(id) 
);

CREATE TABLE registro_exposicao (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_funcionario INT(11) NOT NULL,
    data_inicio DATE NOT NULL,
    data_termino DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_termino TIME NOT NULL,
    pausa TINYINT(1) NOT NULL,
    quantas_pausas INT(11) NOT NULL,
    equipamento_emissor VARCHAR(255),
    distancia DECIMAL(10,2),
    barreira TINYINT(1) DEFAULT 0,
    qual_barreira VARCHAR(255),
    PRIMARY KEY (id),
    INDEX (id_funcionario),
    FOREIGN KEY (id_funcionario)
    REFERENCES funcionario(id) 
);

CREATE TABLE equipamento_exposicao (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_exposicao INT(11) NOT NULL,
    nome_equipamento VARCHAR(255) NOT NULL,
    nivel_radiacao DECIMAL(10,3) NOT NULL,
    PRIMARY KEY (id),
    INDEX (id_exposicao),
    FOREIGN KEY (id_exposicao)
    REFERENCES registro_exposicao(id) 
);

CREATE TABLE pausa_exposicao (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_exposicao INT(11) NOT NULL,
    data_inicio_pausa DATE NOT NULL,
    data_termino_pausa DATE NOT NULL,
    hora_inicio_pausa TIME NOT NULL,
    hora_termino_pausa TIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (id_exposicao),
    FOREIGN KEY (id_exposicao)
    REFERENCES registro_exposicao(id)
);