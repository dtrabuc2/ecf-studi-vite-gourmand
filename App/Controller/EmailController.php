<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Service\EmailService;
use Throwable;

final class EmailController extends BaseController
{
    public function __construct(private readonly EmailService $emailService)
    {
    }

    public function inbox(): void
    {
        $mailbox = ($_GET['mailbox'] ?? 'contact') === 'mail.ai' ? 'mail.ai' : 'contact';
        $requestedFolder = (string) ($_GET['folder'] ?? 'inbox');
        $folder = in_array($requestedFolder, ['inbox', 'sent', 'drafts', 'trash'], true)
            ? $requestedFolder
            : 'inbox';
        $selectedId = isset($_GET['message']) ? (int) $_GET['message'] : 0;

        $messages = $this->emailService->listFolder($mailbox, $folder);
        $outbox = $this->emailService->listOutbox($mailbox);
        $selectedMessage = $selectedId > 0
            ? $this->emailService->findFolderMessage($mailbox, $folder, $selectedId)
            : null;

        $this->render('admin/emails', [
            'mailbox' => $mailbox,
            'folder' => $folder,
            'messages' => $messages,
            'outbox' => $outbox,
            'selectedMessage' => $selectedMessage,
        ]);
    }

    public function reply(int $id): never
    {
        $message = $this->emailService->findMessage($id);
        $mailbox = $message !== null && str_ends_with((string) $message['email'], '@mail.ai')
            ? 'mail.ai'
            : 'contact';

        try {
            $sent = $this->emailService->reply($id, (string) ($_POST['body'] ?? ''));
            Session::flash(
                'admin_success',
                $sent ? 'Réponse envoyée.' : 'Réponse enregistrée mais SMTP indisponible.'
            );
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/emails?mailbox=' . $mailbox . '&message=' . $id);
    }

    public function simulate(): never
    {
        try {
            $id = $this->emailService->simulateIncoming(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['subject'] ?? ''),
                (string) ($_POST['body'] ?? '')
            );
            Session::flash('admin_success', 'Message mail.ai simulé.');
            $this->redirect('/admin/emails?mailbox=mail.ai&message=' . $id);
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
            $this->redirect('/admin/emails?mailbox=mail.ai');
        }
    }

    public function sendTest(): never
    {
        try {
            $sent = $this->emailService->sendTest(
                (string) ($_POST['recipient'] ?? ''),
                (string) ($_POST['subject'] ?? ''),
                (string) ($_POST['body'] ?? '')
            );
            Session::flash(
                'admin_success',
                $sent ? 'Email de test envoyé.' : 'Email de test journalisé, SMTP indisponible.'
            );
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/emails?mailbox=mail.ai');
    }

    public function saveDraft(): never
    {
        $mailbox = ($_POST['mailbox'] ?? 'contact') === 'mail.ai' ? 'mail.ai' : 'contact';

        try {
            $this->emailService->saveDraft(
                $mailbox,
                (string) ($_POST['recipient'] ?? ''),
                (string) ($_POST['subject'] ?? ''),
                (string) ($_POST['body'] ?? '')
            );
            Session::flash('admin_success', 'Brouillon enregistré.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/emails?mailbox=' . $mailbox . '&folder=drafts');
    }

    public function sendComposed(): never
    {
        $mailbox = ($_POST['mailbox'] ?? 'contact') === 'mail.ai' ? 'mail.ai' : 'contact';

        try {
            $sent = $this->emailService->sendComposed(
                $mailbox,
                (string) ($_POST['recipient'] ?? ''),
                (string) ($_POST['subject'] ?? ''),
                (string) ($_POST['body'] ?? '')
            );
            Session::flash('admin_success', $sent ? 'Email envoyé.' : 'Email enregistré mais SMTP indisponible.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/emails?mailbox=' . $mailbox . '&folder=sent');
    }

    public function trash(string $source, int $id): never
    {
        $this->emailService->moveToTrash($source === 'outbox' ? 'outbox' : 'inbox', $id);
        Session::flash('admin_success', 'Message déplacé dans la corbeille.');
        $mailbox = ($_POST['mailbox'] ?? 'contact') === 'mail.ai' ? 'mail.ai' : 'contact';
        $this->redirect('/admin/emails?mailbox=' . $mailbox . '&folder=trash');
    }
}
