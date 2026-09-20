<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Service\QuoteService;
use Throwable;

final class QuoteController extends BaseController
{
    public function __construct(
        private readonly QuoteService $quoteService
    ) {
    }

    public function index(): void
    {
        $this->render('quote/index');
    }

    public function submit(): void
    {
        $data = [
            'email' => trim((string) ($_POST['email'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'company' => trim((string) ($_POST['company'] ?? '')),
            'event_date' => trim((string) ($_POST['event_date'] ?? '')),
            'number_of_people' => $_POST['number_of_people'] ?? null,
            'service_type' => trim((string) ($_POST['service_type'] ?? '')),
            'event_location' => trim((string) ($_POST['event_location'] ?? '')),
            'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
            'request_details' => trim((string) ($_POST['request_details'] ?? '')),
        ];

        try {
            $id = $this->quoteService->create($data, Session::id());
            Session::flash(
                'quote_success',
                'Votre demande de devis a bien été enregistrée. Un accusé de réception vous a été envoyé par email.'
            );
            $this->redirect('/quote?sent=' . $id);
        } catch (Throwable $exception) {
            error_log('Quote request: ' . $exception->getMessage());
            Session::flash('quote_error', $exception->getMessage());
            Session::flash('quote_old_input', $data);
            $this->redirect('/quote');
        }
    }
}
