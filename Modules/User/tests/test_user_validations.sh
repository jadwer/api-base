#!/bin/bash

# Autenticación: obtener token
echo "Obteniendo token..."
response=$(curl -s -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "god@example.com", "password": "supersecure"}')

token=$(echo $response | jq -r '.access_token')
echo "Token obtenido: $token"

# Caso 1: Nombre faltante
echo "Prueba 1: Nombre faltante"
curl -s -X POST http://127.0.0.1:8000/api/v1/users \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $token" \
  -H "Content-Type: application/json" \
  -d '{"email": "test1@example.com", "password": "12345678", "status": "active"}' | jq

# Caso 2: Email inválido
echo "Prueba 2: Email inválido"
curl -s -X POST http://127.0.0.1:8000/api/v1/users \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $token" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "not-an-email", "password": "12345678", "status": "active"}' | jq

# Caso 3: Contraseña muy corta
echo "Prueba 3: Contraseña corta"
curl -s -X POST http://127.0.0.1:8000/api/v1/users \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $token" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test2@example.com", "password": "short", "status": "active"}' | jq

# Caso 4: Status inválido
echo "Prueba 4: Status inválido"
curl -s -X POST http://127.0.0.1:8000/api/v1/users \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $token" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test3@example.com", "password": "12345678", "status": "pending"}' | jq

# Caso 5: Usuario válido
echo "Prueba 5: Usuario válido"
curl -s -X POST http://127.0.0.1:8000/api/v1/users \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $token" \
  -H "Content-Type: application/json" \
  -d '{"name": "Valid User", "email": "valid@example.com", "password": "12345678", "status": "active"}' | jq
