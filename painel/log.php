<?php
function registrarAuditoria(
    PDO $pdo,
    int $usuarioId,
    string $acao,
    string $tabelaAfetada,
    int $registroId,
    ?array $dadosAntes,
    ?array $dadosDepois
): void {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO log_auditoria
                (usuario_id, acao, tabela_afetada, registro_id, dados_antes, dados_depois, ip)
             VALUES
                (:usuario_id, :acao, :tabela_afetada, :registro_id, :dados_antes, :dados_depois, :ip)"
        );

        $antesJson  = $dadosAntes  !== null ? json_encode($dadosAntes, JSON_UNESCAPED_UNICODE)  : null;
        $depoisJson = $dadosDepois !== null ? json_encode($dadosDepois, JSON_UNESCAPED_UNICODE) : null;
        $ip = obterIpAuditoria();

        $stmt->bindParam(':usuario_id',     $usuarioId,     PDO::PARAM_INT);
        $stmt->bindParam(':acao',           $acao,          PDO::PARAM_STR);
        $stmt->bindParam(':tabela_afetada', $tabelaAfetada, PDO::PARAM_STR);
        $stmt->bindParam(':registro_id',    $registroId,    PDO::PARAM_INT);
        $stmt->bindParam(':dados_antes',    $antesJson,     PDO::PARAM_STR);
        $stmt->bindParam(':dados_depois',   $depoisJson,    PDO::PARAM_STR);
        $stmt->bindParam(':ip',             $ip,            PDO::PARAM_STR);

        $stmt->execute();
    } catch (PDOException $e) {
        error_log('Falha ao registrar log_auditoria: ' . $e->getMessage());
    }
}

// Registra uma tentativa de login falha.

function registrarLoginFalho(PDO $pdo, string $usuarioTentado, string $ip): void
{
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO log_login_falho (usuario_tentado, ip, user_agent)
             VALUES (:usuario_tentado, :ip, :user_agent)"
        );

        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $stmt->bindParam(':usuario_tentado', $usuarioTentado, PDO::PARAM_STR);
        $stmt->bindParam(':ip',              $ip,             PDO::PARAM_STR);
        $stmt->bindParam(':user_agent',      $userAgent,      PDO::PARAM_STR);

        $stmt->execute();
    } catch (PDOException $e) {
        error_log('Falha ao registrar log_login_falho: ' . $e->getMessage());
    }
}

function obterIpAuditoria(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
}
