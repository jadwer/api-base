<?php

namespace Modules\MailerManager\Http\Controllers\Api\V1;

use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\MailerManager\Models\EmailTemplate;
use Modules\MailerManager\Models\EmailTemplateImage;
use Modules\MailerManager\Services\TemplateRenderService;

class EmailTemplateController extends JsonApiController
{
    public function preview(Request $request, EmailTemplate $emailTemplate, TemplateRenderService $renderService): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('email-templates.show') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $sampleData = $request->input('sample_data', null);
        $renderedHtml = $renderService->renderPreview($emailTemplate, $sampleData);
        $renderedSubject = $renderService->renderSubject($emailTemplate, $sampleData ?? []);

        return response()->json([
            'data' => [
                'subject' => $renderedSubject,
                'html' => $renderedHtml,
            ],
        ]);
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate, TemplateRenderService $renderService): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('email-templates.update') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate(['email' => 'required|email']);

        try {
            $sampleData = $request->input('sample_data', null);
            $renderedHtml = $renderService->renderPreview($emailTemplate, $sampleData);
            $renderedSubject = $renderService->renderSubject($emailTemplate, $sampleData ?? []);

            // Embed images
            $attachments = [];
            $renderedHtml = $renderService->embedImages($renderedHtml, $attachments);

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
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al enviar email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function uploadImage(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('email-templates.update') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $file = $request->file('image');
        $path = $file->store('email-templates/' . $emailTemplate->id, 'public');

        $image = EmailTemplateImage::create([
            'email_template_id' => $emailTemplate->id,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'data' => [
                'id' => $image->id,
                'filename' => $image->filename,
                'url' => asset('storage/' . $image->path),
                'path' => $image->path,
            ],
        ], 201);
    }

    public function deleteImage(Request $request, EmailTemplate $emailTemplate, EmailTemplateImage $image): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('email-templates.update') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($image->email_template_id !== $emailTemplate->id) {
            return response()->json(['error' => 'Image does not belong to this template'], 404);
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(null, 204);
    }
}
