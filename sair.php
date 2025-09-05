<?php
session_start(); // Inicia a sessão

// Log do logout
require_once __DIR__ . '/Logger/LoggerFactory.php';
$logger = LoggerFactory::createLogger();

if (isset($_SESSION['id'])) {
    $logger->info('Usuário saiu do sistema', [
        'id' => $_SESSION['id'],
        'usuario' => $_SESSION['nome'],
        'email' => $_SESSION['email'],
        'perfil' => $_SESSION['nivel_de_acesso']
    ]);
}

// Limpa todas as variáveis de sessão para deslogar o usuário
session_unset();

// Salva a mensagem de sucesso na sessão para ser exibida na próxima página
$_SESSION['mensagens'] = ["Você foi desconectado com sucesso."];
$_SESSION['mensagem_tipo'] = 'success';

// Redireciona o usuário para a página de login
header("Location: /Views/login.php");
exit();
?>