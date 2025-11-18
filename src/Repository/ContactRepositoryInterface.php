<?php

namespace App\Repository;

use App\Entity\Contact;

interface ContactRepositoryInterface
{
    public function findOneByPPIdentifier(string $ppIdentifier): ?Contact;
    public function listAllContactsToBeDeleted(int $offset, int $limit): array;
}
