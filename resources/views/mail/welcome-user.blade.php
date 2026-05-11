<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, sans-serif; color: #1a1a1a;">
    <p>Olá, {{ $recipientName }},</p>

    <p>Este é um <strong>e-mail automático</strong> para confirmar que a sua conta no <strong>{{ $appName }}</strong> foi <strong>criada com sucesso</strong>.</p>

    <p>Obrigado por começar a usar o sistema. Esperamos que aproveite para organizar tarefas, métricas e seu dia a dia com mais tranquilidade.</p>

    <p>Este mensagem é apenas <strong>informativa</strong>: não é necessário clicar em nenhum link ou responder a este e-mail para ativar a conta — se você acabou de se cadastrar e já consegue entrar, está tudo certo.</p>

    <p>Se você <strong>não</strong> criou esta conta, ignore este e-mail ou entre em contato com o suporte do serviço.</p>

    <p>Atenciosamente,<br>Equipe {{ $appName }}</p>
</body>
</html>
