<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ContactService;
use App\Service\MailService;

final class ContactController extends BaseController
{
    public function __construct(
        private readonly ContactService $contactService,
        private readonly MailService $mailService
    ) {
    }

    public function index(): void
    {
        $this->render('contact/index');
    }

    public function send(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }

        if ($subject === '' || mb_strlen($subject) > 150) {
            $errors['subject'] = 'Le titre est requis et doit contenir au maximum 150 caractères.';
        }

        if ($message === '') {
            $errors['message'] = 'La description est requise.';
        }

        if ($errors !== []) {
            $_SESSION['contact_errors'] = $errors;
            $_SESSION['contact_old_input'] = $_POST;
            $this->redirect('/contact');
        }

        try {
            $this->contactService->createMessage($email, $subject, $message);

            $companyEmail = $_ENV['MAIL_TO_ADDRESS']
                ?? 'contact@website.dylan.local';

            $body = "Message reçu depuis le formulaire de contact.\n\n"
                . "Email : {$email}\nTitre : {$subject}\n\n{$message}";

            if (!$this->mailService->send(
                $companyEmail,
                'Formulaire de contact : ' . $subject,
                $body
            )) {
                error_log('Impossible d’envoyer le message de contact à ' . $companyEmail);
            }

            $_SESSION['contact_success'] = 'Votre message a bien été envoyé.';
        } catch (\Throwable $exception) {
            error_log('Contact form error: ' . $exception->getMessage());
            $_SESSION['contact_errors'] = [
                'general' => 'Impossible d’enregistrer votre message.',
            ];
        }

        $this->redirect('/contact');
    }
}
