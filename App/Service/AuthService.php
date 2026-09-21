<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final readonly class AuthService
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function validatePassword(string $password): ?array
    {
        $errors = [];

        if (strlen($password) < 10) {
            $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return $errors === [] ? null : $errors;
    }

    public function validatePhone(string $phone, string $defaultRegion = 'FR'): ?string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return 'Le numéro de téléphone est requis.';
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $parsed = $phoneUtil->parse($phone, strtoupper($defaultRegion));

            if (!$phoneUtil->isValidNumber($parsed)) {
                return 'Numéro de téléphone invalide pour le pays sélectionné.';
            }

            $region = $phoneUtil->getRegionCodeForNumber($parsed);

            if (!in_array($region, ['FR', 'ES', 'BE', 'GB', 'IT'], true)) {
                return 'Seuls les numéros de France, Espagne, Belgique, Royaume-Uni et Italie sont acceptés.';
            }

            return null;
        } catch (\Throwable) {
            return 'Numéro de téléphone invalide.';
        }
    }

    public function normalizePhone(string $phone, string $defaultRegion = 'FR'): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsed = $phoneUtil->parse(trim($phone), strtoupper($defaultRegion));

        return $phoneUtil->format($parsed, PhoneNumberFormat::E164);
    }

    public function register(array $data): int
    {
        $email = $this->normalizeEmail((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        $errors = $this->validatePassword($password);
        if ($errors !== null) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        return $this->userRepository->create([
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'gsm' => trim((string) ($data['gsm'] ?? '')),
            'address' => trim((string) ($data['address'] ?? '')),
        ]);
    }

    public function login(string $email, string $password): ?User
    {
        $email = $this->normalizeEmail($email);
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !$this->userRepository->isActive($user->getId())) {
            return null;
        }

        $lockedUntil = $user->getLockedUntil();

        if (
            $lockedUntil !== null
            && $lockedUntil->getTimestamp() > time()
        ) {
            return null;
        }

        $storedPassword = $user->getPasswordHash();
        $passwordInfo = password_get_info($storedPassword);
        $passwordMatches = ($passwordInfo['algoName'] ?? 'unknown') !== 'unknown'
            ? password_verify($password, $storedPassword)
            : hash_equals($storedPassword, $password);

        if (!$passwordMatches) {
            $failedAttempts = $user->getFailedAttempts() + 1;

            $this->userRepository->updateFailedAttempts(
                $user->getId(),
                $failedAttempts,
                $failedAttempts >= 5
                    ? new DateTimeImmutable('+15 minutes')
                    : null
            );

            return null;
        }

        if (
            ($passwordInfo['algoName'] ?? 'unknown') !== 'unknown'
            && password_needs_rehash($storedPassword, PASSWORD_DEFAULT)
        ) {
            $this->userRepository->updatePassword(
                $user->getId(),
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        $this->userRepository->updateFailedAttempts(
            $user->getId(),
            0,
            null
        );

        return $user;
    }

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): void {
        $user = $this->userRepository->findById($userId);

        if (
            $user === null
            || !password_verify(
                $currentPassword,
                $user->getPasswordHash()
            )
        ) {
            throw new InvalidArgumentException(
                'Mot de passe actuel incorrect.'
            );
        }

        $errors = $this->validatePassword($newPassword);
        if ($errors !== null) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $this->userRepository->updatePassword(
            $userId,
            password_hash($newPassword, PASSWORD_DEFAULT)
        );
        $this->userRepository->updateFailedAttempts(
            $userId,
            0,
            null
        );
    }

    public function updateProfile(int $userId, array $data): void
    {
        $email = $this->normalizeEmail((string) ($data['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        $existing = $this->userRepository->findByEmail($email);

        if (
            $existing !== null
            && $existing->getId() !== $userId
        ) {
            throw new InvalidArgumentException(
                'Cette adresse email est déjà utilisée.'
            );
        }

        $data['email'] = $email;
        $this->userRepository->update($userId, $data);
    }

    public function createResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail(
            $this->normalizeEmail($email)
        );

        if (
            $user === null
            || !$this->userRepository->isActive($user->getId())
        ) {
            return null;
        }

        $token = bin2hex(random_bytes(32));

        $this->userRepository->updateResetToken(
            $user->getId(),
            hash('sha256', $token),
            new DateTimeImmutable('+1 hour')
        );

        return $token;
    }

    public function validateResetToken(string $token): ?User
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return $this->userRepository->findByResetTokenHash(
            hash('sha256', $token)
        );
    }

    public function resetPassword(
        string $token,
        string $newPassword
    ): void {
        $user = $this->validateResetToken($token);

        if ($user === null) {
            throw new InvalidArgumentException(
                'Le lien de réinitialisation est invalide ou expiré.'
            );
        }

        $errors = $this->validatePassword($newPassword);
        if ($errors !== null) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $this->userRepository->updatePassword(
            $user->getId(),
            password_hash($newPassword, PASSWORD_DEFAULT)
        );
        $this->userRepository->clearResetToken($user->getId());
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}