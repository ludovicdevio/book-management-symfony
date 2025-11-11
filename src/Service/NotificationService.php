<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Loan;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

/**
 * Service de notification
 *
 * Design Pattern : Strategy
 * Permet de changer facilement la stratégie d'envoi (email, SMS, push)
 *
 * Design Pattern : Template Method (via Twig pour les emails)
 */
class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail = 'noreply@bibliotheque.fr',
        private string $fromName = 'Bibliothèque en ligne'
    ) {}

    /**
     * Notification lors de la création d'un emprunt
     */
    public function notifyLoanCreated(Loan $loan): void
    {
        $user = $loan->getUser();
        $book = $loan->getBook();

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Confirmation d\'emprunt')
            ->htmlTemplate('emails/loan_created.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $user,
                'book' => $book,
            ]);

        try {
            $this->mailer->send($email);
            $this->logger->info('Loan confirmation email sent', [
                'user_email' => $user->getEmail(),
                'loan_id' => $loan->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send loan confirmation email', [
                'error' => $e->getMessage(),
                'user_email' => $user->getEmail(),
            ]);
        }
    }

    /**
     * Notification lors du retour d'un livre
     */
    public function notifyLoanReturned(Loan $loan): void
    {
        $user = $loan->getUser();

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Confirmation de retour')
            ->htmlTemplate('emails/loan_returned.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $user,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send return confirmation email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notification lors de la prolongation d'un emprunt
     */
    public function notifyLoanExtended(Loan $loan): void
    {
        $user = $loan->getUser();

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Prolongation d\'emprunt')
            ->htmlTemplate('emails/loan_extended.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $user,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send extension confirmation email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rappel pour les emprunts en retard
     */
    public function notifyOverdueReminder(Loan $loan): void
    {
        $user = $loan->getUser();
        $book = $loan->getBook();

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Rappel : Livre en retard')
            ->htmlTemplate('emails/loan_overdue.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $user,
                'book' => $book,
                'overdueDays' => $loan->getOverdueDays(),
            ]);

        try {
            $this->mailer->send($email);
            $this->logger->info('Overdue reminder sent', [
                'user_email' => $user->getEmail(),
                'loan_id' => $loan->getId(),
                'overdue_days' => $loan->getOverdueDays(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send overdue reminder', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rappel avant la date d'échéance
     */
    public function notifyDueDateReminder(Loan $loan): void
    {
        $user = $loan->getUser();
        $book = $loan->getBook();

        $daysUntilDue = (new \DateTimeImmutable())->diff($loan->getDueDate())->days;

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Rappel : Date de retour approchante')
            ->htmlTemplate('emails/loan_due_soon.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $user,
                'book' => $book,
                'daysUntilDue' => $daysUntilDue,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send due date reminder', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
