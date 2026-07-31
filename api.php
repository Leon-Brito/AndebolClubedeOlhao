<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = trim($_GET['path'] ?? '', '/');
$parts = $path === '' ? [] : explode('/', $path);
$body = input_json();

const ESCALOES_VALIDOS = ['Sub-12', 'Sub-14', 'Sub-16', 'Sub-18'];

const ESCALOES_PLANTEL_VALIDOS = [
    'Sub-18',
    'Sub-16',
    'Sub-14',
    'Sub-12',
    'Minis Feminino',
    'Bambis',
    'Manitas',
    'Baby'
];

function normalizar_escaloes_plantel($escaloes): array {
    $lista = is_array($escaloes) ? $escaloes : [$escaloes];
    $resultado = [];

    foreach ($lista as $escalao) {
        $escalao = trim((string)$escalao);

        if (
            $escalao !== '' &&
            in_array(
                $escalao,
                ESCALOES_PLANTEL_VALIDOS,
                true
            )
        ) {
            $resultado[] = $escalao;
        }
    }

    return array_values(array_unique($resultado));
}


/*
|--------------------------------------------------------------------------
| PASSWORDS SEGURAS
|--------------------------------------------------------------------------
|
| As passwords não são encriptadas de forma reversível.
| São transformadas num hash de sentido único com password_hash().
|
*/

function password_tem_hash_valido(string $valor): bool {
    $info = password_get_info($valor);
    $algoritmo = $info['algo'] ?? null;

    return $algoritmo !== null && $algoritmo !== 0;
}

function criar_hash_password(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}


/*
|--------------------------------------------------------------------------
| FORMULÁRIOS PÚBLICOS E EMAIL
|--------------------------------------------------------------------------
*/

function limpar_texto_formulario(
    mixed $valor,
    int $maximo = 500
): string {
    $texto = trim((string)$valor);
    $texto = preg_replace(
        '/\s+/u',
        ' ',
        $texto
    ) ?? $texto;

    return mb_substr(
        $texto,
        0,
        $maximo
    );
}

function escapar_email_html(mixed $valor): string {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function token_formulario_publico(): string {
    if (
        empty($_SESSION['formulario_csrf']) ||
        !is_string($_SESSION['formulario_csrf'])
    ) {
        $_SESSION['formulario_csrf'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['formulario_csrf'];
}

function exigir_token_formulario_publico(): void {
    $recebido = (string)(
        $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''
    );

    $guardado = token_formulario_publico();

    if (
        $recebido === '' ||
        !hash_equals($guardado, $recebido)
    ) {
        json_response([
            'erro' =>
                'A sessão do formulário expirou. Atualiza a página e tenta novamente.'
        ], 419);
    }

    /*
     * Rotação do token depois de cada submissão válida.
     */
    $_SESSION['formulario_csrf'] =
        bin2hex(random_bytes(32));
}

function bloquear_spam_formulario(
    string $chave,
    int $segundos = 45
): void {
    $honeypot = trim(
        (string)($GLOBALS['body']['website'] ?? '')
    );

    if ($honeypot !== '') {
        /*
         * Resposta neutra para não ensinar o bot.
         */
        json_response([
            'mensagem' =>
                'Pedido recebido.'
        ]);
    }

    $agora = time();
    $ultima = (int)(
        $_SESSION['form_rate'][$chave] ?? 0
    );

    if (
        $ultima > 0 &&
        ($agora - $ultima) < $segundos
    ) {
        json_response([
            'erro' =>
                'Aguarda alguns segundos antes de enviar novamente.'
        ], 429);
    }

    $_SESSION['form_rate'][$chave] = $agora;
}

function configuracao_email_publica(): array {
    return carregar_config_email_aco();
}

function enviar_email_confirmacao_seguro(
    string $email,
    string $nome,
    string $assunto,
    string $html
): bool {
    try {
        enviar_email_aco(
            $email,
            $nome,
            $assunto,
            $html
        );

        return true;

    } catch (Throwable $e) {
        error_log(
            'Falha no email de confirmação: ' .
            $e->getMessage()
        );

        return false;
    }
}

function enviar_emails_convocatoria_aco(
    PDO $pdo,
    int $idConvocatoria,
    array $idsJogadores,
    string $adversario,
    string $data,
    string $local,
    string $escalao,
    int $idCriador
): array {
    $enviados = 0;
    $falhados = 0;

    $dataFormatada = date(
        'd/m/Y H:i',
        strtotime($data)
    );

    $config = configuracao_email_publica();
    $siteUrl = rtrim(
        (string)($config['site_url'] ?? ''),
        '/'
    );

    $urlConvocatoria =
        $siteUrl . '/convocatoria.html';

    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($idsJogadores),
            '?'
        )
    );

    /*
     * Jogadores convocados.
     */
    $stmtJogadores = $pdo->prepare(
        "SELECT id, nome, email
         FROM utilizadores
         WHERE id IN ($placeholders)
           AND tipo = 'jogador'"
    );

    $stmtJogadores->execute(
        $idsJogadores
    );

    $jogadores = $stmtJogadores->fetchAll();
    $nomesPorId = [];

    foreach ($jogadores as $jogador) {
        $idJogador = (int)$jogador['id'];
        $nomeJogador = (string)$jogador['nome'];
        $emailJogador = trim(
            (string)$jogador['email']
        );

        $nomesPorId[$idJogador] =
            $nomeJogador;

        if (
            !filter_var(
                $emailJogador,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            continue;
        }

        $conteudo = '
            <p>
                Olá, <strong>' .
                escapar_email_html($nomeJogador) .
                '</strong>.
            </p>

            <p>
                Foste convocado para o próximo jogo.
            </p>

            <table role="presentation"
                   width="100%"
                   cellspacing="0"
                   cellpadding="8"
                   style="
                       margin:18px 0;
                       border-collapse:collapse;
                       background:#f5faf7;
                       border-radius:12px;
                   ">
                <tr>
                    <td><strong>Escalão</strong></td>
                    <td>' .
                        escapar_email_html($escalao) .
                    '</td>
                </tr>
                <tr>
                    <td><strong>Adversário</strong></td>
                    <td>' .
                        escapar_email_html($adversario) .
                    '</td>
                </tr>
                <tr>
                    <td><strong>Data</strong></td>
                    <td>' .
                        escapar_email_html($dataFormatada) .
                    '</td>
                </tr>
                <tr>
                    <td><strong>Local</strong></td>
                    <td>' .
                        escapar_email_html($local) .
                    '</td>
                </tr>
            </table>

            <p>
                <a href="' .
                    escapar_email_html($urlConvocatoria) .
                '"
                   style="
                       display:inline-block;
                       padding:12px 18px;
                       border-radius:12px;
                       background:#0f5132;
                       color:#ffffff;
                       text-decoration:none;
                       font-weight:700;
                   ">
                    Consultar convocatória
                </a>
            </p>
        ';

        try {
            enviar_email_aco(
                $emailJogador,
                $nomeJogador,
                'Nova convocatória — ' . $escalao,
                email_layout_aco(
                    'Nova convocatória',
                    $conteudo
                )
            );

            $enviados++;

        } catch (Throwable $e) {
            $falhados++;

            error_log(
                'Email convocatória jogador ' .
                $idJogador .
                ': ' .
                $e->getMessage()
            );
        }
    }

    /*
     * Encarregados dos jogadores.
     */
    $stmtEncarregados = $pdo->prepare(
        "SELECT
            e.id,
            e.nome,
            e.email,
            ej.id_jogador
         FROM encarregados_jogadores ej
         INNER JOIN utilizadores e
             ON e.id = ej.id_encarregado
         WHERE ej.id_jogador IN ($placeholders)
           AND e.tipo = 'encarregado'"
    );

    $stmtEncarregados->execute(
        $idsJogadores
    );

    $encarregados = [];

    foreach (
        $stmtEncarregados->fetchAll()
        as $linha
    ) {
        $idEncarregado = (int)$linha['id'];

        if (!isset($encarregados[$idEncarregado])) {
            $encarregados[$idEncarregado] = [
                'nome' => (string)$linha['nome'],
                'email' => (string)$linha['email'],
                'educandos' => [],
            ];
        }

        $idJogador =
            (int)$linha['id_jogador'];

        $encarregados[$idEncarregado]['educandos'][] =
            $nomesPorId[$idJogador] ?? 'Educando';
    }

    foreach ($encarregados as $id => $encarregado) {
        $email = trim(
            (string)$encarregado['email']
        );

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            continue;
        }

        $educandos = array_values(
            array_unique(
                $encarregado['educandos']
            )
        );

        $conteudo = '
            <p>
                Olá, <strong>' .
                escapar_email_html(
                    $encarregado['nome']
                ) .
                '</strong>.
            </p>

            <p>
                Foi criada uma convocatória para:
                <strong>' .
                    escapar_email_html(
                        implode(', ', $educandos)
                    ) .
                '</strong>.
            </p>

            <p>
                <strong>Jogo:</strong> ' .
                    escapar_email_html($adversario) .
                '<br>
                <strong>Data:</strong> ' .
                    escapar_email_html($dataFormatada) .
                '<br>
                <strong>Local:</strong> ' .
                    escapar_email_html($local) .
                '<br>
                <strong>Escalão:</strong> ' .
                    escapar_email_html($escalao) .
                '
            </p>

            <p>
                <a href="' .
                    escapar_email_html($urlConvocatoria) .
                '"
                   style="
                       display:inline-block;
                       padding:12px 18px;
                       border-radius:12px;
                       background:#0f5132;
                       color:#ffffff;
                       text-decoration:none;
                       font-weight:700;
                   ">
                    Consultar convocatória
                </a>
            </p>
        ';

        try {
            enviar_email_aco(
                $email,
                (string)$encarregado['nome'],
                'Convocatória do seu educando — ' .
                    $escalao,
                email_layout_aco(
                    'Nova convocatória',
                    $conteudo
                )
            );

            $enviados++;

        } catch (Throwable $e) {
            $falhados++;

            error_log(
                'Email convocatória encarregado ' .
                $id .
                ': ' .
                $e->getMessage()
            );
        }
    }

    /*
     * Treinadores associados ao escalão.
     */
    $stmtTreinadores = $pdo->prepare(
        'SELECT DISTINCT
            u.id,
            u.nome,
            u.email
         FROM treinador_escalao te
         INNER JOIN utilizadores u
             ON u.id = te.id_treinador
         WHERE te.escalao = ?
           AND u.tipo = "treinador"
           AND u.id <> ?'
    );

    $stmtTreinadores->execute([
        $escalao,
        $idCriador
    ]);

    foreach (
        $stmtTreinadores->fetchAll()
        as $treinador
    ) {
        $email = trim(
            (string)$treinador['email']
        );

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            continue;
        }

        $conteudo = '
            <p>
                Olá, <strong>' .
                escapar_email_html(
                    $treinador['nome']
                ) .
                '</strong>.
            </p>

            <p>
                Foi criada uma convocatória para o escalão
                <strong>' .
                    escapar_email_html($escalao) .
                '</strong>.
            </p>

            <p>
                <strong>Adversário:</strong> ' .
                    escapar_email_html($adversario) .
                '<br>
                <strong>Data:</strong> ' .
                    escapar_email_html($dataFormatada) .
                '<br>
                <strong>Local:</strong> ' .
                    escapar_email_html($local) .
                '
            </p>
        ';

        try {
            enviar_email_aco(
                $email,
                (string)$treinador['nome'],
                'Nova convocatória — ' . $escalao,
                email_layout_aco(
                    'Nova convocatória',
                    $conteudo
                )
            );

            $enviados++;

        } catch (Throwable $e) {
            $falhados++;

            error_log(
                'Email convocatória treinador ' .
                $treinador['id'] .
                ': ' .
                $e->getMessage()
            );
        }
    }

    return [
        'enviados' => $enviados,
        'falhados' => $falhados,
    ];
}


