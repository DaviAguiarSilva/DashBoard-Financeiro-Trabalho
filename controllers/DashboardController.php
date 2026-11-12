<?php

class DashboardController extends Controller
{
    private Lancamento $lancamentos;
    private Meta $metas;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->lancamentos = new Lancamento($pdo);
        $this->metas = new Meta($pdo);
    }

    public function index(): void
    {
        // Identifica o usuário logado
        $usuarioId = $this->usuarioId();

        $mes = (int) date('n');
        $ano = (int) date('Y');

        // Período do mês atual
        $inicio = date('Y-m-01');
        $fim = date('Y-m-t');

        // Calcula receitas, despesas e saldo
        $totais = $this->lancamentos->totais(
            $usuarioId,
            $inicio,
            $fim
        );

        // Dados dos últimos seis meses
        $resumoMensal = $this->lancamentos->resumoMensal(
            $usuarioId,
            6
        );

        // Despesas separadas por categoria
        $despesasPorCategoria = $this->lancamentos->despesasPorCategoria(
            $usuarioId,
            $mes,
            $ano
        );

        // Metas de gasto do mês, com quanto já foi gasto em cada uma
        $metas = $this->metas->progresso($usuarioId, $mes, $ano);

        // Últimos cinco lançamentos
        $lancamentosRecentes = array_slice(
            $this->lancamentos->listar($usuarioId),
            0,
            5
        );

        // Dados no formato que o Chart.js usa (listas de rótulos e de valores)
        $graficoPizza = [
            'labels'  => array_column($despesasPorCategoria, 'categoria'),
            'valores' => array_map('floatval', array_column($despesasPorCategoria, 'total')),
        ];

        $graficoBarras = [
            'labels'   => array_map('mesCurto', array_column($resumoMensal, 'mes')),
            'receitas' => array_map('floatval', array_column($resumoMensal, 'receitas')),
            'despesas' => array_map('floatval', array_column($resumoMensal, 'despesas')),
        ];

        // Envia os dados para a tela
        $this->render('dashboard/index', [
            'totais' => $totais,
            'resumoMensal' => $resumoMensal,
            'despesasPorCategoria' => $despesasPorCategoria,
            'lancamentosRecentes' => $lancamentosRecentes,
            'metas' => $metas,
            'graficoPizza' => $graficoPizza,
            'graficoBarras' => $graficoBarras,
            'mesAtual' => nomeMes($mes) . '/' . $ano
        ]);
    }
}
