<?php

class LancamentoController extends Controller
{
    private Lancamento $lancamentos;
    private Categoria $categorias;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);

        $this->lancamentos = new Lancamento($pdo);
        $this->categorias  = new Categoria($pdo);
    }

    /** Exibe o formulário de nova receita ou despesa. */
    public function novo(): void
    {
        $usuarioId = $this->usuarioId();
        $tipo      = $this->tipoDaRequisicao($this->get('tipo'));

        $this->render('lancamentos/novo', [
            'tipo'       => $tipo,
            'categorias' => $this->categorias->listarPorUsuario($usuarioId, $tipo),
        ]);
    }

    /** Recebe o POST do formulário e grava o lançamento. */
    public function salvar(): void
    {
        $usuarioId = $this->usuarioId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('dashboard');
        }

        $this->exigirCsrf();

        $tipo = $this->tipoDaRequisicao($this->post('tipo'));

        try {
            $this->lancamentos->criar(
                $usuarioId,
                (int) $this->post('categoria_id'),
                $this->post('descricao'),
                $this->post('valor'),
                $this->post('data'),
                $tipo
            );
        } catch (Throwable $e) {
            $this->flash('erro', $this->mensagemDeErro($e));
            $this->redirect('lancamento_novo', ['tipo' => $tipo]);
        }

        $this->flash('sucesso', $tipo === 'receita'
            ? 'Receita cadastrada com sucesso.'
            : 'Despesa cadastrada com sucesso.');
        $this->redirect('dashboard');
    }

    /** Garante que o tipo seja apenas "receita" ou "despesa". */
    private function tipoDaRequisicao(string $tipo): string
    {
        return $tipo === 'receita' ? 'receita' : 'despesa';
    }
}