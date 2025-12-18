<?php

namespace App\Command;

use Exception;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ValidateVisitorsStructureCommand
 *
 * Command for validate and reorganize visitors database structure
 *
 * @package App\Command
 */
#[AsCommand(name: 'visitors:structure:validate', description: 'Validate and reorganize visitors database structure')]
class ValidateVisitorsStructureCommand extends Command
{
    private Connection $doctrineConnection;

    public function __construct(Connection $doctrineConnection)
    {
        $this->doctrineConnection = $doctrineConnection;
        parent::__construct();
    }

    /**
     * Execute command to validate and reorganize visitors database structure
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // get dublicate rows
            $duplicateCount = $this->doctrineConnection->fetchOne('
                SELECT COUNT(*)
                FROM visitors t1
                JOIN visitors t2 
                ON t1.ip_address = t2.ip_address
                WHERE t1.id > t2.id;
            ');

            // check if duplicates exist
            if ($duplicateCount > 0) {
                // delete dublicate records
                $this->doctrineConnection->executeQuery('
                    DELETE t1
                    FROM visitors t1
                    JOIN visitors t2 
                    ON t1.ip_address = t2.ip_address
                    WHERE t1.id > t2.id;
                ');

                // recalculate ids
                $this->doctrineConnection->executeQuery('SET @new_id = 0;');
                $this->doctrineConnection->executeQuery('UPDATE visitors SET id = (@new_id := @new_id + 1);');

                // get max id
                $maxId = $this->doctrineConnection->fetchOne('SELECT MAX(id) FROM visitors;');

                // set new auto increment value
                $this->doctrineConnection->executeQuery('ALTER TABLE visitors AUTO_INCREMENT = ' . ($maxId + 1) . ';');

                // print info message
                $io->success("$duplicateCount duplicate record(s) have been deleted");
            } else {
                $io->success('No validation or reorganization needed');
            }

            // return command status
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
