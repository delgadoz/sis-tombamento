<?php
session_start();
require_once 'conexao.php';
require_once 'log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-bem-movel');
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: login');
    exit;
}

$token_recebido = $_POST['csrf_token']    ?? '';
$token_sessao   = $_SESSION['csrf_token'] ?? '';

if (empty($token_recebido) || !hash_equals($token_sessao, $token_recebido)) {
    $_SESSION['erro'] = 'Requisição inválida. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}

unset($_SESSION['csrf_token']);

// ===== COLETA E SANITIZAÇÃO DOS CAMPOS =====
$numero_tombamento = trim($_POST['numero_tombamento'] ?? '');
$descricao         = trim($_POST['descricao']         ?? '');
$marca             = trim($_POST['marca']             ?? '');
$numero_empenho    = trim($_POST['numero_empenho']    ?? '');
$data_aquisicao    = trim($_POST['data_aquisicao']    ?? '');
$numero_nota       = trim($_POST['numero_nota']       ?? '');
$setor_origem      = trim($_POST['setor_origem']      ?? '');
$setor             = trim($_POST['setor']             ?? '');
$subsetor          = trim($_POST['subsetor']          ?? '');
$unidade           = trim($_POST['unidade']           ?? '');
$grupo_id          = trim($_POST['grupo_id']          ?? '');
$estado            = trim($_POST['estado']            ?? '');
$tipo_id           = trim($_POST['tipo_id']            ?? '');
$valor             = trim($_POST['valor']             ?? '');
$cnpj              = $_SESSION['cnpj'];
$tombamento_massa  = isset($_POST['tombamento_em_massa']) && $_POST['tombamento_em_massa'] === '1';
$quantidade_massa  = (int) trim($_POST['quantidade_massa'] ?? 1);

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

if (empty($numero_tombamento))         $erros[] = 'O Nº de Tombamento é obrigatório.';
if (empty($descricao))                 $erros[] = 'A Descrição é obrigatória.';
if (empty($numero_empenho))            $erros[] = 'O Nº do Empenho é obrigatório.';
if (empty($data_aquisicao))            $erros[] = 'A Data de Aquisição é obrigatória.';
if (empty($numero_nota))               $erros[] = 'O Nº da Nota é obrigatório.';
if (empty($setor_origem))              $erros[] = 'O Setor de Origem é obrigatório.';
if (empty($setor))                     $erros[] = 'O Setor é obrigatório.';
if (empty($subsetor))                  $erros[] = 'O Subsetor é obrigatório.';
if ($grupo_id === '')                  $erros[] = 'O Grupo é obrigatório.';
if (empty($estado))                    $erros[] = 'O Estado é obrigatório.';
if ($tipo_id === '')                   $erros[] = 'O Tipo é obrigatório.';
if ($valor === '' || $valor === false)  $erros[] = 'O Custo é obrigatório.';

if (!empty($erros)) {
    $_SESSION['erro'] = implode(' ', $erros);
    header('Location: cadastrar-bem-movel');
    exit;
}

// ===== VALIDAÇÕES DE FORMATO =====

// Data válida
$dataObj = DateTime::createFromFormat('Y-m-d', $data_aquisicao);
if (!$dataObj || $dataObj->format('Y-m-d') !== $data_aquisicao) {
    $_SESSION['erro'] = 'Data de Aquisição inválida.';
    header('Location: cadastrar-bem-movel');
    exit;
}

$hoje = new DateTime('today');
if($dataObj > $hoje){
	$_SESSION['erro'] = 'A data de aquisição não pode ser maior que a data atual.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// Valor numérico positivo
if (!is_numeric($valor)) {
    $_SESSION['erro'] = 'O campo Custo deve receber um valor numérico.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// Nº tombamento
if (!is_numeric($numero_tombamento) || (int)$numero_tombamento < 0) {
    $_SESSION['erro'] = 'O numero de tombamento não pode ser negativo.';
    header('Location: cadastrar-bem-movel');
    exit;
}

if (!ctype_digit($grupo_id)) {
    $_SESSION['erro'] = 'Grupo inválido.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// Whitelist: estado
if (!in_array($estado, $estados_permitidos, true)) {
    $_SESSION['erro'] = 'Estado inválido.';
    header('Location: cadastrar-bem-movel');
    exit;
}

if (!ctype_digit($tipo_id)) {
    $_SESSION['erro'] = 'Tipo inválido.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// ===== VALIDAÇÃO DO TOMBAMENTO EM MASSA =====
if ($tombamento_massa) {
    // Rejeita qualquer valor fora do intervalo permitido
    if ($quantidade_massa < 2 || $quantidade_massa > 25) {
        $_SESSION['erro'] = 'A quantidade para tombamento em massa deve ser entre 2 e 25.';
        header('Location: cadastrar-bem-movel');
        exit;
    }

    if (!ctype_digit((string) $numero_tombamento) || (int) $numero_tombamento < 1) {
        $_SESSION['erro'] = 'O Nº de Tombamento deve ser um número inteiro positivo para tombamento em massa.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
}

// ===== VERIFICA SE O GRUPO EXISTE NO BANCO =====
try {
    $stmtG = $pdo->prepare("SELECT id FROM grupos WHERE id = :id LIMIT 1");
    $stmtG->bindParam(':id', $grupo_id, PDO::PARAM_INT);
    $stmtG->execute();
    if (!$stmtG->fetch()) {
        $_SESSION['erro'] = 'O grupo selecionado é inválido.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// ===== VERIFICA SE O TIPO EXISTE NO BANCO =====
try {
    $stmtTp = $pdo->prepare("SELECT id FROM tipos WHERE id = :id LIMIT 1");
    $stmtTp->bindParam(':id', $tipo_id, PDO::PARAM_INT);
    $stmtTp->execute();
    if (!$stmtTp->fetch()) {
        $_SESSION['erro'] = 'O tipo selecionado é inválido.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// ===== VERIFICA SE O SETOR DE ORIGEM EXISTE NO BANCO =====
if ($setor_origem === '' || !ctype_digit($setor_origem)) {
    $_SESSION['erro'] = 'O setor de origem selecionado é inválido.';
    header('Location: cadastrar-bem-movel');
    exit;
}

try {
    $stmtSO = $pdo->prepare("SELECT id FROM setores WHERE id = :v LIMIT 1");
    $stmtSO->bindParam(':v', $setor_origem, PDO::PARAM_INT);
    $stmtSO->execute();
    if (!$stmtSO->fetch()) {
        $_SESSION['erro'] = 'O setor de origem selecionado é inválido.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// ===== VERIFICA SE O SETOR EXISTE NO BANCO =====
try {
    $stmtS = $pdo->prepare("SELECT id FROM setores WHERE descricao = :v LIMIT 1");
    $stmtS->bindParam(':v', $setor, PDO::PARAM_STR);
    $stmtS->execute();
    if (!$stmtS->fetch()) {
        $_SESSION['erro'] = 'O setor selecionado é inválido.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// ===== VERIFICA SE O SUBSETOR PERTENCE AO SETOR =====
try {
    $stmtSS = $pdo->prepare("SELECT id FROM subsetores WHERE descricao = :sub AND setor = :set LIMIT 1");
    $stmtSS->bindParam(':sub', $subsetor, PDO::PARAM_STR);
    $stmtSS->bindParam(':set', $setor,    PDO::PARAM_STR);
    $stmtSS->execute();
    if (!$stmtSS->fetch()) {
        $_SESSION['erro'] = 'O subsetor selecionado é inválido.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}

// ===== VERIFICA SE A UNIDADE PERTENCE AO SETOR E SUBSETOR (se informada) =====
if (!empty($unidade)) {
    try {
        $stmtU = $pdo->prepare("SELECT id FROM unidades WHERE descricao = :u AND setor = :s AND subsetor = :ss LIMIT 1");
        $stmtU->bindParam(':u',  $unidade,  PDO::PARAM_STR);
        $stmtU->bindParam(':s',  $setor,    PDO::PARAM_STR);
        $stmtU->bindParam(':ss', $subsetor, PDO::PARAM_STR);
        $stmtU->execute();
        if (!$stmtU->fetch()) {
            $_SESSION['erro'] = 'A unidade selecionada é inválida.';
            header('Location: cadastrar-bem-movel');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro interno. Tente novamente.';
        header('Location: cadastrar-bem-movel');
        exit;
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

        if (!empty($conflitos)) {
            $lista = implode(', ', array_map('htmlspecialchars', $conflitos));
            $_SESSION['erro'] = "Os seguintes números de tombamento já estão cadastrados: {$lista}.";
            header('Location: cadastrar-bem-movel');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro interno. Tente novamente.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
} else {
    try {
        $stmtT = $pdo->prepare("SELECT id FROM bens_moveis WHERE numero_tombamento = :t AND cnpj = :c LIMIT 1");
        $stmtT->bindParam(':t', $numero_tombamento, PDO::PARAM_STR);
        $stmtT->bindParam(':c', $cnpj,              PDO::PARAM_STR);
        $stmtT->execute();
        if ($stmtT->fetch()) {
            $_SESSION['erro'] = 'Este número de tombamento já está cadastrado.';
            header('Location: cadastrar-bem-movel');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro interno. Tente novamente.';
        header('Location: cadastrar-bem-movel');
        exit;
    }
}

// ===== UPLOAD DAS IMAGENS =====
$urlsImagens   = [];
$pastaUpload   = '../uploads/bens/';
$tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
$tamanhoMaximo   = 5 * 1024 * 1024; // 5MB

if (!is_dir($pastaUpload)) {
    mkdir($pastaUpload, 0755, true);
}

$arquivos = $_FILES['imagens'] ?? [];

// Normaliza a estrutura do $_FILES para array de arquivos
$listaArquivos = [];
if (!empty($arquivos['name'][0])) {
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
        $_SESSION['erro'] = 'Erro no upload de uma das imagens.';
        header('Location: cadastrar-bem-movel');
        exit;
    }

    if ($arquivo['size'] > $tamanhoMaximo) {
        $_SESSION['erro'] = 'Cada imagem deve ter no máximo 5MB.';
        header('Location: cadastrar-bem-movel');
        exit;
    }

    // Valida o tipo real do arquivo (não confia no mime informado pelo cliente)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeReal, $tiposPermitidos, true)) {
        $_SESSION['erro'] = 'Apenas imagens JPG, PNG ou WEBP são permitidas.';
        header('Location: cadastrar-bem-movel');
        exit;
    }

    $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extensao  = $extensoes[$mimeReal];
    $nomeArquivo = uniqid('bem_', true) . '.' . $extensao;
    $destino     = $pastaUpload . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        $_SESSION['erro'] = 'Falha ao salvar a imagem. Tente novamente.';
        header('Location: cadastrar-bem-movel');
        exit;
    }

    $urlsImagens[] = $destino;
}

if (empty($urlsImagens)) {
    $_SESSION['erro'] = 'Adicione ao menos uma imagem do bem.';
    header('Location: cadastrar-bem-movel');
    exit;
}

$imagensJson = json_encode($urlsImagens);

// ===== INSERÇÃO NO BANCO =====
try {
    $sql = "INSERT INTO bens_moveis 
                (numero_tombamento, descricao, marca, numero_empenho, data_aquisicao,
                 numero_nota, setor, subsetor, unidade, grupo_id, estado, tipo_id, valor, imagens, created_by, cnpj, setor_original)
            VALUES 
                (:numero_tombamento, :descricao, :marca, :numero_empenho, :data_aquisicao,
                 :numero_nota, :setor, :subsetor, :unidade, :grupo_id, :estado, :tipo_id, :valor, :imagens, :created_by, :cnpj,   :setor_original)";

    $stmt = $pdo->prepare($sql);

    // Determina quantos registros serão inseridos
    $total_insercoes = $tombamento_massa ? $quantidade_massa : 1;
	
	$valor = number_format((float)$valor, 2, '.', '');
	
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

    if ($tombamento_massa) {
        $numFim = $numBase + $quantidade_massa - 1;
        $_SESSION['sucesso'] = "{$quantidade_massa} bens móveis cadastrados com sucesso! (Tombamentos {$numBase} a {$numFim})";
    } else {
        $_SESSION['sucesso'] = 'Bem móvel cadastrado com sucesso!';
    }

    header('Location: cadastrar-bem-movel');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Remove imagens já salvas em caso de falha no banco
    foreach ($urlsImagens as $url) {
        if (file_exists($url)) unlink($url);
    }
    $_SESSION['erro'] = 'Erro interno. Tente novamente.';
    header('Location: cadastrar-bem-movel');
    exit;
}