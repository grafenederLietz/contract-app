<?php

declare(strict_types=1);

function current_user_id(): int
{
    $user = current_user();
    return (int)($user['id'] ?? 0);
}

function current_user_role(): string
{
    $user = current_user();
    return (string)($user['role'] ?? '');
}

function can_manage_contracts(): bool
{
    return in_array(current_user_role(), ['admin', 'editor'], true);
}

function allowed_contract_statuses(): array
{
    return ['active', 'terminated', 'ended', 'adjustment_required', 'archived'];
}

function allowed_traffic_light_labels(): array
{
    return ['Grün', 'Gelb', 'Rot', 'Überfällig', 'Grau'];
}

function calculate_latest_termination_date(string $contractEnd, int $terminationMonths): string
{
    if ($terminationMonths <= 0) {
        return $contractEnd;
    }

    $date = new DateTime($contractEnd);
    $date->modify("-{$terminationMonths} months");

    return $date->format('Y-m-d');
}

function calculate_traffic_light(string $status, string $latestTerminationDate): array
{
    $greyStatuses = ['terminated', 'ended', 'archived'];

    if (in_array($status, $greyStatuses, true)) {
        return [
            'label' => 'Grau',
            'color' => '#9e9e9e',
            'days' => null,
        ];
    }

    $today = new DateTime('today');
    $deadline = new DateTime($latestTerminationDate);
    $interval = $today->diff($deadline);
    $days = (int)$interval->format('%r%a');

    if ($days < 0) {
        return [
            'label' => 'Überfällig',
            'color' => '#7f0000',
            'days' => $days,
        ];
    }

    if ($days <= 90) {
        return [
            'label' => 'Rot',
            'color' => '#d32f2f',
            'days' => $days,
        ];
    }

    if ($days <= 180) {
        return [
            'label' => 'Gelb',
            'color' => '#fbc02d',
            'days' => $days,
        ];
    }

    return [
        'label' => 'Grün',
        'color' => '#388e3c',
        'days' => $days,
    ];
}

function get_allowed_locations(mysqli $db, int $userId, string $userRole): array
{
    $items = [];

    if ($userRole === 'admin') {
        $result = $db->query("
            SELECT id, name
            FROM locations
            ORDER BY name ASC
        ");

        if (!$result) {
            app_log('get_allowed_locations', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
            ];
        }

        return $items;
    }

    $stmt = db_prepare($db, "
        SELECT l.id, l.name
        FROM locations l
        INNER JOIN user_locations ul ON ul.location_id = l.id
        WHERE ul.user_id = ?
        ORDER BY l.name ASC
    ", 'get_allowed_locations');

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
        ];
    }

    $stmt->close();

    return $items;
}

function get_allowed_departments(mysqli $db, int $userId, string $userRole): array
{
    $items = [];

    if ($userRole === 'admin') {
        $result = $db->query("
            SELECT id, name
            FROM departments
            ORDER BY name ASC
        ");

        if (!$result) {
            app_log('get_allowed_departments', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
            ];
        }

        return $items;
    }

    $stmt = db_prepare($db, "
        SELECT d.id, d.name
        FROM departments d
        INNER JOIN user_departments ud ON ud.department_id = d.id
        WHERE ud.user_id = ?
        ORDER BY d.name ASC
    ", 'get_allowed_departments');

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
        ];
    }

    $stmt->close();

    return $items;
}

function get_allowed_ids(array $items): array
{
    $ids = [];

    foreach ($items as $item) {
        $ids[] = (int)$item['id'];
    }

    return $ids;
}

function get_active_users(mysqli $db): array
{
    $items = [];

    $result = $db->query("
        SELECT id, display_name
        FROM users
        WHERE is_active = 1
        ORDER BY display_name ASC
    ");

    if (!$result) {
        app_log('get_active_users', $db->error);
        app_abort('Datenbank-Fehler.', 500);
    }

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int)$row['id'],
            'display_name' => (string)$row['display_name'],
        ];
    }

    return $items;
}

