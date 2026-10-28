# APROV — API

API da plataforma APROV, em Laravel 12. Hoje ela tem o cadastro e o login do
cliente, que é o fluxo usado pelo aplicativo.

## O que você precisa

- PHP 8.2 ou mais novo (o do XAMPP serve)
- Composer
- MySQL rodando (o do XAMPP serve)

## Como rodar

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crie a base de dados e rode as migrações com a conta de teste:

```bash
php artisan migrate --seed
php artisan serve
```

A API sobe em `http://127.0.0.1:8000`. A conta de teste é `cliente@aprov.com`,
com a senha `aprov1234`.

## Formato das respostas

Toda resposta sai no mesmo molde, inclusive as de erro. Deu certo:

```json
{
  "success": true,
  "message": "Login feito.",
  "data": { }
}
```

Deu errado:

```json
{
  "success": false,
  "message": "Este nome de usuário já está em uso.",
  "errors": { "username": ["Este nome de usuário já está em uso."] }
}
```

Em `errors` vêm as mensagens campo a campo, quando o erro for de validação. O
`message` é sempre uma frase pronta para mostrar na tela, em português. Dentro do
controller, isso vem dos métodos `sendResponse` e `sendError`; os erros que o
Laravel lança sozinho (validação, token, rota que não existe) são moldados em
`bootstrap/app.php`.

## Rotas

Mande `Accept: application/json` em toda chamada.

| Método | Rota | Precisa de token |
|---|---|---|
| POST | `/api/register` | não |
| POST | `/api/login` | não |
| POST | `/api/logout` | sim |
| GET | `/api/me` | sim |

O cadastro e o login devolvem um token do Sanctum em `data.token`. Mande ele nas
rotas protegidas no cabeçalho `Authorization: Bearer <token>`.

### Cadastro

```json
{
  "name": "Mariana Albuquerque",
  "username": "mari.cavalcanti",
  "email": "mari@email.com",
  "birthDate": "2000-03-14",
  "cpf": "52998224725",
  "password": "aprov1234"
}
```

O nome de usuário vai de 3 a 30 caracteres, entre letras, números, ponto e
sublinhado. A senha precisa de 8 caracteres ou mais, com letra e número. E-mail,
CPF e nome de usuário não podem se repetir.

A resposta traz o token e o cliente:

```json
{
  "success": true,
  "message": "Conta criada.",
  "data": {
    "token": "1|...",
    "client": {
      "id": 1,
      "name": "Mariana Albuquerque",
      "username": "mari.cavalcanti",
      "email": "mari@email.com",
      "birthDate": "2000-03-14",
      "createdAt": "2026-10-28T03:34:41+00:00"
    }
  }
}
```

O CPF nunca volta na resposta.

### Login

```json
{ "email": "cliente@aprov.com", "password": "aprov1234" }
```

E-mail ou senha errados voltam em `401`. Campo inválido volta em `422`. Token
ausente ou revogado volta em `401`.
