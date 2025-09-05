<?php
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DBHandler extends AbstractProcessingHandler
{
    private $pdo;

    public function __construct(PDO $pdo, $level = \Monolog\Level::Debug, bool $bubble = true)
    {
        $this->pdo = $pdo;
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO logs (nivel, mensagem, contexto, data_log) VALUES (:nivel, :mensagem, :contexto, :data)");
        $stmt->execute([
            ':nivel' => $record->level->getName(),
            ':mensagem' => $record->message,
            ':contexto' => json_encode($record->context),
            ':data' => $record->datetime->format('Y-m-d H:i:s'),
        ]);
    }
}
