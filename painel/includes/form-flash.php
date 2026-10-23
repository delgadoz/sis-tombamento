<?php
/**
 * Persistência dos dados digitados em um formulário quando o processamento
 * falha (padrão "old input" / flash de sessão).
 *
 * Fluxo:
 *   1. O processador (POST) encontra um erro e chama voltarComErro(): os campos
 *      permitidos são copiados do $_POST para $_SESSION['old'][$form] e o
 *      usuário é redirecionado de volta para o formulário.
 *   2. A página do formulário (GET) chama pegarOld($form), que devolve os dados
 *      e os APAGA da sessão (valem para uma única exibição).
 *   3. Os dados são entregues ao JavaScript com oldParaJs(), que gera um JSON
 *      seguro para ser impresso dentro de uma tag <script>.
 *
 * Segurança:
 *   - Só os campos listados (whitelist) são guardados; nada de csrf_token,
 *     arquivos ou chaves arbitrárias enviadas por um atacante.
 *   - Valores que não são string (ex.: descricao[]=x) são ignorados.
 *   - Cada valor é limitado a FORM_FLASH_TAMANHO_MAX caracteres, evitando
 *     inflar a sessão com POSTs gigantes.
 *   - NÃO chame voltarComErro() quando o token CSRF for inválido: nesse caso a
 *     requisição pode ter vindo de outro site, e guardar os dados permitiria a
 *     um atacante pré-preencher o formulário da vítima.
 */

const FORM_FLASH_TAMANHO_MAX = 255;

/**
 * Lê um campo de texto do $_POST com trim.
 * Retorna '' quando o campo não existe ou não é string (ex.: foi enviado como
 * array), evitando o TypeError que trim() lançaria no PHP 8.
 */
function campoPost(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/**
 * Copia para a sessão apenas os campos permitidos do $_POST.
 */
function salvarOld(string $form, array $camposPermitidos): void
{
    $dados = [];

    foreach ($camposPermitidos as $campo) {
        $valor = $_POST[$campo] ?? null;

        if (is_string($valor)) {
            $dados[$campo] = mb_substr($valor, 0, FORM_FLASH_TAMANHO_MAX, 'UTF-8');
        }
    }

    $_SESSION['old'][$form] = $dados;
}

/**
 * Guarda os dados do formulário, define a mensagem de erro e redireciona.
 * Encerra a execução do script.
 */
function voltarComErro(string $mensagem, string $destino, string $form, array $camposPermitidos): void
{
    salvarOld($form, $camposPermitidos);
    $_SESSION['erro'] = $mensagem;
    header('Location: ' . $destino);
    exit;
}

/**
 * Devolve os dados guardados para o formulário e os remove da sessão.
 */
function pegarOld(string $form): array
{
    $old = $_SESSION['old'][$form] ?? [];
    unset($_SESSION['old'][$form]);

    return is_array($old) ? $old : [];
}

/**
 * Descarta dados guardados de um formulário (ex.: após salvar com sucesso).
 */
function limparOld(string $form): void
{
    unset($_SESSION['old'][$form]);
}

/**
 * Converte os dados em um literal JSON seguro para uso dentro de <script>.
 * As flags JSON_HEX_* transformam < > & ' " em \u00XX, impedindo que um valor
 * como "</script><script>..." feche a tag e injete código.
 */
function oldParaJs(array $old): string
{
    $json = json_encode(
        (object) $old,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
    );

    return $json === false ? '{}' : $json;
}
