<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service that allows to generate a mail
 */
class MailerService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $templateTwig
     * @param array $context
     * @return void
     * @throws TransportExceptionInterface
     */
    public function sendMail(string $to, string $subject, string $templateTwig, array $context): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@wizardshop.com', 'Wizard Shop'))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate("mails/$templateTwig")
            ->context($context);

        $this->mailer->send($email);
    }
}
