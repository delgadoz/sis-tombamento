<?php

const RL_TENTATIVAS_MINIMAS = 3;      // até 3 tentativas sem bloqueio
const RL_TEMPOS_BLOQUEIO = [
    4 => 30,        // 4ª tentativa falha -> 30s
    5 => 120,       // 5ª -> 2 min
    6 => 300,       // 6ª -> 5 min
    7 => 900,       // 7ª+ -> 15 min
];
const RL_BLOQUEIO_MAXIMO = 900; 
const RL_JANELA_RESET_HORAS = 24; 


// Verifica se um identificador (IP ou e-mail) está bloqueado no momento.

function estaBloqueado(PDO $pdo, string $identificador, string $tipo): bool
{
    $sql = "SELECT bloqueado_ate FROM tentativas_login 
            WHERE identificador = :id AND tipo = :tipo 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $identificador, ':tipo' => $tipo]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro || !$registro['bloqueado_ate']) {
        return false;
    }

    return strtotime($registro['bloqueado_ate']) > time();
}


// Registra uma tentativa de login falha e aplica bloqueio progressivo

function registrarTentativaFalha(PDO $pdo, string $identificador, string $tipo): void
{
    $agora = date('Y-m-d H:i:s');

    // Busca registro existente
    $sql = "SELECT tentativas, primeira_tentativa FROM tentativas_login 
            WHERE identificador = :id AND tipo = :tipo 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $identificador, ':tipo' => $tipo]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        $tentativas = $registro['tentativas'] + 1;
    } else {
        $tentativas = 1;
    }

    $bloqueadoAte = null;
    if ($tentativas > RL_TENTATIVAS_MINIMAS) {
        $segundos = RL_TEMPOS_BLOQUEIO[$tentativas] ?? RL_BLOQUEIO_MAXIMO;
        $bloqueadoAte = date('Y-m-d H:i:s', time() + $segundos);
    }

    $sql = "INSERT INTO tentativas_login 
                (identificador, tipo, tentativas, primeira_tentativa, ultima_tentativa, bloqueado_ate)
            VALUES 
                (:id, :tipo, 1, :primeira, :ultima_ins, :bloqueado_ins)
            ON DUPLICATE KEY UPDATE
                tentativas = :tentativas,
                ultima_tentativa = :ultima_upd,
                bloqueado_ate = :bloqueado_upd";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'            => $identificador,
        ':tipo'          => $tipo,
        ':primeira'      => $agora,
        ':ultima_ins'    => $agora,
        ':bloqueado_ins' => $bloqueadoAte,
        ':tentativas'    => $tentativas,
        ':ultima_upd'    => $agora,
        ':bloqueado_upd' => $bloqueadoAte,
    ]);
}


//Limpa o histórico de tentativas após um login bem-sucedido.

function limparTentativas(PDO $pdo, string $identificador, string $tipo): void
{
    $sql = "DELETE FROM tentativas_login WHERE identificador = :id AND tipo = :tipo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $identificador, ':tipo' => $tipo]);
}

// Faz uma limpeza probabilística de registros antigos

 
function limparRegistrosAntigos(PDO $pdo): void
{
    // ~2% de chance de rodar a cada chamada
    if (random_int(1, 100) > 2) {
        return;
    }

    $limite = date('Y-m-d H:i:s', strtotime('-' . RL_JANELA_RESET_HORAS . ' hours'));
    $sql = "DELETE FROM tentativas_login WHERE ultima_tentativa < :limite";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':limite' => $limite]);
}

function obterIpCliente(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}