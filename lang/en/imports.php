<?php

declare(strict_types=1);

/**
 * Shared import-suite translations (engine UI: wizard steps, preview,
 * template descriptions, notifications and errors) used by every
 * import in the suite. Feature-specific column labels and mode names
 * live under `resources.<feature>.import`.
 */
return [

    'wizard' => [
        'submit_label' => 'Run import',
        'mode_label' => 'Import type',
        'step_upload' => 'Upload file',
        'step_upload_desc' => 'Choose an import type, download the template and upload your CSV or XLSX file.',
        'step_mapping' => 'Map columns',
        'step_mapping_desc' => 'Match your file headers to the import fields. Required fields are marked with an asterisk.',
        'step_preview' => 'Preview and confirm',
        'step_preview_desc' => 'Review the row-level result before anything is written.',
        'mapping_fieldset' => 'Column mapping',
        'download_csv_template' => 'Download CSV template',
        'download_xlsx_template' => 'Download XLSX template',
        'file_label' => 'Import file',
        'file_helper' => 'CSV or XLSX. Column order may differ from the template; map your columns in the next step.',
        'confirm_updates' => 'I understand that existing records will be updated',
        'confirm_updates_help' => 'Rows that match existing records are only updated after you confirm here.',
    ],

    'preview' => [
        'total_rows' => 'Total rows',
        'to_create' => 'To create',
        'to_update' => 'To update',
        'to_reject' => 'To reject',
        'update_warning_title' => 'Updates detected',
        'update_warning_body' => ':count row(s) will update existing records. Tick the confirmation checkbox before running the import.',
        'col_row' => 'Row',
        'col_status' => 'Status',
        'col_identifier' => 'Identifier',
        'col_details' => 'Details',
        'badge_create' => 'Create',
        'badge_update' => 'Update',
        'badge_reject' => 'Reject',
        'msg_ready_create' => 'Will create a new record',
        'msg_ready_update' => 'Will update the matching record',
        'no_rows' => 'No rows to preview. Go back and check your file and mappings.',
    ],

    'template' => [
        'field' => 'Field',
        'description' => 'Description',
        'descriptions_sheet' => 'Field descriptions',
    ],

    'notifications' => [
        'queued_title' => 'Import queued',
        'queued_body' => ':count row(s) are being processed in the background. You will receive a notification when it finishes.',
        'completed_title' => 'Import completed',
        'completed_body' => 'Created: :created · Updated: :updated · Rejected: :rejected · Skipped: :skipped',
        'failed_title' => 'Import failed',
    ],

    'errors' => [
        'file_missing' => 'The import file could not be found. Please upload it again.',
        'unreadable_file' => 'The uploaded file could not be read.',
        'empty_file' => 'The uploaded file is empty or has no header row.',
        'unsupported_format' => 'Only CSV and XLSX templates are supported.',
        'unknown_definition' => 'Unknown import type ":key".',
        'missing_required_mapping' => 'The ":field" column must be mapped before previewing.',
        'preview_expired' => 'The preview expired. Go back to the mapping step and advance again to refresh it, then confirm.',
    ],

];
