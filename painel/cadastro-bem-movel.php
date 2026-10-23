<?php
session_start();
require_once 'conexao.php';
require_once 'log.php';
require_once 'includes/form-flash.php';

// Campos do formulário que podem ser devolvidos ao usuário em caso de erro.
// (csrf_token e imagens ficam de fora de propósito.)
const CAMPOS_FORM_CADASTRO = [
    'numero_tombamento', 'tombamento_em_massa', 'quantidade_massa',
    'descricao', 'marca', 'numero_empenho', 'data_aquisicao', 'numero_nota',
    'setor_origem', 'setor', 'subsetor', 'unidade', 'grupo_id',
    'estado', 'tipo_id', 'valor',
];

/**
 * Volta para o formulário de cadastro com a mensagem de erro, preservando
 * os dados digitados. Encerra a execução.
 */
function voltarParaCadastro(string $mensagem): void
{
    voltarComErro($mensagem, 'cadastrar-bem-movel', 'cadastro_bem', CAMPOS_FORM_CADASTRO);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-bem-movel');
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

$token_recebido = campoPost('csrf_token');
$token_sessao   = $_SESSION['csrf_token'] ?? '';

if (empty($token_recebido) || !is_string($token_sessao) || !hash_equals($token_sessao, $token_recebido)) {
    // Sem preservar os dados: a requisição pode ter sido forjada por outro site.
    $_SESSION['erro'] = 'Requisição inválida. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}

unset($_SESSION['csrf_token']);

// ===== COLETA E SANITIZAÇÃO DOS CAMPOS =====
$numero_tombamento = campoPost('numero_tombamento');
$descricao         = campoPost('descricao');
$marca             = campoPost('marca');
$numero_empenho    = campoPost('numero_empenho');
$data_aquisicao    = campoPost('data_aquisicao');
$numero_nota       = campoPost('numero_nota');
$setor_origem      = campoPost('setor_origem');
$setor             = campoPost('setor');
$subsetor          = campoPost('subsetor');
$unidade           = campoPost('unidade');
$grupo_id          = campoPost('grupo_id');
$estado            = campoPost('estado');
$tipo_id           = campoPost('tipo_id');
$valor             = campoPost('valor');
$cnpj              = $_SESSION['cnpj'];
$tombamento_massa  = campoPost('tombamento_em_massa') === '1';
$quantidade_massa  = (int) (campoPost('quantidade_massa') ?: 1);

// ===== ID DO USUÁRIO LOGADO (created_by é FK para usuarios.id) =====
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['erro'] = 'Sessão inválida. Faça login novamente.';
    header('Location: login');
    exit;
}
$created_by = $_SESSION['usuario_id'];

// ===== VALORES PERMITIDOS (WHITELIST) =====
$estados_permitidos = ['Novo', 'Bom', 'Regular', 'Ruim', 'Depreciado', 'Inservivel'];

// ===== VALIDAÇÕES OBRIGATÓRIAS =====
$erros = [];

if ($numero_tombamento === '')  $erros[] = 'O Nº de Tombamento é obrigatório.';
if ($descricao === '')          $erros[] = 'A Descrição é obrigatória.';
if ($numero_empenho === '')     $erros[] = 'O Nº do Empenho é obrigatório.';
if ($data_aquisicao === '')     $erros[] = 'A Data de Aquisição é obrigatória.';
if ($numero_nota === '')        $erros[] = 'O Nº da Nota é obrigatório.';
if ($setor_origem === '')       $erros[] = 'O Setor de Origem é obrigatório.';
if ($setor === '')              $erros[] = 'O Setor é obrigatório.';
if ($subsetor === '')           $erros[] = 'O Subsetor é obrigatório.';
if ($grupo_id === '')           $erros[] = 'O Grupo é obrigatório.';
if ($estado === '')             $erros[] = 'O Estado é obrigatório.';
if ($tipo_id === '')            $erros[] = 'O Tipo é obrigatório.';
if ($valor === '')              $erros[] = 'O Custo é obrigatório.';

if (!empty($erros)) {
    voltarParaCadastro(implode(' ', $erros));
}

// ===== VALIDAÇÕES DE TAMANHO (em caracteres, não em bytes) =====
if (mb_strlen($descricao, 'UTF-8') > 255) {
    voltarParaCadastro('A descrição não pode ultrapassar 255 caracteres.');
}
if (mb_strlen($marca, 'UTF-8') > 100) {
    voltarParaCadastro('A marca não pode ultrapassar 100 caracteres.');
}
if (mb_strlen($numero_empenho, 'UTF-8') > 100) {
    voltarParaCadastro('O Nº do Empenho não pode ultrapassar 100 caracteres.');
}
if (mb_strlen($numero_nota, 'UTF-8') > 100) {
    voltarParaCadastro('O Nº da Nota não pode ultrapassar 100 caracteres.');
}

// ===== VALIDAÇÕES DE FORMATO =====

// Data válida
$dataObj = DateTime::createFromFormat('Y-m-d', $data_aquisicao);
if (!$dataObj || $dataObj->format('Y-m-d') !== $data_aquisicao) {
    voltarParaCadastro('Data de Aquisição inválida.');
}

$hoje = new DateTime('today');
if ($dataObj > $hoje) {
    voltarParaCadastro('A data de aquisição não pode ser maior que a data atual.');
}

// Valor numérico não negativo (zero é permitido: ex. bens recebidos em doação)
if (!is_numeric($valor) || (float) $valor < 0) {
    voltarParaCadastro('O campo Custo deve ser um valor numérico igual ou maior que zero.');
}

// Nº tombamento: apenas dígitos (is_numeric aceitaria "1.5", "1e3", "-0"...)
if (!ctype_digit($numero_tombamento)) {
    voltarParaCadastro('O Nº de Tombamento deve conter apenas números.');
}

// Normaliza zeros à esquerda ("007" -> "7"). O INSERT já gravava o número
// normalizado, mas a checagem de duplicidade usava o texto cru, então "007"
// passava pela checagem mesmo com o "7" já cadastrado.
$numero_tombamento = ltrim($numero_tombamento, '0');
if ($numero_tombamento === '') {
    $numero_tombamento = '0';
}

// Evita estouro de inteiro ao converter com (int) mais abaixo
if (strlen($numero_tombamento) > 15) {
    voltarParaCadastro('O Nº de Tombamento é grande demais.');
}

if (!ctype_digit($grupo_id)) {
    voltarParaCadastro('Grupo inválido.');
}

// Whitelist: estado
if (!in_array($estado, $estados_permitidos, true)) {
    voltarParaCadastro('Estado inválido.');
}

if (!ctype_digit($tipo_id)) {
    voltarParaCadastro('Tipo inválido.');
}

// ===== VALIDAÇÃO DO TOMBAMENTO EM MASSA =====
if ($tombamento_massa) {
    // Rejeita qualquer valor fora do intervalo permitido
    if ($quantidade_massa < 2 || $quantidade_massa > 25) {
        voltarParaCadastro('A quantidade para tombamento em massa deve ser entre 2 e 25.');
    }

    if ((int) $numero_tombamento < 1) {
        voltarParaCadastro('O Nº de Tombamento deve ser um número inteiro positivo para tombamento em massa.');
    }
}

// ===== VERIFICA SE O GRUPO EXISTE NO BANCO =====
try {
    $stmtG = $pdo->prepare("SELECT id FROM grupos WHERE id = :id LIMIT 1");
    $stmtG->bindParam(':id', $grupo_id, PDO::PARAM_INT);
    $stmtG->execute();
    if (!$stmtG->fetch()) {
        voltarParaCadastro('O grupo selecionado é inválido.');
    }
} catch (PDOException $e) {
    voltarParaCadastro('Erro interno. Tente novamente.');
}

// ===== VERIFICA SE O TIPO EXISTE NO BANCO =====
try {
    $stmtTp = $pdo->prepare("SELECT id FROM tipos WHERE id = :id LIMIT 1");
    $stmtTp->bindParam(':id', $tipo_id, PDO::PARAM_INT);
    $stmtTp->execute();
    if (!$stmtTp->fetch()) {
        voltarParaCadastro('O tipo selecionado é inválido.');
    }
} catch (PDOException $e) {
    voltarParaCadastro('Erro interno. Tente novamente.');
}

// ===== VERIFICA SE O SETOR DE ORIGEM EXISTE NO BANCO =====
if (!ctype_digit($setor_origem)) {
    voltarParaCadastro('O setor de origem selecionado é inválido.');
}

try {
    $stmtSO = $pdo->prepare("SELECT id FROM setores WHERE id = :v LIMIT 1");
    $stmtSO->bindParam(':v', $setor_origem, PDO::PARAM_INT);
    $stmtSO->execute();
    if (!$stmtSO->fetch()) {
        voltarParaCadastro('O setor de origem selecionado é inválido.');
    }
} catch (PDOException $e) {
    voltarParaCadastro('Erro interno. Tente novamente.');
}

// ===== VERIFICA SE O SETOR EXISTE NO BANCO =====
try {
    $stmtS = $pdo->prepare("SELECT id FROM setores WHERE descricao = :v LIMIT 1");
    $stmtS->bindParam(':v', $setor, PDO::PARAM_STR);
    $stmtS->execute();
    if (!$stmtS->fetch()) {
        voltarParaCadastro('O setor selecionado é inválido.');
    }
} catch (PDOException $e) {
    voltarParaCadastro('Erro interno. Tente novamente.');
}

// ===== VERIFICA SE O SUBSETOR PERTENCE AO SETOR =====
try {
    $stmtSS = $pdo->prepare("SELECT id FROM subsetores WHERE descricao = :sub AND setor = :set LIMIT 1");
    $stmtSS->bindParam(':sub', $subsetor, PDO::PARAM_STR);
    $stmtSS->bindParam(':set', $setor,    PDO::PARAM_STR);
    $stmtSS->execute();
    if (!$stmtSS->fetch()) {
        voltarParaCadastro('O subsetor selecionado é inválido.');
    }
} catch (PDOException $e) {
    voltarParaCadastro('Erro interno. Tente novamente.');
}

// ===== VERIFICA SE A UNIDADE PERTENCE AO SETOR E SUBSETOR (se informada) =====
if ($unidade !== '') {
    try {
        $stmtU = $pdo->prepare("SELECT id FROM unidades WHERE descricao = :u AND setor = :s AND subsetor = :ss LIMIT 1");
        $stmtU->bindParam(':u',  $unidade,  PDO::PARAM_STR);
        $stmtU->bindParam(':s',  $setor,    PDO::PARAM_STR);
        $stmtU->bindParam(':ss', $subsetor, PDO::PARAM_STR);
        $stmtU->execute();
        if (!$stmtU->fetch()) {
            voltarParaCadastro('A unidade selecionada é inválida.');
        }
    } catch (PDOException $e) {
        voltarParaCadastro('Erro interno. Tente novamente.');
    }
}

// ===== VERIFICA SE O(S) Nº DE TOMBAMENTO JÁ EXIST(E/EM) =====
if ($tombamento_massa) {
    $numInicio = (int) $numero_tombamento;
    $numFim    = $numInicio + $quantidade_massa - 1;

    try {
        $stmtT = $pdo->prepare(
            "SELECT numero_tombamento FROM bens_moveis
             WHERE cnpj = :c
               AND numero_tombamento BETWEEN :ini AND :fim"
        );
        $stmtT->bindParam(':c',   $cnpj,     PDO::PARAM_STR);
        $stmtT->bindParam(':ini', $numInicio, PDO::PARAM_INT);
        $stmtT->bindParam(':fim', $numFim,    PDO::PARAM_INT);
        $stmtT->execute();
        $conflitos = $stmtT->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        voltarParaCadastro('Erro interno. Tente novamente.');
    }

    if (!empty($conflitos)) {
        // Sem htmlspecialchars aqui: a mensagem já é escapada ao ser exibida.
        $lista = implode(', ', $conflitos);
        voltarParaCadastro("Os seguintes números de tombamento já estão cadastrados: {$lista}.");
    }
} else {
    try {
        $stmtT = $pdo->prepare("SELECT id FROM bens_moveis WHERE numero_tombamento = :t AND cnpj = :c LIMIT 1");
        $stmtT->bindParam(':t', $numero_tombamento, PDO::PARAM_STR);
        $stmtT->bindParam(':c', $cnpj,              PDO::PARAM_STR);
        $stmtT->execute();
        $jaExiste = (bool) $stmtT->fetch();
    } catch (PDOException $e) {
        voltarParaCadastro('Erro interno. Tente novamente.');
    }

    if ($jaExiste) {
        voltarParaCadastro('Este número de tombamento já está cadastrado.');
    }
}

// ===== UPLOAD DAS IMAGENS =====
$urlsImagens     = [];
$pastaUpload     = '../uploads/bens/';
$tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
$tamanhoMaximo   = 5 * 1024 * 1024; // 5MB

if (!is_dir($pastaUpload)) {
    mkdir($pastaUpload, 0755, true);
}

/**
 * Remove as imagens já gravadas nesta requisição (usado quando uma imagem
 * posterior falha ou o INSERT dá erro, para não deixar arquivos órfãos).
 */
function removerImagensSalvas(array $caminhos): void
{
    foreach ($caminhos as $caminho) {
        if (is_file($caminho)) {
            unlink($caminho);
        }
    }
}

$arquivos = $_FILES['imagens'] ?? [];

// Normaliza a estrutura do $_FILES para array de arquivos
$listaArquivos = [];
if (isset($arquivos['name']) && is_array($arquivos['name']) && !empty($arquivos['name'][0])) {
    for ($i = 0; $i < count($arquivos['name']); $i++) {
        $listaArquivos[] = [
            'name'     => $arquivos['name'][$i],
            'type'     => $arquivos['type'][$i],
            'tmp_name' => $arquivos['tmp_name'][$i],
            'error'    => $arquivos['error'][$i],
            'size'     => $arquivos['size'][$i],
        ];
    }
}

// Limita a 2 imagens
$listaArquivos = array_slice($listaArquivos, 0, 2);

foreach ($listaArquivos as $arquivo) {

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        removerImagensSalvas($urlsImagens);
        voltarParaCadastro('Erro no upload de uma das imagens.');
    }

    if ($arquivo['size'] > $tamanhoMaximo) {
        removerImagensSalvas($urlsImagens);
        voltarParaCadastro('Cada imagem deve ter no máximo 5MB.');
    }

    // Valida o tipo real do arquivo (não confia no mime informado pelo cliente)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeReal, $tiposPermitidos, true)) {
        removerImagensSalvas($urlsImagens);
        voltarParaCadastro('Apenas imagens JPG, PNG ou WEBP são permitidas.');
    }

    $extensoes   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extensao    = $extensoes[$mimeReal];
    $nomeArquivo = uniqid('bem_', true) . '.' . $extensao;
    $destino     = $pastaUpload . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        removerImagensSalvas($urlsImagens);
        voltarParaCadastro('Falha ao salvar a imagem. Tente novamente.');
    }

    $urlsImagens[] = $destino;
}

