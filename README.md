# MetricNotes

> **Gestão Produtiva e Business Intelligence para Autônomos**
> 
> *Protótipo de sistema web desenvolvido como Trabalho de Conclusão de Curso (TCC) em Análise e Desenvolvimento de Sistemas.*

![Status](https://img.shields.io/badge/Status-Em_Desenvolvimento-yellow)<br>

### Pré-requisitos
- PHP (8.3.30)
- Composer (2.9.5)
- Node.js ( v24.13.1)
- NPM (11.8.0)

### Passo a Passo

1. Backend:<br>
   Clone o repositório
   ```bash
   git clone https://github.com/gabriellatcc/metricnotes-api/
   ```
   Instale as dependências e rode o backend:
     ```bash
   composer install
   cp .env.example .env
   php artisan jwt:secret
   php artisan serve
   ```

2. Frontend:<br>
   Clone o repositório
   ```bash
   git clone https://github.com/gabriellatcc/metricnotes-web/
   ```
   Instale as dependências e rode o frontend:
   ```bash
   npm install
   npm run dev
   ```

3. Acesse: http://localhost:3000
