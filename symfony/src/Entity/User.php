<?php

namespace App\Entity;

use App\Entity\MasterData\BusinessUnit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;


/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\AttributeOverrides({
 *     @ORM\AttributeOverride(name="password",
 *          column=@ORM\Column(
 *              name     = "password",
 *              type     = "string",
 *              nullable=true
 *          )
 *      )
 * })
 */
class User extends BaseUser implements SamlUserInterface
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_TRADER = 'ROLE_TRADER';
    const ROLE_BOARD_MEMBER = 'ROLE_BOARD_MEMBER';
    const ROLE_RISK_CONTROLLER = 'ROLE_RISK_CONTROLLER';
    const ROLE_BU_HEDGING_COMMITTEE = 'ROLE_BU_HEDGING_COMMITTEE';
    const ROLE_BU_MEMBER = 'ROLE_BU_MEMBER';

    public static $adminRolesChoices = [
        self::ROLE_ADMIN => 'Admin',
    ];

    public static $rolesChoices = [
        self::ROLE_TRADER => 'Trader',
        self::ROLE_BOARD_MEMBER => 'Board member',
        self::ROLE_RISK_CONTROLLER => 'Risk controller',
        self::ROLE_BU_HEDGING_COMMITTEE => 'BU hedging committee',
        self::ROLE_BU_MEMBER => 'BU Member',
    ];

    const DEFAULT_USER_FIRSTNAME = 'CYLIPOL';
    const DEFAULT_USER_LASTNAME = 'CYLIPOL';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $function;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\MasterData\BusinessUnit", inversedBy="users")
     */
    private $businessUnits;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $role;

    /**
     * @var string
     * @ORM\Column(name="name_id", type="string", length=255)
     */
    public $nameId;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Pricer", mappedBy="uploadUser")
     */
    private $pricers;


    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->businessUnits = new ArrayCollection();
        $this->pricers = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFullName(): ?string
    {
        return $this->firstName . " " . $this->lastName;
    }

    /**
     * @return null|string
     */
    public function getFunction(): ?string
    {
        return $this->function;
    }

    /**
     * @param string $function
     *
     * @return User
     */
    public function setFunction(string $function): self
    {
        $this->function = $function;

        return $this;
    }

    /**
     * @return Collection|BusinessUnit[]
     */
    public function getBusinessUnits(): Collection
    {
        return $this->businessUnits;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return User
     */
    public function addBusinessUnit(BusinessUnit $businessUnit): self
    {
        if (!$this->businessUnits->contains($businessUnit)) {
            $this->businessUnits[] = $businessUnit;
        }

        return $this;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return User
     */
    public function removeBusinessUnit(BusinessUnit $businessUnit): self
    {
        if ($this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->removeElement($businessUnit);
        }

        return $this;
    }

    /**
     * @param string|null $role
     * @return User
     */
    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        return $this->role == $role;
    }

    public function hasCylipolRole(): bool
    {
        return $this->role ? true : false;
    }

    /**
     * @param string $nameId
     *
     * @return self
     */
    public function setNameId(string $nameId): self
    {
        $this->nameId = $nameId;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameId(): ?string
    {
        return $this->nameId;
    }

    /**
     * Set SAML attributes in user object.
     *
     * @param array $attributes
     */
    public function setSamlAttributes(array $attributes): void
    {

    }

    /**
     * @return bool
     */
    public function isTrader(): bool
    {
        return $this->hasRole(self::ROLE_TRADER);
    }

    /**
     * @return bool
     */
    public function isBoardMember(): bool
    {
        return $this->hasRole(self::ROLE_BOARD_MEMBER);
    }

    /**
     * @return bool
     */
    public function isRiskController(): bool
    {
        return $this->hasRole(self::ROLE_RISK_CONTROLLER);
    }

    /**
     * @return bool
     */
    public function isBuHedgingCommittee(): bool
    {
        return $this->hasRole(self::ROLE_BU_HEDGING_COMMITTEE);
    }

    /**
     * @return bool
     */
    public function isBuMember(): bool
    {
        return $this->hasRole(self::ROLE_BU_MEMBER);
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }

    /**
     * @return Collection|Pricer[]
     */
    public function getPricers(): Collection
    {
        return $this->pricers;
    }

    public function addPricer(Pricer $pricer): self
    {
        if (!$this->pricers->contains($pricer)) {
            $this->pricers[] = $pricer;
            $pricer->setUploadUser($this);
        }

        return $this;
    }

    public function removePricer(Pricer $pricer): self
    {
        if ($this->pricers->contains($pricer)) {
            $this->pricers->removeElement($pricer);
            // set the owning side to null (unless already changed)
            if ($pricer->getUploadUser() === $this) {
                $pricer->setUploadUser(null);
            }
        }

        return $this;
    }
}
