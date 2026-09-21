<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final readonly class EmailTemplateRenderer
{
    public function __construct(private string $templateDirectory)
    {
    }

    public function render(string $template, array $values): string
    {
        $path = rtrim($this->templateDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($template);
        $content = is_file($path) ? file_get_contents($path) : false;

        if ($content === false) {
            throw new RuntimeException('Modèle email introuvable : ' . $template);
        }

        foreach ($values as $key => $value) {
            $content = str_replace(
                '{{' . $key . '}}',
                str_ends_with((string) $key, '_html')
                    ? (string) $value
                    : htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $content
            );
        }

        return $content;
    }
}
