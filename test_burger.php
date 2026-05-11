<?php
$data = '{"groups":[{"name":"Personlize seu Burger","type":"qty","items":[{"name":"Cebola Caramelizada","qty":1,"unit_price":2,"price":0,"default_qty":1,"delta_qty":0,"removed":false},{"name":"Bacon fatiado","qty":3,"unit_price":5,"price":0,"default_qty":3,"delta_qty":0,"removed":false},{"name":"Burger 90g","qty":1,"unit_price":5,"price":0,"default_qty":1,"delta_qty":0,"removed":false},{"name":"Molho Woll","qty":1,"unit_price":1.5,"price":0,"default_qty":1,"delta_qty":0,"removed":false},{"name":"P\u00e3o Brioche","qty":1,"unit_price":3,"price":0,"default_qty":1,"delta_qty":0,"removed":false},{"name":"Queijo Mussarela","qty":1,"unit_price":2.5,"price":0,"default_qty":1,"delta_qty":0,"removed":false}]}]}';
$customData = json_decode($data, true);
$customGroups = is_array($customData) && isset($customData['groups']) ? $customData['groups'] : $customData;
if ($customGroups && is_array($customGroups)) {
    foreach ($customGroups as $groupData) {
        $groupType = $groupData['type'] ?? 'extra';
        $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice', 'pool']);
        if (!empty($groupData['items'])) {
            foreach ($groupData['items'] as $customItem) {
                $customQty = isset($customItem['qty']) ? (int)$customItem['qty'] : null;
                $customDefaultQty = isset($customItem['default_qty']) ? (int)$customItem['default_qty'] : null;
                $customDeltaQty = isset($customItem['delta_qty']) ? (int)$customItem['delta_qty'] : null;
                $customPrice = !empty($customItem['price']) ? (float)$customItem['price'] : 0;
                
                $isRemoved = !empty($customItem['removed']) || ($customDefaultQty !== null && $customDefaultQty > 0 && ($customQty === 0 || $customQty === null));
                
                if ($isRemoved && $customItem['name']) {
                    echo "REMOVED: " . $customItem['name'] . "\n";
                } elseif ($isChoiceGroup) {
                    echo "CHOICE: " . $customItem['name'] . "\n";
                } else {
                    $shouldShow = false;
                    $effectiveQty = $customQty ?? 0;
                    if ($customDeltaQty === null && $customDefaultQty !== null && $customQty !== null) {
                        $customDeltaQty = $customQty - $customDefaultQty;
                    }
                    if ($customDeltaQty !== null && $customDeltaQty != 0) {
                        $shouldShow = true;
                    } elseif ($customPrice > 0 && $effectiveQty > 0) {
                        $shouldShow = true;
                    }
                    if ($shouldShow) {
                        echo "MODIFIED: " . $customItem['name'] . "\n";
                    } else {
                        echo "HIDDEN: " . $customItem['name'] . "\n";
                    }
                }
            }
        }
    }
}