/*
|--------------------------------------------------------------------------
| ÁREA PESSOAL
|--------------------------------------------------------------------------
*/

function token_area_pessoal(): string {
    if (
        empty($_SESSION['area_pessoal_csrf']) ||
        !is_string($_SESSION['area_pessoal_csrf'])
    ) {
        $_SESSION['area_pessoal_csrf'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['area_pessoal_csrf'];
}

function rodar_token_area_pessoal(): string {
    $_SESSION['area_pessoal_csrf'] =
        bin2hex(random_bytes(32));

    return $_SESSION['area_pessoal_csrf'];
}

function exigir_token_area_pessoal(): void {
    $recebido = (string)(
        $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''
    );

    $guardado = token_area_pessoal();

    if (
        $recebido === '' ||
        !hash_equals(
            $guardado,
            $recebido
        )
    ) {
        json_response([
            'erro' =>
                'A sessão de segurança expirou. Atualiza a página e tenta novamente.'
        ], 419);
    }
}

function perfil_publico_utilizador(array $user): array {
    return [
        'id' => (int)$user['id'],
        'nome' => (string)$user['nome'],
        'email' => (string)$user['email'],
        'nif' =>
            $user['nif'] !== null
                ? (string)$user['nif']
                : '',
        'morada' =>
            $user['morada'] !== null
                ? (string)$user['morada']
                : '',
        'tipo' => (string)$user['tipo'],
    ];
}

function verificar_password_guardada(
    string $passwordIntroduzida,
    string $passwordGuardada
): bool {
    if (
        password_tem_hash_valido(
            $passwordGuardada
        )
    ) {
        return password_verify(
            $passwordIntroduzida,
            $passwordGuardada
        );
    }

    return hash_equals(
        $passwordGuardada,
        $passwordIntroduzida
    );
}

function normalizar_ids_jogadores($ids_jogadores): array {
    if (!$ids_jogadores) {
        return [];
    }

    $lista = is_array($ids_jogadores) ? $ids_jogadores : [$ids_jogadores];

    $ids = [];

    foreach ($lista as $id) {
        $id = (int)$id;

        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function pode_gerir_escalao(PDO $pdo, array $user, string $escalao): bool {
    if ($user['tipo'] === 'admin') {
        return true;
    }

    if ($user['tipo'] !== 'treinador') {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT 1
         FROM treinador_escalao
         WHERE id_treinador = ?
           AND escalao = ?
         LIMIT 1'
    );

    $stmt->execute([
        $user['id'],
        $escalao
    ]);

    return (bool)$stmt->fetchColumn();
}

function jogadores_pertencem_ao_escalao(PDO $pdo, array $idsJogadores, string $escalao): bool {
    if (count($idsJogadores) === 0) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($idsJogadores), '?'));

    $sql = "
        SELECT COUNT(DISTINCT u.id) AS total
        FROM utilizadores u
        INNER JOIN jogador_escaloes je ON je.id_jogador = u.id
        WHERE u.tipo = 'jogador'
          AND je.escalao = ?
          AND u.id IN ($placeholders)
    ";

    $params = array_merge([$escalao], $idsJogadores);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $totalValidos = (int)$stmt->fetchColumn();

    return $totalValidos === count($idsJogadores);
}


/*
|--------------------------------------------------------------------------
| TOKEN DOS FORMULÁRIOS PÚBLICOS
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET' &&
    $path === 'formularios/token'
) {
    json_response([
        'token' =>
            token_formulario_publico()
    ]);
}

/*
|--------------------------------------------------------------------------
| FORMULÁRIO DA LOJA
|--------------------------------------------------------------------------
*/

if (
    $method === 'POST' &&
    $path === 'formularios/loja'
) {
    exigir_token_formulario_publico();
    bloquear_spam_formulario('loja', 45);

    $nome = limpar_texto_formulario(
        $body['nome'] ?? '',
        120
    );

    $email = strtolower(
        limpar_texto_formulario(
            $body['email'] ?? '',
            150
        )
    );

    $atleta = limpar_texto_formulario(
        $body['atleta'] ?? '',
        120
    );

    $descricao = trim(
        mb_substr(
            (string)($body['descricao'] ?? ''),
            0,
            3000
        )
    );

    $consentimento =
        (bool)($body['consentimento'] ?? false);

    if (
        $nome === '' ||
        $email === '' ||
        $atleta === '' ||
        $descricao === ''
    ) {
        json_response([
            'erro' =>
                'Preenche todos os campos da encomenda.'
        ], 400);
    }

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        json_response([
            'erro' => 'Indica um endereço de e-mail válido.'
        ], 400);
    }

    if (!$consentimento) {
        json_response([
            'erro' =>
                'É necessário aceitar o tratamento de dados.'
        ], 400);
    }

    $config = configuracao_email_publica();

    $conteudoClube = '
        <p>
            Foi recebido um novo pedido através da loja.
        </p>

        <table role="presentation"
               width="100%"
               cellspacing="0"
               cellpadding="8"
               style="
                   border-collapse:collapse;
                   background:#f5faf7;
               ">
            <tr>
                <td><strong>Nome</strong></td>
                <td>' .
                    escapar_email_html($nome) .
                '</td>
            </tr>

            <tr>
                <td><strong>E-mail</strong></td>
                <td>' .
                    escapar_email_html($email) .
                '</td>
            </tr>

            <tr>
                <td><strong>Atleta / pessoa</strong></td>
                <td>' .
                    escapar_email_html($atleta) .
                '</td>
            </tr>
        </table>

        <h3 style="color:#0f5132;">
            Artigos, tamanhos e observações
        </h3>

        <div style="
            padding:16px;
            border-radius:12px;
            background:#f8fbf9;
            white-space:pre-wrap;
        ">' .
            nl2br(
                escapar_email_html($descricao)
            ) .
        '</div>
    ';

    try {
        enviar_email_aco(
            (string)$config['club_email'],
            (string)$config['club_name'],
            'Novo pedido da loja — ' . $nome,
            email_layout_aco(
                'Novo pedido da loja',
                $conteudoClube
            ),
            $email,
            $nome
        );

    } catch (Throwable $e) {
        error_log(
            'Erro no formulário da loja: ' .
            $e->getMessage()
        );

        json_response([
            'erro' =>
                'O pedido não pôde ser enviado. Tenta novamente mais tarde.'
        ], 500);
    }

    $conteudoCliente = '
        <p>
            Olá, <strong>' .
                escapar_email_html($nome) .
            '</strong>.
        </p>

        <p>
            Recebemos o teu pedido para
            <strong>' .
                escapar_email_html($atleta) .
            '</strong>.
        </p>

        <p>
            O pedido ainda não representa uma encomenda confirmada.
            O clube irá responder com informação sobre disponibilidade,
            preço e prazo de entrega.
        </p>
    ';

    $confirmacaoEnviada =
        enviar_email_confirmacao_seguro(
            $email,
            $nome,
            'Recebemos o seu pedido da loja',
            email_layout_aco(
                'Pedido recebido',
                $conteudoCliente
            )
        );

    json_response([
        'mensagem' =>
            $confirmacaoEnviada
                ? 'O pedido foi enviado. Também enviámos uma confirmação para o teu e-mail.'
                : 'O pedido foi enviado ao clube. A confirmação por e-mail poderá demorar.'
    ]);
}

/*
|--------------------------------------------------------------------------
| FORMULÁRIO NOVO SÓCIO
|--------------------------------------------------------------------------
*/

if (
    $method === 'POST' &&
    $path === 'formularios/novo-socio'
) {
    exigir_token_formulario_publico();
    bloquear_spam_formulario(
        'novo-socio',
        60
    );

    $nome = limpar_texto_formulario(
        $body['nome'] ?? '',
        120
    );

    $documento = limpar_texto_formulario(
        $body['documento'] ?? '',
        30
    );

    $nif = preg_replace(
        '/\D+/',
        '',
        (string)($body['nif'] ?? '')
    ) ?? '';

    $email = strtolower(
        limpar_texto_formulario(
            $body['email'] ?? '',
            150
        )
    );

    $telefone = limpar_texto_formulario(
        $body['telefone'] ?? '',
        30
    );

    $morada = trim(
        mb_substr(
            (string)($body['morada'] ?? ''),
            0,
            500
        )
    );

    $codigoPostal = limpar_texto_formulario(
        $body['codigo_postal'] ?? '',
        20
    );

    $cidade = limpar_texto_formulario(
        $body['cidade'] ?? '',
        120
    );

    $concelho = limpar_texto_formulario(
        $body['concelho'] ?? '',
        120
    );

    $distrito = limpar_texto_formulario(
        $body['distrito'] ?? '',
        120
    );

    $pais = limpar_texto_formulario(
        $body['pais'] ?? '',
        120
    );

    $categoria = limpar_texto_formulario(
        $body['categoria'] ?? '',
        50
    );

    $genero = limpar_texto_formulario(
        $body['genero'] ?? '',
        10
    );

    $nascimento = is_array(
        $body['nascimento'] ?? null
    )
        ? $body['nascimento']
        : [];

    $dia = (int)($nascimento['dia'] ?? 0);
    $mes = (int)($nascimento['mes'] ?? 0);
    $ano = (int)($nascimento['ano'] ?? 0);

    $consentimento =
        (bool)($body['consentimento'] ?? false);

    if (
        $nome === '' ||
        $documento === '' ||
        $nif === '' ||
        $email === '' ||
        $telefone === '' ||
        $morada === '' ||
        $codigoPostal === '' ||
        $cidade === '' ||
        $concelho === '' ||
        $distrito === '' ||
        $pais === '' ||
        $categoria === '' ||
        $genero === ''
    ) {
        json_response([
            'erro' =>
                'Preenche todos os campos obrigatórios da inscrição.'
        ], 400);
    }

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        json_response([
            'erro' => 'Indica um endereço de e-mail válido.'
        ], 400);
    }

    if (
        strlen($nif) !== 9
    ) {
        json_response([
            'erro' =>
                'O NIF deve conter 9 algarismos.'
        ], 400);
    }

    if (
        !checkdate(
            $mes,
            $dia,
            $ano
        )
    ) {
        json_response([
            'erro' =>
                'A data de nascimento não é válida.'
        ], 400);
    }

    if (
        !in_array(
            $genero,
            ['m', 'f'],
            true
        )
    ) {
        json_response([
            'erro' =>
                'Seleciona uma opção válida para o género.'
        ], 400);
    }

    if (
        !in_array(
            $categoria,
            [
                'Efetivos',
                'Mérito',
                'Honorários',
                'Atletas'
            ],
            true
        )
    ) {
        json_response([
            'erro' =>
                'Seleciona uma categoria de sócio válida.'
        ], 400);
    }

    if (!$consentimento) {
        json_response([
            'erro' =>
                'É necessário aceitar o tratamento de dados.'
        ], 400);
    }

    $dataNascimento = sprintf(
        '%02d/%02d/%04d',
        $dia,
        $mes,
        $ano
    );

    $config = configuracao_email_publica();

    $conteudoClube = '
        <p>
            Foi recebido um novo pedido de inscrição de sócio.
        </p>

        <table role="presentation"
               width="100%"
               cellspacing="0"
               cellpadding="8"
               style="
                   border-collapse:collapse;
                   background:#f5faf7;
               ">
            <tr>
                <td><strong>Nome</strong></td>
                <td>' .
                    escapar_email_html($nome) .
                '</td>
            </tr>

            <tr>
                <td><strong>Documento</strong></td>
                <td>' .
                    escapar_email_html($documento) .
                '</td>
            </tr>

            <tr>
                <td><strong>NIF</strong></td>
                <td>' .
                    escapar_email_html($nif) .
                '</td>
            </tr>

            <tr>
                <td><strong>Nascimento</strong></td>
                <td>' .
                    escapar_email_html($dataNascimento) .
                '</td>
            </tr>

            <tr>
                <td><strong>Género</strong></td>
                <td>' .
                    escapar_email_html(
                        $genero === 'm'
                            ? 'Masculino'
                            : 'Feminino'
                    ) .
                '</td>
            </tr>

            <tr>
                <td><strong>E-mail</strong></td>
                <td>' .
                    escapar_email_html($email) .
                '</td>
            </tr>

            <tr>
                <td><strong>Telemóvel</strong></td>
                <td>' .
                    escapar_email_html($telefone) .
                '</td>
            </tr>

            <tr>
                <td><strong>Morada</strong></td>
                <td>' .
                    nl2br(
                        escapar_email_html($morada)
                    ) .
                '</td>
            </tr>

            <tr>
                <td><strong>Código postal</strong></td>
                <td>' .
                    escapar_email_html($codigoPostal) .
                '</td>
            </tr>

            <tr>
                <td><strong>Cidade</strong></td>
                <td>' .
                    escapar_email_html($cidade) .
                '</td>
            </tr>

            <tr>
                <td><strong>Concelho</strong></td>
                <td>' .
                    escapar_email_html($concelho) .
                '</td>
            </tr>

            <tr>
                <td><strong>Distrito</strong></td>
                <td>' .
                    escapar_email_html($distrito) .
                '</td>
            </tr>

            <tr>
                <td><strong>País</strong></td>
                <td>' .
                    escapar_email_html($pais) .
                '</td>
            </tr>

            <tr>
                <td><strong>Categoria</strong></td>
                <td>' .
                    escapar_email_html($categoria) .
                '</td>
            </tr>
        </table>
    ';

    try {
        enviar_email_aco(
            (string)$config['club_email'],
            (string)$config['club_name'],
            'Novo pedido de sócio — ' . $nome,
            email_layout_aco(
                'Novo pedido de inscrição',
                $conteudoClube
            ),
            $email,
            $nome
        );

    } catch (Throwable $e) {
        error_log(
            'Erro no formulário novo sócio: ' .
            $e->getMessage()
        );

        json_response([
            'erro' =>
                'A inscrição não pôde ser enviada. Tenta novamente mais tarde.'
        ], 500);
    }

    $conteudoCliente = '
        <p>
            Olá, <strong>' .
                escapar_email_html($nome) .
            '</strong>.
        </p>

        <p>
            Recebemos o teu pedido para fazer parte do
            Andebol Clube Olhão como sócio da categoria
            <strong>' .
                escapar_email_html($categoria) .
            '</strong>.
        </p>

        <p>
            O clube irá confirmar os dados e entrar em contacto
            para concluir a inscrição.
        </p>
    ';

    $confirmacaoEnviada =
        enviar_email_confirmacao_seguro(
            $email,
            $nome,
            'Recebemos o seu pedido de inscrição',
            email_layout_aco(
                'Pedido de inscrição recebido',
                $conteudoCliente
            )
        );

    json_response([
        'mensagem' =>
            $confirmacaoEnviada
                ? 'A inscrição foi enviada. Também enviámos uma confirmação para o teu e-mail.'
                : 'A inscrição foi enviada ao clube. A confirmação por e-mail poderá demorar.'
    ]);
}

