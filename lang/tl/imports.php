<?php

declare(strict_types=1);

/**
 * Shared import-suite translations (engine UI: wizard steps, preview,
 * template descriptions, notifications and errors). Feature-specific
 * column labels and mode names live under `resources.<feature>.import`.
 */
return [

    'wizard' => [
        'submit_label' => 'Ipatupad ang importasyon', // TODO(i18n): verify
        'mode_label' => 'Uri ng importasyon', // TODO(i18n): verify
        'step_upload' => 'Mag-upload ng file', // TODO(i18n): verify
        'step_upload_desc' => 'Pumili ng uri ng importasyon, i-download ang template, at i-upload ang iyong CSV o XLSX na file.', // TODO(i18n): verify
        'step_mapping' => 'Itugma ang mga column', // TODO(i18n): verify
        'step_mapping_desc' => 'Itugma ang mga header ng iyong file sa mga patlang ng importasyon. May tuldok-asterisk ang mga required na patlang.', // TODO(i18n): verify
        'step_preview' => 'Pag-preview at kumpirmasyon', // TODO(i18n): verify
        'step_preview_desc' => 'Suriin ang resulta bawat bago magsulat ng kahit ano.', // TODO(i18n): verify
        'mapping_fieldset' => 'Pagtutugma ng column', // TODO(i18n): verify
        'download_csv_template' => 'I-download ang CSV template', // TODO(i18n): verify
        'download_xlsx_template' => 'I-download ang XLSX template', // TODO(i18n): verify
        'file_label' => 'Import file', // TODO(i18n): verify
        'file_helper' => 'CSV o XLSX. Maaaring magkaiba ang pagkakaayos ng column sa template; itugma ang iyong column sa susunod na hakbang.', // TODO(i18n): verify
        'confirm_updates' => 'Naiintindihan kong a-update ang mga umiiral na record', // TODO(i18n): verify
        'confirm_updates_help' => 'Ang mga row na tumutugma sa umiiral na record ay na-update lamang kapag nakumpirma dito.', // TODO(i18n): verify
    ],

    'preview' => [
        'total_rows' => 'Kabuuang row', // TODO(i18n): verify
        'to_create' => 'Gagawa', // TODO(i18n): verify
        'to_update' => 'Aa-update', // TODO(i18n): verify
        'to_reject' => 'Tatanggihan', // TODO(i18n): verify
        'update_warning_title' => 'May mga natukoy na update', // TODO(i18n): verify
        'update_warning_body' => ':count row ang maga-update sa umiiral na record. Markahan ang kumpirmasyon bago ipatupad ang importasyon.', // TODO(i18n): verify
        'col_row' => 'Row', // TODO(i18n): verify
        'col_status' => 'Katayuan', // TODO(i18n): verify
        'col_identifier' => 'Identifier', // TODO(i18n): verify
        'col_details' => 'Mga detalye', // TODO(i18n): verify
        'badge_create' => 'Gumawa', // TODO(i18n): verify
        'badge_update' => 'I-update', // TODO(i18n): verify
        'badge_reject' => 'Tanggihan', // TODO(i18n): verify
        'msg_ready_create' => 'Gagawa ng bagong record', // TODO(i18n): verify
        'msg_ready_update' => 'Aa-update ang tumutugmang record', // TODO(i18n): verify
        'no_rows' => 'Walang row para i-preview. Bumalik at suriin ang file at mga pagtutugma.', // TODO(i18n): verify
        'rows_truncated' => 'Nagpapakita ng unang :shown sa :total na row. Tumpak ang mga bilang sa itaas para sa buong file.', // TODO(i18n): verify
    ],

    'template' => [
        'field' => 'Patlang', // TODO(i18n): verify
        'description' => 'Paglalarawan', // TODO(i18n): verify
        'descriptions_sheet' => 'Mga paglalarawan ng patlang', // TODO(i18n): verify
    ],

    'notifications' => [
        'queued_title' => 'Nakapila ang importasyon', // TODO(i18n): verify
        'queued_body' => ':count row ang proseso sa background. Makakatanggap ka ng abiso kapag tapos na.', // TODO(i18n): verify
        'completed_title' => 'Tapos ang importasyon', // TODO(i18n): verify
        'completed_body' => 'Ginawa: :created · In-update: :updated · Tinanggihan: :rejected · Nilaktawan: :skipped', // TODO(i18n): verify
        'failed_title' => 'Nabigo ang importasyon', // TODO(i18n): verify
        'failed_body' => 'Hindi matapos ang importasyon. Subukan muli.', // TODO(i18n): verify
    ],

    'errors' => [
        'file_missing' => 'Hindi mahanap ang import file. Mag-upload muli.', // TODO(i18n): verify
        'unreadable_file' => 'Hindi mabasa ang na-upload na file.', // TODO(i18n): verify
        'corrupt_file' => 'Corrupted ang na-upload na file o hindi ito valid na XLSX file.', // TODO(i18n): verify
        'empty_file' => 'Walang laman o walang header row ang na-upload na file.', // TODO(i18n): verify
        'unsupported_format' => 'CSV at XLSX templates lamang ang tinatanggap.',
        'file_too_large' => 'Ang laki ng file ay lumampas sa 10MB limit.',
        'max_rows_exceeded' => 'Ang import file ay lumampas sa maximum na pinapayagani na :max na linya.', // TODO(i18n): verify
        'unknown_definition' => 'Hindi kilalang uri ng importasyon ":key".', // TODO(i18n): verify
        'missing_required_mapping' => 'Kailangang itugma ang ":field" na column bago mag-preview.', // TODO(i18n): verify
        'preview_expired' => 'Nag-expire ang preview. Bumalik sa hakbang ng pagtutugma, mag-proceed muli para i-refresh, pagkatapos kumpirmahin.', // TODO(i18n): verify
        'preview_failed' => 'Hindi magawa ang preview para sa file na ito. Tiyaking tumutugma ito sa napiling uri ng importasyon, pagkatapos subukan muli.', // TODO(i18n): verify
    ],

];
