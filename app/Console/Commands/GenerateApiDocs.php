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
        
        // Extraer campos y relaciones con más detalle
        $fields = [];
        $relationships = [];
        
        // Mejorar la regex para capturar más detalles de los campos - línea por línea
        if (preg_match_all('/(\w+)::make\([\'"]([^\'"]+)[\'"]?\)([^,\n]*)/m', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $type = $matches[1][$i];
                $name = $matches[2][$i];
                $modifiers = $matches[3][$i];
                
                $fieldInfo = [
                    'name' => $name,
                    'type' => $this->mapFieldType($type),
                    'required' => !str_contains($modifiers, 'nullable'),
                    'readonly' => str_contains($modifiers, 'readOnly'),
                    'sortable' => str_contains($modifiers, 'sortable'),
                    'filterable' => str_contains($modifiers, 'filterable')
                ];
                
                if (in_array($type, ['BelongsTo', 'BelongsToMany', 'HasMany', 'HasOne'])) {
                    $relationships[] = $fieldInfo;
                } else {
                    $fields[] = $fieldInfo;
                }
            }
        }
        
        // Buscar validaciones en el Request correspondiente
        $validations = $this->findValidationRules($filePath);

        return [
            'file' => basename($filePath),
            'fields' => $fields,
            'relationships' => $relationships,
            'validations' => $validations
        ];
    }

    private function findValidationRules(string $schemaPath): array
    {
        // Encontrar el Request correspondiente
        $requestPath = str_replace('Schema.php', 'Request.php', $schemaPath);
        
        if (!file_exists($requestPath)) {
            return [];
        }
        
        $content = file_get_contents($requestPath);
        $validations = [];
        
        // Extraer reglas de validación con mejor parsing
        if (preg_match('/public function rules.*?\{(.*?)\}/s', $content, $matches)) {
            $rulesContent = $matches[1];
            
            // Buscar patrones como 'field' => ['required', 'string'] de manera más flexible
            if (preg_match_all('/[\'"](\w+)[\'"]\s*=>\s*\[([^\]]*)\]/s', $rulesContent, $ruleMatches)) {
                for ($i = 0; $i < count($ruleMatches[0]); $i++) {
                    $field = $ruleMatches[1][$i];
                    $rulesText = $ruleMatches[2][$i];
                    
                    // Extraer reglas individuales, manteniendo comillas simples
                    $rules = [];
                    if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $rulesText, $ruleItems)) {
                        $rules = $ruleItems[1];
                    }
                    
                    $validations[$field] = $rules;
                }
            }
        }
        
        return $validations;
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
            'ArrayList' => 'array',
            'BelongsTo' => 'relationship',
            'BelongsToMany' => 'relationship[]',
            'HasMany' => 'relationship[]',
            'HasOne' => 'relationship',
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
                                'attributes' => [
                                    'title' => 'Ejemplo',
                                    'status' => 'published',
                                    'createdAt' => '2025-01-01T00:00:00.000000Z'
                                ],
                                'relationships' => [
                                    'user' => [
                                        'data' => [
                                            'type' => 'users',
                                            'id' => '1'
                                        ],
                                        'links' => [
                                            'self' => "/api/v1/{$resourceType}/1/relationships/user",
                                            'related' => "/api/v1/{$resourceType}/1/user"
                                        ]
                                    ]
                                ],
                                'links' => [
                                    'self' => "/api/v1/{$resourceType}/1"
                                ]
                            ]
                        ],
                        'meta' => [
                            'pagination' => [
                                'currentPage' => 1,
                                'from' => 1,
                                'lastPage' => 1,
                                'perPage' => 15,
                                'to' => 1,
                                'total' => 1
                            ]
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
                            'attributes' => [
                                'title' => 'Nueva página',
                                'slug' => 'nueva-pagina',
                                'html' => '<h1>Contenido HTML</h1>',
                                'css' => 'h1 { color: blue; }',
                                'json' => ['component' => 'header'],
                                'status' => 'draft'
                            ],
                            'relationships' => [
                                'user' => [
                                    'data' => [
                                        'type' => 'users',
                                        'id' => '1'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $examples['response'] = [
                    'status' => 201,
                    'body' => [
                        'data' => [
                            'type' => $resourceType,
                            'id' => '2',
                            'attributes' => [
                                'title' => 'Nueva página',
                                'status' => 'draft',
                                'createdAt' => '2025-01-01T00:00:00.000000Z'
                            ]
                        ]
                    ]
                ];
                break;
                
            case 'PATCH':
                $examples['request'] = [
                    'method' => 'PATCH',
                    'url' => "/api/v1/{$resourceType}/1",
                    'headers' => [
                        'Content-Type' => 'application/vnd.api+json',
                        'Accept' => 'application/vnd.api+json',
                        'Authorization' => 'Bearer {token}'
                    ],
                    'body' => [
                        'data' => [
                            'type' => $resourceType,
                            'id' => '1',
                            'attributes' => [
                                'status' => 'published',
                                'title' => 'Título actualizado'
                            ]
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
                        $sortable = $field['sortable'] ? '🔄' : '';
                        $filterable = $field['filterable'] ? '🔍' : '';
                        $markdown .= "- {$required} `{$field['name']}` ({$field['type']}) {$readonly}{$sortable}{$filterable}\n";
                    }
                    
                    if (!empty($endpoint['schema']['relationships'])) {
                        $markdown .= "\n**Relaciones disponibles:**\n\n";
                        foreach ($endpoint['schema']['relationships'] as $rel) {
                            $markdown .= "- `{$rel['name']}` ({$rel['type']})\n";
                        }
                    }
                    
                    if (!empty($endpoint['schema']['validations'])) {
                        $markdown .= "\n**Validaciones:**\n\n";
                        foreach ($endpoint['schema']['validations'] as $field => $rules) {
                            $rulesText = implode(', ', $rules);
                            $markdown .= "- `{$field}`: {$rulesText}\n";
                        }
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
