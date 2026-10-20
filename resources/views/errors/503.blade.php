<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @if (! empty($retryAfter))
        <meta http-equiv="refresh" content="{{ (int) $retryAfter }}">
    @endif
    <title>{{ config('app.name', 'CCB Nexus') }} — Manutenção</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --ink: #0b1220;
            --ink-soft: #1e293b;
            --mist: #94a3b8;
            --paper: #f8fafc;
            --accent: #2563eb;
            --accent-soft: rgba(37, 99, 235, 0.18);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
            color: var(--paper);
            background:
                radial-gradient(1200px 600px at 15% -10%, rgba(37, 99, 235, 0.35), transparent 55%),
                radial-gradient(900px 500px at 100% 110%, rgba(14, 165, 233, 0.18), transparent 50%),
                linear-gradient(165deg, #020617 0%, #0f172a 48%, #111827 100%);
            display: grid;
            place-items: center;
            padding: 2rem 1.25rem;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.06) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 45%, #000 20%, transparent 75%);
            pointer-events: none;
        }

        .stage {
            position: relative;
            z-index: 1;
            width: min(100%, 34rem);
            text-align: center;
            animation: rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .brand {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .mark {
            width: 4.25rem;
            height: 4.25rem;
            border-radius: 1.1rem;
            background: linear-gradient(145deg, #3b82f6, #1d4ed8);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.12) inset,
                0 18px 40px -18px rgba(37, 99, 235, 0.85);
            animation: float 4.5s ease-in-out infinite;
        }

        .wordmark {
            font-size: clamp(2.4rem, 8vw, 3.35rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 0.95;
            margin: 0;
            background: linear-gradient(180deg, #f8fafc 10%, #93c5fd 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 0 1rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            background: rgba(15, 23, 42, 0.55);
            color: #cbd5e1;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pulse {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: #38bdf8;
            box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.55);
            animation: pulse 1.8s ease-out infinite;
        }

        .lead {
            margin: 0 auto;
            max-width: 28ch;
            font-size: clamp(1.05rem, 2.6vw, 1.25rem);
            font-weight: 500;
            line-height: 1.45;
            color: #e2e8f0;
        }

        .sub {
            margin: 0.85rem auto 0;
            max-width: 36ch;
            font-size: 0.95rem;
            line-height: 1.55;
            color: var(--mist);
        }

        .meter {
            margin: 2rem auto 0;
            width: min(100%, 14rem);
            height: 3px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.2);
            overflow: hidden;
        }

        .meter > span {
            display: block;
            height: 100%;
            width: 40%;
            border-radius: inherit;
            background: linear-gradient(90deg, transparent, #60a5fa, #38bdf8, transparent);
            animation: sweep 2.2s ease-in-out infinite;
        }

        .foot {
            margin-top: 2.25rem;
            font-size: 0.8rem;
            color: rgba(148, 163, 184, 0.85);
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.55); }
            70% { box-shadow: 0 0 0 10px rgba(56, 189, 248, 0); }
            100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); }
        }

        @keyframes sweep {
            0% { transform: translateX(-120%); }
            100% { transform: translateX(280%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .stage, .mark, .pulse, .meter > span { animation: none; }
        }
    </style>
</head>
<body>
    <main class="stage" role="status" aria-live="polite">
        <div class="brand">
            <div class="mark" aria-hidden="true">CCB</div>
            <h1 class="wordmark">NEXUS</h1>
        </div>

        <p class="eyebrow">
            <span class="pulse" aria-hidden="true"></span>
            {{ __('Em manutenção') }}
        </p>

        <p class="lead">
            {{ $message ?? __('Estamos atualizando o sistema.') }}
        </p>
        <p class="sub">
            {{ __('Voltamos em instantes. Obrigado pela paciência.') }}
        </p>

        <div class="meter" aria-hidden="true"><span></span></div>

        <p class="foot">{{ config('app.name', 'CCB Nexus') }}</p>
    </main>
</body>
</html>