function validate_selected_ids(array $selectedIds, array $allowedIds): array
{
    $validated = [];

    foreach ($selectedIds as $value) {
        $id = (int)$value;

        if ($id <= 0) {
            continue;
        }

        if (!in_array($id, $allowedIds, true)) {
            app_abort('Zugriff verweigert.', 403);
        }

        $validated[] = $id;
    }

    return array_values(array_unique($validated));
}

function get_contract_location_ids(mysqli $db, int $contractId): array
{
    $ids = [];

    $stmt = db_prepare($db, "
        SELECT location_id
        FROM contract_locations
        WHERE contract_id = ?
    ", 'get_contract_location_ids');

    $stmt->bind_param('i', $contractId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['location_id'];
    }

    $stmt->close();

    return $ids;
}

function get_contract_department_ids(mysqli $db, int $contractId): array
{
    $ids = [];

    $stmt = db_prepare($db, "
        SELECT department_id
        FROM contract_departments
        WHERE contract_id = ?
    ", 'get_contract_department_ids');

    $stmt->bind_param('i', $contractId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['department_id'];
    }

    $stmt->close();

    return $ids;
}

function user_can_access_contract(mysqli $db, int $userId, string $userRole, int $contractId): bool
{
    if ($userRole === 'admin') {
        return true;
    }

    $stmt = db_prepare($db, "
        SELECT 1
        FROM contracts c
        LEFT JOIN contract_locations cl ON cl.contract_id = c.id
        LEFT JOIN contract_departments cd ON cd.contract_id = c.id
        WHERE c.id = ?
          AND (
                cl.location_id IN (
                    SELECT location_id
                    FROM user_locations
                    WHERE user_id = ?
                )
                OR
                cd.department_id IN (
                    SELECT department_id
                    FROM user_departments
                    WHERE user_id = ?
                )
          )
        LIMIT 1
    ", 'user_can_access_contract');

    $stmt->bind_param('iii', $contractId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (bool)$result;
}

function require_contract_access(mysqli $db, int $userId, string $userRole, int $contractId): void
{
    if (!user_can_access_contract($db, $userId, $userRole, $contractId)) {
        app_abort('Zugriff verweigert.', 403);
    }
}

function user_can_access_file(mysqli $db, int $userId, string $userRole, int $fileId): bool
{
    if ($userRole === 'admin') {
        return true;
    }

    $stmt = db_prepare($db, "
        SELECT contract_id
        FROM contract_files
        WHERE id = ?
        LIMIT 1
    ", 'user_can_access_file');

    $stmt->bind_param('i', $fileId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        return false;
    }

    return user_can_access_contract($db, $userId, $userRole, (int)$result['contract_id']);
}

function require_file_access(mysqli $db, int $userId, string $userRole, int $fileId): void
{
    if (!user_can_access_file($db, $userId, $userRole, $fileId)) {
        app_abort('Zugriff verweigert.', 403);
    }
}

function save_contract_locations(mysqli $db, int $contractId, array $locationIds): void
{
    $stmtDelete = db_prepare($db, "
        DELETE FROM contract_locations
        WHERE contract_id = ?
    ", 'save_contract_locations_delete');

    $stmtDelete->bind_param('i', $contractId);
    $stmtDelete->execute();
    $stmtDelete->close();

    if ($locationIds === []) {
        return;
    }

    $stmtInsert = db_prepare($db, "
        INSERT INTO contract_locations (contract_id, location_id)
        VALUES (?, ?)
    ", 'save_contract_locations_insert');

    foreach ($locationIds as $locationId) {
        $stmtInsert->bind_param('ii', $contractId, $locationId);
        $stmtInsert->execute();
    }

    $stmtInsert->close();
}

function save_contract_departments(mysqli $db, int $contractId, array $departmentIds): void
{
    $stmtDelete = db_prepare($db, "
        DELETE FROM contract_departments
        WHERE contract_id = ?
    ", 'save_contract_departments_delete');

    $stmtDelete->bind_param('i', $contractId);
    $stmtDelete->execute();
    $stmtDelete->close();

    if ($departmentIds === []) {
        return;
    }

    $stmtInsert = db_prepare($db, "
        INSERT INTO contract_departments (contract_id, department_id)
        VALUES (?, ?)
    ", 'save_contract_departments_insert');

    foreach ($departmentIds as $departmentId) {
        $stmtInsert->bind_param('ii', $contractId, $departmentId);
        $stmtInsert->execute();
    }

    $stmtInsert->close();
}

function build_contract_base_query(): string
{
    return "
        SELECT DISTINCT
            c.id,
            c.supplier,
            c.contract_subject,
            c.contract_start,
            c.duration_months,
            c.contract_end,
            c.termination_period_months,
            c.status,
            u.display_name AS responsible_name
        FROM contracts c
        LEFT JOIN users u ON u.id = c.responsible_user_id
        LEFT JOIN contract_locations cl ON cl.contract_id = c.id
        LEFT JOIN contract_departments cd ON cd.contract_id = c.id
    ";
}

function build_contract_access_where(array &$where, array &$params, string &$types, int $userId, string $userRole): void
{
    if ($userRole === 'admin') {
        return;
    }

    $where[] = "(
        cl.location_id IN (
            SELECT location_id
            FROM user_locations
            WHERE user_id = ?
        )
        OR
        cd.department_id IN (
            SELECT department_id
            FROM user_departments
            WHERE user_id = ?
        )
    )";

    $params[] = $userId;
    $params[] = $userId;
    $types .= 'ii';
}

function build_contract_filter_where(
    array &$where,
    array &$params,
    string &$types,
    string $supplierFilter,
    string $statusFilter,
    int $locationFilter,
    int $departmentFilter
): void {
    if ($supplierFilter !== '') {
        $where[] = "c.supplier LIKE ?";
        $params[] = '%' . $supplierFilter . '%';
        $types .= 's';
    }

    if ($statusFilter !== '' && in_array($statusFilter, allowed_contract_statuses(), true)) {
        $where[] = "c.status = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }

    if ($locationFilter > 0) {
        $where[] = "EXISTS (
            SELECT 1
            FROM contract_locations cl_filter
            WHERE cl_filter.contract_id = c.id
              AND cl_filter.location_id = ?
        )";
        $params[] = $locationFilter;
        $types .= 'i';
    }

    if ($departmentFilter > 0) {
        $where[] = "EXISTS (
            SELECT 1
            FROM contract_departments cd_filter
            WHERE cd_filter.contract_id = c.id
              AND cd_filter.department_id = ?
        )";
        $params[] = $departmentFilter;
        $types .= 'i';
    }
}

function load_contract_rows(
    mysqli $db,
    int $userId,
    string $userRole,
    string $supplierFilter,
    string $statusFilter,
    int $locationFilter,
    int $departmentFilter
): array {
    $sql = build_contract_base_query();
    $where = [];
    $params = [];
    $types = '';

    build_contract_access_where($where, $params, $types, $userId, $userRole);
    build_contract_filter_where($where, $params, $types, $supplierFilter, $statusFilter, $locationFilter, $departmentFilter);

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY c.contract_end ASC, c.id DESC';

    $stmt = db_prepare($db, $sql, 'load_contract_rows');

    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $latestTerminationDate = calculate_latest_termination_date(
            (string)$row['contract_end'],
            (int)$row['termination_period_months']
        );

        $trafficLight = calculate_traffic_light(
            (string)$row['status'],
            $latestTerminationDate
        );

        $row['latest_termination_date'] = $latestTerminationDate;
        $row['traffic_light'] = $trafficLight;

        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}