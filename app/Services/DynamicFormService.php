<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Database;

/** Server-owned form definitions shared by deposit, KYC and withdrawal workflows. */
final class DynamicFormService
{
    public function __construct(private ?Database $db) {}

    public function definition(?int $formId): array
    {
        if (!$this->db || !$formId) return [];
        $fields = $this->db->select('SELECT * FROM dynamic_form_fields WHERE form_id=? AND is_visible=1 ORDER BY sort_order,id', [$formId]);
        foreach ($fields as &$field) {
            $field['options'] = $this->db->select('SELECT option_value,option_label FROM dynamic_form_field_options WHERE field_id=? AND is_active=1 ORDER BY sort_order,id', [(int)$field['id']]);
            $field['accepted_mimes'] = $field['accepted_mimes'] ? (json_decode((string)$field['accepted_mimes'], true) ?: []) : [];
            $field['validation_rules'] = $field['validation_rules'] ? (json_decode((string)$field['validation_rules'], true) ?: []) : [];
        }
        return $fields;
    }

    public function snapshot(?int $formId, string $purpose): ?int
    {
        if (!$this->db || !$formId) return null;
        $definition = $this->definition($formId);
        $this->db->execute('INSERT INTO dynamic_form_snapshots(form_id,purpose,definition) VALUES (?,?,?)', [$formId, $purpose, json_encode($definition, JSON_THROW_ON_ERROR)]);
        return (int)$this->db->pdo()->lastInsertId();
    }

    /** @return array{values:array<string,string>,files:array<string,array>} */
    public function validate(array $fields, array $input, array $files): array
    {
        $values = []; $acceptedKeys = []; $errors = [];
        foreach ($fields as $field) {
            $key = (string)$field['field_key']; $acceptedKeys[$key] = true;
            if (!in_array($field['field_type'], ['FILE','COPYABLE_VALUE','INSTRUCTION','HIDDEN'], true) && !(bool)$field['is_user_editable']) continue;
            $value = $input[$key] ?? null;
            $file = $files[$key] ?? null;
            if ($field['field_type'] === 'FILE') {
                if ((bool)$field['is_required'] && (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) $errors[$key] = 'This file is required.';
                if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    if ($field['max_file_size'] && (int)$file['size'] > (int)$field['max_file_size']) $errors[$key] = 'The file is too large.';
                    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
                    if ($field['accepted_mimes'] && !in_array($mime, $field['accepted_mimes'], true)) $errors[$key] = 'This file type is not permitted.';
                }
                continue;
            }
            if (is_array($value)) $value = implode(',', array_map('strval', $value));
            $value = trim((string)$value);
            if ((bool)$field['is_required'] && $value === '') { $errors[$key] = 'This field is required.'; continue; }
            if ($value === '') { $values[$key] = ''; continue; }
            if ($field['field_type'] === 'EMAIL' && !filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[$key] = 'Enter a valid email address.';
            if ($field['field_type'] === 'NUMBER' && !preg_match('/^-?\d+(\.\d+)?$/', $value)) $errors[$key] = 'Enter a valid number.';
            if ($field['field_type'] === 'DATE' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) $errors[$key] = 'Enter a valid date.';
            if (in_array($field['field_type'], ['SELECT','RADIO','MULTI_SELECT'], true)) {
                $allowed = array_column($field['options'], 'option_value');
                foreach (explode(',', $value) as $selected) if (!in_array($selected, $allowed, true)) $errors[$key] = 'Select an available option.';
            }
            if ($field['min_value'] !== null && mb_strlen($value) < (int)$field['min_value']) $errors[$key] = 'Value is too short.';
            if ($field['max_value'] !== null && mb_strlen($value) > (int)$field['max_value']) $errors[$key] = 'Value is too long.';
            $values[$key] = $value;
        }
        foreach ($input as $key => $_) if (str_starts_with((string)$key, 'field_') && !isset($acceptedKeys[substr((string)$key, 6)])) $errors[(string)$key] = 'Unexpected form field.';
        if ($errors) throw new \InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        return ['values'=>$values,'files'=>$files];
    }
}
