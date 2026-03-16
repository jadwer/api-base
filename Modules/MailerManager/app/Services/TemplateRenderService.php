<?php

namespace Modules\MailerManager\Services;

use Modules\MailerManager\Models\EmailTemplate;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class TemplateRenderService
{
    public function __construct(
        protected VariableReplacer $variableReplacer
    ) {}

    /**
     * Full render pipeline:
     * 1. Take HTML + CSS from EmailTemplate
     * 2. Replace variables with data
     * 3. Inline CSS for email client compatibility
     * 4. Return final HTML
     */
    public function render(EmailTemplate $template, array $variables = []): string
    {
        $html = $template->html ?? '';
        $css = $template->css ?? '';

        if (empty($html)) {
            return '<p>Template sin contenido</p>';
        }

        // Step 1: Replace variables
        $html = $this->variableReplacer->replace($html, $variables);

        // Step 2: Also replace variables in any inline style attributes
        // (in case variables are used in style values)

        // Step 3: Inline CSS
        $html = $this->inlineCss($html, $css);

        return $html;
    }

    /**
     * Render a template subject line with variables.
     */
    public function renderSubject(EmailTemplate $template, array $variables = []): string
    {
        $subject = $template->subject ?? '';
        if (empty($subject)) {
            return $template->name;
        }

        return $this->variableReplacer->replace($subject, $variables);
    }

    /**
     * Render with sample data from a SystemEmail registry entry.
     */
    public function renderPreview(EmailTemplate $template, ?array $sampleData = null): string
    {
        $variables = $sampleData ?? $this->getDefaultSampleData();
        return $this->render($template, $variables);
    }

    /**
     * Inline CSS into HTML for email client compatibility.
     * Gmail, Outlook, etc. strip <style> tags, so CSS must be inline.
     */
    protected function inlineCss(string $html, string $css): string
    {
        if (empty($css)) {
            return $html;
        }

        // Ensure HTML has proper structure for CSS inlining
        if (!str_contains($html, '<html')) {
            $html = '<html><head></head><body>' . $html . '</body></html>';
        }

        $inliner = new CssToInlineStyles();
        return $inliner->convert($html, $css);
    }

    /**
     * Get image paths from template HTML for CID embedding.
     * Returns array of [src => local_path] for images stored in our system.
     */
    public function extractLocalImages(string $html): array
    {
        $images = [];

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $src) {
                // Only process local storage URLs
                if (str_contains($src, '/storage/email-templates/')) {
                    $relativePath = preg_replace('#.*/storage/#', '', $src);
                    if ($relativePath) {
                        $localPath = storage_path('app/public/' . $relativePath);
                        if (file_exists($localPath)) {
                            $images[$src] = $localPath;
                        }
                    }
                }
            }
        }

        return $images;
    }

    /**
     * Replace local image URLs with CID references for email embedding.
     */
    public function embedImages(string $html, array &$attachments): string
    {
        $localImages = $this->extractLocalImages($html);

        foreach ($localImages as $src => $localPath) {
            $cid = 'img_' . md5($src);
            $html = str_replace($src, 'cid:' . $cid, $html);
            $attachments[] = [
                'path' => $localPath,
                'cid' => $cid,
                'mime' => mime_content_type($localPath) ?: 'image/png',
            ];
        }

        return $html;
    }

    protected function getDefaultSampleData(): array
    {
        return [
            'customer_name' => 'Juan Perez',
            'customer_email' => 'juan@example.com',
            'company_name' => 'Labor Wasser de Mexico',
            'company_email' => 'ventas@laborwasserdemexico.com',
            'quote_number' => 'COT-26000001',
            'order_number' => 'OV-26000001',
            'total' => 3015.98,
            'subtotal' => 2599.98,
            'tax' => 416.00,
            'currency' => 'MXN',
            'date' => date('d/m/Y'),
        ];
    }
}
