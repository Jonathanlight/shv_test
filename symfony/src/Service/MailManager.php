<?php

namespace App\Service;

use App\Entity\CMS\Letter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Templating\EngineInterface;

class MailManager
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @param EntityManagerInterface $entityManager
     * @param EngineInterface $engine
     * @param \Swift_Mailer $mailer
     */
    public function __construct(EntityManagerInterface $entityManager, EngineInterface $engine, \Swift_Mailer $mailer)
    {
        $this->em = $entityManager;
        $this->engine = $engine;
        $this->mailer = $mailer;
    }

    /**
     * @param string $code
     * @param string $email
     * @param array $bindings
     * @param array $attachments
     */
    public function send(string $code, ?string $email, array $bindings, ?array $attachments = null): void
    {
        $letter = $this->em->getRepository(Letter::class)->findOneByCode($code);

        if (!$letter instanceof Letter || null === $email) {
            return;
        }

        $message = new \Swift_Message();

        $instance = getenv('APP_INSTANCE');
        $subject = '';

        if ($instance != 'PRODUCTION') {
            $subject = $instance . ' - ';
        }

        $subject .=  $letter->getSubject();

        $message
            ->setSubject($subject)
            ->setFrom([getenv('MAILER_FROM') => 'SHV'])
            ->setTo($email)
            ->setBody($this->engine->render('mail/mail.html.twig', [
                'content' => $this->setBinding($letter->getContent(), $bindings)
            ]), 'text/html')
        ;

        if (!empty($attachments)) {
            foreach ($attachments as $data) {
                $attachment = new \Swift_Attachment(
                    $data['pdf'],
                    $data['filename'],
                    'application/pdf'
                );

                $message->attach($attachment);
            }
        }

        $this->mailer->send($message);
    }

    /**
     * @param string $content
     * @param array $bindings
     * @return string
     */
    private function setBinding(string $content, array $bindings): string
    {
        return \str_replace(
            array_map(function ($binding) {
                return '%' . $binding . '%';
            }, array_keys($bindings)),
            array_values($bindings),
            $content
        );
    }
}