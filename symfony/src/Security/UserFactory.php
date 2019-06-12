<?php
namespace App\Security;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
class UserFactory implements SamlUserFactoryInterface
{

    const ATTRIBUTE_FIRST_NAME = 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname';
    const ATTRIBUTE_LAST_NAME = 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createUser(SamlTokenInterface $token)
    {
        $nameId = $token->getUsername();
        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->findOneBy(['nameId' => $nameId]);
        if (!$user) {
            $user = new User();
            $user->setRoles([]);
            $user->setRole(User::ROLE_BU_MEMBER);
            $user->setFirstName($token->getAttribute(self::ATTRIBUTE_FIRST_NAME)[0]);
            $user->setLastName($token->getAttribute(self::ATTRIBUTE_LAST_NAME)[0]);
            $user->setUsername($nameId);
            $user->setEmail($nameId);
            $user->setNameId($nameId);
            $user->setFunction('TBD');
            $this->em->persist($user);
            $this->em->flush();
        }

        if (!$user->isEnabled()) {
            throw new UnauthorizedHttpException('Basic', 'The authentication failed.');
        }

        return $user;
    }
}