<?php
/** Company and book identity shared by KashHub printable/exportable documents. */
function khDocumentIdentity(int $bookId): array
{
    $sql = "SELECT b.id AS book_id, b.name AS book_name, b.Details AS book_details,
                   p.name AS company_name, p.address, p.contact1, p.contact2,
                   p.email, p.reg_no, p.profile
            FROM cashbook_books b
            LEFT JOIN cashbook_business_profile p ON p.id = b.business_id
            WHERE b.id = ? LIMIT 1";
    $result = prepared_statements($sql, 'i', [$bookId]);
    $identity = $result->fetch_assoc();
    if (!$identity) {
        throw new RuntimeException('Business book not found.');
    }
    $identity['company_name'] = trim((string)($identity['company_name'] ?? ''))
        ?: (string)$identity['book_name'];
    return $identity;
}

/** Use local file paths for Dompdf: no remote image access required. */
function khDocumentLogoPath(array $identity): ?string
{
    $profile = trim((string)($identity['profile'] ?? ''));
    if ($profile === '') return null;
    $file = basename(str_replace('\\', '/', $profile));
    $path = __DIR__ . '/uploads/business_logos/' . $file;
    return is_file($path) ? realpath($path) : null;
}

function khDocumentLogoDataUri(array $identity): ?string
{
    $path = khDocumentLogoPath($identity);
    if (!$path) return null;
    $mime = mime_content_type($path);
    if (!in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) return null;
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
}

function khDocumentCompanyLines(array $identity): array
{
    $lines = [];
    foreach (['address', 'contact1', 'contact2', 'email'] as $field) {
        $value = trim((string)($identity[$field] ?? ''));
        if ($value !== '') $lines[] = $value;
    }
    if (!empty($identity['reg_no'])) $lines[] = 'Reg. No: ' . $identity['reg_no'];
    return $lines;
}
