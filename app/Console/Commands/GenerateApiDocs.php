<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class GenerateApiDocs extends Command
{
    protected $signature = 'api:generate-docs';
    protected $description = 'Generate API documentation from routes and schemas';

    public function handle()
    {
        $this->info('🚀 Generando documentación de la API...');
        
        // Asegurar que exista el directorio
        $docsApiPath = base_path('docs/api');
        if (!is_dir($docsApiPath)) {
            mkdir($docsApiPath, 0755, true);
        }
        
        $documentation = $this->generateDocumentation();
        
        // Guardar como JSON en docs/api/
        $jsonPath = $docsApiPath . '/endpoints.json';
        file_put_contents($jsonPath, json_encode($documentation, JSON_PRETTY_PRINT));
        
        // Guardar como Markdown en docs/api/
        $markdown = $this->generateMarkdown($documentation);
        $markdownPath = $docsApiPath . '/documentation.md';
        file_put_contents($markdownPath, $markdown);
        
        $this->info('✅ Documentación generada en docs/api/:');
        $this->line('📄 endpoints.json');
        $this->line('📄 documentation.md');
        
        return 0;
    }

    private function generateDocumentation(): array
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/v1/');
        });

        $documentation = [
            'generated_at' => now()->toISOString(),
            'base_url' => config('app.url') . '/api/v1',
            'authentication' => [
                'type' => 'Bearer Token',
                'header' => 'Authorization: Bearer {token}',
                'login_endpoint' => '/api/auth/login'
            ],
            'endpoints' => []
        ];

        foreach ($routes as $route) {
            $endpoint = $this->analyzeEndpoint($route);
            if ($endpoint) {
                $documentation['endpoints'][] = $endpoint;
            }
        }

        return $documentation;
    }

    private function analyzeEndpoint($route): ?array
    {
        $methods = $route->methods();
        $uri = $route->uri();
        $action = $route->getAction();
        
        // Extraer información del controlador
        $controller = $action['controller'] ?? null;
        if (!$controller) return null;

        [$controllerClass, $method] = explode('@', $controller);
        
        // Determinar el tipo de recurso
        $resourceType = $this->extractResourceType($uri);
        $schema = $this->findSchema($controllerClass);
        
        return [
            'methods' => array_diff($methods, ['HEAD']),
            'uri' => $uri,
            'resource_type' => $resourceType,
            'controller' => $controllerClass,
            'action' => $method,
            'middleware' => $this->getMiddleware($route),
            'schema' => $schema,
            'examples' => $this->generateExamples($resourceType, $methods[0])
        ];
    }

    private function extractResourceType(string $uri): string
    {
        if (preg_match('/api\/v1\/([^\/]+)/', $uri, $matches)) {
            return str_replace('-', '_', $matches[1]);
        }
        return 'unknown';
    }

    private function findSchema(string $controllerClass): ?array
    {
        // Intentar encontrar el schema correspondiente
        $modulePath = $this->getModulePath($controllerClass);
        if (!$modulePath) return null;

        $schemaFiles = glob($modulePath . '/app/JsonApi/V1/*/');
        
        foreach ($schemaFiles as $schemaDir) {
            $schemaFile = $schemaDir . '*Schema.php';
            $schemaFiles = glob($schemaFile);
            
            if (!empty($schemaFiles)) {
                return $this->parseSchemaFile($schemaFiles[0]);
            }
        }

        return null;
    }

    private function parseSchemaFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        
        // Extraer campos básicos
        $fields = [];
        if (preg_match_all('/(\w+)::make\([\'"]([^\'"]+)[\'"]?\)/', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $type = $matches[1][$i];
                $name = $matches[2][$i];
                
                $fields[] = [
                    'name' => $name,
                    'type' => $this->mapFieldType($type),
                    'required' => !str_contains($matches[0][$i], 'nullable'),
                    'readonly' => str_contains($matches[0][$i], 'readOnly')
                ];
            }
        }

        return [
            'file' => basename($filePath),
            'fields' => $fields
        ];
    }

    private function mapFieldType(string $laravelType): string
    {
        return match($laravelType) {
            'ID' => 'integer',
            'Str' => 'string',
            'Number' => 'number',
            'DateTime' => 'datetime',
            'Boolean' => 'boolean',
            'ArrayHash' => 'object',
            'BelongsTo' => 'relationship',
            'BelongsToMany' => 'relationship[]',
            'HasMany' => 'relationship[]',
            default => 'mixed'
        };
    }

    private function getModulePath(string $controllerClass): ?string
    {
        if (preg_match('/Modules\\\\(\w+)\\\\/', $controllerClass, $matches)) {
            return base_path('Modules/' . $matches[1]);
        }
        return null;
    }

    private function getMiddleware($route): array
    {
        return $route->gatherMiddleware();
    }

    private function generateExamples(string $resourceType, string $method): array
    {
        $examples = [];
        
        switch ($method) {
            case 'GET':
                $examples['request'] = [
                    'method' => 'GET',
                    'url' => "/api/v1/{$resourceType}",
                    'headers' => [
                        'Accept' => 'application/vnd.api+json',
                        'Authorization' => 'Bearer {token}'
                    ]
                ];
                
                $examples['response'] = [
                    'status' => 200,
                    'body' => [
                        'data' => [
                            [
                                'type' => $resourceType,
                                'id' => '1',
                                'attributes' => ['...'],
                                'relationships' => ['...']
                            ]
                        ],
                        'meta' => [
                            'pagination' => ['...']
                        ]
                    ]
                ];
                break;

            case 'POST':
                $examples['request'] = [
                    'method' => 'POST',
                    'url' => "/api/v1/{$resourceType}",
                    'headers' => [
                        'Content-Type' => 'application/vnd.api+json',
                        'Accept' => 'application/vnd.api+json',
                        'Authorization' => 'Bearer {token}'
                    ],
                    'body' => [
                        'data' => [
                            'type' => $resourceType,
                            'attributes' => ['...']
                        ]
                    ]
                ];
                break;
        }

        return $examples;
    }

    private function generateMarkdown(array $documentation): string
    {
        $markdown = "# API Documentation\n\n";
        $markdown .= "**Generado:** {$documentation['generated_at']}\n\n";
        $markdown .= "**Base URL:** `{$documentation['base_url']}`\n\n";
        
        $markdown .= "## 🔐 Autenticación\n\n";
        $markdown .= "**Tipo:** {$documentation['authentication']['type']}\n\n";
        $markdown .= "**Header:** `{$documentation['authentication']['header']}`\n\n";
        $markdown .= "**Login:** `POST {$documentation['authentication']['login_endpoint']}`\n\n";
        
        $markdown .= "## 📋 Endpoints\n\n";
        
        $grouped = collect($documentation['endpoints'])->groupBy('resource_type');
        
        foreach ($grouped as $resourceType => $endpoints) {
            $markdown .= "### 📦 " . ucfirst(str_replace('_', ' ', $resourceType)) . "\n\n";
            
            foreach ($endpoints as $endpoint) {
                $methods = implode(', ', $endpoint['methods']);
                $markdown .= "#### `{$methods}` `{$endpoint['uri']}`\n\n";
                
                if ($endpoint['schema']) {
                    $markdown .= "**Campos disponibles:**\n\n";
                    foreach ($endpoint['schema']['fields'] as $field) {
                        $required = $field['required'] ? '✅' : '⚪';
                        $readonly = $field['readonly'] ? '🔒' : '';
                        $markdown .= "- {$required} `{$field['name']}` ({$field['type']}) {$readonly}\n";
                    }
                    $markdown .= "\n";
                }
                
                if (isset($endpoint['examples']['request'])) {
                    $markdown .= "**Ejemplo de Request:**\n\n";
                    $markdown .= "```json\n";
                    $markdown .= json_encode($endpoint['examples']['request'], JSON_PRETTY_PRINT);
                    $markdown .= "\n```\n\n";
                }
                
                $markdown .= "---\n\n";
            }
        }
        
        return $markdown;
    }
}