/*
|--------------------------------------------------------------------------
| ESCALÕES QUE O UTILIZADOR PODE GERIR
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === 'me/escaloes') {
    $user = exigir_login();

    if ($user['tipo'] === 'admin') {
        json_response(['Sub-12', 'Sub-14', 'Sub-16', 'Sub-18']);
    }

    if ($user['tipo'] !== 'treinador') {
        json_response(['erro' => 'Sem permissão.'], 403);
    }

    $stmt = $pdo->prepare(
        'SELECT escalao
         FROM treinador_escalao
         WHERE id_treinador = ?
         ORDER BY escalao ASC'
    );

    $stmt->execute([
        $user['id']
    ]);

    $rows = $stmt->fetchAll();

    $escaloes = array_map(
        fn($row) => $row['escalao'],
        $rows
    );

    json_response($escaloes);
}

/*
|--------------------------------------------------------------------------
| CRIAR CONVOCATÓRIA
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === 'convocatoria') {
    $user = exigir_login();

    $adversario = trim((string)($body['adversario'] ?? ''));
    $data = trim((string)($body['data'] ?? ''));
    $local = trim((string)($body['local'] ?? ''));
    $escalao = trim((string)($body['escalao'] ?? ''));

    $idsJogadores = normalizar_ids_jogadores(
        $body['ids_jogadores'] ?? []
    );

    if (
        $adversario === '' ||
        $data === '' ||
        $local === '' ||
        $escalao === ''
    ) {
        json_response([
            'erro' =>
                'Preenche adversário, data, local e escalão.'
        ], 400);
    }

    if (!in_array($escalao, ESCALOES_VALIDOS, true)) {
        json_response([
            'erro' => 'Escalão inválido.'
        ], 400);
    }

    if (count($idsJogadores) === 0) {
        json_response([
            'erro' => 'Seleciona pelo menos um jogador.'
        ], 400);
    }

    if (!pode_gerir_escalao(
        $pdo,
        $user,
        $escalao
    )) {
        json_response([
            'erro' =>
                'Sem permissão. O treinador só pode lançar ' .
                'convocatórias do escalão associado.'
        ], 403);
    }

    if (!jogadores_pertencem_ao_escalao(
        $pdo,
        $idsJogadores,
        $escalao
    )) {
        json_response([
            'erro' =>
                'Há jogadores selecionados que não pertencem ' .
                'ao escalão escolhido.'
        ], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO convocatorias
             (
                jogo_contra,
                data_jogo,
                local,
                escalao,
                id_criador
             )
             VALUES (?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $adversario,
            $data,
            $local,
            $escalao,
            $user['id']
        ]);

        $idConvocatoria =
            (int)$pdo->lastInsertId();

        $stmtConvocado = $pdo->prepare(
            'INSERT INTO convocados
             (
                id_convocatoria,
                id_jogador
             )
             VALUES (?, ?)'
        );

        foreach ($idsJogadores as $idJogador) {
            $stmtConvocado->execute([
                $idConvocatoria,
                $idJogador
            ]);
        }

        /*
         * -------------------------------------------------------------
         * NOTIFICAÇÕES AUTOMÁTICAS
         * -------------------------------------------------------------
         *
         * Recebem aviso:
         * - jogadores convocados;
         * - encarregados ligados aos jogadores;
         * - treinadores do escalão, exceto quem criou a convocatória.
         */

        $dataFormatada = date(
            'd/m/Y H:i',
            strtotime($data)
        );

        $titulo =
            'Nova convocatória — ' . $escalao;

        $url =
            '/convocatoria.html';

        $stmtNotificacao = $pdo->prepare(
            'INSERT IGNORE INTO notificacoes
             (
                id_utilizador,
                id_convocatoria,
                tipo,
                titulo,
                mensagem,
                url
             )
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        /*
         * Jogadores convocados.
         */
        foreach ($idsJogadores as $idJogador) {
            $mensagemJogador =
                'Foste convocado para o jogo contra ' .
                $adversario .
                ', no dia ' .
                $dataFormatada .
                ', em ' .
                $local .
                '.';

            $stmtNotificacao->execute([
                $idJogador,
                $idConvocatoria,
                'convocatoria',
                $titulo,
                $mensagemJogador,
                $url
            ]);
        }

        /*
         * Nomes dos jogadores selecionados.
         */
        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($idsJogadores),
                '?'
            )
        );

        $stmtJogadores = $pdo->prepare(
            "SELECT id, nome
             FROM utilizadores
             WHERE id IN ($placeholders)"
        );

        $stmtJogadores->execute(
            $idsJogadores
        );

        $nomesJogadores = [];

        foreach (
            $stmtJogadores->fetchAll()
            as $jogador
        ) {
            $nomesJogadores[
                (int)$jogador['id']
            ] = (string)$jogador['nome'];
        }

        /*
         * Encarregados de educação.
         * Um encarregado recebe uma única notificação,
         * mesmo que tenha mais de um educando convocado.
         */
        $stmtEncarregados = $pdo->prepare(
            "SELECT
                ej.id_encarregado,
                ej.id_jogador
             FROM encarregados_jogadores ej
             WHERE ej.id_jogador IN ($placeholders)"
        );

        $stmtEncarregados->execute(
            $idsJogadores
        );

        $educandosPorEncarregado = [];

        foreach (
            $stmtEncarregados->fetchAll()
            as $ligacao
        ) {
            $idEncarregado =
                (int)$ligacao['id_encarregado'];

            $idJogador =
                (int)$ligacao['id_jogador'];

            if (
                !isset(
                    $educandosPorEncarregado[
                        $idEncarregado
                    ]
                )
            ) {
                $educandosPorEncarregado[
                    $idEncarregado
                ] = [];
            }

            $educandosPorEncarregado[
                $idEncarregado
            ][] =
                $nomesJogadores[$idJogador]
                ?? 'Educando';
        }

        foreach (
            $educandosPorEncarregado
            as $idEncarregado => $nomes
        ) {
            $nomes = array_values(
                array_unique($nomes)
            );

            $mensagemEncarregado =
                'Foi criada uma convocatória contra ' .
                $adversario .
                ', no dia ' .
                $dataFormatada .
                ', em ' .
                $local .
                '. Educando(s): ' .
                implode(', ', $nomes) .
                '.';

            $stmtNotificacao->execute([
                (int)$idEncarregado,
                $idConvocatoria,
                'convocatoria',
                $titulo,
                $mensagemEncarregado,
                $url
            ]);
        }

        /*
         * Outros treinadores associados ao escalão.
         */
        $stmtTreinadores = $pdo->prepare(
            'SELECT DISTINCT id_treinador
             FROM treinador_escalao
             WHERE escalao = ?'
        );

        $stmtTreinadores->execute([
            $escalao
        ]);

        foreach (
            $stmtTreinadores->fetchAll()
            as $treinador
        ) {
            $idTreinador =
                (int)$treinador['id_treinador'];

            if (
                $idTreinador <= 0 ||
                $idTreinador === (int)$user['id']
            ) {
                continue;
            }

            $mensagemTreinador =
                'Foi criada uma convocatória do escalão ' .
                $escalao .
                ' contra ' .
                $adversario .
                ', no dia ' .
                $dataFormatada .
                ', em ' .
                $local .
                '.';

            $stmtNotificacao->execute([
                $idTreinador,
                $idConvocatoria,
                'convocatoria',
                $titulo,
                $mensagemTreinador,
                $url
            ]);
        }

        $pdo->commit();

        /*
         * A convocatória já está gravada.
         * Se algum e-mail falhar, o jogo não é apagado.
         */
        try {
            $resultadoEmails =
                enviar_emails_convocatoria_aco(
                    $pdo,
                    $idConvocatoria,
                    $idsJogadores,
                    $adversario,
                    $data,
                    $local,
                    $escalao,
                    (int)$user['id']
                );

        } catch (Throwable $emailError) {
            error_log(
                'Erro geral nos emails da convocatória: ' .
                $emailError->getMessage()
            );

            $resultadoEmails = [
                'enviados' => 0,
                'falhados' => 1,
            ];
        }

        $mensagem =
            'Convocatória enviada e notificações criadas!';

        if ($resultadoEmails['enviados'] > 0) {
            $mensagem .=
                ' E-mails enviados: ' .
                $resultadoEmails['enviados'] .
                '.';
        }

        if ($resultadoEmails['falhados'] > 0) {
            $mensagem .=
                ' Alguns e-mails não puderam ser enviados.';
        }

        json_response([
            'mensagem' => $mensagem,
            'id_convocatoria' =>
                $idConvocatoria,
            'emails' =>
                $resultadoEmails
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        json_response([
            'erro' =>
                'Erro ao criar convocatória: ' .
                $e->getMessage()
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| LISTAR JOGOS DO JOGADOR
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === 'meus-jogos') {
    $idJogador = $_GET['id'] ?? null;

    if (!$idJogador) {
        json_response([]);
    }

    $stmt = $pdo->prepare(
        "SELECT
            c.*,
            CASE
                WHEN u.tipo = 'treinador' THEN u.nome
                ELSE NULL
            END AS nome_treinador,
            u.tipo AS tipo_criador
         FROM convocatorias c
         JOIN convocados cv ON c.id = cv.id_convocatoria
         LEFT JOIN utilizadores u ON u.id = c.id_criador
         WHERE cv.id_jogador = ?
         ORDER BY c.data_jogo DESC"
    );

    $stmt->execute([
        (int)$idJogador
    ]);

    json_response($stmt->fetchAll());
}



/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === 'login') {
    $login = trim((string)($body['email'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($login === '' || $password === '') {
        json_response([
            'sucesso' => false,
            'mensagem' =>
                'Preenche o utilizador/email e a password.'
        ], 400);
    }

    /*
     * Procuramos primeiro o utilizador.
     * A password nunca é usada diretamente na consulta SQL.
     */
    if (str_contains($login, '@')) {
        $stmt = $pdo->prepare(
            'SELECT *
             FROM utilizadores
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$login]);

    } else {
        /*
         * Mantém o comportamento anterior:
         * login sem @ é permitido apenas ao administrador.
         */
        $stmt = $pdo->prepare(
            "SELECT *
             FROM utilizadores
             WHERE tipo = 'admin'
               AND (nome = ? OR email = ?)
             LIMIT 1"
        );

        $stmt->execute([
            $login,
            $login
        ]);
    }

    $user = $stmt->fetch();

    if (!$user) {
        json_response([
            'sucesso' => false,
            'mensagem' =>
                'Utilizador/email ou password errados.'
        ], 401);
    }

    $passwordGuardada =
        (string)($user['senha'] ?? '');

    $jaTemHash =
        password_tem_hash_valido($passwordGuardada);

    /*
     * Contas novas:
     * password_verify() compara a password com o hash.
     *
     * Contas antigas:
     * ainda podem entrar com a password antiga em texto simples.
     * No primeiro login correto, a password é imediatamente
     * convertida para hash e atualizada na base de dados.
     */
    if ($jaTemHash) {
        $passwordCorreta = password_verify(
            $password,
            $passwordGuardada
        );

    } else {
        $passwordCorreta = hash_equals(
            $passwordGuardada,
            $password
        );
    }

    if (!$passwordCorreta) {
        json_response([
            'sucesso' => false,
            'mensagem' =>
                'Utilizador/email ou password errados.'
        ], 401);
    }

    /*
     * Atualização automática:
     * - migra passwords antigas em texto simples;
     * - atualiza hashes antigos quando PASSWORD_DEFAULT mudar.
     */
    if (
        !$jaTemHash ||
        password_needs_rehash(
            $passwordGuardada,
            PASSWORD_DEFAULT
        )
    ) {
        $novoHash = criar_hash_password($password);

        $stmtAtualizar = $pdo->prepare(
            'UPDATE utilizadores
             SET senha = ?
             WHERE id = ?'
        );

        $stmtAtualizar->execute([
            $novoHash,
            (int)$user['id']
        ]);
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'nome' => $user['nome'],
        'email' => $user['email'],
        'tipo' => $user['tipo'],
    ];

    json_response([
        'sucesso' => true,
        'utilizador' => [
            'id' => (int)$user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'nif' => $user['nif'] ?? null,
            'tipo' => $user['tipo'],
            'admin' =>
                $user['tipo'] === 'admin' ? 1 : 0,
        ]
    ]);
}


/*
|--------------------------------------------------------------------------
| ÁREA PESSOAL DO UTILIZADOR
|--------------------------------------------------------------------------
*/

/*
 * A conta é identificada exclusivamente pela sessão.
 * O navegador nunca envia o ID da conta a editar.
 */
if (
    $method === 'GET' &&
    $path === 'me/perfil'
) {
    $sessionUser = exigir_login();

    $stmt = $pdo->prepare(
        'SELECT
            id,
            nome,
            email,
            nif,
            morada,
            tipo
         FROM utilizadores
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([
        (int)$sessionUser['id']
    ]);

    $perfil = $stmt->fetch();

    if (!$perfil) {
        session_destroy();

        json_response([
            'erro' =>
                'A conta associada à sessão já não existe.'
        ], 401);
    }

    json_response([
        'perfil' =>
            perfil_publico_utilizador($perfil),
        'csrf' =>
            token_area_pessoal()
    ]);
}

if (
    $method === 'PUT' &&
    $path === 'me/perfil'
) {
    $sessionUser = exigir_login();
    exigir_token_area_pessoal();

    $idUtilizador =
        (int)$sessionUser['id'];

    $nome = limpar_texto_formulario(
        $body['nome'] ?? '',
        120
    );

    $email = strtolower(
        limpar_texto_formulario(
            $body['email'] ?? '',
            150
        )
    );

    $nif = preg_replace(
        '/\D+/',
        '',
        (string)($body['nif'] ?? '')
    ) ?? '';

    $morada = trim(
        mb_substr(
            (string)($body['morada'] ?? ''),
            0,
            500
        )
    );

    $passwordConfirmacao =
        (string)(
            $body['password_confirmacao'] ?? ''
        );

    if (
        mb_strlen($nome) < 2
    ) {
        json_response([
            'erro' =>
                'O nome deve ter pelo menos 2 caracteres.'
        ], 400);
    }

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        json_response([
            'erro' =>
                'Indica um endereço de e-mail válido.'
        ], 400);
    }

    if (
        $nif !== '' &&
        strlen($nif) !== 9
    ) {
        json_response([
            'erro' =>
                'O NIF deve ter exatamente 9 algarismos.'
        ], 400);
    }

    $stmtAtual = $pdo->prepare(
        'SELECT
            id,
            nome,
            email,
            nif,
            morada,
            tipo,
            senha
         FROM utilizadores
         WHERE id = ?
         LIMIT 1'
    );

    $stmtAtual->execute([
        $idUtilizador
    ]);

    $atual = $stmtAtual->fetch();

    if (!$atual) {
        session_destroy();

        json_response([
            'erro' =>
                'A conta associada à sessão já não existe.'
        ], 401);
    }

    $emailMudou =
        strtolower((string)$atual['email']) !==
        $email;

    if ($emailMudou) {
        if ($passwordConfirmacao === '') {
            json_response([
                'erro' =>
                    'Introduz a password atual para alterar o e-mail.'
            ], 400);
        }

        if (
            !verificar_password_guardada(
                $passwordConfirmacao,
                (string)$atual['senha']
            )
        ) {
            json_response([
                'erro' =>
                    'A password atual está incorreta.'
            ], 403);
        }
    }

    $stmtDuplicado = $pdo->prepare(
        'SELECT id
         FROM utilizadores
         WHERE email = ?
           AND id <> ?
         LIMIT 1'
    );

    $stmtDuplicado->execute([
        $email,
        $idUtilizador
    ]);

    if ($stmtDuplicado->fetch()) {
        json_response([
            'erro' =>
                'Já existe uma conta com esse e-mail.'
        ], 409);
    }

    try {
        $stmtAtualizar = $pdo->prepare(
            'UPDATE utilizadores
             SET
                nome = ?,
                email = ?,
                nif = ?,
                morada = ?
             WHERE id = ?'
        );

        $stmtAtualizar->execute([
            $nome,
            $email,
            $nif !== '' ? $nif : null,
            $morada !== '' ? $morada : null,
            $idUtilizador
        ]);

        $_SESSION['user']['nome'] = $nome;
        $_SESSION['user']['email'] = $email;

        $stmtPerfil = $pdo->prepare(
            'SELECT
                id,
                nome,
                email,
                nif,
                morada,
                tipo
             FROM utilizadores
             WHERE id = ?
             LIMIT 1'
        );

        $stmtPerfil->execute([
            $idUtilizador
        ]);

        $perfilAtualizado =
            $stmtPerfil->fetch();

        json_response([
            'mensagem' =>
                'As informações pessoais foram atualizadas.',
            'perfil' =>
                perfil_publico_utilizador(
                    $perfilAtualizado
                ),
            'csrf' =>
                rodar_token_area_pessoal()
        ]);

    } catch (Throwable $e) {
        error_log(
            'Erro ao atualizar perfil: ' .
            $e->getMessage()
        );

        json_response([
            'erro' =>
                'Não foi possível atualizar as informações.'
        ], 500);
    }
}

if (
    $method === 'PUT' &&
    $path === 'me/password'
) {
    $sessionUser = exigir_login();
    exigir_token_area_pessoal();

    $idUtilizador =
        (int)$sessionUser['id'];

    $passwordAtual =
        (string)($body['password_atual'] ?? '');

    $passwordNova =
        (string)($body['password_nova'] ?? '');

    if (
        $passwordAtual === '' ||
        $passwordNova === ''
    ) {
        json_response([
            'erro' =>
                'Preenche a password atual e a nova password.'
        ], 400);
    }

    if (
        mb_strlen($passwordNova) < 8
    ) {
        json_response([
            'erro' =>
                'A nova password deve ter pelo menos 8 caracteres.'
        ], 400);
    }

    if (
        hash_equals(
            $passwordAtual,
            $passwordNova
        )
    ) {
        json_response([
            'erro' =>
                'A nova password não pode ser igual à password atual.'
        ], 400);
    }

    $stmt = $pdo->prepare(
        'SELECT senha
         FROM utilizadores
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $idUtilizador
    ]);

    $passwordGuardada =
        $stmt->fetchColumn();

    if ($passwordGuardada === false) {
        session_destroy();

        json_response([
            'erro' =>
                'A conta associada à sessão já não existe.'
        ], 401);
    }

    if (
        !verificar_password_guardada(
            $passwordAtual,
            (string)$passwordGuardada
        )
    ) {
        json_response([
            'erro' =>
                'A password atual está incorreta.'
        ], 403);
    }

    try {
        $novoHash =
            criar_hash_password($passwordNova);

        $stmtAtualizar = $pdo->prepare(
            'UPDATE utilizadores
             SET senha = ?
             WHERE id = ?'
        );

        $stmtAtualizar->execute([
            $novoHash,
            $idUtilizador
        ]);

        /*
         * Renova o identificador da sessão depois
         * de uma alteração de credenciais.
         */
        session_regenerate_id(true);

        json_response([
            'mensagem' =>
                'A password foi alterada com sucesso.',
            'csrf' =>
                rodar_token_area_pessoal()
        ]);

    } catch (Throwable $e) {
        error_log(
            'Erro ao alterar password: ' .
            $e->getMessage()
        );

        json_response([
            'erro' =>
                'Não foi possível alterar a password.'
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| SESSÃO
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === 'check-session') {
    $user = user_logado();

    json_response([
        'logado' => (bool)$user,
        'user' => $user
    ]);
}

/*
|--------------------------------------------------------------------------
| UTILIZADORES
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === 'todos-utilizadores') {
    exigir_admin_ou_treinador();

    $stmt = $pdo->query('SELECT id, nome, tipo FROM utilizadores');
    json_response($stmt->fetchAll());
}

if ($method === 'GET' && $path === 'jogadores-completo') {
    exigir_admin_ou_treinador();

    $stmt = $pdo->query(
        "SELECT id, nome, nif, morada
         FROM utilizadores
         WHERE tipo = 'jogador'"
    );

    json_response($stmt->fetchAll());
}

if ($method === 'GET' && ($parts[0] ?? '') === 'jogadores' && isset($parts[1])) {
    exigir_admin_ou_treinador();

    $escalao = urldecode($parts[1]);

    $stmt = $pdo->prepare(
        "SELECT u.id, u.nome
         FROM utilizadores u
         JOIN jogador_escaloes je ON u.id = je.id_jogador
         WHERE je.escalao = ?"
    );
    $stmt->execute([$escalao]);

    json_response($stmt->fetchAll());
}

if ($method === 'DELETE' && ($parts[0] ?? '') === 'utilizadores' && isset($parts[1])) {
    exigir_admin_ou_treinador();

    $id = (int)$parts[1];

    $stmt = $pdo->prepare('DELETE FROM utilizadores WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        json_response(['mensagem' => 'Utilizador não encontrado'], 404);
    }

    json_response(['mensagem' => 'Utilizador apagado com sucesso!']);
}

if ($method === 'POST' && $path === 'criar-utilizador') {
    exigir_admin_ou_treinador();

    $nome = trim((string)($body['nome'] ?? ''));
    $email = trim((string)($body['email'] ?? ''));
    $senha = trim((string)($body['senha'] ?? ''));
    $tipo = trim((string)($body['tipo'] ?? ''));
    $nif = $body['nif'] ?? null;
    $morada = $body['morada'] ?? null;
    $escaloes = $body['escaloes'] ?? [];

    if ($nome === '' || $email === '' || $senha === '' || $tipo === '') {
        json_response([
            'erro' => 'Dados obrigatórios em falta.'
        ], 400);
    }

    if (mb_strlen($senha) < 8) {
        json_response([
            'erro' =>
                'A password deve ter pelo menos 8 caracteres.'
        ], 400);
    }

    $pdo->beginTransaction();

    try {
        $senhaHash = criar_hash_password($senha);

        $stmt = $pdo->prepare(
            'INSERT INTO utilizadores
             (nome, email, senha, tipo, nif, morada)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $nome,
            $email,
            $senhaHash,
            $tipo,
            $nif,
            $morada
        ]);

        $idNovo = (int)$pdo->lastInsertId();

        if ($tipo === 'jogador' && is_array($escaloes)) {
            $stmtEsc = $pdo->prepare(
                'INSERT INTO jogador_escaloes (id_jogador, escalao) VALUES (?, ?)'
            );

            foreach ($escaloes as $escalao) {
                $stmtEsc->execute([$idNovo, $escalao]);
            }
        }

        if ($tipo === 'treinador' && is_array($escaloes)) {
            $stmtEsc = $pdo->prepare(
                'INSERT INTO treinador_escalao (id_treinador, escalao) VALUES (?, ?)'
            );

            foreach ($escaloes as $escalao) {
                $stmtEsc->execute([$idNovo, $escalao]);
            }
        }

        $pdo->commit();

        json_response(['mensagem' => 'Utilizador criado com sucesso!']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['erro' => $e->getMessage()], 500);
    }
}

/*
|--------------------------------------------------------------------------
| FATURAS
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === 'minhas-faturas') {
    $user = exigir_login();
    $tipo = strtolower((string)($user['tipo'] ?? ''));

    /*
     * Área financeira pessoal:
     * - jogador: consulta apenas as próprias faturas;
     * - treinador: consulta apenas as próprias faturas;
     * - encarregado: consulta apenas faturas dos educandos;
     * - admin: emite no painel, mas não exporta nesta área.
     */

    if ($tipo === 'jogador' || $tipo === 'treinador') {
        $idTitular = (int)$user['id'];

    } elseif ($tipo === 'encarregado') {
        $idTitular = (int)($_GET['id'] ?? 0);

        if ($idTitular <= 0) {
            json_response([
                'erro' => 'Seleciona um educando válido.'
            ], 400);
        }

        $stmtLigacao = $pdo->prepare(
            'SELECT 1
             FROM encarregados_jogadores
             WHERE id_encarregado = ?
               AND id_jogador = ?
             LIMIT 1'
        );

        $stmtLigacao->execute([
            (int)$user['id'],
            $idTitular
        ]);

        if (!$stmtLigacao->fetchColumn()) {
            json_response([
                'erro' =>
                    'Não tens permissão para consultar ' .
                    'as faturas deste jogador.'
            ], 403);
        }

    } else {
        json_response([
            'erro' =>
                'Este perfil não tem acesso à ' .
                'área financeira pessoal.'
        ], 403);
    }

    $stmt = $pdo->prepare(
        'SELECT *
         FROM faturas
         WHERE id_jogador = ?
         ORDER BY data_emissao DESC, id DESC'
    );

    $stmt->execute([$idTitular]);

    json_response($stmt->fetchAll());
}

if ($method === 'POST' && $path === 'faturas') {
    $user = exigir_login();

    if (($user['tipo'] ?? '') !== 'admin') {
        json_response([
            'erro' => 'Apenas administradores podem emitir faturas.'
        ], 403);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO faturas
        (numero_recibo, data_emissao, id_jogador, nome_jogador, nif_jogador, morada_jogador,
         descricao, valor_base, valor_iva, valor_desconto, valor_total,
         data_pagamento, metodo_pagamento, nota_pagamento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $body['numero_recibo'] ?? null,
        $body['data_emissao'] ?? null,
        $body['id_jogador'] ?? null,
        $body['nome_jogador'] ?? null,
        $body['nif_jogador'] ?? null,
        $body['morada_jogador'] ?? null,
        $body['descricao'] ?? null,
        $body['valor_base'] ?? null,
        $body['valor_iva'] ?? null,
        $body['valor_desconto'] ?? null,
        $body['valor_total'] ?? null,
        $body['data_pagamento'] ?? null,
        $body['metodo_pagamento'] ?? null,
        $body['nota_pagamento'] ?? null,
    ]);

    json_response(['mensagem' => 'Fatura lançada com sucesso!']);
}

/*
|--------------------------------------------------------------------------
| ENCARREGADOS / EDUCANDOS
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === 'encarregados') {
    exigir_admin_ou_treinador();

    $stmt = $pdo->query(
        "SELECT id, nome
         FROM utilizadores
         WHERE tipo = 'encarregado'
         ORDER BY nome ASC"
    );

    json_response($stmt->fetchAll());
}

if ($method === 'POST' && ($path === 'associar-familia' || $path === 'associar-educando')) {
    exigir_admin_ou_treinador();

    $stmt = $pdo->prepare(
        'INSERT INTO encarregados_jogadores (id_encarregado, id_jogador)
         VALUES (?, ?)'
    );

    try {
        $stmt->execute([
            $body['id_encarregado'] ?? null,
            $body['id_jogador'] ?? null,
        ]);

        json_response(['mensagem' => 'Ligação criada com sucesso!']);
    } catch (PDOException $e) {
        json_response(['erro' => 'Esta ligação já existe ou os dados são inválidos.'], 400);
    }
}

if ($method === 'GET' && $path === 'meus-educandos') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        json_response([]);
    }

    $stmt = $pdo->prepare(
        'SELECT u.id, u.nome
         FROM utilizadores u
         JOIN encarregados_jogadores ej ON u.id = ej.id_jogador
         WHERE ej.id_encarregado = ?'
    );
    $stmt->execute([(int)$id]);

    json_response($stmt->fetchAll());
}

/*
|--------------------------------------------------------------------------
| PLANTEL
|--------------------------------------------------------------------------
*/

/*
 * Upload da fotografia.
 * A imagem é guardada em /img/equipa/uploads/
 * e a API devolve o caminho público.
 */
if ($method === 'POST' && $path === 'plantel-foto') {
    $user = exigir_login();

    if (($user['tipo'] ?? '') !== 'admin') {
        json_response([
            'erro' =>
                'Apenas administradores podem enviar fotografias.'
        ], 403);
    }

    if (
        !isset($_FILES['foto']) ||
        !is_array($_FILES['foto'])
    ) {
        json_response([
            'erro' => 'Seleciona uma fotografia.'
        ], 400);
    }

    $foto = $_FILES['foto'];

    if (
        ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !==
        UPLOAD_ERR_OK
    ) {
        json_response([
            'erro' =>
                'Não foi possível receber a fotografia.'
        ], 400);
    }

    $tamanhoMaximo = 5 * 1024 * 1024;

    if ((int)($foto['size'] ?? 0) > $tamanhoMaximo) {
        json_response([
            'erro' =>
                'A fotografia não pode ultrapassar 5 MB.'
        ], 400);
    }

    $ficheiroTemporario =
        (string)($foto['tmp_name'] ?? '');

    if (
        $ficheiroTemporario === '' ||
        !is_uploaded_file($ficheiroTemporario)
    ) {
        json_response([
            'erro' => 'Upload de fotografia inválido.'
        ], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($ficheiroTemporario);

    $formatosPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($formatosPermitidos[$mime])) {
        json_response([
            'erro' =>
                'Formato inválido. Usa JPG, PNG ou WEBP.'
        ], 400);
    }

    $pastaUploads =
        __DIR__ . '/img/equipa/uploads';

    if (
        !is_dir($pastaUploads) &&
        !mkdir($pastaUploads, 0755, true) &&
        !is_dir($pastaUploads)
    ) {
        json_response([
            'erro' =>
                'Não foi possível criar a pasta das fotografias.'
        ], 500);
    }

    $extensao = $formatosPermitidos[$mime];

    try {
        $nomeFicheiro =
            'atleta_' .
            date('Ymd_His') .
            '_' .
            bin2hex(random_bytes(5)) .
            '.' .
            $extensao;

    } catch (Throwable $e) {
        $nomeFicheiro =
            'atleta_' .
            date('Ymd_His') .
            '_' .
            uniqid() .
            '.' .
            $extensao;
    }

    $destino =
        $pastaUploads . '/' . $nomeFicheiro;

    if (
        !move_uploaded_file(
            $ficheiroTemporario,
            $destino
        )
    ) {
        json_response([
            'erro' =>
                'Não foi possível guardar a fotografia.'
        ], 500);
    }

    json_response([
        'mensagem' =>
            'Fotografia enviada com sucesso.',
        'foto' =>
            '/img/equipa/uploads/' . $nomeFicheiro
    ]);
}

/*
 * Lista pública do plantel.
 * Quando recebe ?escalao=Sub-14 devolve todos os atletas
 * associados a esse escalão, mesmo que também pertençam a outros.
 */
if ($method === 'GET' && $path === 'plantel-jogadores') {
    $escalao = trim(
        (string)($_GET['escalao'] ?? '')
    );

    $sql = "
        SELECT
            p.id,
            p.nome,
            p.numero,
            p.idade,
            p.posicao,
            p.escalao,
            p.foto,
            p.ativo,
            COALESCE(
                (
                    SELECT GROUP_CONCAT(
                        pe2.escalao
                        ORDER BY pe2.escalao
                        SEPARATOR '||'
                    )
                    FROM plantel_jogador_escaloes pe2
                    WHERE pe2.id_plantel_jogador = p.id
                ),
                p.escalao
            ) AS escaloes_lista
        FROM plantel_jogadores p
        WHERE p.ativo = 1
    ";

    $params = [];

    if ($escalao !== '') {
        $sql .= "
            AND EXISTS (
                SELECT 1
                FROM plantel_jogador_escaloes pe
                WHERE pe.id_plantel_jogador = p.id
                  AND pe.escalao = ?
            )
        ";

        $params[] = $escalao;
    }

    $sql .= "
        ORDER BY
            p.posicao ASC,
            CAST(p.numero AS UNSIGNED) ASC,
            p.nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $jogadores = $stmt->fetchAll();

    foreach ($jogadores as &$jogador) {
        $lista = explode(
            '||',
            (string)($jogador['escaloes_lista'] ?? '')
        );

        $jogador['escaloes'] = array_values(
            array_filter(
                array_map('trim', $lista),
                fn($valor) => $valor !== ''
            )
        );

        unset($jogador['escaloes_lista']);
    }

    unset($jogador);

    json_response($jogadores);
}

/*
 * Adiciona uma única ficha de atleta e associa-a
 * a um ou vários escalões.
 */
if ($method === 'POST' && $path === 'plantel-jogadores') {
    $user = exigir_login();

    if (($user['tipo'] ?? '') !== 'admin') {
        json_response([
            'erro' =>
                'Apenas administradores podem gerir jogadores.'
        ], 403);
    }

    $nome = trim(
        (string)($body['nome'] ?? '')
    );

    $escaloes = normalizar_escaloes_plantel(
        $body['escaloes'] ??
        $body['escalao'] ??
        []
    );

    if ($nome === '') {
        json_response([
            'erro' => 'Indica o nome do jogador.'
        ], 400);
    }

    if (count($escaloes) === 0) {
        json_response([
            'erro' =>
                'Escolhe pelo menos um escalão válido.'
        ], 400);
    }

    $foto = trim(
        (string)($body['foto'] ?? '')
    );

    if ($foto === '') {
        $foto = '/img/equipa/default.png';
    }

    try {
        $pdo->beginTransaction();

        /*
         * Mantemos a coluna antiga escalao com o primeiro valor
         * para compatibilidade com páginas antigas.
         */
        $stmt = $pdo->prepare(
            'INSERT INTO plantel_jogadores
             (
                nome,
                numero,
                idade,
                posicao,
                escalao,
                foto,
                ativo
             )
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );

        $stmt->execute([
            $nome,
            $body['numero'] ?? null,
            $body['idade'] ?? null,
            trim(
                (string)(
                    $body['posicao'] ??
                    'Universal'
                )
            ),
            $escaloes[0],
            $foto
        ]);

        $idJogador =
            (int)$pdo->lastInsertId();

        $stmtEscalao = $pdo->prepare(
            'INSERT INTO plantel_jogador_escaloes
             (
                id_plantel_jogador,
                escalao
             )
             VALUES (?, ?)'
        );

        foreach ($escaloes as $escalao) {
            $stmtEscalao->execute([
                $idJogador,
                $escalao
            ]);
        }

        $pdo->commit();

        json_response([
            'mensagem' =>
                'Jogador adicionado aos escalões: ' .
                implode(', ', $escaloes) .
                '.',
            'id' => $idJogador,
            'foto' => $foto,
            'escaloes' => $escaloes
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        json_response([
            'erro' =>
                'Erro ao adicionar jogador: ' .
                $e->getMessage()
        ], 500);
    }
}

if (
    $method === 'DELETE' &&
    ($parts[0] ?? '') === 'plantel-jogadores' &&
    isset($parts[1])
) {
    $user = exigir_login();

    if (($user['tipo'] ?? '') !== 'admin') {
        json_response([
            'erro' =>
                'Apenas administradores podem gerir jogadores.'
        ], 403);
    }

    $stmt = $pdo->prepare(
        'UPDATE plantel_jogadores
         SET ativo = 0
         WHERE id = ?'
    );

    $stmt->execute([
        (int)$parts[1]
    ]);

    json_response([
        'mensagem' =>
            'Jogador removido das páginas das equipas.'
    ]);
}

/*
|--------------------------------------------------------------------------
| APAGAR UTILIZADOR
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === 'apagar-utilizador') {
    $user = exigir_login();

    if ($user['tipo'] !== 'admin') {
        json_response(['erro' => 'Apenas administradores podem apagar utilizadores.'], 403);
    }

    $id = (int)($body['id'] ?? 0);

    if ($id <= 0) {
        json_response(['erro' => 'ID inválido.'], 400);
    }

    if ((int)$user['id'] === $id) {
        json_response(['erro' => 'Não podes apagar a tua própria conta enquanto estás logado.'], 400);
    }

    try {
        $pdo->beginTransaction();

        /*
         * Apagar relações primeiro.
         * Isto evita erro de foreign key:
         * Cannot delete or update a parent row.
         */

        $stmt = $pdo->prepare('DELETE FROM jogador_escaloes WHERE id_jogador = ?');
        $stmt->execute([$id]);

        $stmt = $pdo->prepare('DELETE FROM treinador_escalao WHERE id_treinador = ?');
        $stmt->execute([$id]);

        $stmt = $pdo->prepare('DELETE FROM encarregados_jogadores WHERE id_encarregado = ? OR id_jogador = ?');
        $stmt->execute([$id, $id]);

        $stmt = $pdo->prepare('DELETE FROM convocados WHERE id_jogador = ?');
        $stmt->execute([$id]);

        /*
         * Se o utilizador criou convocatórias, apagamos primeiro os convocados dessas convocatórias.
         */
        $stmt = $pdo->prepare('SELECT id FROM convocatorias WHERE id_criador = ?');
        $stmt->execute([$id]);
        $convocatorias = $stmt->fetchAll();

        foreach ($convocatorias as $conv) {
            $idConv = (int)$conv['id'];

            $stmtDelConvocados = $pdo->prepare('DELETE FROM convocados WHERE id_convocatoria = ?');
            $stmtDelConvocados->execute([$idConv]);
        }

        $stmt = $pdo->prepare('DELETE FROM convocatorias WHERE id_criador = ?');
        $stmt->execute([$id]);

        /*
         * Atenção: isto apaga faturas do jogador.
         * Se quiseres manter histórico financeiro, diz-me e eu mudo para soft delete.
         */
        $stmt = $pdo->prepare('DELETE FROM faturas WHERE id_jogador = ?');
        $stmt->execute([$id]);

        /*
         * Finalmente apagar o utilizador.
         */
        $stmt = $pdo->prepare('DELETE FROM utilizadores WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            json_response(['erro' => 'Utilizador não encontrado.'], 404);
        }

        $pdo->commit();

        json_response([
            'mensagem' => 'Utilizador apagado da base de dados com sucesso.'
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        json_response([
            'erro' => 'Erro ao apagar utilizador: ' . $e->getMessage()
        ], 500);
    }
}


/*
|--------------------------------------------------------------------------
| NOTIFICAÇÕES
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET' &&
    $path === 'minhas-notificacoes'
) {
    $user = exigir_login();

    $stmt = $pdo->prepare(
        'SELECT
            id,
            id_convocatoria,
            tipo,
            titulo,
            mensagem,
            url,
            lida,
            criado_em
         FROM notificacoes
         WHERE id_utilizador = ?
         ORDER BY criado_em DESC, id DESC
         LIMIT 30'
    );

    $stmt->execute([
        (int)$user['id']
    ]);

    $notificacoes = $stmt->fetchAll();

    foreach ($notificacoes as &$notificacao) {
        $notificacao['id'] =
            (int)$notificacao['id'];

        $notificacao['lida'] =
            (int)$notificacao['lida'];
    }

    unset($notificacao);

    $stmtCount = $pdo->prepare(
        'SELECT COUNT(*)
         FROM notificacoes
         WHERE id_utilizador = ?
           AND lida = 0'
    );

    $stmtCount->execute([
        (int)$user['id']
    ]);

    json_response([
        'notificacoes' => $notificacoes,
        'nao_lidas' =>
            (int)$stmtCount->fetchColumn()
    ]);
}

if (
    $method === 'POST' &&
    ($parts[0] ?? '') === 'notificacoes' &&
    isset($parts[1]) &&
    ($parts[2] ?? '') === 'ler'
) {
    $user = exigir_login();
    $idNotificacao = (int)$parts[1];

    if ($idNotificacao <= 0) {
        json_response([
            'erro' => 'Notificação inválida.'
        ], 400);
    }

    $stmt = $pdo->prepare(
        'UPDATE notificacoes
         SET lida = 1
         WHERE id = ?
           AND id_utilizador = ?'
    );

    $stmt->execute([
        $idNotificacao,
        (int)$user['id']
    ]);

    json_response([
        'mensagem' =>
            'Notificação marcada como lida.'
    ]);
}

if (
    $method === 'POST' &&
    $path === 'notificacoes/marcar-todas'
) {
    $user = exigir_login();

    $stmt = $pdo->prepare(
        'UPDATE notificacoes
         SET lida = 1
         WHERE id_utilizador = ?
           AND lida = 0'
    );

    $stmt->execute([
        (int)$user['id']
    ]);

    json_response([
        'mensagem' =>
            'Notificações marcadas como lidas.'
    ]);
}

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === 'logout') {
    session_destroy();

    header('Location: /');
    exit;
}

json_response([
    'erro' => 'Rota não encontrada.',
    'rota' => $path,
    'metodo' => $method
], 404);