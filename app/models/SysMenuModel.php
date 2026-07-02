<?php

class SysMenuModel extends BaseModel
{
    public function getMenuTree($languageId, $roleCode = null)
    {
        // Se não informar roleCode, retorna todos os menus (backwards compatibility)
        $groups = $this->db->fetchAll(
            'SELECT g.RecId, g.GroupCode, g.LabelKey,
                    COALESCE(l.TextValue, g.LabelKey) AS LabelText,
                    g.SequenceNo
             FROM SysMenuGroup g
             LEFT JOIN SysLabelText l ON l.LabelKey = g.LabelKey AND l.LanguageId = ? AND l.IsActive = "1"
             WHERE g.IsActive = "1"
             ORDER BY g.SequenceNo ASC',
            [$languageId]
        );

        // Se roleCode foi informado, filtrar por permissões
        if ($roleCode !== null) {
            $visibleGroupIds = $this->db->fetchAll(
                'SELECT rmp.MenuGroupId
                 FROM RoleMenuPermission rmp
                 INNER JOIN SecurityRole r ON r.RecId = rmp.RoleId
                 WHERE r.RoleCode = ? AND rmp.IsVisible = "1" AND rmp.IsActive = "1"',
                [$roleCode]
            );

            $visibleIds = array_map(fn($row) => (int) $row['MenuGroupId'], $visibleGroupIds);

            $groups = array_filter($groups, fn($group) => in_array((int) $group['RecId'], $visibleIds));
        }

        $items = $this->db->fetchAll(
            'SELECT i.RecId, i.GroupId, i.ParentMenuId, i.MenuCode, i.LabelKey, i.ViewKey,
                    COALESCE(l.TextValue, i.LabelKey) AS LabelText,
                    i.SequenceNo
             FROM SysMenuItem i
             LEFT JOIN SysLabelText l ON l.LabelKey = i.LabelKey AND l.LanguageId = ? AND l.IsActive = "1"
             WHERE i.IsActive = "1"
             ORDER BY i.GroupId ASC, i.ParentMenuId ASC, i.SequenceNo ASC',
            [$languageId]
        );

        $itemsByGroup = [];

        foreach ($items as $item) {
            $itemsByGroup[$item['GroupId']][] = $item;
        }

        $menu = [];

        foreach ($groups as $group) {
            $groupItems = isset($itemsByGroup[$group['RecId']]) ? $itemsByGroup[$group['RecId']] : [];
            $menu[] = [
                'GroupCode' => $group['GroupCode'],
                'LabelKey' => $group['LabelKey'],
                'LabelText' => $group['LabelText'],
                'Items' => $this->buildTree($groupItems, 0)
            ];
        }

        return $menu;
    }

    private function buildTree($items, $parentId)
    {
        $result = [];

        foreach ($items as $item) {
            if ((int) $item['ParentMenuId'] !== (int) $parentId) {
                continue;
            }

            $result[] = [
                'RecId' => (int) $item['RecId'],
                'MenuCode' => $item['MenuCode'],
                'LabelKey' => $item['LabelKey'],
                'LabelText' => $item['LabelText'],
                'ViewKey' => $item['ViewKey'],
                'Children' => $this->buildTree($items, $item['RecId'])
            ];
        }

        return $result;
    }
}