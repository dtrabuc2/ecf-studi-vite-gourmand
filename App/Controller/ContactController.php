<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ContactService;
use App\Service\MailService;
use App\Service\NotificationService;
use App\Core\Session;

final class ContactController extends BaseController
{
    public function __construct(
        private readonly ContactService $contactService,
        private readonly MailService $mailService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(): void
    {
        $this->render('contact/index', [
            'errors' => (array) Session::pullFlash('contact_errors', []),
            'old' => (array) Session::pullFlash('contact_old_input', []),
        ]);
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
            Session::flash('contact_errors', $errors);
            Session::flash('contact_old_input', ['email' => $email, 'subject' => $subject, 'message' => $message]);
            $this->redirect('/contact');
        }

        try {
            $messageId = $this->contactService->createMessage($email, $subject, $message);

            // MAIL_TO_ADDRESS du .env (à défaut MAIL_FROM_ADDRESS).
            $companyEmail = $this->mailService->companyAddress();

            $body = "Message reçu depuis le formulaire de contact.\n\n"
                . "Email : {$email}\nTitre : {$subject}\n\n{$message}";

            if (!$this->mailService->send(
                $companyEmail,
                'Formulaire de contact : ' . $subject,
                $body
            )) {
                error_log('Impossible d’envoyer le message de contact à ' . $companyEmail);
            }

            if (Session::id() !== null) {
                $this->notificationService->notify(
                    Session::id(),
                    'contact',
                    'Message envoyé',
                    'Votre message de contact #' . $messageId . ' a bien été enregistré.',
                    null,
                    null
                );
            }

            $this->notificationService->notifyStaff(
                'contact',
                'Nouveau message de contact',
                'Le message « ' . $subject . ' » de ' . $email . ' est disponible dans la boîte de réception.'
            );

            Session::flash(
                'contact_success',
                Session::id() !== null
                    ? 'Votre message a bien été envoyé. Une confirmation a été enregistrée dans votre espace.'
                    : 'Votre message a bien été envoyé.'
            );
        } catch (\Throwable $exception) {
            error_log('Contact form error: ' . $exception->getMessage());
            Session::flash('contact_errors', ['general' => 'Impossible d’enregistrer votre message.']);
            Session::flash('contact_old_input', ['email' => $email, 'subject' => $subject, 'message' => $message]);
        }

        $this->redirect('/contact');
    }
}
