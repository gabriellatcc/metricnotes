<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, sans-serif;">
    <p>Olá,</p>
    <p>Seu código para redefinir a senha no <strong>{{ $appName }}</strong>:</p>
    <p style="font-size: 1.25rem; letter-spacing: 0.25em;"><strong>{{ $code }}</strong></p>
    <p>Este código expira em 15 minutos. Se você não solicitou esta alteração, ignore este e-mail.</p>
</body>
</html>
