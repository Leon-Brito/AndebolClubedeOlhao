<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function carregar_config_email_aco(): array {
    $candidatos = [
        dirname(__DIR__) . '/email-config.php',
        __DIR__ . '/email-config.php',
    ];

    foreach ($candidatos as $ficheiro) {
        if (is_file($ficheiro)) {
            $config = require $ficheiro;

            if (!is_array($config)) {
                throw new RuntimeException(
                    'O ficheiro email-config.php deve devolver um array.'
                );
            }

            return $config;
        }
    }

    throw new RuntimeException(
        'Não foi encontrado o ficheiro email-config.php.'
    );
}

function carregar_phpmailer_aco(): void {
    if (class_exists(PHPMailer::class)) {
        return;
    }

    $candidatos = [
        __DIR__ . '/vendor/autoload.php',
        dirname(__DIR__) . '/vendor/autoload.php',
    ];

    foreach ($candidatos as $autoload) {
        if (is_file($autoload)) {
            require_once $autoload;
            break;
        }
    }

    if (!class_exists(PHPMailer::class)) {
        throw new RuntimeException(
            'PHPMailer não está instalado. Executa composer install.'
        );
    }
}

function texto_email_aco(string $html): string {
    $texto = preg_replace(
        '/<br\s*\/?>/i',
        "\n",
        $html
    );

    $texto = strip_tags((string)$texto);
    $texto = html_entity_decode(
        $texto,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    return trim(
        preg_replace(
            "/\n{3,}/",
            "\n\n",
            $texto
        ) ?? $texto
    );
}

function enviar_email_aco(
    string $destinatarioEmail,
    string $destinatarioNome,
    string $assunto,
    string $html,
    ?string $responderEmail = null,
    ?string $responderNome = null
): void {
    carregar_phpmailer_aco();
    $config = carregar_config_email_aco();

    if (!filter_var(
        $destinatarioEmail,
        FILTER_VALIDATE_EMAIL
    )) {
        throw new InvalidArgumentException(
            'O endereço do destinatário é inválido.'
        );
    }

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = (string)$config['host'];
    $mail->Port = (int)$config['port'];
    $mail->SMTPAuth = true;
    $mail->Username = (string)$config['username'];
    $mail->Password = (string)$config['password'];
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 20;
    $mail->SMTPDebug = 0;

    $encryption = strtolower(
        (string)($config['encryption'] ?? 'tls')
    );

    if ($encryption === 'ssl' || $encryption === 'smtps') {
        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls' || $encryption === 'starttls') {
        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
    }

    $mail->setFrom(
        (string)$config['from_email'],
        (string)$config['from_name']
    );

    $mail->addAddress(
        $destinatarioEmail,
        $destinatarioNome
    );

    if (
        $responderEmail &&
        filter_var(
            $responderEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $mail->addReplyTo(
            $responderEmail,
            $responderNome ?: $responderEmail
        );
    }

    $mail->isHTML(true);
    $mail->Subject = $assunto;
    $mail->Body = $html;
    $mail->AltBody = texto_email_aco($html);

    $mail->send();
}

function email_layout_aco(
    string $titulo,
    string $conteudo,
    string $rodape = ''
): string {
    $rodapeFinal = $rodape !== ''
        ? $rodape
        : 'Andebol Clube Olhão · #maisqueumclube';

    return '
    <!doctype html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width">
        <title>' . htmlspecialchars(
            $titulo,
            ENT_QUOTES,
            'UTF-8'
        ) . '</title>
    </head>

    <body style="
        margin:0;
        padding:0;
        background:#eef4f0;
        font-family:Arial,Helvetica,sans-serif;
        color:#20352b;
    ">
        <table role="presentation"
               width="100%"
               cellspacing="0"
               cellpadding="0"
               style="background:#eef4f0;padding:26px 12px;">
            <tr>
                <td align="center">
                    <table role="presentation"
                           width="620"
                           cellspacing="0"
                           cellpadding="0"
                           style="
                               width:100%;
                               max-width:620px;
                               background:#ffffff;
                               border-radius:18px;
                               overflow:hidden;
                               box-shadow:0 16px 45px rgba(15,81,50,.12);
                           ">
                        <tr>
                            <td style="
                                background:#0f5132;
                                padding:26px;
                                color:#ffffff;
                            ">
                                <div style="
                                    font-size:22px;
                                    font-weight:800;
                                ">
                                    Andebol Clube Olhão
                                </div>

                                <div style="
                                    margin-top:5px;
                                    color:#ffe16b;
                                    font-size:13px;
                                    font-weight:700;
                                ">
                                    #maisqueumclube
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:28px;">
                                <h1 style="
                                    margin:0 0 18px;
                                    color:#0f5132;
                                    font-size:22px;
                                    line-height:1.3;
                                ">
                                    ' . htmlspecialchars(
                                        $titulo,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) . '
                                </h1>

                                <div style="
                                    color:#3c5147;
                                    font-size:15px;
                                    line-height:1.65;
                                ">
                                    ' . $conteudo . '
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="
                                border-top:1px solid #e4ede7;
                                padding:18px 28px;
                                background:#f8fbf9;
                                color:#708077;
                                font-size:12px;
                                text-align:center;
                            ">
                                ' . htmlspecialchars(
                                    $rodapeFinal,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}