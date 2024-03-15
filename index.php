<?php
class EstoqueManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function atualizarEstoqueFromJSON($filePath) {
        $produtosJson = file_get_contents($filePath);

        if ($produtosJson === false) {
            throw new Exception("Erro ao ler arquivo JSON.");
        }

        $this->atualizarEstoque($produtosJson);
    }

    public function atualizarEstoque($produtosJson) {
        $produtos = json_decode($produtosJson, true);

        if (!$produtos) {
            throw new Exception("Erro ao decodificar JSON.");
        }

        $this->pdo->beginTransaction();

        try {
            foreach ($produtos as $produto) {
                $produtoExistente = $this->buscarProdutoExistente($produto);
                
                if ($produtoExistente) {
                    $this->atualizarProduto($produtoExistente, $produto);
                } else {
                    $this->inserirProduto($produto);
                }
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    private function buscarProdutoExistente($produto) {
        $sql = "SELECT id, quantidade FROM estoque 
                    WHERE produto = :produto AND cor = :cor 
                    AND tamanho = :tamanho AND deposito = :deposito 
                    AND data_disponibilidade = :data_disponibilidade";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':produto' => $produto['produto'],
            ':cor' => $produto['cor'],
            ':tamanho' => $produto['tamanho'],
            ':deposito' => $produto['deposito'],
            ':data_disponibilidade' => $produto['data_disponibilidade']
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function atualizarProduto($produtoExistente, $novoProduto) {
        $quantidadeTotal = $produtoExistente['quantidade'] + $novoProduto['quantidade'];

        $sql = "UPDATE estoque SET quantidade = :quantidade 
                    WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $produtoExistente['id'],
            ':quantidade' => $quantidadeTotal
        ]);
    }

    private function inserirProduto($produto) {
        $sql = "INSERT INTO estoque (produto, cor, tamanho, deposito, data_disponibilidade, quantidade) 
                    VALUES (:produto, :cor, :tamanho, :deposito, :data_disponibilidade, :quantidade)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':produto' => $produto['produto'],
            ':cor' => $produto['cor'],
            ':tamanho' => $produto['tamanho'],
            ':deposito' => $produto['deposito'],
            ':data_disponibilidade' => $produto['data_disponibilidade'],
            ':quantidade' => $produto['quantidade']
        ]);
    }
}

$dbHost = 'localhost';
$dbName = 'Teste';
$dbUser = 'admin';
$dbPass = 'Loki0252';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

    $estoqueManager = new EstoqueManager($pdo);

    $estoqueManager->atualizarEstoqueFromJSON('produtos.JSON');

    echo "Estoque atualizado com sucesso!";
} catch (Exception $e) {
    echo "Erro ao atualizar estoque: " . $e->getMessage();
}
?>