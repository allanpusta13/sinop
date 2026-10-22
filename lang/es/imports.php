<?php

declare(strict_types=1);

/**
 * Traducciones compartidas de la suite de importaciones (UI del motor:
 * pasos del asistente, vista previa, descripciones de plantilla,
 * notificaciones y errores). Las etiquetas de columnas y los nombres
 * de modo específicos de cada entidad viven en
 * `resources.<recurso>.import`.
 */
return [

    'wizard' => [
        'submit_label' => 'Ejecutar importación',
        'mode_label' => 'Tipo de importación',
        'step_upload' => 'Subir archivo',
        'step_upload_desc' => 'Elija un tipo de importación, descargue la plantilla y suba su archivo CSV o XLSX.',
        'step_mapping' => 'Asignar columnas',
        'step_mapping_desc' => 'Relacione los encabezados de su archivo con los campos de importación. Los campos obligatorios llevan asterisco.',
        'step_preview' => 'Vista previa y confirmación',
        'step_preview_desc' => 'Revise el resultado por fila antes de escribir nada.',
        'mapping_fieldset' => 'Asignación de columnas',
        'download_csv_template' => 'Descargar plantilla CSV',
        'download_xlsx_template' => 'Descargar plantilla XLSX',
        'file_label' => 'Archivo de importación',
        'file_helper' => 'CSV o XLSX. El orden de las columnas puede diferir de la plantilla; asigne sus columnas en el siguiente paso.',
        'confirm_updates' => 'Entiendo que se actualizarán registros existentes',
        'confirm_updates_help' => 'Las filas que coincidan con registros existentes solo se actualizan tras confirmar aquí.',
    ],

    'preview' => [
        'total_rows' => 'Total de filas',
        'to_create' => 'Por crear',
        'to_update' => 'Por actualizar',
        'to_reject' => 'Por rechazar',
        'update_warning_title' => 'Actualizaciones detectadas',
        'update_warning_body' => ':count fila(s) actualizarán registros existentes. Marque la casilla de confirmación antes de ejecutar la importación.',
        'col_row' => 'Fila',
        'col_status' => 'Estado',
        'col_identifier' => 'Identificador',
        'col_details' => 'Detalles',
        'badge_create' => 'Crear',
        'badge_update' => 'Actualizar',
        'badge_reject' => 'Rechazar',
        'msg_ready_create' => 'Creará un registro nuevo',
        'msg_ready_update' => 'Actualizará el registro coincidente',
        'no_rows' => 'No hay filas para la vista previa. Regrese y verifique su archivo y las asignaciones.',
        'rows_truncated' => 'Mostrando las primeras :shown filas de :total. Los recuentos de arriba son exactos para todo el archivo.',
    ],

    'template' => [
        'field' => 'Campo',
        'description' => 'Descripción',
        'descriptions_sheet' => 'Descripciones de campos',
    ],

    'notifications' => [
        'queued_title' => 'Importación en cola',
        'queued_body' => ':count fila(s) se están procesando en segundo plano. Recibirá una notificación al finalizar.',
        'completed_title' => 'Importación completada',
        'completed_body' => 'Creados: :created · Actualizados: :updated · Rechazados: :rejected · Omitidos: :skipped',
        'failed_title' => 'Falló la importación',
        'failed_body' => 'No se pudo completar la importación. Inténtalo de nuevo.',
    ],

    'errors' => [
        'file_missing' => 'No se encontró el archivo de importación. Suba el archivo de nuevo.',
        'unreadable_file' => 'No se pudo leer el archivo subido.',
        'corrupt_file' => 'El archivo subido está dañado o no es un archivo XLSX válido.',
        'empty_file' => 'El archivo subido está vacío o no tiene fila de encabezados.',
        'unsupported_format' => 'Solo se admiten plantillas CSV y XLSX.',
        'unknown_definition' => 'Tipo de importación desconocido ":key".',
        'missing_required_mapping' => 'La columna ":field" debe asignarse antes de la vista previa.',
        'preview_expired' => 'La vista previa expiró. Regrese al paso de asignación, avance de nuevo para actualizarla y confirme.',
        'preview_failed' => 'No se pudo generar la vista previa de este archivo. Verifique que coincida con el tipo de importación seleccionado e intente de nuevo.',
    ],

];
