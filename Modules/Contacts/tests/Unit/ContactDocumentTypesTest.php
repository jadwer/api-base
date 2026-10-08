<?php

namespace Modules\Contacts\Tests\Unit;

use Modules\Contacts\Support\ContactDocumentTypes;
use PHPUnit\Framework\TestCase;

/**
 * Candado de la lista de tipos de documento (paquete C, F2/B3). El frontend
 * no tiene endpoint de catalogo para estos tipos y fija la lista en
 * packages/contacts/src/utils/documentTypes.ts; si un lado cambia sin el
 * otro, este test y tests/utils/documentTypes.test.ts del frontend fallan.
 */
class ContactDocumentTypesTest extends TestCase
{
    private const FRONTEND_FILE = __DIR__ . '/../../../../../webapp-base/packages/contacts/src/utils/documentTypes.ts';

    public function test_backend_list_matches_the_frontend_list(): void
    {
        if (! is_file(self::FRONTEND_FILE)) {
            $this->markTestSkipped('webapp-base no esta junto a api-base (ej. servidor); solo corre en el monorepo local.');
        }

        $source = file_get_contents(self::FRONTEND_FILE);
        $this->assertMatchesRegularExpression('/CONTACT_DOCUMENT_TYPES\s*=\s*\[(.*?)\]\s*as const/s', $source);
        preg_match('/CONTACT_DOCUMENT_TYPES\s*=\s*\[(.*?)\]\s*as const/s', $source, $m);
        preg_match_all("/'([a-z_]+)'/", $m[1], $values);

        $this->assertSame(ContactDocumentTypes::ALL, $values[1], 'La lista de tipos de documento difiere entre backend y frontend (mismo orden y valores).');
    }

    public function test_upload_and_jsonapi_request_use_the_shared_list(): void
    {
        $base = __DIR__ . '/../../app';
        foreach ([
            $base . '/Http/Controllers/Api/V1/ContactDocumentUploadController.php',
            $base . '/JsonApi/V1/ContactDocuments/ContactDocumentRequest.php',
        ] as $file) {
            $this->assertStringContainsString('Rule::in(ContactDocumentTypes::ALL)', file_get_contents($file), basename($file) . ' debe validar contra ContactDocumentTypes::ALL.');
        }
        $this->assertStringContainsString('ContactDocumentTypes::ALL', file_get_contents($base . '/Models/ContactDocument.php'));
    }
}
