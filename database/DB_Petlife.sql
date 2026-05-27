CREATE DATABASE DB_Petlife;
USE DB_Petlife;

-- 1. TABELA DE USUÁRIOS (Sistema de Login e Segurança)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL, -- Aqui usaremos password_hash no PHP
    pergunta_secreta VARCHAR(255) NOT NULL,
    resposta_secreta VARCHAR(255) NOT NULL,
    nivel ENUM('admin', 'funcionario', 'veterinario') DEFAULT 'funcionario',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABELA DE CLIENTES (Cadastro Obrigatório 1)
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    endereco TEXT,
    foto_perfil VARCHAR(255),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. TABELA DE PETS (Cadastro Obrigatório 2)
CREATE TABLE pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(50) NOT NULL, -- Cão, Gato, etc.
    raca VARCHAR(50),
    idade INT,
    peso DECIMAL(5,2),
    status_adocao BOOLEAN DEFAULT FALSE, -- Funcionalidade de Adoção
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE
);

-- 4. TABELA DE FUNCIONÁRIOS/VETERINÁRIOS (Cadastro Obrigatório 3)
CREATE TABLE funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cargo VARCHAR(50) NOT NULL, -- Veterinário, Tosador, Atendente
    crmv VARCHAR(20), -- Apenas para veterinários
    telefone VARCHAR(20),
    data_admissao DATE
);

-- 5. TABELA DE PRODUTOS (Cadastro Obrigatório 4 - Loja)
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL,
    categoria VARCHAR(50) -- Ração, Brinquedo, Medicamento
);

-- 6. TABELA DE SERVIÇOS (Base para Agendamentos)
CREATE TABLE servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL, -- Banho, Tosa, Hospedagem
    preco DECIMAL(10,2) NOT NULL,
    duracao_minutos INT
);

-- 7. TABELA DE AGENDAMENTOS (Funcionalidade Temática: Banho/Tosa/Hospedagem)
CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pet INT NOT NULL,
    id_servico INT NOT NULL,
    data_hora DATETIME NOT NULL,
    status ENUM('Agendado', 'Concluído', 'Cancelado') DEFAULT 'Agendado',
    observacoes TEXT,
    FOREIGN KEY (id_pet) REFERENCES pets(id),
    FOREIGN KEY (id_servico) REFERENCES servicos(id)
);

-- 8. TABELA DE CONSULTAS (Funcionalidade Temática: Veterinária)
CREATE TABLE consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pet INT NOT NULL,
    id_funcionario INT NOT NULL, -- O Veterinário
    data_consulta DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnostico TEXT,
    prescricao TEXT,
    FOREIGN KEY (id_pet) REFERENCES pets(id),
    FOREIGN KEY (id_funcionario) REFERENCES funcionarios(id)
);

-- 9. TABELA DE VENDAS (Registro de Vendas de Produtos)
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valor_total DECIMAL(10,2),
    FOREIGN KEY (id_produto) REFERENCES produtos(id)
);
