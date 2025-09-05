<?php
// controllers/validar_sessao.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define o tempo máximo de inatividade: 5 minutos (300 segundos)
$tempo_inatividade_limite = 300; 

// A verificação de login deve vir antes do controle de inatividade
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../Views/login.php');
    exit();
}

// Verifica se a sessão expirou por inatividade
if (isset($_SESSION['start']) && (time() - $_SESSION['start'] > $tempo_inatividade_limite)) {

    // --- LÓGICA DE LOG DE INATIVIDADE INICIADA AQUI ---
    // Captura os dados do usuário antes de destruir a sessão
    $id_usuario = $_SESSION['id_usuario'] ?? 'N/A';
    $nome_usuario = $_SESSION['nome_usuario'] ?? 'N/A';
    $email_usuario = $_SESSION['email_usuario'] ?? 'N/A';
    $permissoes = implode(', ', $_SESSION['permissoes'] ?? ['N/A']);
    
    // Inclui a fábrica de logger e registra o evento
    require_once __DIR__ . '/../Logger/LoggerFactory.php';
    $logger = LoggerFactory::createLogger();
    $logger->info('Usuário deslogado por inatividade', [
        'id' => $id_usuario,
        'usuario' => $nome_usuario,
        'email' => $email_usuario,
        'permissoes' => $permissoes
    ]);
    // --- LÓGICA DE LOG DE INATIVIDADE TERMINADA AQUI ---

    // Armazena a mensagem em uma variável temporária
    $mensagem_aviso = "Sua sessão expirou por inatividade. Por favor, faça login novamente.";

    // Destrói a sessão atual
    session_unset();
    session_destroy();

    // Inicia uma nova sessão para armazenar a mensagem antes de redirecionar
    session_start();
    $_SESSION['mensagens'][] = $mensagem_aviso;

    // Redireciona para a página de login
    header('Location: ../Views/login.php');
    exit();
}

// Renova o tempo da sessão somente se não for uma requisição AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $_SESSION['start'] = time();
}