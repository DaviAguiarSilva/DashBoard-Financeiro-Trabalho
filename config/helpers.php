<?php
/**
 * config/helpers.php
 * Funções auxiliares usadas pelos controllers e pelas views.
 */

/** Escapa texto para exibir com segurança em HTML (evita XSS). Use em TODA saída de dados. */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Monta o link de uma rota. Ex.: url('lancamentos', ['mes' => '2026-11']) */
function url(string $rota, array $params = []): string
{
    return 'index.php?' . http_build_query(['rota' => $rota] + $params);
}

/** Redireciona para uma rota e encerra a execução. */
function redirecionar(string $rota, array $params = []): void
{
    header('Location: ' . url($rota, $params));
    exit;
}

/** 1234.5 → "R$ 1.234,50" */
function dinheiro($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/** "2026-11-05" → "05/11/2026" */
function dataBr(?string $data): string
{
    if (!$data) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', substr($data, 0, 10));
    return $dt ? $dt->format('d/m/Y') : '';
}

/** 11 → "Novembro" */
function nomeMes(int $mes): string
{
    $nomes = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];
    return $nomes[$mes] ?? '';
}

/** "2026-11" → "Novembro/2026" */
function mesAno(string $ym): string
{
    if (!preg_match('/^(\d{4})-(\d{2})/', $ym, $m)) {
        return '';
    }
    return nomeMes((int) $m[2]) . '/' . $m[1];
}

/** "2026-11" → "Nov/2026" (usado nos rótulos dos gráficos) */
function mesCurto(string $ym): string
{
    if (!preg_match('/^(\d{4})-(\d{2})/', $ym, $m)) {
        return '';
    }
    return substr(nomeMes((int) $m[2]), 0, 3) . '/' . $m[1];
}
