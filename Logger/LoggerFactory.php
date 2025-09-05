<?php
// Logger/LoggerFactory.php

use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/DBHandler.php';
require_once __DIR__ . '/../ConexaoDB/conexao.php';

class LoggerFactory
{
    public static function createLogger(): Logger
    {
        // Cria uma instância do Logger
        $logger = new Logger('app');

        // Define o formato da data e hora
        $logger->setTimezone(new DateTimeZone('America/Cuiaba'));

        // Conecta ao banco de dados utilizando a classe Conexao
        $pdo = Conexao::getConexao();

        // Adiciona o handler customizado para logar no banco de dados
        $logger->pushHandler(new DBHandler($pdo));

        // Retorna o logger configurado
        return $logger;
    }
}
