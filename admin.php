<?php
require_once __DIR__ . '/config.php';

$user = user_logado();

if (!$user) {
    header('Location: /login.html');
    exit;
}

if ($user['tipo'] !== 'admin' && $user['tipo'] !== 'treinador') {
    http_response_code(403);

    $nome = htmlspecialchars($user['nome'] ?? 'Utilizador', ENT_QUOTES, 'UTF-8');
    $tipo = htmlspecialchars($user['tipo'] ?? 'sem perfil', ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso Negado - Andebol Clube Olhão</title>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

        <style>
            :root {
                --verde: #198754;
                --verde-escuro: #0f5132;
                --amarelo: #ffc107;
                --fundo: #f3f7f4;
                --texto: #17251c;
                --muted: #6c757d;
                --branco: #ffffff;
                --vermelho: #dc3545;
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                min-height: 100vh;
                font-family: "Segoe UI", Arial, sans-serif;
                background:
                    radial-gradient(circle at top left, rgba(25, 135, 84, 0.18), transparent 35%),
                    radial-gradient(circle at bottom right, rgba(255, 193, 7, 0.18), transparent 35%),
                    linear-gradient(135deg, #f7fff9 0%, #eef5f0 100%);
                color: var(--texto);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }

            .access-wrapper {
                width: min(980px, 100%);
                display: grid;
                grid-template-columns: 1fr 1.1fr;
                background: var(--branco);
                border-radius: 28px;
                overflow: hidden;
                box-shadow: 0 30px 80px rgba(15, 81, 50, 0.18);
                border: 1px solid rgba(25, 135, 84, 0.14);
            }

            .access-brand {
                background:
                    linear-gradient(160deg, rgba(25, 135, 84, 0.96), rgba(15, 81, 50, 0.98)),
                    var(--verde);
                color: white;
                padding: 44px 34px;
                position: relative;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                min-height: 560px;
            }

            .access-brand::before {
                content: "";
                position: absolute;
                width: 260px;
                height: 260px;
                border-radius: 50%;
                background: rgba(255, 193, 7, 0.14);
                top: -80px;
                right: -90px;
            }

            .access-brand::after {
                content: "";
                position: absolute;
                width: 180px;
                height: 180px;
                border-radius: 50%;
                border: 30px solid rgba(255, 255, 255, 0.07);
                bottom: -70px;
                left: -60px;
            }

            .brand-top {
                position: relative;
                z-index: 2;
            }

            .club-badge {
                width: 86px;
                height: 86px;
                border-radius: 22px;
                background: white;
                display: grid;
                place-items: center;
                margin-bottom: 22px;
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.14);
            }

            .club-badge img {
                width: 72px;
                height: 72px;
                object-fit: contain;
            }

            .brand-title {
                font-size: clamp(2rem, 4vw, 3.1rem);
                line-height: 1;
                font-weight: 900;
                letter-spacing: -1px;
                margin-bottom: 14px;
            }

            .brand-subtitle {
                font-size: 1rem;
                color: rgba(255, 255, 255, 0.82);
                line-height: 1.7;
                max-width: 340px;
            }

            .security-list {
                position: relative;
                z-index: 2;
                display: grid;
                gap: 14px;
                margin-top: 30px;
            }

            .security-item {
                display: flex;
                align-items: center;
                gap: 12px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.16);
                padding: 13px 14px;
                border-radius: 14px;
                backdrop-filter: blur(8px);
            }

            .security-item i {
                color: var(--amarelo);
                width: 22px;
                text-align: center;
            }

            .access-content {
                padding: 54px 48px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .status-pill {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                width: fit-content;
                background: rgba(220, 53, 69, 0.09);
                color: #b02a37;
                border: 1px solid rgba(220, 53, 69, 0.16);
                padding: 9px 14px;
                border-radius: 999px;
                font-size: 0.86rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                margin-bottom: 22px;
            }

            .access-icon {
                width: 98px;
                height: 98px;
                border-radius: 28px;
                background:
                    linear-gradient(145deg, rgba(255, 193, 7, 0.18), rgba(220, 53, 69, 0.10));
                display: grid;
                place-items: center;
                margin-bottom: 26px;
                color: var(--verde);
                font-size: 3rem;
                border: 1px solid rgba(25, 135, 84, 0.12);
            }

            h1 {
                font-size: clamp(2.2rem, 5vw, 4.2rem);
                line-height: 0.95;
                letter-spacing: -2px;
                color: var(--verde-escuro);
                margin-bottom: 18px;
                font-weight: 950;
            }

            .lead {
                color: #425348;
                line-height: 1.7;
                font-size: 1.05rem;
                margin-bottom: 28px;
                max-width: 560px;
            }

            .user-box {
                background: #f8faf8;
                border: 1px solid #e2eee6;
                border-radius: 18px;
                padding: 18px;
                display: grid;
                gap: 10px;
                margin-bottom: 28px;
            }

            .user-row {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                border-bottom: 1px solid #e8f0ea;
                padding-bottom: 10px;
            }

            .user-row:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .user-label {
                color: var(--muted);
                font-size: 0.9rem;
            }

            .user-value {
                font-weight: 800;
                color: var(--verde-escuro);
                text-align: right;
            }

            .actions {
                display: flex;
                gap: 14px;
                flex-wrap: wrap;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 9px;
                text-decoration: none;
                border-radius: 999px;
                padding: 14px 22px;
                font-weight: 800;
                transition: transform 0.18s ease, box-shadow 0.18s ease;
            }

            .btn:hover {
                transform: translateY(-2px);
            }

            .btn-primary {
                background: var(--verde);
                color: white;
                box-shadow: 0 12px 24px rgba(25, 135, 84, 0.22);
            }

            .btn-secondary {
                background: #eef3ef;
                color: var(--verde-escuro);
                border: 1px solid #d8e7dd;
            }

            .note {
                margin-top: 24px;
                font-size: 0.88rem;
                color: var(--muted);
                line-height: 1.6;
            }

            @media (max-width: 850px) {
                body {
                    padding: 14px;
                    align-items: flex-start;
                }

                .access-wrapper {
                    grid-template-columns: 1fr;
                    border-radius: 22px;
                }

                .access-brand {
                    min-height: auto;
                    padding: 32px 24px;
                }

                .security-list {
                    margin-top: 24px;
                }

                .access-content {
                    padding: 36px 24px;
                }

                .actions {
                    flex-direction: column;
                }

                .btn {
                    width: 100%;
                }

                .user-row {
                    flex-direction: column;
                }

                .user-value {
                    text-align: left;
                }
            }
        </style>
    </head>

    <body>
        <main class="access-wrapper">
            <section class="access-brand">
                <div class="brand-top">
                    <div class="club-badge">
                        <img src="/img/logo_9JToiDZ.png" alt="Andebol Clube Olhão">
                    </div>

                    <div class="brand-title">
                        Andebol Clube Olhão
                    </div>

                    <p class="brand-subtitle">
                        Área interna protegida para gestão do clube, atletas, convocatórias, faturas e equipas.
                    </p>
                </div>

                <div class="security-list">
                    <div class="security-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Acesso restrito a administradores e treinadores autorizados</span>
                    </div>

                    <div class="security-item">
                        <i class="fa-solid fa-user-lock"></i>
                        <span>Sessão verificada antes de abrir o painel</span>
                    </div>

                    <div class="security-item">
                        <i class="fa-solid fa-database"></i>
                        <span>Dados do clube protegidos contra acessos indevidos</span>
                    </div>
                </div>
            </section>

            <section class="access-content">
                <div class="status-pill">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Acesso bloqueado
                </div>

                <div class="access-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>

                <h1>Acesso<br>negado</h1>

                <p class="lead">
                    A tua conta está autenticada, mas não tem permissões suficientes para abrir o painel de administração.
                    Se achas que isto é um erro, contacta a administração do clube.
                </p>

                <div class="user-box">
                    <div class="user-row">
                        <span class="user-label">Utilizador</span>
                        <span class="user-value"><?php echo $nome; ?></span>
                    </div>

                    <div class="user-row">
                        <span class="user-label">Perfil atual</span>
                        <span class="user-value"><?php echo $tipo; ?></span>
                    </div>

                    <div class="user-row">
                        <span class="user-label">Permissão necessária</span>
                        <span class="user-value">Admin ou Treinador</span>
                    </div>
                </div>

                <div class="actions">
                    <a href="/" class="btn btn-primary">
                        <i class="fa-solid fa-house"></i>
                        Voltar ao início
                    </a>

                    <a href="/logout" class="btn btn-secondary">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Terminar sessão
                    </a>
                </div>

                <p class="note">
                    Nota: por segurança, o acesso ao painel não depende apenas do menu visível.
                    A validação é feita no servidor antes de abrir esta página.
                </p>
            </section>
        </main>
    </body>
    </html>
    <?php
    exit;
}

readfile(__DIR__ . '/admin-view.html');