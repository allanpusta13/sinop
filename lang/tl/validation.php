<?php

declare(strict_types=1);

return [
    'accepted' => 'The :attribute field must be accepted.', // TODO(i18n): translate
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.', // TODO(i18n): translate
    'active_url' => 'The :attribute field must be a valid URL.', // TODO(i18n): translate
    'after' => 'Dapat pagkatapos :date ang :attribute.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.', // TODO(i18n): translate
    'alpha' => 'The :attribute field must only contain letters.', // TODO(i18n): translate
    'alpha_dash' => 'The :attribute field must only contain letters, numbers, dashes, and underscores.', // TODO(i18n): translate
    'alpha_num' => 'The :attribute field must only contain letters and numbers.', // TODO(i18n): translate
    'any_of' => 'The :attribute field is invalid.', // TODO(i18n): translate
    'array' => 'The :attribute field must be an array.', // TODO(i18n): translate
    'array_keys' => 'The :attribute field must only contain the following keys: :values.', // TODO(i18n): translate
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.', // TODO(i18n): translate
    'base64' => 'The :attribute field must be a valid Base64 string.', // TODO(i18n): translate
    'before' => 'Dapat bago :date ang :attribute.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.', // TODO(i18n): translate
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.', // TODO(i18n): translate
        'file' => 'The :attribute field must be between :min and :max kilobytes.', // TODO(i18n): translate
        'numeric' => 'The :attribute field must be between :min and :max.', // TODO(i18n): translate
        'string' => 'The :attribute field must be between :min and :max characters.', // TODO(i18n): translate
    ],
    'boolean' => 'The :attribute field must be true or false.', // TODO(i18n): translate
    'can' => 'The :attribute field contains an unauthorized value.', // TODO(i18n): translate
    'confirmed' => 'Hindi match ang :attribute confirmation.',
    'contains' => 'The :attribute field is missing a required value.', // TODO(i18n): translate
    'current_password' => 'The password is incorrect.', // TODO(i18n): translate
    'date' => 'Valid date ang :attribute.',
    'date_equals' => 'The :attribute field must be a date equal to :date.', // TODO(i18n): translate
    'date_format' => 'The :attribute field must match the format :format.', // TODO(i18n): translate
    'decimal' => 'The :attribute field must have :decimal decimal places.', // TODO(i18n): translate
    'declined' => 'The :attribute field must be declined.', // TODO(i18n): translate
    'declined_if' => 'The :attribute field must be declined when :other is :value.', // TODO(i18n): translate
    'different' => 'The :attribute field and :other must be different.', // TODO(i18n): translate
    'digits' => 'The :attribute field must be :digits digits.', // TODO(i18n): translate
    'digits_between' => 'The :attribute field must be between :min and :max digits.', // TODO(i18n): translate
    'dimensions' => 'The :attribute field has invalid image dimensions.', // TODO(i18n): translate
    'distinct' => 'The :attribute field has a duplicate value.', // TODO(i18n): translate
    'doesnt_contain' => 'The :attribute field must not contain any of the following: :values.', // TODO(i18n): translate
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.', // TODO(i18n): translate
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.', // TODO(i18n): translate
    'email' => 'Valid email address ang :attribute.',
    'encoding' => 'The :attribute field must be encoded in :encoding.', // TODO(i18n): translate
    'ends_with' => 'The :attribute field must end with one of the following: :values.', // TODO(i18n): translate
    'enum' => 'The selected :attribute is invalid.', // TODO(i18n): translate
    'exists' => 'Invalid ang selected :attribute.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.', // TODO(i18n): translate
    'file' => 'The :attribute field must be a file.', // TODO(i18n): translate
    'filled' => 'The :attribute field must have a value.', // TODO(i18n): translate
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.', // TODO(i18n): translate
        'file' => 'The :attribute field must be greater than :value kilobytes.', // TODO(i18n): translate
        'numeric' => 'The :attribute field must be greater than :value.', // TODO(i18n): translate
        'string' => 'The :attribute field must be greater than :value characters.', // TODO(i18n): translate
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.', // TODO(i18n): translate
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.', // TODO(i18n): translate
        'numeric' => 'The :attribute field must be greater than or equal to :value.', // TODO(i18n): translate
        'string' => 'The :attribute field must be greater than or equal to :value characters.', // TODO(i18n): translate
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.', // TODO(i18n): translate
    'image' => 'The :attribute field must be an image.', // TODO(i18n): translate
    'in' => 'Invalid ang selected :attribute.',
    'in_array' => 'The :attribute field must exist in :other.', // TODO(i18n): translate
    'in_array_keys' => 'The :attribute field must contain at least one of the following keys: :values.', // TODO(i18n): translate
    'integer' => 'Dapat integer ang :attribute.',
    'ip' => 'The :attribute field must be a valid IP address.', // TODO(i18n): translate
    'ipv4' => 'The :attribute field must be a valid IPv4 address.', // TODO(i18n): translate
    'ipv6' => 'The :attribute field must be a valid IPv6 address.', // TODO(i18n): translate
    'json' => 'The :attribute field must be a valid JSON string.', // TODO(i18n): translate
    'list' => 'The :attribute field must be a list.', // TODO(i18n): translate
    'lowercase' => 'The :attribute field must be lowercase.', // TODO(i18n): translate
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.', // TODO(i18n): translate
        'file' => 'The :attribute field must be less than :value kilobytes.', // TODO(i18n): translate
        'numeric' => 'The :attribute field must be less than :value.', // TODO(i18n): translate
        'string' => 'The :attribute field must be less than :value characters.', // TODO(i18n): translate
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.', // TODO(i18n): translate
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.', // TODO(i18n): translate
        'numeric' => 'The :attribute field must be less than or equal to :value.', // TODO(i18n): translate
        'string' => 'The :attribute field must be less than or equal to :value characters.', // TODO(i18n): translate
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.', // TODO(i18n): translate
    'max' => [
        'array' => 'The :attribute field must not have more than :max items.', // TODO(i18n): translate
        'file' => 'The :attribute field must not be greater than :max kilobytes.', // TODO(i18n): translate
        'numeric' => 'Hindi dapat lumampas ng :max ang :attribute.',
        'string' => 'Hindi dapat lumampas ng :max characters ang :attribute.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.', // TODO(i18n): translate
    'mimes' => 'The :attribute field must be a file of type: :values.', // TODO(i18n): translate
    'mimetypes' => 'The :attribute field must be a file of type: :values.', // TODO(i18n): translate
    'min' => [
        'array' => 'The :attribute field must have at least :min items.', // TODO(i18n): translate
        'file' => 'The :attribute field must be at least :min kilobytes.', // TODO(i18n): translate
        'numeric' => 'Minimum :min ang :attribute.',
        'string' => 'Minimum :min characters ang :attribute.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.', // TODO(i18n): translate
    'missing' => 'The :attribute field must be missing.', // TODO(i18n): translate
    'missing_if' => 'The :attribute field must be missing when :other is :value.', // TODO(i18n): translate
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.', // TODO(i18n): translate
    'missing_with' => 'The :attribute field must be missing when :values is present.', // TODO(i18n): translate
    'missing_with_all' => 'The :attribute field must be missing when :values are present.', // TODO(i18n): translate
    'multiple_of' => 'The :attribute field must be a multiple of :value.', // TODO(i18n): translate
    'not_in' => 'The selected :attribute is invalid.', // TODO(i18n): translate
    'not_regex' => 'The :attribute field format is invalid.', // TODO(i18n): translate
    'numeric' => 'Dapat number ang :attribute.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.', // TODO(i18n): translate
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.', // TODO(i18n): translate
        'numbers' => 'The :attribute field must contain at least one number.', // TODO(i18n): translate
        'symbols' => 'The :attribute field must contain at least one symbol.', // TODO(i18n): translate
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.', // TODO(i18n): translate
    ],
    'present' => 'The :attribute field must be present.', // TODO(i18n): translate
    'present_if' => 'The :attribute field must be present when :other is :value.', // TODO(i18n): translate
    'present_unless' => 'The :attribute field must be present unless :other is :value.', // TODO(i18n): translate
    'present_with' => 'The :attribute field must be present when :values is present.', // TODO(i18n): translate
    'present_with_all' => 'The :attribute field must be present when :values are present.', // TODO(i18n): translate
    'prohibited' => 'The :attribute field is prohibited.', // TODO(i18n): translate
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.', // TODO(i18n): translate
    'prohibited_if_accepted' => 'The :attribute field is prohibited when :other is accepted.', // TODO(i18n): translate
    'prohibited_if_declined' => 'The :attribute field is prohibited when :other is declined.', // TODO(i18n): translate
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.', // TODO(i18n): translate
    'prohibits' => 'The :attribute field prohibits :other from being present.', // TODO(i18n): translate
    'regex' => 'The :attribute field format is invalid.', // TODO(i18n): translate
    'required' => 'Required ang :attribute field.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.', // TODO(i18n): translate
    'required_if' => 'The :attribute field is required when :other is :value.', // TODO(i18n): translate
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.', // TODO(i18n): translate
    'required_if_declined' => 'The :attribute field is required when :other is declined.', // TODO(i18n): translate
    'required_unless' => 'The :attribute field is required unless :other is in :values.', // TODO(i18n): translate
    'required_with' => 'The :attribute field is required when :values is present.', // TODO(i18n): translate
    'required_with_all' => 'The :attribute field is required when :values are present.', // TODO(i18n): translate
    'required_without' => 'The :attribute field is required when :values is not present.', // TODO(i18n): translate
    'required_without_all' => 'The :attribute field is required when none of :values are present.', // TODO(i18n): translate
    'same' => 'The :attribute field must match :other.', // TODO(i18n): translate
    'size' => [
        'array' => 'The :attribute field must contain :size items.', // TODO(i18n): translate
        'file' => 'The :attribute field must be :size kilobytes.', // TODO(i18n): translate
        'numeric' => 'The :attribute field must be :size.', // TODO(i18n): translate
        'string' => 'The :attribute field must be :size characters.', // TODO(i18n): translate
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.', // TODO(i18n): translate
    'string' => 'Dapat string ang :attribute.',
    'timezone' => 'The :attribute field must be a valid timezone.', // TODO(i18n): translate
    'unique' => 'Taken na ang :attribute.',
    'uploaded' => 'The :attribute failed to upload.', // TODO(i18n): translate
    'uppercase' => 'The :attribute field must be uppercase.', // TODO(i18n): translate
    'url' => 'The :attribute field must be a valid URL.', // TODO(i18n): translate
    'ulid' => 'The :attribute field must be a valid ULID.', // TODO(i18n): translate
    'uuid' => 'The :attribute field must be a valid UUID.', // TODO(i18n): translate
    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message', // TODO(i18n): translate
        ],
    ],
    'insufficient_stock' => 'Insufficient stock for :sku in :warehouse (requested :requested, available :available).', // TODO(i18n): translate
];
