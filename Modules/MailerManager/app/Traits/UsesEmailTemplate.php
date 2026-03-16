<?php

namespace Modules\MailerManager\Traits;

use Modules\MailerManager\Models\SystemEmail;
use Modules\MailerManager\Services\TemplateRenderService;

/**
 * Trait for Mailables to support email template override from MailerManager.
 *
 * Usage in a Mailable:
 *   use UsesEmailTemplate;
 *   protected function getRegistryKey(): string { return 'sales.quote_sent'; }
 *   protected function getTemplateVariables(): array { return [...]; }
 *
 * Then in build()/content(), call buildWithTemplate() first:
 *   $result = $this->buildWithTemplate();
 *   if ($result !== null) return $result;
 *   // ... original Blade rendering
 */
trait UsesEmailTemplate
{
    /**
     * Return the system email registry key for this Mailable.
     * Override in each Mailable.
     */
    protected function getRegistryKey(): string
    {
        return '';
    }

    /**
     * Return the variables to inject into the custom template.
     * Override in each Mailable to extract data from the model.
     */
    protected function getTemplateVariables(): array
    {
        return [];
    }

    /**
     * Check if this email should be sent (is_enabled in registry).
     */
    public function shouldSend(): bool
    {
        $key = $this->getRegistryKey();
        if (empty($key)) {
            return true;
        }

        return SystemEmail::isEnabled($key);
    }

    /**
     * Try to build the email using a custom template from the registry.
     * Returns $this (the Mailable) if a template is found and rendered,
     * or null if no custom template is assigned (use default Blade).
     */
    protected function buildWithTemplate(): ?static
    {
        $key = $this->getRegistryKey();
        if (empty($key)) {
            return null;
        }

        $template = SystemEmail::getTemplate($key);
        if (!$template) {
            return null;
        }

        try {
            $renderService = app(TemplateRenderService::class);
            $variables = $this->getTemplateVariables();

            $html = $renderService->render($template, $variables);
            $subject = $renderService->renderSubject($template, $variables);

            // Embed local images as CID attachments
            $attachments = [];
            $html = $renderService->embedImages($html, $attachments);

            $this->subject($subject)->html($html);

            foreach ($attachments as $attachment) {
                $this->attachData(
                    file_get_contents($attachment['path']),
                    basename($attachment['path']),
                    [
                        'mime' => $attachment['mime'],
                        'as' => basename($attachment['path']),
                    ]
                );
            }

            return $this;
        } catch (\Throwable) {
            // If rendering fails, fall back to default Blade
            return null;
        }
    }
}
