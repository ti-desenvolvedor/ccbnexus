<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>CCB Nexus</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <style>
            /* minimal, self-contained styles (Tailwind-like utility subset) */
            :root{color-scheme:light;}
            body{margin:0;font-family:Figtree,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,"Noto Sans","Helvetica Neue",Arial,"Apple Color Emoji","Segoe UI Emoji";background:#0b1020;color:#e5e7eb;}
            a{text-decoration:none;color:inherit}
            .container{max-width:1100px;margin:0 auto;padding:32px 20px}
            .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);border-radius:16px;backdrop-filter: blur(10px);}
            .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:12px;padding:12px 16px;font-weight:600}
            .btn-primary{background:#ffffff;color:#0b1020}
            .btn-ghost{border:1px solid rgba(255,255,255,.16);color:#e5e7eb}
            .muted{color:rgba(229,231,235,.75)}
            .grid{display:grid;gap:14px}
            @media(min-width:900px){.grid-2{grid-template-columns:1.2fr .8fr}}
            .pill{display:inline-flex;gap:10px;align-items:center;border:1px solid rgba(255,255,255,.14);border-radius:999px;padding:8px 12px;background:rgba(255,255,255,.05)}
            .dot{width:8px;height:8px;border-radius:999px;background:#34d399}
            .h1{font-size:40px;line-height:1.1;margin:14px 0 10px;font-weight:700;letter-spacing:-0.02em}
            @media(min-width:900px){.h1{font-size:52px}}
            .features{margin-top:18px}
            .feature{padding:14px 16px;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.04)}
            .footer{margin-top:28px;font-size:13px}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="grid grid-2" style="align-items:start">
                <div>
                    <div class="pill">
                        <span class="dot"></span>
                        <span class="muted">Ambiente local pronto • Laravel + Livewire</span>
                    </div>

                    <div class="h1">CCB Nexus</div>
                    <div class="muted" style="font-size:16px;line-height:1.7;max-width:58ch">
                        Plataforma modular para <strong>Agenda</strong>, <strong>Eventos</strong>, <strong>Secretaria</strong>, <strong>Avisos</strong>,
                        <strong>Aprovações</strong>, <strong>Relatórios</strong> e <strong>Usuários/Permissões</strong>.
                    </div>

                    <div style="margin-top:18px;display:flex;gap:12px;flex-wrap:wrap">
                        @if (Route::has('login'))
                            <a class="btn btn-primary" href="{{ route('login') }}">
                                Entrar no sistema
                                <span aria-hidden="true">→</span>
                            </a>
                        @else
                            <span class="btn btn-ghost" style="opacity:.85;cursor:not-allowed">
                                Autenticação em construção
                            </span>
                        @endif
                        <a class="btn btn-ghost" href="{{ url('/status') }}">
                            Status
                        </a>
                    </div>

                    <div class="features grid" style="margin-top:22px">
                        <div class="feature">
                            <strong>Fluxo com aprovação e auditoria</strong>
                            <div class="muted" style="margin-top:6px">Mudanças em agenda/eventos com justificativa e rastreabilidade.</div>
                        </div>
                        <div class="feature">
                            <strong>Avisos automáticos</strong>
                            <div class="muted" style="margin-top:6px">Regras padrão editáveis (30/15/7/1 dias) com override por evento.</div>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding:18px 18px 16px">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center">
                        <strong>Atalhos</strong>
                        <span class="muted" style="font-size:13px">Herd: {{ request()->getHost() }}</span>
                    </div>

                    <div class="grid" style="margin-top:12px">
                        <div class="feature">
                            <strong>Acesso</strong>
                            <div class="muted" style="margin-top:6px">Login, Google OAuth e solicitação de acesso</div>
                        </div>
                        <div class="feature">
                            <strong>Administração</strong>
                            <div class="muted" style="margin-top:6px">Usuários, perfis e permissões (RBAC)</div>
                        </div>
                        <div class="feature">
                            <strong>Eventos</strong>
                            <div class="muted" style="margin-top:6px">Cadastro, recorrência, ocorrências, aprovações e auditoria</div>
                        </div>
                    </div>

                    <div class="footer muted">
                        Versão inicial • em construção
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

