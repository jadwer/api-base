<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Atomo\Documents\Layout\DocumentLayout;
use Atomo\Documents\Layout\LayoutRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AppConfig\Models\AppSetting;
use Modules\Sales\Models\DocumentDesign;
use Modules\Sales\Support\Documents\DocumentDesigns;
use Symfony\Component\HttpFoundation\Response;

/**
 * Editor visual de documentos (2026-11-12): leer, guardar, restaurar y ver en
 * PDF el diseno de cada documento. Quien puede cambiar la configuracion del
 * sistema puede cambiar el diseno (mismo permiso que app-settings).
 */
class DocumentDesignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->deny($request)) {
            return $denied;
        }
        $saved = DocumentDesign::query()->pluck('updated_at', 'document_type');
        $out = [];
        foreach (DocumentDesigns::types() as $type => $def) {
            $out[] = $this->summary($type, $def) + ['customized' => $saved->has($type)];
        }

        return response()->json(['data' => $out]);
    }

    public function show(Request $request, string $type): JsonResponse
    {
        if ($denied = $this->deny($request) ?? $this->unknown($type)) {
            return $denied;
        }
        $def = DocumentDesigns::type($type);

        return response()->json(['data' => $this->summary($type, $def) + DocumentDesigns::editable($type) + [
            'catalog' => DocumentDesigns::catalog($type),
            'modes' => AppSetting::query()->where('key', $def['mode'])->value('options') ?? [],
        ]]);
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
        if ($problem = DocumentDesigns::problem($type, $layout)) {
            return $this->invalid($problem);
        }
        DocumentDesign::query()->updateOrCreate(['document_type' => $type], ['layout' => $layout, 'updated_by' => $request->user('sanctum')?->getKey()]);

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
        $def = DocumentDesigns::type($type);
        $mode = (string) $request->input('mode', AppSetting::get($def['mode'], 'color'));
        if (! in_array($mode, DocumentLayout::MODES, true)) {
            return $this->invalid('Los colores deben ser una de las opciones de la lista.');
        }
        $doc = DocumentDesigns::previewDocument($type, $request->integer('id') ?: null);
        if ($doc === null) {
            return response()->json(['message' => "Todavía no hay ningún documento de tipo {$def['label']} para usar en la vista previa."], 404);
        }

        return response(app(LayoutRenderer::class)->pdf($layout, $doc, $mode, 'letter'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="vista-previa.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** @param  array<string, mixed>  $def */
    private function summary(string $type, array $def): array
    {
        return [
            'type' => $type,
            'label' => $def['label'],
            'enabledKey' => $def['enabled'],
            'enabled' => (bool) AppSetting::get($def['enabled'], false),
            'modeKey' => $def['mode'],
            'mode' => (string) AppSetting::get($def['mode'], 'color'),
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

    private function invalid(string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => ['layout' => [$message]]], 422);
    }
}
