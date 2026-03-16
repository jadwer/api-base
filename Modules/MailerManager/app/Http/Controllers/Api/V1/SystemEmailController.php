<?php

namespace Modules\MailerManager\Http\Controllers\Api\V1;

use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\MailerManager\Models\SystemEmail;
use Modules\MailerManager\Services\TemplateRenderService;

class SystemEmailController extends JsonApiController
{
    public function preview(Request $request, SystemEmail $systemEmail, TemplateRenderService $renderService): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('system-emails.show') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $template = $systemEmail->emailTemplate;
        $sampleData = $systemEmail->sample_data ?? [];

        if (!$template) {
            return response()->json([
                'data' => [
                    'subject' => $systemEmail->default_subject ?? $systemEmail->name,
                    'html' => '<div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">'
                        . '<div style="background: #f0f4f8; border-radius: 8px; padding: 20px; text-align: center;">'
                        . '<h2 style="color: #1a3764;">Template por Defecto</h2>'
                        . '<p style="color: #555;">Este email (<strong>' . e($systemEmail->name) . '</strong>) usa el template Blade del sistema.</p>'
                        . '<p style="color: #888; font-size: 13px;">Asigna un template personalizado para cambiar su apariencia.</p>'
                        . '</div></div>',
                    'has_custom_template' => false,
                    'available_variables' => $systemEmail->available_variables,
                ],
            ]);
        }

        $renderedHtml = $renderService->renderPreview($template, $sampleData);
        $renderedSubject = $renderService->renderSubject($template, $sampleData);

        return response()->json([
            'data' => [
                'subject' => $renderedSubject,
                'html' => $renderedHtml,
                'has_custom_template' => true,
                'available_variables' => $systemEmail->available_variables,
            ],
        ]);
    }

    public function sendTest(Request $request, SystemEmail $systemEmail, TemplateRenderService $renderService): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('system-emails.update') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate(['email' => 'required|email']);

        try {
            $template = $systemEmail->emailTemplate;
            $sampleData = $systemEmail->sample_data ?? [];

            if ($template) {
                $renderedHtml = $renderService->renderPreview($template, $sampleData);
                $renderedSubject = $renderService->renderSubject($template, $sampleData);
                $usedCustomTemplate = true;
            } else {
                $renderedHtml = '<div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">'
                    . '<h2 style="color: #1a3764;">' . e($systemEmail->name) . '</h2>'
                    . '<p>Este es un email de prueba del sistema.</p>'
                    . '<p style="color: #888;">Este email usa el template Blade por defecto. Asigna un template personalizado desde el Mailer Manager.</p>'
                    . '</div>';
                $renderedSubject = $systemEmail->default_subject ?? $systemEmail->name;
                $usedCustomTemplate = false;
            }

            // Embed images if using custom template
            $attachments = [];
            if ($usedCustomTemplate) {
                $renderedHtml = $renderService->embedImages($renderedHtml, $attachments);
            }

            Mail::html($renderedHtml, function ($message) use ($request, $renderedSubject, $attachments) {
                $message->to($request->input('email'))
                    ->subject('[TEST] ' . $renderedSubject);

                foreach ($attachments as $attachment) {
                    $message->attachData(
                        file_get_contents($attachment['path']),
                        basename($attachment['path']),
                        ['mime' => $attachment['mime']]
                    );
                }
            });

            return response()->json([
                'message' => 'Email de prueba enviado a ' . $request->input('email'),
                'email' => $request->input('email'),
                'used_custom_template' => $usedCustomTemplate,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al enviar email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
