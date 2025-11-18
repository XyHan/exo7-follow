<?php

namespace App\Entity;

use App\Entity\Trait\Hashable;
use App\Entity\Trait\SoftDeletable;
use App\Entity\Trait\Timestampable;
use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact implements HashableInterface
{
    use Hashable;
    use SoftDeletable;
    use Timestampable;

    public const PP_IDENTIFIER_TYPE_ADELI = 0;
    public const PP_IDENTIFIER_TYPE_RPPS = 8;
    public const PP_IDENTIFIER_TYPES = [self::PP_IDENTIFIER_TYPE_RPPS, self::PP_IDENTIFIER_TYPE_ADELI];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(name: 'pp_identifier', type: 'string', length: 11, nullable: true)]
    #[Assert\Length(min: '8', max: '11')]
    public $ppIdentifier;

    #[ORM\Column(name: 'pp_identifier_type', type: 'smallint', nullable: true)]
    #[Assert\Choice(choices: Contact::PP_IDENTIFIER_TYPES)]
    public $ppIdentifierType;

    #[ORM\Column(name: 'title', type: 'string', nullable: true)]
    public $title;

    #[ORM\Column(name: 'first_name', type: 'string', nullable: true)]
    public $firstName;

    #[ORM\Column(name: 'family_name', type: 'string', nullable: false)]
    #[Assert\NotBlank]
    public $familyName;

    #[ORM\ManyToMany(targetEntity: Organization::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'contact_organizations')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'organization_id', referencedColumnName: 'id')]
    #[Assert\Valid]
    public $organizations;

    public function __construct()
    {
        $this->organizations = new ArrayCollection();
    }
}
