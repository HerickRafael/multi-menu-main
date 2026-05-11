<?php
$data = '{"groups":[{"name":"Escolha at\u00e9 3 acompanhamentos","type":"pool","items":[{"name":"Kiwi","qty":1,"unit_price":2,"price":0,"free_qty":1,"paid_qty":0},{"name":"Manga","qty":1,"unit_price":2,"price":0,"free_qty":1,"paid_qty":0},{"name":"Leite em P\u00f3","qty":1,"unit_price":2,"price":0,"free_qty":1,"paid_qty":0}]}],"total_delta":0,"has_customization":true}';
$customData = json_decode($data, true);
$customGroups = is_array($customData) && isset($customData['groups']) ? $customData['groups'] : $customData;
if ($customGroups && is_array($customGroups)) {
    foreach ($customGroups as $groupData) {
        $groupType = $groupData['type'] ?? 'extra';
        $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice', 'pool']);
        if (!empty($groupData['items'])) {
            foreach ($groupData['items'] as $customItem) {
                $customQty = isset($customItem['qty']) ? (int)$customItem['qty'] : null;
                $isSelected = !empty($customItem['selected']) || ($customQty !== null && $customQty > 0);
                if ($isChoiceGroup && $isSelected) {
                    echo "SHOW CHOICE: " . $customItem['name'] . "\n";
                }
            }
        }
    }
}
