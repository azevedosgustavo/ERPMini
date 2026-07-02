<?php

class LedgerCategoryController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Lista todas as categorias de lançamento ativas
     * GET /api/ledger-categories
     */
    public function index()
    {
        try {
            $this->requireAuth();
            $categories = $this->db->fetchAll(
                'SELECT RecId, CategoryCode, Name, Description, CategoryType, IsActive
                 FROM LedgerCategoryTable
                 WHERE IsActive = "1"
                 ORDER BY Name'
            );

            $this->success($categories);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    /**
     * Retorna categorias para dropdown/lookup
     * GET /api/ledger-categories/lookup
     */
    public function lookup()
    {
        try {
            $this->requireAuth();
            $categoryType = isset($_GET['CategoryType']) ? $_GET['CategoryType'] : null;

            $conditions = ['IsActive = "1"'];
            $params = [];

            if ($categoryType) {
                $conditions[] = 'CategoryType = ?';
                $params[] = strtoupper(substr($categoryType, 0, 1));
            }

            $categories = $this->db->fetchAll(
                'SELECT RecId as id, CategoryCode as code, Name as name, CategoryType as type
                 FROM LedgerCategoryTable
                 WHERE ' . implode(' AND ', $conditions) . '
                 ORDER BY Name',
                $params
            );

            $this->success($categories);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    /**
     * Retorna uma categoria específica
     * GET /api/ledger-categories/{id}
     */
    public function show($id)
    {
        try {
            $this->requireAuth();
            $category = $this->db->fetchOne(
                'SELECT RecId, CategoryCode, Name, Description, CategoryType, IsActive
                 FROM LedgerCategoryTable
                 WHERE RecId = ?',
                [(int) $id]
            );

            if (!$category) {
                $this->failure('Categoria nao encontrada.', 404);
                return;
            }

            $this->success($category);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    /**
     * Cria uma nova categoria
     * POST /api/ledger-categories
     */
    public function store()
    {
        try {
            $this->requireAuth();
            $data = $this->getJsonInput();
            if (empty($data['CategoryCode']) || empty($data['Name'])) {
                $this->failure('CategoryCode e Name sao obrigatorios.', 400);
                return;
            }

            $now = date('Y-m-d H:i:s');
            $this->db->execute(
                'INSERT INTO LedgerCategoryTable (CategoryCode, Name, Description, CategoryType, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                 VALUES (?, ?, ?, ?, "1", ?, ?, ?)',
                [
                    strtoupper($data['CategoryCode']),
                    $data['Name'],
                    isset($data['Description']) ? $data['Description'] : '',
                    strtoupper(isset($data['CategoryType']) ? $data['CategoryType'] : 'E'),
                    $now,
                    $now,
                    $this->currentUserId()
                ]
            );

            $this->success([], 'Categoria criada com sucesso.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    /**
     * Atualiza uma categoria existente
     * PUT /api/ledger-categories/{id}
     */
    public function update($id)
    {
        try {
            $this->requireAuth();
            $data = $this->getJsonInput();
            $now = date('Y-m-d H:i:s');
            $this->db->execute(
                'UPDATE LedgerCategoryTable 
                 SET Name = ?, Description = ?, CategoryType = ?, ModifiedDateTime = ?
                 WHERE RecId = ?',
                [
                    $data['Name'],
                    isset($data['Description']) ? $data['Description'] : '',
                    strtoupper(isset($data['CategoryType']) ? $data['CategoryType'] : 'E'),
                    $now,
                    (int) $id
                ]
            );

            $this->success([], 'Categoria atualizada com sucesso.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    /**
     * Desativa uma categoria (soft delete)
     * DELETE /api/ledger-categories/{id}
     */
    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->db->execute(
                'UPDATE LedgerCategoryTable SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
                [date('Y-m-d H:i:s'), (int) $id]
            );

            $this->success([], 'Categoria desativada com sucesso.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}
