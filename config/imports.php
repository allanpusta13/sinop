<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Import suite registry
|--------------------------------------------------------------------------
|
| Maps import definition registry keys (e.g. "products.families") to
| their ImportDefinition classes. The shared engine resolves keys from
| this map for the wizard actions, the preview/execution services and
| the template download route; unknown keys 404.
|
*/

return [

    'definitions' => [
        // Wave 1 feature definitions are registered here.
    ],

];
