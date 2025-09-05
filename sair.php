<?php
session_start(); // Inicia a sessão

// Log do logout
require_once __DIR__ . '/Logger/LoggerFactory.php';
$logger = LoggerFactory::createLogger();

// Apenas registra o log de saída se a sessão do usuário estiver ativa
if (isset($_SESSION['id_usuario'])) {
    $logger->info('Usuário saiu do sistema', [
        'id' => $_SESSION['id_usuario'],
        'usuario' => $_SESSION['nome_usuario'],
        'email' => $_SESSION['email_usuario']
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