if (empty($urlsImagens)) {
    voltarParaCadastro('Adicione ao menos uma imagem do bem.');
}

$imagensJson = json_encode($urlsImagens);

// ===== INSERÇÃO NO BANCO =====
try {
    $sql = "INSERT INTO bens_moveis
                (numero_tombamento, descricao, marca, numero_empenho, data_aquisicao,
                 numero_nota, setor, subsetor, unidade, grupo_id, estado, tipo_id, valor, imagens, created_by, cnpj, setor_original)
            VALUES
                (:numero_tombamento, :descricao, :marca, :numero_empenho, :data_aquisicao,
                 :numero_nota, :setor, :subsetor, :unidade, :grupo_id, :estado, :tipo_id, :valor, :imagens, :created_by, :cnpj, :setor_original)";

    $stmt = $pdo->prepare($sql);

    // Determina quantos registros serão inseridos
    $total_insercoes = $tombamento_massa ? $quantidade_massa : 1;

    $valor = number_format((float) $valor, 2, '.', '');

    $numBase = (int) $numero_tombamento;

    $pdo->beginTransaction();

    for ($i = 0; $i < $total_insercoes; $i++) {
        $tombAtual = (string) ($numBase + $i);

        $stmt->bindParam(':numero_tombamento', $tombAtual,      PDO::PARAM_STR);
        $stmt->bindParam(':descricao',         $descricao,      PDO::PARAM_STR);
        $stmt->bindParam(':marca',             $marca,          PDO::PARAM_STR);
        $stmt->bindParam(':numero_empenho',    $numero_empenho, PDO::PARAM_STR);
        $stmt->bindParam(':data_aquisicao',    $data_aquisicao, PDO::PARAM_STR);
        $stmt->bindParam(':numero_nota',       $numero_nota,    PDO::PARAM_STR);
        $stmt->bindParam(':setor',             $setor,          PDO::PARAM_STR);
        $stmt->bindParam(':subsetor',          $subsetor,       PDO::PARAM_STR);
        $stmt->bindParam(':unidade',           $unidade,        PDO::PARAM_STR);
        $stmt->bindParam(':grupo_id',          $grupo_id,       PDO::PARAM_INT);
        $stmt->bindParam(':estado',            $estado,         PDO::PARAM_STR);
        $stmt->bindParam(':tipo_id',           $tipo_id,        PDO::PARAM_INT);
        $stmt->bindParam(':valor',             $valor,          PDO::PARAM_STR);
        $stmt->bindParam(':imagens',           $imagensJson,    PDO::PARAM_STR);
        $stmt->bindParam(':created_by',        $created_by,     PDO::PARAM_INT);
        $stmt->bindParam(':cnpj',              $cnpj,           PDO::PARAM_STR);
        $stmt->bindParam(':setor_original',    $setor_origem,   PDO::PARAM_INT);
        $stmt->execute();

        $bemIdInserido = (int) $pdo->lastInsertId();
        registrarAuditoria($pdo, $created_by, 'cadastro', 'bens_moveis', $bemIdInserido, null, [
            'numero_tombamento' => $tombAtual,
            'descricao'         => $descricao,
            'marca'             => $marca,
            'numero_empenho'    => $numero_empenho,
            'data_aquisicao'    => $data_aquisicao,
            'numero_nota'       => $numero_nota,
            'setor'             => $setor,
            'subsetor'          => $subsetor,
            'unidade'           => $unidade,
            'grupo_id'          => $grupo_id,
            'estado'            => $estado,
            'tipo_id'           => $tipo_id,
            'valor'             => $valor,
            'setor_original'    => $setor_origem,
        ]);
    }

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Remove imagens já salvas em caso de falha no banco
    removerImagensSalvas($urlsImagens);
    voltarParaCadastro('Erro interno. Tente novamente.');
}

limparOld('cadastro_bem');

if ($tombamento_massa) {
    $numFim = $numBase + $quantidade_massa - 1;
    $_SESSION['sucesso'] = "{$quantidade_massa} bens móveis cadastrados com sucesso! (Tombamentos {$numBase} a {$numFim})";
} else {
    $_SESSION['sucesso'] = 'Bem móvel cadastrado com sucesso!';
}

header('Location: cadastrar-bem-movel');
exit;
