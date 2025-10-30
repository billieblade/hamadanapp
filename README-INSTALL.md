# Hamadan App (HostGator)
## Instalação
1) Envie **public_html/** para seu HostGator.
2) cPanel → MySQL® Databases → crie DB/usuário (já informados), com privilégios.
3) phpMyAdmin → rode `public_html/sql/schema.sql`.
4) Edite `public_html/app/config/env.php` (já está com suas credenciais/URL).
5) Localmente:
```
cd public_html
composer install
```
Envie a pasta **vendor/** para o HostGator (ou use SSH).

## Fluxo
- Master: cadastre/importe **Tabelas de Preço** (CSV sem arredondamento).
- Cliente (final/corporativo) → Orçamento → Aprovar → Gera **OS** e **Etiquetas**.
- Relatórios em **/reports** (diário e por serviço; CSV).

## Etiquetas
- PDF 80×50mm, com **logo Hamadan** e **QR Code**.
