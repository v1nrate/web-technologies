<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CompatibilityService
{
    public function check(Collection $items): array
    {
        $parts = $items->keyBy('category');
        $issues = [];
        $add = function (string $severity, string $title, string $message, string $recommendation, array $categories) use (&$issues): void {
            $issues[] = compact('severity', 'title', 'message', 'recommendation', 'categories');
        };
        $cpu = $parts->get('cpu');
        $board = $parts->get('motherboard');
        $ram = $parts->get('ram');
        $gpu = $parts->get('gpu');
        $psu = $parts->get('psu');
        $case = $parts->get('case');
        $cooler = $parts->get('cooler');

        if ($cpu && $board) {
            $a = $cpu->specs['socket'] ?? null;
            $b = $board->specs['socket'] ?? null;
            if ($a && $b && $a !== $b) {
                $add('error', 'Несовместимые сокеты', "Процессор {$cpu->name}: {$a}; материнская плата {$board->name}: {$b}.", "Выберите материнскую плату с сокетом {$a} или процессор с сокетом {$b}.", ['cpu', 'motherboard']);
            }
        }
        if ($ram && $board) {
            $a = $ram->specs['memory_type'] ?? null;
            $b = $board->specs['memory_type'] ?? null;
            if ($a && $b && $a !== $b) {
                $add('error', 'Несовместимая оперативная память', "Оперативная память {$ram->name}: {$a}; материнская плата {$board->name}: {$b}.", "Выберите память {$b} либо материнскую плату с поддержкой {$a}.", ['ram', 'motherboard']);
            }
        }
        if ($cooler && $cpu) {
            $socket = $cpu->specs['socket'] ?? null;
            $sockets = $cooler->specs['sockets'] ?? null;
            if ($socket && is_array($sockets) && $sockets && !in_array($socket, $sockets, true)) {
                $add('error', 'Кулер не поддерживает сокет', "Кулер {$cooler->name} не поддерживает сокет {$socket} процессора {$cpu->name}.", "Выберите кулер с поддержкой {$socket}.", ['cooler']);
            }
        }
        if ($case && $board) {
            $factor = $board->specs['form_factor'] ?? null;
            $factors = $case->specs['form_factors'] ?? null;
            if ($factor && is_array($factors) && $factors && !in_array($factor, $factors, true)) {
                $add('error', 'Материнская плата не подходит к корпусу', "Форм-фактор {$board->name}: {$factor}; корпус {$case->name} поддерживает: " . implode(', ', $factors) . '.', 'Выберите корпус с поддержкой нужного форм-фактора либо другую материнскую плату.', ['case']);
            }
        }
        if ($case && $gpu) {
            $length = $gpu->specs['length_mm'] ?? null;
            $limit = $case->specs['max_gpu_mm'] ?? null;
            if (is_numeric($length) && is_numeric($limit) && $length > $limit) {
                $add('error', 'Видеокарта не помещается в корпус', "Длина {$gpu->name}: {$length} мм; максимум корпуса {$case->name}: {$limit} мм.", "Выберите корпус с допустимой длиной видеокарты не менее {$length} мм или более короткую видеокарту.", ['case', 'gpu']);
            }
        }
        if ($case && $cooler) {
            $height = $cooler->specs['height_mm'] ?? null;
            $limit = $case->specs['max_cooler_mm'] ?? null;
            if (is_numeric($height) && is_numeric($limit) && $height > $limit) {
                $add('error', 'Кулер не помещается в корпус', "Высота {$cooler->name}: {$height} мм; максимум корпуса {$case->name}: {$limit} мм.", "Выберите корпус с допустимой высотой кулера не менее {$height} мм или более низкий кулер.", ['case', 'cooler']);
            }
        }
        $ssd = $parts->get('ssd');
        if ($ssd && $board) {
            $interface = (string) ($ssd->specs['interface'] ?? '');
            $formFactor = (string) ($ssd->specs['form_factor'] ?? '');
            $isM2 = str_starts_with($formFactor, 'M.2') || str_starts_with($interface, 'NVMe');
            $isSata = str_contains(strtoupper($interface), 'SATA') && !$isM2;
            $slotKey = $isM2 ? 'm2_slots' : ($isSata ? 'sata_ports' : null);
            if ($slotKey !== null) {
                $slots = $board->specs[$slotKey] ?? null;
                if (is_numeric($slots) && (int) $slots === 0) {
                    $kind = $isM2 ? 'M.2' : 'SATA';
                    $add('error', 'Нет разъёма для накопителя', "Накопитель {$ssd->name} требует подключение {$kind}, но у платы {$board->name} указано 0 подходящих разъёмов.", "Выберите плату с разъёмом {$kind} или другой накопитель.", ['ssd', 'motherboard']);
                }
            }
        }
        if ($ram && $board) {
            $capacity = $ram->specs['capacity_gb'] ?? null;
            $limit = $board->specs['max_ram_gb'] ?? null;
            if (is_numeric($capacity) && is_numeric($limit) && (float) $capacity > (float) $limit) {
                $add('error', 'Превышен максимальный объём ОЗУ', "Комплект {$ram->name}: {$capacity} ГБ; плата {$board->name} поддерживает до {$limit} ГБ.", 'Выберите комплект меньшего объёма или материнскую плату с большей ёмкостью памяти.', ['ram', 'motherboard']);
            }
            $modules = $ram->specs['modules'] ?? null;
            $slots = $board->specs['ram_slots'] ?? null;
            if (is_numeric($modules) && is_numeric($slots) && (int) $modules > (int) $slots) {
                $add('error', 'Недостаточно слотов для ОЗУ', "В комплекте {$ram->name} — {$modules} модулей; у платы {$board->name} — {$slots} слотов.", 'Выберите комплект с меньшим количеством модулей или плату с большим количеством слотов.', ['ram', 'motherboard']);
            }
        }
        if ($psu && $case) {
            $length = $psu->specs['length_mm'] ?? null;
            $limit = $case->specs['max_psu_mm'] ?? null;
            if (is_numeric($length) && is_numeric($limit) && (float) $length > (float) $limit) {
                $add('error', 'Блок питания не помещается в корпус', "Длина {$psu->name}: {$length} мм; корпус {$case->name} допускает до {$limit} мм.", 'Выберите более короткий БП или корпус с большим отсеком.', ['psu', 'case']);
            }
            $factor = $psu->specs['form_factor'] ?? null;
            $supported = $case->specs['psu_form_factors'] ?? null;
            if ($factor && is_array($supported) && count($supported) > 0 && !in_array($factor, $supported, true)) {
                $add('error', 'Неподходящий форм-фактор БП', "Блок питания {$psu->name}: {$factor}; корпус {$case->name} поддерживает " . implode(', ', $supported) . '.', 'Выберите совместимый блок питания или корпус.', ['psu', 'case']);
            }
        }
        if ($gpu && $psu) {
            $required8 = $gpu->specs['required_pcie_8pin'] ?? null;
            $available8 = $psu->specs['pcie_8pin'] ?? null;
            $required16 = $gpu->specs['required_pcie_16pin'] ?? null;
            $available16 = $psu->specs['pcie_16pin'] ?? null;
            if (is_numeric($required8) && is_numeric($available8) && (int) $required8 > (int) $available8) {
                $add('error', 'Недостаточно разъёмов PCIe 8-pin', "Видеокарте {$gpu->name} требуется {$required8} разъёмов PCIe 8-pin; у БП {$psu->name} указано {$available8}.", 'Выберите БП с нужным количеством кабелей питания PCIe.', ['gpu', 'psu']);
            }
            if (is_numeric($required16) && is_numeric($available16) && (int) $required16 > (int) $available16) {
                $add('warning', 'Нет необходимого разъёма питания 16-pin', "Видеокарте {$gpu->name} требуется {$required16} разъёмов 16-pin; у БП {$psu->name} указано {$available16}. Совместимость через переходники не проверяется.", 'Выберите БП с подходящим штатным кабелем или проверьте требования производителя к адаптеру.', ['gpu', 'psu']);
            }
        }
        if ($cpu && $cooler) {
            $cpuTdp = $cpu->specs['cooling_requirement_watts'] ?? null;
            $coolerCapacity = $cooler->specs['cooling_capacity_watts'] ?? null;
            if (is_numeric($cpuTdp) && is_numeric($coolerCapacity) && (float) $cpuTdp > (float) $coolerCapacity) {
                $add('warning', 'Недостаточная заявленная эффективность охлаждения', "Для {$cpu->name} указана требуемая мощность охлаждения {$cpuTdp} Вт; для {$cooler->name} — {$coolerCapacity} Вт.", 'Выберите более производительный кулер и уточните требования производителя процессора.', ['cpu', 'cooler']);
            }
        }
        $estimated = $items->sum(fn ($item) => (int) ($item->power_watts ?? 0)) + 70;
        $recommended = (int) ceil($estimated * 1.25);
        if ($psu) {
            $wattage = $psu->specs['wattage'] ?? null;
            if (is_numeric($wattage) && $wattage < $recommended) {
                $add('warning', 'Недостаточный запас мощности', "Ориентировочное потребление: {$estimated} Вт; рекомендуется не менее {$recommended} Вт с запасом 25%. Выбран БП {$psu->name}: {$wattage} Вт.", "Выберите блок питания мощностью не менее {$recommended} Вт.", ['psu']);
            }
        }
        $missing = array_values(array_diff(['cpu', 'motherboard', 'gpu', 'ram', 'ssd', 'psu', 'case', 'cooler'], $parts->keys()->all()));
        return [
            'missing' => $missing,
            'status' => count($issues) && collect($issues)->contains(fn ($issue) => $issue['severity'] === 'error') ? 'error' : (count($issues) || count($missing) ? 'warning' : 'ok'),
            'issues' => $issues,
            'warnings' => array_map(fn ($issue) => $issue['message'], $issues),
            'estimated_watts' => $estimated,
            'recommended_psu_watts' => $recommended,
            'psu_watts' => $psu && is_numeric($psu->specs['wattage'] ?? null) ? (int) $psu->specs['wattage'] : null,
        ];
    }
}
