
<?php

class DashboardController extends Controller
{
    private Lancamento $lancamentos;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->lancamentos = new Lancamento($pdo);
    }

    public function index(): void
    {
        // Identifica o usuário logado
        $usuarioId = $this->usuarioId();

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
            (int) date('n'),
            (int) date('Y')
        );

        // Últimos cinco lançamentos
        $lancamentosRecentes = array_slice(
            $this->lancamentos->listar($usuarioId),
            0,
            5
        );

        // Envia os dados para a tela
        $this->render('dashboard/index', [
            'totais' => $totais,
            'resumoMensal' => $resumoMensal,
            'despesasPorCategoria' => $despesasPorCategoria,
            'lancamentosRecentes' => $lancamentosRecentes,
            'mesAtual' => nomeMes((int) date('n')) . '/' . date('Y')
        ]);
    }
}
