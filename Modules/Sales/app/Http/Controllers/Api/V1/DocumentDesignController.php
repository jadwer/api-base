<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Atomo\Documents\Layout\LayoutRenderer;
use Atomo\Documents\Layout\LayoutStyles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AppConfig\Models\AppSetting;
use Modules\Sales\Models\DocumentDesign;
use Modules\Sales\Support\Documents\DocumentDesigns;
use Symfony\Component\HttpFoundation\Response;

/**
 * Diseno de los PDF (2026-11-12): galeria de plantillas, estilo por fila,
 * vista previa con el PDF real, guardar y restaurar. Quien puede cambiar la
 * configuracion del sistema puede cambiar el diseno (mismo permiso que app-settings).
 */
class DocumentDesignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->deny($request)) {
            return $denied;
        }
        $saved = DocumentDesign::query()->pluck('style', 'document_type');
        $out = [];
        foreach (DocumentDesigns::types() as $type => $def) {
            $out[] = $this->summary($type, $def) + ['customized' => $saved->has($type), 'style' => $saved->get($type) ?: 'standard'];
        }

        return response()->json(['data' => $out]);
    }

    public function show(Request $request, string $type): JsonResponse
    {
        if ($denied = $this->deny($request) ?? $this->unknown($type)) {
            return $denied;
        }

        return response()->json(['data' => $this->summary($type, DocumentDesigns::type($type)) + DocumentDesigns::editable($type) + [
            'styles' => DocumentDesigns::styles($type),
            'catalog' => DocumentDesigns::catalog($type),
        ]]);
    }

    /** El documento con una plantilla de la galeria puesta, sin guardarlo. */
    public function style(Request $request, string $type): JsonResponse
    {
        if ($denied = $this->deny($request) ?? $this->unknown($type)) {
            return $denied;
        }
        $style = (string) $request->input('style', '');
        if (! LayoutStyles::has($style)) {
            return $this->invalid('Esa plantilla no existe.', 'style');
        }

        return response()->json(['data' => ['layout' => DocumentDesigns::withStyle($type, $style), 'style' => $style]]);
    }

    public function thumbnail(Request $request, string $type, string $style): Response
    {
        if ($denied = $this->deny($request) ?? $this->unknown($type)) {
            return $denied;
        }
        $path = DocumentDesigns::thumbnailPath($type, $style);
        if ($path === null) {
            return response()->json(['message' => 'Esa plantilla no tiene miniatura.'], 404);
        }

        return response()->file($path, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, max-age=86400']);
    }

    public function update(Request $request, string $type): JsonResponse
    {
        if ($denied = $this->deny($request) ?? $this->unknown($type)) {
            return $denied;
        }
        $layout = $request->input('layout');
        if (! is_array($layout)) {
            return $this->invalid('Falta el diseño del documento.');
        }
        $style = (string) $request->input('style', 'standard');
        if (! LayoutStyles::has($style)) {
            return $this->invalid('Esa plantilla no existe.', 'style');
        }
        if ($problem = DocumentDesigns::problem($type, $layout)) {
            return $this->invalid($problem);
        }
        DocumentDesign::query()->updateOrCreate(['document_type' => $type], ['layout' => $layout, 'style' => $style, 'updated_by' => $request->user('sanctum')?->getKey()]);

        return response()->json(['data' => DocumentDesigns::editable($type), 'message' => 'Diseño guardado. Los PDF nuevos ya salen así.']);
    }

    public function destroy(Request $request, string $type): JsonResponse
    {
        if ($denied = $this->deny($request) ?? $this->unknown($type)) {
            return $denied;
        }
        DocumentDesign::query()->where('document_type', $type)->delete();

        return response()->json(['data' => DocumentDesigns::editable($type), 'message' => 'Se restauró el diseño de fábrica.']);
    }

    /** El PDF del diseno en edicion (sin guardarlo) con el documento mas reciente de ese tipo. */
    public function preview(Request $request, string $type): Response
    {
        if ($denied = $this->deny($request) ?? $this->unknown($type)) {
            return $denied;
        }
        $layout = $request->input('layout');
        if (! is_array($layout)) {
            return $this->invalid('Falta el diseño del documento.');
        }
        if ($problem = DocumentDesigns::problem($type, $layout)) {
            return $this->invalid($problem);
        }
        $doc = DocumentDesigns::previewDocument($type, $request->integer('id') ?: null);
        if ($doc === null) {
            return response()->json(['message' => 'Todavía no hay ningún documento de tipo '.DocumentDesigns::type($type)['label'].' para usar en la vista previa.'], 404);
        }

        // Sin modo: el color lo trae el diseno (theme.mode), igual que al imprimir.
        return response(app(LayoutRenderer::class)->pdf($layout, $doc, null, 'letter'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="vista-previa.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array<string, mixed>
     */
    private function summary(string $type, array $def): array
    {
        return [
            'type' => $type,
            'label' => $def['label'],
            'enabledKey' => $def['enabled'],
            'enabled' => (bool) AppSetting::get($def['enabled'], false),
        ];
    }

    private function deny(Request $request): ?JsonResponse
    {
        $user = $request->user('sanctum');
        if (! $user || (! $user->can('app-settings.update') && ! $user->hasRole(['god', 'admin', 'administrator']))) {
            return response()->json(['message' => 'No tiene permisos para cambiar el diseño de los documentos.'], 403);
        }

        return null;
    }

    private function unknown(string $type): ?JsonResponse
    {
        return array_key_exists($type, DocumentDesigns::types())
            ? null
            : response()->json(['message' => 'Ese documento no tiene diseño editable.'], 404);
    }

    private function invalid(string $message, string $field = 'layout'): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
    }
}
