<?php

namespace App\Command;

use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-contact',
    description: 'Update all contacts and their organizations from CSV files',
)]
class UpdateContactCommand extends Command
{
    private ?SymfonyStyle $io;

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Process contacts
        $this->io->section('Processing Contacts');
        $countContacts = $this->processContacts();
        $deleteContacts = $this->deleteContacts();

        $this->io->info('Created/Updated: ' . $countContacts);
        $this->io->info('Deleted: ' . $deleteContacts);

        // Process organizations
        // @TODO : Create or update organizations based on the CSV data
        $this->io->section('Processing Organizations');
        $this->io->info('Created/Updated: 0');
        $this->io->info('Deleted: 0');

        // Process contact organizations
        // @TODO : Create or update contact organizations based on the CSV data
        $this->io->section('Processing Contact Organizations');
        $this->io->info('Created/Updated: 0');
        $this->io->info('Deleted: 0');

        return Command::SUCCESS;
    }

    private function processContacts(): int
    {
        $this->io->writeln('Updating contacts');
        $progress = new ProgressBar($this->io);
        $progress->setFormat('debug_nomax');

        $count = 0;
        $reader = Reader::createFromPath('files/contacts.csv');
        $records = $reader->getRecords();

        foreach ($records as $i => $item) {
            $progress->advance();

            // @TODO : Create or update contact based on the CSV data

            $count++;
        }
        $progress->finish();

        return $count;
    }

    private function deleteContacts(): int
    {
        $this->io->writeln('Deleting contacts');
        $count = 0;

        // @TODO : Create a query to soft delete contacts if updated_at has not changed since 1 week

        return $count;
    }
}
