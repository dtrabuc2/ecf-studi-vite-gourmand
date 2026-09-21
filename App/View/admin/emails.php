<main class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <span class="eyebrow">Centre de messages</span>
                <h1 class="h2 text-primary mt-2 mb-1">Boîte email</h1>
                <p class="text-muted mb-0">Messages reçus par le formulaire et boîte locale de test.</p>
            </div>
            <div class="btn-group" role="group" aria-label="Boîte sélectionnée">
                <a class="btn <?= $mailbox === 'contact' ? 'btn-primary' : 'btn-outline-primary' ?>" href="/admin/emails?mailbox=contact">Contact</a>
                <a class="btn <?= $mailbox === 'mail.ai' ? 'btn-primary' : 'btn-outline-primary' ?>" href="/admin/emails?mailbox=mail.ai">mail.ai</a>
            </div>
        </div>

        <nav class="email-folders mb-4" aria-label="Dossiers email">
            <?php foreach (['inbox' => 'Reçus', 'sent' => 'Envoyés', 'drafts' => 'Brouillons', 'trash' => 'Corbeille'] as $key => $label): ?>
                <a class="email-folder <?= $folder === $key ? 'is-active' : '' ?>" href="/admin/emails?mailbox=<?= urlencode($mailbox) ?>&folder=<?= $key ?>">
                    <span><?= $label ?></span>
                    <?php if ($folder === $key): ?><span class="email-folder__mark"></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($selectedMessage !== null): ?>
            <section class="email-reader mb-4">
                <div class="email-reader__header">
                    <div>
                        <span class="small text-uppercase opacity-75">Message #<?= (int) $selectedMessage['id'] ?></span>
                        <h2 class="h4 mb-1"><?= $escape($selectedMessage['subject']) ?></h2>
                        <p class="mb-0 small">De <?= $escape($selectedMessage['email']) ?> · <?= $escape($selectedMessage['created_at']) ?></p>
                    </div>
                    <span class="badge text-bg-light"><?= $escape($selectedMessage['status']) ?></span>
                </div>
                <div class="email-reader__body"><?= nl2br($escape($selectedMessage['message'])) ?></div>
                <?php if ($folder === 'inbox'): ?>
                    <form method="post" action="/admin/emails/<?= (int) $selectedMessage['id'] ?>/reply" class="email-reply">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="mailbox" value="<?= $escape($mailbox) ?>">
                        <label class="form-label fw-semibold" for="email_reply">Répondre</label>
                        <textarea class="form-control" id="email_reply" name="body" rows="5" required placeholder="Votre réponse..."></textarea>
                        <div class="d-flex justify-content-between gap-2 mt-3">
                            <button class="btn btn-outline-secondary" formaction="/admin/emails/<?= (int) $selectedMessage['source'] ?>/<?= (int) $selectedMessage['id'] ?>/trash" type="submit">Corbeille</button>
                            <button class="btn btn-primary" type="submit">Envoyer la réponse</button>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="post" action="/admin/emails/<?= $escape($selectedMessage['source']) ?>/<?= (int) $selectedMessage['id'] ?>/trash" class="email-reply text-end">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="mailbox" value="<?= $escape($mailbox) ?>">
                        <button class="btn btn-outline-secondary" type="submit">Déplacer dans la corbeille</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom"><h2 class="h5 mb-0"><?= $escape(['inbox' => 'Messages reçus', 'sent' => 'Messages envoyés', 'drafts' => 'Brouillons', 'trash' => 'Corbeille'][$folder] ?? 'Messages') ?></h2></div>
                        <?php foreach ($messages as $message): ?>
                            <a class="email-row <?= (int) ($selectedMessage['id'] ?? 0) === (int) $message['id'] ? 'is-active' : '' ?>" href="/admin/emails?mailbox=<?= urlencode($mailbox) ?>&folder=<?= $folder ?>&message=<?= (int) $message['id'] ?>">
                                <span class="email-row__dot <?= $message['status'] === 'new' ? 'is-new' : '' ?>"></span>
                                <span class="email-row__content"><strong><?= $escape($message['subject']) ?></strong><small><?= $escape($message['email']) ?> · <?= $escape(mb_strimwidth($message['message'], 0, 90, '...')) ?></small></span>
                                <time><?= $escape(substr((string) $message['created_at'], 0, 16)) ?></time>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($messages === []): ?><p class="p-4 text-muted mb-0">Aucun message dans cette boîte.</p><?php endif; ?>
                    </div>
                </section>
            </div>
            <div class="col-lg-5">
                <?php if ($mailbox === 'mail.ai' && $folder === 'inbox'): ?>
                    <section class="card border-0 shadow-sm">
                        <div class="card-body p-4"><h2 class="h5 text-primary">Simuler un entrant</h2><p class="small text-muted">Les adresses <strong>@mail.ai</strong> sont locales et ne reçoivent pas de vrais emails Internet.</p>
                            <form method="post" action="/admin/emails/simulate">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <label class="form-label" for="fake_email">Adresse</label><input class="form-control mb-3" id="fake_email" name="email" type="email" placeholder="client@mail.ai" required>
                                <label class="form-label" for="fake_subject">Sujet</label><input class="form-control mb-3" id="fake_subject" name="subject" required>
                                <label class="form-label" for="fake_body">Message</label><textarea class="form-control mb-3" id="fake_body" name="body" rows="5" required></textarea>
                                <button class="btn btn-outline-primary w-100" type="submit">Créer le message de test</button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>
                <section class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4"><h2 class="h5 text-primary">Nouveau message</h2><p class="small text-muted">Les brouillons restent disponibles dans leur dossier.</p>
                        <form method="post" action="/admin/emails/send">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="mailbox" value="<?= $escape($mailbox) ?>">
                            <label class="form-label" for="compose_recipient">Destinataire</label><input class="form-control mb-3" id="compose_recipient" name="recipient" type="email" placeholder="contact@example.com" required>
                            <label class="form-label" for="compose_subject">Sujet</label><input class="form-control mb-3" id="compose_subject" name="subject" required>
                            <label class="form-label" for="compose_body">Message</label><textarea class="form-control mb-3" id="compose_body" name="body" rows="4"></textarea>
                            <div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Envoyer</button><button class="btn btn-outline-secondary" type="submit" formaction="/admin/emails/draft">Brouillon</button></div>
                        </form>
                    </div>
                </section>
            </div>
        </div>

        <section class="card border-0 shadow-sm mt-4">
            <div class="card-body p-0">
                <div class="p-4 border-bottom"><h2 class="h5 mb-0">Historique des envois · <?= $mailbox === 'mail.ai' ? 'mail.ai' : 'contact' ?></h2></div>
                <?php foreach ($outbox as $sent): ?>
                    <div class="email-outbox-row"><span><strong><?= $escape($sent['subject']) ?></strong><small><?= $escape($sent['recipient']) ?></small></span><span class="badge <?= $sent['status'] === 'sent' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= $escape($sent['status']) ?></span></div>
                <?php endforeach; ?>
                <?php if ($outbox === []): ?><p class="p-4 text-muted mb-0">Aucun envoi dans cette boîte.</p><?php endif; ?>
            </div>
        </section>
    </div>
</main>
