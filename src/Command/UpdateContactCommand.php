<?php

namespace App\Command;

use App\Entity\Contact;
use App\Repository\ContactRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
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

    private array $errors = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactRepositoryInterface $contactRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);

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

        $this->io->section('Encountered Errors');
        $this->io->info($this->errors);

        return Command::SUCCESS;
    }

    private function processContacts(): int
    {
        $this->io->writeln('Updating contacts');
        $progress = new ProgressBar($this->io);
        $progress->setFormat('debug_nomax');

        $reader = Reader::createFromPath('files/contacts.csv');
        $reader->setHeaderOffset(0);

        $headers = $reader->getHeader();
        $ppIdentifierHeader = 'Identifiant PP';
        $ppIdentifierTypeHeader = 'Type d\'identifiant PP';
        $titleHeader = 'Libellé civilité d\'exercice';
        $firstNameHeader = 'Prénom d\'exercice';
        $familyNameHeader = 'Nom d\'exercice';

        foreach ([
            $ppIdentifierHeader,
            $ppIdentifierTypeHeader,
            $titleHeader,
            $firstNameHeader,
            $familyNameHeader,
        ] as $requiredColumn) {
            if (!in_array($requiredColumn, $headers)) {
                throw new \RuntimeException(sprintf('Colonne "%s" manquante dans le CSV.', $requiredColumn));
            }
        }

        $count = 0;
        $batchSize = 1000;

        $records = $reader->getRecords();

        gc_enable();

        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->beginTransaction();
        }

        try {
            foreach ($records as $i => $item) {
                $progress->advance();

                $familyName = $item[$familyNameHeader] ?? null;
                if (null === $familyName) {
                    $this->errors['contact'][$i] = 'A family name is required';
                    continue;
                }

                $ppIdentifier = $item[$ppIdentifierHeader] ?? null;
                $ppIdentifierType = $item[$ppIdentifierTypeHeader] ?? null;

                $existingContact = $this->contactRepository->findOneByPPIdentifier($ppIdentifier);
                $contact = null === $existingContact ? new Contact() : $existingContact;
                $contact->ppIdentifier = $ppIdentifier;
                $contact->ppIdentifierType = null === $ppIdentifierType ? null : (int) $ppIdentifierType;
                $contact->title = $item[$titleHeader] ?? null;
                $contact->firstName = $item[$firstNameHeader] ?? null;
                $contact->familyName = $familyName;

                if ($existingContact && $existingContact->identicalTo($contact)) {
                    $this->entityManager->detach($contact);
                    continue;
                }

                if ($count % $batchSize === 0) {
                    $this->contactRepository->save($contact, true, true);
                    gc_collect_cycles();
                } else {
                    $this->contactRepository->save($contact);
                }

                $count++;
            }
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        gc_disable();

        $progress->finish();

        return $count;
    }

    private function deleteContacts(): int
    {
        $this->io->writeln('Deleting contacts');
        $progress = new ProgressBar($this->io);
        $progress->setFormat('debug_nomax');

        $count = 0;
        $offset = 0;
        $batchSize = 1000;

        gc_enable();

        try {
            do {
                $contacts = $this->contactRepository->listAllContactsToBeDeleted($offset, $batchSize);
                foreach ($contacts as $contact) {
                    $progress->advance();

                    $contact->deletedAt = new \DateTimeImmutable();

                    if ($count % $batchSize === 0) {
                        $this->contactRepository->save($contact, true, true);
                        gc_collect_cycles();
                    } else {
                        $this->contactRepository->save($contact);
                    }

                    $count++;
                }

                $offset = $offset + ($batchSize - 1);
            } while (count($contacts) > 0);
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        gc_disable();

        $progress->finish();

        return $count;
    }
}
