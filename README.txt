HamadanApp v4 (HostGator / PHP 8.x)

1) Suba a pasta "hamadanapp" para o docroot do seu site (ex: public_html/hamadanapp).
2) No phpMyAdmin:
   - Se necessário, crie o BD: cervejac_hamadanapp (utf8mb4).
   - Selecione o BD > Aba "Importar" > importe "bootstrap.sql".
   - Depois importe "services_seed.sql".
3) Acesse: http://SEU-DOMINIO/hamadanapp/login.php

Usuários:
- Master: admin@admin.com / admin
- Funcionário: func@hamadan.com / 1234  (se não funcionar, use /adjust_password.php para ajustar)

Ajustar senha (apenas Master): http://SEU-DOMINIO/hamadanapp/adjust_password.php

Observações:
- Labels horizontais (padrão 85x48mm); ajuste CSS em /assets/style.css conforme sua impressora.
- Permissões: Master pode tudo; Funcionário pode criar OS e alterar status de peças.
- Após criar cliente/OS, você volta ao Dashboard com mensagem de sucesso.
