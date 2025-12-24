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

            // check for missing IDs in sequence
            $minId = $this->doctrineConnection->fetchOne('SELECT MIN(id) FROM visitors');
            $maxId = $this->doctrineConnection->fetchOne('SELECT MAX(id) FROM visitors');
            $count = $this->doctrineConnection->fetchOne('SELECT COUNT(*) FROM visitors');
            $expectedCount = $maxId - $minId + 1;
            $hasMissingIds = $count < $expectedCount;

            // check if sequence starts from ID 1
            $missingStartIds = $minId > 1 ? ($minId - 1) : 0;

            // check if reordering needed
            if ($duplicateCount > 0 || $hasMissingIds || $missingStartIds > 0) {
                // initialize id mapping array
                $idMapping = [];

                // duplicate visitors processing
                if ($duplicateCount > 0) {
                    $io->section('Removing duplicate visitor records...');

                    // find visitors with same IP but higher IDs
                    $this->doctrineConnection->executeQuery('
                        DELETE t1 FROM visitors t1
                        INNER JOIN visitors t2 ON t1.ip_address = t2.ip_address AND t1.id > t2.id
                    ');
                    $io->text('✓ Removed ' . $duplicateCount . 'duplicate visitor records');
                }

                // process missing ids in sequence
                if ($hasMissingIds || $missingStartIds > 0) {
                    $io->section('Recalculating visitor IDs...');

                    // disable foreign key checks
                    $this->doctrineConnection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');

                    try {
                        // get current visitor IDs
                        $currentVisitors = $this->doctrineConnection->fetchAllAssociative('SELECT id FROM visitors ORDER BY id');

                        // recalculate IDs starting from 1
                        $newId = 1;

                        foreach ($currentVisitors as $visitor) {
                            $oldId = $visitor['id'];
                            if ($oldId !== $newId) {
                                $idMapping[$oldId] = $newId;
                            }
                            $newId++;
                        }

                        // update visitor IDs
                        $this->doctrineConnection->executeQuery('SET @new_id = 0;');
                        $this->doctrineConnection->executeQuery('UPDATE visitors SET id = (@new_id := @new_id + 1)');

                        // update foreign keys in related tables
                        if (!empty($idMapping)) {
                            $io->section('Updating foreign keys in related tables...');

                            // update users table in batch
                            foreach ($idMapping as $oldId => $newId) {
                                if ($oldId !== $newId) {
                                    $this->doctrineConnection->executeQuery('UPDATE users SET visitor_id = ? WHERE visitor_id = ?', [$newId, $oldId]);
                                }
                            }
                            $io->text('✓ Updated users.visitor_id');

                            // update messages table in batch
                            foreach ($idMapping as $oldId => $newId) {
                                if ($oldId !== $newId) {
                                    $this->doctrineConnection->executeQuery('UPDATE inbox_messages SET visitor_id = ? WHERE visitor_id = ?', [$newId, $oldId]);
                                }
                            }
                            $io->text('✓ Updated inbox_messages.visitor_id');

                            // update logs table in batch
                            foreach ($idMapping as $oldId => $newId) {
                                if ($oldId !== $newId) {
                                    $this->doctrineConnection->executeQuery('UPDATE logs SET visitor_id = ? WHERE visitor_id = ?', [$newId, $oldId]);
                                }
                            }
                            $io->text('✓ Updated logs.visitor_id');
                        }

                        // set correct auto increment
                        $maxId = $this->doctrineConnection->fetchOne('SELECT MAX(id) FROM visitors');
                        $this->doctrineConnection->executeQuery('ALTER TABLE visitors AUTO_INCREMENT = ' . ($maxId + 1));
                    } finally {
                        // re-enable foreign key checks
                        $this->doctrineConnection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
                    }
                }

                // get max id
                $maxId = $this->doctrineConnection->fetchOne('SELECT MAX(id) FROM visitors;');

                // set new auto increment value
                $this->doctrineConnection->executeQuery('ALTER TABLE visitors AUTO_INCREMENT = ' . ($maxId + 1) . ';');

                // re-enable foreign key checks
                $this->doctrineConnection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');

                // print info message
                $message = [];
                if ($duplicateCount > 0) {
                    $message[] = $duplicateCount . ' duplicate record(s) have been deleted';
                }
                if ($missingStartIds > 0) {
                    $message[] = $missingStartIds . ' missing ID(s) at the beginning of sequence have been reorganized';
                }
                if ($hasMissingIds) {
                    $message[] = 'Missing IDs in sequence have been reorganized';
                    $message[] = 'Foreign keys updated in ' . count($idMapping) . ' records';
                }
                $io->success(implode('; ', $message));
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
